<?php

use Dnetix\Dates\DateHelper;

class EGM_PlacetoPay_Block_Sales_Order_Info extends Mage_Sales_Block_Order_Info
{
    protected $_links = array();

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/info.phtml');
    }

    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
        }
    }

    public function _t($text)
    {
        return EGM_PlacetoPay_Model_Abstract::trans($text);
    }

    public function getPaymentInfoHtml()
    {
        $payment = $this->getOrder()->getPayment();
        /**
         * @var EGM_PlacetoPay_Model_Abstract $p2p
         */
        $p2p = $payment->getMethodInstance();
        $information = $payment->getAdditionalInformation();

        $html = '<p class="subtitle"><strong>' . $p2p->getConfig('title') . '</strong></p>';

        $html .= '<dl class="payment-info">';
        if (isset($information['request_id']))
            $html .= '<dt>' . $this->_t('request_id') . ' <span>' . $information['request_id'] . '</span></dt>';
        if (isset($information['status_date']))
            $html .= '<dt>' . $this->_t('request_date') . ' <span>' . DateHelper::create($information['status_date'])->format('Y-m-d H:i:s') . '</span></dt>';
        if (isset($information['process_url'])) {
            $action = $this->_t('Finish payment');
            $status = new \Dnetix\Redirection\Entities\Status(['status' => isset($information['status']) ? $information['status'] : 'PENDING']);
            if ($status->isApproved() || $status->isRejected()) {
                $action = $this->_t('View payment details');
            }
            $html .= '<div style="text-align: center;"><a class="button btn-cart" href="' . $information['process_url'] . '" style="padding: 3px 10px; margin: 5px 0;">' . $action . '</a></div>';
        }
        if (isset($information['transactions']) && sizeof($information['transactions']) > 0) {
            $html .= '<p class="transactions">' . $this->_t('Transactions') . '</p>';
            foreach ($information['transactions'] as $transaction) {
                $html .= '<div class="transaction">' . $this->_t($transaction['franchise']) . ' (' . $transaction['authorization'] . ') ' . '<span class="' . $transaction['status'] . '">' . $this->_t($transaction['status']) . '</span></div>';
            }
        }
        $html .= '</dl>';

        return $html;
    }

    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function addLink($name, $path, $label)
    {
        $this->_links[$name] = new Varien_Object(array(
            'name' => $name,
            'label' => $label,
            'url' => empty($path) ? '' : Mage::getUrl($path, array('order_id' => $this->getOrder()->getId()))
        ));
        return $this;
    }

    public function getLinks()
    {
        $this->checkLinks();
        return $this->_links;
    }

    private function checkLinks()
    {
        $order = $this->getOrder();
        if (!$order->hasInvoices()) {
            unset($this->_links['invoice']);
        }
        if (!$order->hasShipments()) {
            unset($this->_links['shipment']);
        }
        if (!$order->hasCreditmemos()) {
            unset($this->_links['creditmemo']);
        }
    }

    /**
     * Get url for reorder action
     *
     * @deprecated after 1.6.0.0, logic moved to new block
     * @param Mage_Sales_Order $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getUrl('sales/guest/reorder', array('order_id' => $order->getId()));
        }
        return $this->getUrl('sales/order/reorder', array('order_id' => $order->getId()));
    }

    /**
     * Get url for printing order
     *
     * @deprecated after 1.6.0.0, logic moved to new block
     * @param Mage_Sales_Order $order
     * @return string
     */
    public function getPrintUrl($order)
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getUrl('sales/guest/print', array('order_id' => $order->getId()));
        }
        return $this->getUrl('sales/order/print', array('order_id' => $order->getId()));
    }
}
