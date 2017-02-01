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
class EGM_PlacetoPay_Model_System_Config_Source_ConnectionTypes
{
    public function toOptionArray()
    {
        $options = [
            ['value' => 'soap', 'label' => 'SOAP'],
            ['value' => 'rest', 'label' => 'REST'],
        ];
        return $options;
    }
}
