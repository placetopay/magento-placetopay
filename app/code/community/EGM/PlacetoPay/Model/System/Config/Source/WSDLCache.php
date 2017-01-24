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
class EGM_PlacetoPay_Model_System_Config_Source_WSDLCache
{
    public function toOptionArray()
    {
        $options = [
            ['value' => WSDL_CACHE_NONE, 'label' => 'Ninguno'],
            ['value' => WSDL_CACHE_DISK, 'label' => 'Disco'],
            ['value' => WSDL_CACHE_MEMORY, 'label' => 'Memoria'],
            ['value' => WSDL_CACHE_BOTH, 'label' => 'Ambos'],
        ];
        return $options;
    }
}
