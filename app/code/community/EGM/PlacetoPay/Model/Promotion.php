<?php

/**
 * Procesa las peticiones de PlacetoPay, generando las tramas e interpretandolas
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Place to Pay. <desarrollo@placetopay.com>
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
        $dateRange = $this->getConfig('daterange');
        if (!$this->getConfig('login'))
            return false;

        if (parent::isAvailable($quote) && !empty($dateRange)) {
            return \Dnetix\Dates\DateRangeChecker::load($dateRange)->check();
        }
        return false;
    }

    public function isDefault()
    {
        return false;
    }
}
