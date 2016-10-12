<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2015 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version   $Id: Observer.php,v 1.1.1 2016-01-22 15:45:00-05 egarcia Exp $
 */

/**
 * Procesa las transacciones de PlacetoPay que estan aun sin respuesta
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      viernes, 7 de mayo de 2010
 */
class EGM_PlacetoPay_Model_Observer
{
    public function resolvePendingTransactions()
    {
        Mage::log('Resolving PlacetoPay pending orders');

        /**
         * @var Mage_Sales_Model_Order[] $collection
         */
        $collection = Mage::getModel('sales/order')->getCollection()
            //->addAttributeToFilter('updated_at', array('lt' => date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time() - 15 * 60))))
            ->addAttributeToSelect('increment_id')
            //->addAttributeToFilter('created_at', array('gt' => date('Y-m-d H:i:s', time() - 5 * 24 * 60 * 60))) // 5 días
            ->addAttributeToFilter('created_at', array('lt' => date('Y-m-d H:i:s', time() - 7 * 60)))// 7 min
            ->addAttributeToFilter('updated_at', array('lt' => date('Y-m-d H:i:s', time() - 7 * 60)))// 7 minutos
            ->addAttributeToFilter('state', array('in' => array(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_NEW
            )))
            ->addAttributeToFilter('status', array('in' => array(
                'pending', 'pending_payment', 'pending_placetopay', Mage_Payment_Model_Method_Abstract::STATUS_UNKNOWN
            )))
            ->addAttributeToSort('created_at')
            ->load()
            ->getItems();

        if (sizeof($collection))
            foreach ($collection as $order) {
                $order = $order->loadByIncrementId($order->getIncrementId());
                $payment = $order->getPayment();
                $p2p = $payment->getMethodInstance();

                if ($p2p instanceof EGM_PlacetoPay_Model_Abstract) {
                    $response = $p2p->resolve($order, $payment);
                    Mage::log('Resolving ' . $order->getId() . ' [' . $response->status()->status() . '] ' . $response->status()->message());
                }

                unset($p2p);
                unset($payment);
            }
    }
}
