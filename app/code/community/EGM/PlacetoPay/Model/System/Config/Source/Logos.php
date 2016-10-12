<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2013 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Logos.php,v 1.0.3 2014-02-25 00:33:00-05 egarcia Exp $
 */

/**
 * Logos de las franquicias soportadas
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      jueves, 2 de junio de 2011
 */
class EGM_PlacetoPay_Model_System_Config_Source_Logos
{
    public function toOptionArray()
    {
        // define las franquicias soportadas por el medio de pago
        $options = array(
            array('value' => 'CR_VS', 'label' => 'VISA'),
            array('value' => 'RM_MC', 'label' => 'MASTERCARD'),
            array('value' => 'CR_AM', 'label' => 'AMEX'),
            array('value' => 'CR_DN', 'label' => 'DINERS'),
            array('value' => 'TY_EX', 'label' => 'Tarjeta Éxito'),
            array('value' => 'TY_AK', 'label' => 'Tarjeta Alkosto'),
            array('value' => 'V_VBV', 'label' => 'Verified by VISA'),
            array('value' => '_PSE_', 'label' => 'PSE'),
            array('value' => 'SFPAY', 'label' => 'SafetyPay'),
            array('value' => 'PINVL', 'label' => 'Pinválida'),
            array('value' => 'Efecty', 'label' => 'Efecty'),
            array('value' => 'CASH', 'label' => 'Pago en efectivo'),
        );
        return $options;
    }
}
