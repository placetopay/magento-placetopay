<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2011 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Info.php,v 1.0.4 2011-08-16 12:34:00-05 egarcia Exp $
 */

/**
 * Muestra los datos resultantes del proceso de pago
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      martes, 17 de noviembre de 2009
 */

/**
 * Bloque de información comun a los pagos por PlacetoPay
 * Uses default templates
 */
class EGM_PlacetoPay_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Prepara la información del pago específica a PlacetoPay
     *
     * @param Varien_Object|array $transport
     * return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $placetopayInfo = Mage::getModel('placetopay/info');
        if (!$this->getIsSecureMode()) {
            $info = $placetopayInfo->getPaymentInfo($payment, true);
        } else {
            $info = $placetopayInfo->getPublicPaymentInfo($payment, true);
        }
        return $transport->addData($info);
    }
}
