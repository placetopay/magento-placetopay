<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2010 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: PendingPayment.php,v 1.0.1 2010-05-06 17:33:00-05 egarcia Exp $
 */

/**
 * Estado de una nueva orden iniciada con PlacetoPay
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Place to Pay. <desarrollo@placetopay.com>
 * @since      jueves, 6 de mayo de 2010
 */
class EGM_PlacetoPay_Model_System_Config_Source_PendingPayment extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    protected $_stateStatuses = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
}
