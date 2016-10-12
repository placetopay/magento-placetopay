<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2015 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: FinalPage.php,v 1.0.1 2015-04-15 15:37:00-05 egarcia Exp $
 */

/**
 * Página de finalización del proceso
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      miércoles, 15 de abril de 2015
 */
class EGM_PlacetoPay_Model_System_Config_Source_FinalPage
{
    public function toOptionArray()
    {
        // define la página de llegada al finalizar el pago
        $options = [
            ['value' => 'order_info', 'label' => Mage::helper('placetopay')->__('Order information')],
            ['value' => 'magento_default', 'label' => Mage::helper('placetopay')->__('Magento default')],
        ];
        return $options;
    }
}
