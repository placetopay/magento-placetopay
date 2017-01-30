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
class EGM_PlacetoPay_Model_System_Config_Source_Franchises
{
    public function toOptionArray()
    {
        // Define las franquicias soportadas por el medio de pago
        $options = [
            ['value' => 'CR_VS', 'label' => 'Visa'],
            ['value' => 'CR_CR', 'label' => 'Credencial Banco de Occidente'],
            ['value' => 'CR_VE', 'label' => 'Visa Electron'],
            ['value' => 'CR_DN', 'label' => 'Diners Club'],
            ['value' => 'CR_AM', 'label' => 'American Express'],
            ['value' => 'RM_MC', 'label' => 'MasterCard'],
            ['value' => 'TY_EX', 'label' => 'Tarjeta Éxito'],
            ['value' => 'TY_AK', 'label' => 'Alkosto'],
            ['value' => '_PSE_', 'label' => 'Débito a cuentas corrientes y ahorros (PSE)'],
            ['value' => 'SFPAY', 'label' => 'Safety Pay'],
            ['value' => '_ATH_', 'label' => 'Corresponsales bancarios Grupo Aval'],
            ['value' => 'EFCTY', 'label' => 'Efecty'],
            ['value' => 'AC_WU', 'label' => 'Western Union'],
            ['value' => 'PYPAL', 'label' => 'PayPal'],
            ['value' => 'T1_BC', 'label' => 'Bancolombia Recaudos'],
            ['value' => 'AV_BO', 'label' => 'Banco de Occidente Recaudos'],
            ['value' => 'AV_AV', 'label' => 'Banco AV Villas Recaudos'],
            ['value' => 'AV_BB', 'label' => 'Banco de Bogotá Recaudos'],
            ['value' => 'VISAC', 'label' => 'Visa Checkout'],
            ['value' => 'GNPIN', 'label' => 'GanaPIN'],
            ['value' => 'GNRIS', 'label' => 'Tarjeta RIS'],
            ['value' => 'MSTRP', 'label' => 'Masterpass'],
            ['value' => 'DBTAC', 'label' => 'Registro cuentas débito'],
            ['value' => '_PPD_', 'label' => 'Débito pre-autorizado (PPD)'],

        ];
        return $options;
    }
}
