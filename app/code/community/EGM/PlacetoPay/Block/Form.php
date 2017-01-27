<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2012 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Form.php,v 1.0.3 2012-09-20 15:52:00-05 ingenieria Exp $
 */

/**
 * Muestra el formulario en el proceso de pago
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      martes, 17 de noviembre de 2009
 */
class EGM_PlacetoPay_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Último número de pedido/orden
     * @var string
     */
    public $lastOrder;

    /**
     * Último número de autorización/CUS
     * @var string
     */
    public $lastAuthorization;

    /**
     * Número de ordenes pendientes
     * @var int
     */
    public $countPendingOrders;

    /**
     * Constructor. Establece la plantilla
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('placetopay/form.phtml');
    }

    public function hasPendingOrders()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer()->getId();

        /**
         * @var Mage_Sales_Model_Order[] $collection
         */
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToSelect('*')
            ->addFieldToFilter('customer_id', $customer)
            ->addAttributeToFilter('state', ['in' => [
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_NEW,
            ]])
            ->addAttributeToFilter('status', ['in' => [
                'pending', 'pending_payment', 'pending_placetopay', Mage_Payment_Model_Method_Abstract::STATUS_UNKNOWN,
            ]])
            ->addAttributeToSort('created_at', 'DESC')
            ->load()
            ->getItems();

        $this->countPendingOrders = sizeof($collection);
        if ($this->countPendingOrders > 0) {
            /**
             * @var Mage_Sales_Model_Order $lastOrder
             */
            $lastOrder = reset($collection);
            $information = $lastOrder->getPayment()->getAdditionalInformation();
            $this->lastOrder = $lastOrder->getRealOrderId();
            $this->lastAuthorization = isset($information['authorization']) ? $information['authorization'] : null;
        }

        return ($this->countPendingOrders > 0);
    }

    public function lastAuthorization()
    {
        return $this->lastAuthorization;
    }

    /**
     * Retorna la variable de configuración
     * @return boolean
     */
    public function hasCifin()
    {
        $paymentMethodCode = $this->getMethod()->getCode();
        return Mage::getStoreConfig('payment/' . $paymentMethodCode . '/hascifin');
    }
}