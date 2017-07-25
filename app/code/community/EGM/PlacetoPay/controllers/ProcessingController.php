<?php

require_once(__DIR__ . '/../bootstrap.php');

class EGM_PlacetoPay_ProcessingController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * When the user clicks on the proceed to payment button
     * @return Mage_Core_Controller_Varien_Action
     */
    public function redirectAction()
    {
        $session = $this->_getCheckout();

        try {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                Mage::throwException(Mage::helper('placetopay')->__('No order for processing was found.'));
            }

            /**
             * @var EGM_PlacetoPay_Model_Abstract $p2p
             */
            $p2p = $order->getPayment()->getMethodInstance();
            $url = $p2p->getCheckoutRedirect($order);

            $session->setPlacetoPayQuoteId($session->getQuoteId());
            $session->setPlacetoPayRealOrderId($session->getLastRealOrderId());
            $session->getQuote()->setIsActive(false)->save();
            $session->clear();

            $order->setStatus('pending');
            $order->save();

            return $this->_redirectUrl($url);
        } catch (Exception $e) {
            Mage::log('P2P_LOG: RedirectAction ' . $e->getMessage() . ' ON ' . $e->getFile() . ' LINE ' . $e->getLine());
            $session->addError($e->getMessage());
            return $this->_redirectError('checkout/cart');
        }
    }

    /**
     * Cuando PlacetoPay retorna la respuesta a la tienda
     */
    public function responseAction()
    {
        $session = $this->_getCheckout();
        $quoteId = $session->getPlacetoPayQuoteId();
        $orderId = $session->getPlacetoPayRealOrderId();

        try {
            if ($orderId && Mage::app()->getRequest()->getParam('reference') == $orderId) {

                /**
                 * @var Mage_Sales_Model_Order $order
                 */
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                if (!$order->getId())
                    Mage::throwException(Mage::helper('placetopay')->__('Order not found.'));

                $payment = $order->getPayment();
                /**
                 * @var EGM_PlacetoPay_Model_Abstract $p2p
                 */
                $p2p = $payment->getMethodInstance();

                if (0 !== strpos($p2p->getCode(), 'placetopay_'))
                    Mage::throwException(Mage::helper('placetopay')->__('Unknown payment method.'));

                if ($p2p->isPendingOrder($order)) {
                    $response = $p2p->resolve($order, $payment);
                    $status = $response->status();
                } else {
                    $status = $p2p->parseOrderState($order);
                }

                if (Mage::getStoreConfig('payment/' . $p2p->getCode() . '/final_page') == 'magento_default') {
                    if ($status->isApproved()) {
                        $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
                        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                    } else if ($status->isRejected()) {
                        $quote = Mage::getModel('sales/quote')->load($quoteId);
                        if ($quote->getId()) {
                            $quote->setIsActive(true)->save();
                            $session->setQuoteId($quoteId);
                        }
                        return $this->_redirect('checkout/cart');
                    } else {
                        $session->addSuccess($p2p::trans('transaction_pending_message'));
                        return $this->_redirect('checkout/cart');
                    }
                } else {
                    if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                        Mage::dispatchEvent('checkout_onepage_controller_success_action', ['order_ids' => [$orderId]]);
                        return $this->_redirect('sales/order/view/order_id/' . $orderId);
                    } else {
                        return $this->_redirect('sales/guest/form/');
                    }
                }

            } else {
                $reference = $this->getRequest()->getParam('reference');
                Mage::log('P2P_LOG: ResponseAction0 [' . $orderId . '] with Reference: ' . $reference);
                /**
                 * @var Mage_Sales_Model_Order $order
                 */
                $order = Mage::getModel('sales/order')->loadByIncrementId($reference);
                if ($order->getId()) {
                    $payment = $order->getPayment();
                    if ($payment) {
                        /**
                         * @var EGM_PlacetoPay_Model_Abstract $p2p
                         */
                        $p2p = $payment->getMethodInstance();
                        if ($p2p instanceof EGM_PlacetoPay_Model_Abstract) {
                            $additional = $payment->getAdditionalInformation();
                            if ($p2p->isPendingOrder($order) && $additional && isset($additional['request_id'])) {
                                if ($p2p->isPendingOrder($order) && $additional && isset($additional['request_id'])) {
                                    $information = $p2p->gateway()->query($additional['request_id']);
                                    $p2p->settleOrderStatus($information, $order);
                                }
                            }
                        }
                    }
                }
            }

            return $this->_redirect('sales/order/history/');
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
            Mage::log('P2P_LOG: ResponseAction1 [' . $orderId . ']' . $e->getMessage() . ' ON ' . $e->getFile() . ' LINE ' . $e->getLine());
        } catch (Exception $e) {
            Mage::log('P2P_LOG: ResponseAction2 [' . $orderId . ']' . $e->getMessage() . ' ON ' . $e->getFile() . ' LINE ' . $e->getLine());
        }

        return $this->_redirect('checkout/cart');
    }

    /**
     * Redirection notification endpoint
     */
    public function notifyAction()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['reference'])) {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order')->loadByIncrementId($data['reference']);
            if (!$order->getId()) {
                Mage::log('P2P_LOG: Non existent order: ' . serialize($data));
                Mage::throwException(Mage::helper('placetopay')->__('Order not found.'));
            }

            /**
             * @var EGM_PlacetoPay_Model_Abstract $p2p
             */
            $p2p = $order->getPayment()->getMethodInstance();
            $notification = $p2p->gateway()->readNotification($data);

            if ($notification->isValidNotification()) {
                $information = $p2p->gateway()->query($notification->requestId());
                $p2p->settleOrderStatus($information, $order);
            } else {
                Mage::log('P2P_LOG: Invalid notification: ' . serialize($data));
            }
        } else {
            Mage::log('P2P_LOG: Wrong or empty notification data: ' . serialize($data));
        }
    }

}
