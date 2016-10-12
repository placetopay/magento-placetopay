<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2011 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Info.php,v 1.0.8 2014-03-04 18:21:00-05 egarcia Exp $
 */

/**
 * Modelo de información almacenada con cada transacción realizada
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      jueves, 06 de mayo de 2010
 */
class EGM_PlacetoPay_Model_Info
{
    /**
     * Llaves de intercambio de informacion
     *
     * @var string
     */
    const REFERENCE = 'reference';
    const TRANSACTION_DATE = 'transaction_date';
    const RESPONSE_STATUS = 'status';
    const RESPONSE_CODE = 'response_code';
    const RESPONSE_MESSAGE = 'response_message';
    const FRANCHISE = 'franchise';
    const FRANCHISE_NAME = 'franchise_name';
    const BANK_NAME = 'bank_name';
    const CREDIT_CARD = 'creditcard';
    const AUTH_CODE = 'auth_code';
    const RECEIPT = 'receipt';
    const CONVERSION_RATE = 'conversion_rate';
    const PAYER_NAME = 'payer_name';
    const PAYER_EMAIL = 'payer_email';
    const IP_ADDRESS = 'ipaddress';

    /**
     * Mapeo de los campos y sus nombres de campos para exportación
     *
     * @var array
     */
    protected $_paymentMap = array(
        'merchantname' => 'placetopay_merchantname',
        'merchantdocument' => 'placetopay_merchantdocument',
        self::REFERENCE => 'placetopay_reference',
        'paymentdescription' => 'placetopay_paymentdescription',
        self::TRANSACTION_DATE => 'placetopay_transaction_date',
        self::RESPONSE_STATUS => 'placetopay_status',
        self::RESPONSE_CODE => 'placetopay_response_code',
        self::RESPONSE_MESSAGE => 'placetopay_response_message',
        self::FRANCHISE => 'placetopay_franchise',
        self::FRANCHISE_NAME => 'placetopay_franchise_name',
        self::BANK_NAME => 'placetopay_bank_name',
        self::CREDIT_CARD => 'placetopay_credit_card',
        self::AUTH_CODE => 'placetopay_auth_code',
        self::RECEIPT => 'placetopay_receipt',
        self::CONVERSION_RATE => 'placetopay_conversion_rate',
        self::PAYER_NAME => 'placetopay_payer_name',
        self::PAYER_EMAIL => 'placetopay_payer_email',
        self::IP_ADDRESS => 'placetopay_ipaddress',
    );

    /**
     * Información disponible a ser vista por el cliente
     *
     * @var array
     */
    protected $_paymentPublicMap = array(
        'placetopay_merchantname', 'placetopay_merchantdocument', 'placetopay_paymentdescription',
        'placetopay_transaction_date', 'placetopay_status', 'placetopay_response_message',
        'placetopay_franchise_name', 'placetopay_bank_name', 'placetopay_auth_code', 'placetopay_receipt',
        'placetopay_conversion_rate', 'placetopay_ipaddress', 'placetopay_note'
    );

    /**
     * Datos a ser visualizados
     *
     * @var array
     */
    protected $_paymentMapFull = array();

    /**
     * Obtiene toda la información del pago
     *
     * @param Mage_Payment_Model_Info $payment
     * @param bool $labelValuesOnly
     * @return array
     */
    public function getPaymentInfo(Mage_Payment_Model_Info $payment, $labelValuesOnly = false)
    {
        // obtiene la información propia a PlacetoPay
        $result = $this->_getFullInfo(array_values($this->_paymentMap), $payment, $labelValuesOnly);

        return $result;
    }

    /**
     * Obtiene toda la información del pago que es pública
     *
     * @param Mage_Payment_Model_Info $payment
     * @param bool $labelValuesOnly
     * @return array
     */
    public function getPublicPaymentInfo(Mage_Payment_Model_Info $payment, $labelValuesOnly = false)
    {
        // agrega la información de la compañía
        $this->_paymentMapFull['placetopay_merchantname'] = array(
            'label' => Mage::helper('placetopay')->__('Company Name'),
            'value' => Mage::getStoreConfig('placetopay/merchantname'));
        $this->_paymentMapFull['placetopay_merchantdocument'] = array(
            'label' => Mage::helper('placetopay')->__('Merchant ID'),
            'value' => Mage::getStoreConfig('placetopay/merchantdocument'));
        $this->_paymentMapFull['placetopay_paymentdescription'] = array(
            'label' => Mage::helper('placetopay')->__('Payment Description'),
            'value' => sprintf($payment->getMethodInstance()->getConfigData('description'), $payment->getAdditionalInformation('placetopay_reference')));
        $this->_paymentMapFull['placetopay_note'] = array(
            'label' => Mage::helper('placetopay')->__('Questions?'),
            'value' => sprintf(Mage::helper('placetopay')->__('If you wish more information please contact us in our support phone %s or send your questions to the email %s.'),
                Mage::getStoreConfig('general/store_information/phone'),
                Mage::getStoreConfig('trans_email/ident_sales/email'))
        );

        return $this->_getFullInfo($this->_paymentPublicMap, $payment, $labelValuesOnly);
    }

    /**
     * Almacena los datos en la información del pago
     *
     * @param array|Varien_Object|callback $from
     * @param Mage_Payment_Model_Info $payment
     */
    public function importToPayment($from, Mage_Payment_Model_Info $payment)
    {
        Varien_Object_Mapper::accumulateByMap($from, array($payment, 'setAdditionalInformation'), $this->_paymentMap);
    }

    /**
     * Obtiene los datos propios del pago, para la exportación a PDF o plano
     *
     * @param Mage_Payment_Model_Info $payment
     * @param array|Varien_Object|callback $to
     * @param array $map
     * @return array|Varien_Object
     */
    public function &exportFromPayment(Mage_Payment_Model_Info $payment, $to, array $map = null)
    {
        Varien_Object_Mapper::accumulateByMap(array($payment, 'getAdditionalInformation'), $to,
            $map ? $map : array_flip($this->_paymentMap)
        );
        return $to;
    }

    /**
     * Hace el volcado de la información
     *
     * @param array $keys
     * @param Mage_Payment_Model_Info $payment
     * @param bool $labelValuesOnly
     */
    protected function _getFullInfo(array $keys, Mage_Payment_Model_Info $payment, $labelValuesOnly)
    {
        $result = array();
        foreach ($keys as $key) {
            if (!isset($this->_paymentMapFull[$key])) {
                $this->_paymentMapFull[$key] = array();
            }
            if (!isset($this->_paymentMapFull[$key]['label'])) {
                if (!$payment->hasAdditionalInformation($key)) {
                    $this->_paymentMapFull[$key]['label'] = false;
                    $this->_paymentMapFull[$key]['value'] = false;
                } else {
                    $value = $payment->getAdditionalInformation($key);
                    $this->_paymentMapFull[$key]['label'] = $this->_getLabel($key);
                    if ($labelValuesOnly && ($key == 'placetopay_response_message') && ($keys === $this->_paymentPublicMap))
                        $this->_paymentMapFull[$key]['value'] = $payment->getAdditionalInformation('placetopay_response_code') . ' - ' . $this->_getValue($value, $key);
                    else
                        $this->_paymentMapFull[$key]['value'] = $this->_getValue($value, $key);
                }
            }
            if (!empty($this->_paymentMapFull[$key]['value'])) {
                if ($labelValuesOnly) {
                    $result[$this->_paymentMapFull[$key]['label']] = $this->_paymentMapFull[$key]['value'];
                } else {
                    $result[$key] = $this->_paymentMapFull[$key];
                }
            }
        }
        return $result;
    }

    /**
     * Hace un volcado de las etiquetas
     *
     * @param string $key
     */
    protected function _getLabel($key)
    {
        switch ($key) {
            case 'placetopay_merchantname':
                return Mage::helper('placetopay')->__('Company Name');
            case 'placetopay_merchantdocument':
                return Mage::helper('placetopay')->__('Merchant ID');
            case 'placetopay_status':
                return Mage::helper('placetopay')->__('Status');
            case 'placetopay_reference':
                return Mage::helper('placetopay')->__('Reference');
            case 'placetopay_transaction_date':
                return Mage::helper('placetopay')->__('Transaction Date');
            case 'placetopay_response_code':
                return Mage::helper('placetopay')->__('Response Code');
            case 'placetopay_response_message':
                return Mage::helper('placetopay')->__('Reason');
            case 'placetopay_franchise':
                return Mage::helper('placetopay')->__('Franchise Code');
            case 'placetopay_franchise_name':
                return Mage::helper('placetopay')->__('Franchise');
            case 'placetopay_bank_name':
                return Mage::helper('placetopay')->__('Bank Name');
            case 'placetopay_credit_card':
                return Mage::helper('placetopay')->__('Credit Card');
            case 'placetopay_auth_code':
                return Mage::helper('placetopay')->__('Authorization/UFC');
            case 'placetopay_receipt':
                return Mage::helper('placetopay')->__('Receipt');
            case 'placetopay_conversion_rate':
                return Mage::helper('placetopay')->__('Conversion Factor');
            case 'placetopay_payer_name':
                return Mage::helper('placetopay')->__('Payer Name');
            case 'placetopay_payer_email':
                return Mage::helper('placetopay')->__('Payer Email');
            case 'placetopay_ipaddress':
                return Mage::helper('placetopay')->__('IP Address');
            case 'placetopay_note':
                return Mage::helper('placetopay')->__('Questions?');
        }
        return $key;
    }

    /**
     * Aplica un filtro en la salida del dato
     *
     * @param string $value
     * @param string $key
     * @return string
     */
    protected function _getValue($value, $key)
    {
        if ($key == 'placetopay_status') {
            switch ($value) {
                case Mage_Payment_Model_Method_Abstract::STATUS_APPROVED:
                case Mage_Payment_Model_Method_Abstract::STATUS_SUCCESS:
                    return Mage::helper('placetopay')->__('Transaction Approved');
                case Mage_Payment_Model_Method_Abstract::STATUS_DECLINED:
                    return Mage::helper('placetopay')->__('Transaction Rejected');
                case Mage_Payment_Model_Method_Abstract::STATUS_ERROR:
                    return Mage::helper('placetopay')->__('Transaction Failed');
                case Mage_Payment_Model_Method_Abstract::STATUS_UNKNOWN:
                    return Mage::helper('placetopay')->__('Transaction Pending');
            }
        }
        return $value;
    }
}
