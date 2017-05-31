<?php


class EGM_PlacetoPay_Block_Adminhtml_System_Config_Form_Field_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Override method to output our custom HTML with JavaScript
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    // @codingStandardsIgnoreStart
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Build url
        $url = $this->getUrl('placetopay/processing/notify', array('_forced_secure' => true));
        // Strip everything after key string
        $url = preg_replace('/\/key[\w\/]+$/', '', $url);
        return $url;
    }

}