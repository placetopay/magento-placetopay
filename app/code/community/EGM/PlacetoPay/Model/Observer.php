<?php

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
            ->addAttributeToSelect('increment_id')
            ->addAttributeToFilter('created_at', ['lt' => date('Y-m-d H:i:s', time() - 7 * 60)])
            ->addAttributeToFilter('updated_at', ['lt' => date('Y-m-d H:i:s', time() - 7 * 60)])
            ->addAttributeToFilter('state', ['in' => [
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_NEW,
            ]])
            ->addAttributeToFilter('status', ['in' => [
                'pending', 'pending_payment', 'pending_placetopay', Mage_Payment_Model_Method_Abstract::STATUS_UNKNOWN,
            ]])
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
