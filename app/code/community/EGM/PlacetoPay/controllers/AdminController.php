<?php

require_once(__DIR__ . '/../bootstrap.php');

class EGM_PlacetoPay_AdminController extends Mage_Core_Controller_Front_Action
{

    public function statusAction()
    {
        $agent = Mage::helper('core/http')->getHttpUserAgent();
        if (strpos($agent, 'curl') === false) {
            $this->getResponse()->setHttpResponseCode(403);
            return null;
        }

        $locale = Mage::app()->getLocale()->getLocaleCode();
        $version = EGM_PlacetoPay_Model_Abstract::VERSION;
        $date = ((new DateTime())->format('c'));

        /**
         * @var EGM_PlacetoPay_Model_Abstract $p2pStandard
         */
        $p2pStandard = Mage::helper('payment')->getMethodInstance('placetopay_standard');
        /**
         * @var EGM_PlacetoPay_Model_Abstract $p2pPromotion
         */
        $p2pPromotion = Mage::helper('payment')->getMethodInstance('placetopay_promotion');

        $status = [
            'standard' => [
                'active' => $p2pStandard->getConfig('active'),
                'environment' => $p2pStandard->getConfig('environment'),
                'login' => $p2pStandard->getConfig('login'),
                'pm' => $p2pStandard->getConfig('payment_method'),
            ],
            'promotion' => [
                'active' => $p2pPromotion->getConfig('active'),
                'environment' => $p2pPromotion->getConfig('environment'),
                'login' => $p2pPromotion->getConfig('login'),
                'dateRange' => $p2pPromotion->getConfig('daterange'),
                'pm' => $p2pPromotion->getConfig('payment_method'),
            ],
            'cache' => EGM_PlacetoPay_Model_Abstract::getModuleConfig('cache_wsdl'),
            'expiration' => EGM_PlacetoPay_Model_Abstract::getModuleConfig('expiration'),
            'addressMap' => EGM_PlacetoPay_Model_Abstract::getModuleConfig('addressmap'),
            'ignorepaymentmethod' => EGM_PlacetoPay_Model_Abstract::getModuleConfig('ignorepaymentmethod'),
        ];

        $data = json_encode(compact('locale', 'version', 'date', 'status'), JSON_PRETTY_PRINT) . "\n";

        $this->getResponse()->setHeader('Content-Type', 'text/plain');
        $this->getResponse()->setBody($data);
    }

    public function createAction()
    {
        try {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getRequest()->getParam('reference'));
            if (!$order->getId()) {
                Mage::throwException(Mage::helper('placetopay')->__('No order for processing was found.'));
            }

            /**
             * @var EGM_PlacetoPay_Model_Abstract $p2p
             */
            $p2p = $order->getPayment()->getMethodInstance();

            if (!$p2p instanceof EGM_PlacetoPay_Model_Abstract)
                return $this->norouteAction();

            $url = $p2p->getCheckoutRedirect($order);

            return $this->_redirectUrl($url);
        } catch (Exception $e) {
            Mage::log('P2P_LOG: CreateAction ' . $e->getMessage() . ' ON ' . $e->getFile() . ' LINE ' . $e->getLine());
            return $this->_redirectError('checkout/cart');
        }
    }

    public function debugAction()
    {
        try {

            $hash = $this->getRequest()->getParam('hash');
            if (!$hash)
                return $this->norouteAction();

            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getRequest()->getParam('reference'));
            if (!$order->getId()) {
                Mage::throwException(Mage::helper('placetopay')->__('No order for processing was found.'));
            }

            /**
             * @var EGM_PlacetoPay_Model_Abstract $p2p
             */
            $p2p = Mage::helper('payment')->getMethodInstance('placetopay_standard');

            if ($hash != md5($order->getRealOrderId() . $p2p->getConfig('trankey'))) {
                $this->getResponse()->setHttpResponseCode(403);
                return null;
            }

            $data = json_encode($p2p->getRedirectRequestDataFromOrder($order), JSON_PRETTY_PRINT);

            $this->getResponse()->setHeader('Content-Type', 'text/plain');
            $this->getResponse()->setBody($data);

        } catch (Exception $e) {
            Mage::log('P2P_LOG: DebugAction ' . $e->getMessage() . ' ON ' . $e->getFile() . ' LINE ' . $e->getLine());
            return $this->_redirectError('checkout/cart');
        }
    }

}
