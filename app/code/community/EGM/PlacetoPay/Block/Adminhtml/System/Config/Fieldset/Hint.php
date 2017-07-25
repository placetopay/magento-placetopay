<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2011 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version    $Id: Hint.php,v 1.0.1 2011-05-31 18:21:00-05 ingenieria Exp $
 */

/**
 * Despliega el anuncio de PlacetoPay en la configuración del sistema
 * @author     Place to Pay. <desarrollo@placetopay.com>
 */
class EGM_PlacetoPay_Block_Adminhtml_System_Config_Fieldset_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'placetopay/system/config/fieldset/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
