<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2011 EGM Ingeniería sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Redirect.php,v 1.0.5 2011-08-16 12:37:00-05 egarcia Exp $
 */

/**
 * Genera la redirección a PlacetoPay
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Place to Pay. <desarrollo@placetopay.com>
 * @since      martes, 17 de noviembre de 2009
 */
class EGM_PlacetoPay_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        // obtiene la sesion del checkout
        $objPlacetoPay = Mage::getSingleton('checkout/session')->getQuote();
        $mi = $objPlacetoPay->getPayment()->getMethodInstance();
        Mage::app()->getResponse()->setRedirect($mi->getCheckoutRedirect());
        return '';
    }
}