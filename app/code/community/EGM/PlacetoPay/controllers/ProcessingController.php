<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2015 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: ProcessingController.php,v 1.0.9 2015-04-20 10:13:00-05 ingenieria Exp $
 */

/**
 * PlacetoPay Processing Checkout Controller
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      martes, 17 de noviembre de 2009
 */
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
     * Cuando un usuario selecciona PlacetoPay en la página Checkout/Payment
     *
     */
    public function redirectAction()
    {
        try {
            $session = $this->_getCheckout();

            // obtiene la orden
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                Mage::throwException(Mage::helper('placetopay')->__('No order for processing was found.'));
            }

            // obtiene la URL para redirección
            $url = $order->getPayment()->getMethodInstance()->getCheckoutRedirect();
            if (!$url)
                Mage::throwException(Mage::helper('placetopay')->__('Can not generate secure data to connect with PlacetoPay.'));

            // almacena el identificador del carro y de la orden en la sesion
            // inactiva el carrito para que no pueda ser modificado
            $session->setPlacetoPayQuoteId($session->getQuoteId());
            //Mage::log([$session->getQuoteId(),$order->getQuoteId(),$session->getLastRealOrderId()]);
            $session->setPlacetoPayRealOrderId($session->getLastRealOrderId());
            $session->getQuote()->setIsActive(false)->save();
            $session->clear();

            // redirige el flujo a Place to Pay
            Mage::app()->getResponse()->setRedirect($url);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Cuando PlacetoPay retorna la respuesta a la tienda
     */
    public function responseAction()
    {
        try {
            // verifica que los datos vengan POST
            if (!$this->getRequest()->isPost())
                Mage::throwException(Mage::helper('placetopay')->__('Can not process an empty response.'));

            // obtiene los datos de la session
            $session = $this->_getCheckout();
            $quoteId = $session->getPlacetoPayQuoteId();
            if ($quoteId) {
                // obtiene la orden asociada a la sesión
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getPlacetoPayRealOrderId());
                if (!$order->getId())
                    Mage::throwException(Mage::helper('placetopay')->__('Order not found.'));

                // obtiene el nombre del medio de pago
                $paymentCode = $order->getPayment()->getMethodInstance()->getCode();

                // valida que la orden tenga a PlacetoPay como medio de pago
                if (0 !== strpos($paymentCode, 'placetopay_'))
                    Mage::throwException(Mage::helper('placetopay')->__('Unknown payment method.'));

                // procesa el pago
                $orderId = $order->getPayment()->getMethodInstance()->processPayment($order, $this->getRequest()->getPost());
                $p2pInfo = $order->getPayment()->getAdditionalInformation();

                // determina cual flujo seguir, si ir al flujo normal de magento a ir a visualizar la orden
                if (Mage::getStoreConfig('payment/' . $paymentCode . '/final_page') == 'magento_default') {
                    // si el pago es exitoso va a la de checkout
                    if (isset($p2pInfo['placetopay_status'])
                        && ($p2pInfo['placetopay_status'] == Mage_Payment_Model_Method_Abstract::STATUS_APPROVED)
                    ) {
                        $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
                        $this->_redirect('checkout/onepage/success', array('_secure' => true));
                    } // sino va al carrito de compras con los articulos nuevamente
                    else {
                        $quote = Mage::getModel('sales/quote')->load($quoteId);
                        if ($quote->getId()) {
                            $quote->setIsActive(true)->save();
                            $session->setQuoteId($quoteId);
                        }
                        if (isset($p2pInfo['placetopay_response_message']))
                            $session->addError($p2pInfo['placetopay_response_message']);
                        $this->_redirect('checkout/cart');
                    }
                } // va a la página del estado de la orden
                else {
                    if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($orderId)));
                        $this->_redirect('sales/order/view/order_id/' . $orderId);
                    } else {
                        $this->_redirect('sales/guest/form/');
                    }
                }
                return;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }
}
