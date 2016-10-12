<?php

class EGM_PlacetoPay_Model_System_Config_Source_Environment
{
    public function toOptionArray()
    {
        $options = [
            ['value' => 'production', 'label' => Mage::helper('placetopay')->__('env_production')],
            ['value' => 'testing', 'label' => Mage::helper('placetopay')->__('env_testing')],
            ['value' => 'development', 'label' => Mage::helper('placetopay')->__('env_development')],
        ];
        return $options;
    }
}
