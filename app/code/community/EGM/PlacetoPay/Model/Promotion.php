<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2011 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Promotion.php,v 1.0.17 2011-08-16 11:30:00-05 egarcia Exp $
 */

/**
 * Procesa las peticiones de PlacetoPay, generando las tramas e interpretandolas
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      martes, 17 de noviembre de 2009
 */
class EGM_PlacetoPay_Model_Promotion extends EGM_PlacetoPay_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code = 'placetopay_promotion';

    /**
     * Verifica que el metodo pueda ser usado, validando las horas en las que se usa
     *
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $promoHours = $this->getConfigData('promo_hours');
        if (parent::isAvailable($quote) && !empty($promoHours)) {
            $now = Mage::getModel('core/date')->timestamp(time());
            if (in_array(date('H', $now), explode(',', $promoHours)))
                return true;
        }
        return false;
    }
}
