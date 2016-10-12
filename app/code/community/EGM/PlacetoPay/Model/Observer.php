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
        // notifica de la operacion
        Mage::log('PlacetoPay resolviendo transacciones pendientes');

        // obtiene todas las ordenes de PlacetoPay que estan en estado pendiente y que tienen
        // mas de 7 minutos de haber sido creadas
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

        // recorre las operaciones halladas solo si son con PlacetoPay
        if (sizeof($collection))
            foreach ($collection as $obj) {
                // recupera toda la informacion de la orden
                $order = Mage::getModel('sales/order')->loadByIncrementId($obj->getIncrementId());
                $payment = $order->getPayment()->getMethodInstance();
                $paymentMethod = $payment->getCode();
                if (($paymentMethod == 'placetopay_standard') || ($paymentMethod == 'placetopay_promotion')) {
                    // instancia PlacetoPay y busca la transaccion
                    $p2p = new PlacetoPay();
                    $rc = $p2p->queryPayment($payment->getConfigData('customersiteid'), $order->getIncrementId(), $order->getOrderCurrencyCode(), Mage::app()->getStore()->roundPrice($order->getTotalDue()));
                    if (($rc == PlacetoPay::P2P_ERROR) && ($p2p->getErrorCode() == 'HTTP')) {
                        // hay un problema de conectividad del Webservice para resolver la operacion
                        // realice un registro y continue con la siguiente operacion
                        Mage::log('Orden # ' . $order->getIncrementId() . "\n" . $p2p->getErrorMessage());
                        continue;
                    } else
                        Mage::log('Orden # ' . $order->getIncrementId() . "\n" . $p2p->getErrorCode() . ' - ' . $p2p->getErrorMessage());

                    // procesa el resultado de la operacion
                    $payment->settlePlacetoPayPayment($order, $rc, $p2p);

                    // libera los recursos
                    unset($p2p);
                    unset($payment);
                }
            }
    }
}
