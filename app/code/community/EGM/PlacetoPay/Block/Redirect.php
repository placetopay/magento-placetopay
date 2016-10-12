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
 * @author     Enrique Garcia M. <ingenieria@egm.co>
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
        /*
                $form = new Varien_Data_Form();
                $form->setAction($objPlacetoPay->getPlacetoPayUrl())
                    ->setId($objPlacetoPay->getCode() . '_checkout')
                    ->setName($objPlacetoPay->getCode() . '_checkout')
                    ->setMethod('POST')
                    ->setUseContainer(true);
                foreach ($objPlacetoPay->getCheckoutFormFields() as $field => $value) {
                    $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
                }
                $html = '<html><body>';
                $html.= $this->__('You will be redirected to PlacetoPay secure site in a few seconds.');
                $html.= $form->toHtml();
                $html.= '<script type="text/javascript">document.getElementById("' . $objPlacetoPay->getCode() . '_checkout").submit();</script>';
                $html.= '</body></html>';

                return $html;
        */
    }
}