<?php

class EGM_PlacetoPay_Model_System_Config_Source_Country
{
    public function toOptionArray()
    {
        $options = [
            ['value' => 'CO', 'label' => Mage::helper('placetopay')->__('Colombia')],
            ['value' => 'EC', 'label' => Mage::helper('placetopay')->__('Ecuador')],
        ];
        return $options;
    }
}
