<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2015 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Abstract.php,v 1.0.13 2016-01-22 16:34:00-05 egarcia Exp $
 */

/**
 * Procesa las peticiones de PlacetoPay, generando las tramas e interpretandolas
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      martes, 17 de noviembre de 2009
 */
abstract class EGM_PlacetoPay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code = 'placetopay_abstract';

    protected $_formBlockType = 'placetopay/form';
    protected $_infoBlockType = 'placetopay/info';

    /**
     * Opciones de disponiblidad
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;

    protected $_defaultLocale = 'es';
    protected $_supportedLocales = array('en', 'es', 'fr');
    protected $_p2pStatus = self::STATUS_UNKNOWN;

    /*
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * Carga la libreria de PlacetoPay
     */
    public function __construct()
    {
        include_once dirname(__FILE__) . '/Classes/PlacetoPay.class.php';
        parent::__construct();
    }

    /**
     * Determina si puede procesar usando la moneda
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * Marca si es necesario inicializar el pago mientras la orden tiene lugar
     *
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Este m�todo ser� usado en vez de authorize or capture
     * si la bandera isInitilizeNeeded es true
     *
     * @param   string $paymentAction
     * @param
     * @return  Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
        return $this;
    }

    /**
     * Obtiene el namespace de session
     *
     * @return EGM_PlacetoPay_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('placetopay/session');
    }

    /**
     * Obtiene el namespace del checkout
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Obtiene el valor actual del pedido
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Obtiene la orden actual
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!isset($this->_order)) {
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        }
        return $this->_order;
    }

    /**
     * Genera el bloque que muestra la informaci�n en el checkout
     *
     * @param string $name
     * @return EGM_PlacetoPay_Block_Form
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('placetopay/form', $name)
            ->setMethod($this->getMethod())
            ->setPayment($this->getPayment())
            ->setTemplate('placetopay/form.phtml');

        return $block;
    }

    /**
     * Obtiene el idioma predeterminado para la solicitud a PlacetoPay
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (!empty($locale) && is_array($locale) && in_array($locale[0], $this->_supportedLocales)) {
            return $locale[0];
        }
        return $this->getDefaultLocale();
    }

    /**
     * URL a la cual ir una vez se pone la orden
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('placetopay/processing/redirect', array('_secure' => true));
    }

    /**
     * URL a la cual se remite la solicitud de la transaccion
     *
     * @return string
     */
    public function getPlacetoPayUrl()
    {
        return PlacetoPay::PAYMENT_URL;
    }

    /**
     * Capture la informaci�n del pago a trav�s de PlacetoPay
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return EGM_PlacetoPay_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus($this->p2pStatus)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Cancele el pago
     *
     * @param Varien_Object $payment
     * @return EGM_PlacetoPay_Model_Standard
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus($this->p2pStatus)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    /**
     * Obtiene el objeto de PlacetoPay instanciado para la remisi�n
     * de par�metros
     *
     * @return array (PlacetoPay, array)
     */
    public function getPlacetoPayObj()
    {
        // determina los datos a enviar la plataforma
        $billing = $this->getOrder()->getBillingAddress();
        $shipping = $this->getOrder()->getShippingAddress();
        $totalAmount = Mage::app()->getStore()->roundPrice($this->getOrder()->getTotalDue());
        $taxAmount = Mage::app()->getStore()->roundPrice($this->getOrder()->getTaxAmount());
        if (empty($taxAmount))
            $baseDevAmount = 0;
        else
            $baseDevAmount = Mage::app()->getStore()->roundPrice($totalAmount - $taxAmount - $this->getOrder()->getShippingAmount());
        $currencyCode = $this->getOrder()->getOrderCurrencyCode();
        $extraData = array();
        foreach ($this->getOrder()->getAllVisibleItems() as $item) {
            $extraData[] = $item->getName();
        }
        $extraData = implode(', ', $extraData);
        if (strlen($extraData) > 252) $extraData = substr($extraData, 0, 252) . '...';

        // obtiene los datos base de configuracion del GPG
        $gpgConf = Mage::getStoreConfig('placetopay/gpg');

        // instancia el componente
        $p2p = new PlacetoPay();
        $p2p->setGPGProgramPath($gpgConf['gpgpath']);
        $p2p->setGPGHomeDirectory($gpgConf['gpghomedir']);
        $p2p->setOverrideReturn(Mage::getUrl('placetopay/processing/response', array('_secure' => true)));
        $p2p->setCurrency($currencyCode);
        $p2p->setLanguage(strtoupper($this->getLocale()));
        $p2p->setPayerInfo('', '', $billing->getFirstname() . ' ' . $billing->getLastname(),
            $this->getOrder()->getCustomerEmail(),
            $billing->getStreetFull(), $billing->getCity(),
            $billing->getRegion(), $billing->getCountryId(), $billing->getTelephone(), '');
        if ($shipping)
            $p2p->setBuyerInfo('', '', $shipping->getFirstname() . ' ' . $shipping->getLastname(),
                '',
                $shipping->getStreetFull(), $shipping->getCity(),
                $shipping->getRegion(), $shipping->getCountryId(), $shipping->getTelephone(), '');
        $p2p->setExtraData($extraData);
        $p2p->addAdditionalData('ecommerce', 'Magento ' . Mage::getVersion());

        // argumentos para el m�todo de llamada
        $args = array(
            $gpgConf['keyid'],
            $gpgConf['passphrase'],
            $gpgConf['recipientkeyid'],
            $this->getConfigData('customersiteid'),
            $this->getCheckout()->getLastRealOrderId(),
            sprintf('%.2f', $totalAmount), sprintf('%.2f', $taxAmount), sprintf('%.2f', $baseDevAmount)
        );

        return array($p2p, $args);
    }

    /**
     * Obtiene la URL de redirecci�n
     *
     * @return string
     */
    public function getCheckoutRedirect()
    {
        list($p2p, $args) = $this->getPlacetoPayObj();
        $url = $p2p->getPaymentRedirect($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
        if (empty($url)) {
            error_log($p2p->getErrorCode() . ' - ' . $p2p->getErrorMessage());
        }

        return $url;
    }

    /**
     * Obtiene los campos que deben pasarse en el formulario de petici�n
     *
     * @return array
     */
    public function getCheckoutFormFields()
    {
        // obtiene el objeto y los parametros; luego los pasa como
        // campos ocultos
        list($p2p, $args) = $this->getPlacetoPayObj();
        $aFlds = $p2p->getPaymentFields($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);

        return $aFlds;
    }

    /**
     * Crea la factura para la orden
     * @param Mage_Sales_Model_Order $order
     */
    protected function _createInvoice($order)
    {
        if (!$order->canInvoice())
            return;
        $invoice = $order->prepareInvoice();
        $invoice->register()->capture();
        $order->addRelatedObject($invoice);
    }

    /**
     * Asienta el pago
     * @param Mage_Sales_Model_Order $order
     * @param int $rc
     * @param PlacetoPay $p2p
     */
    public function settlePlacetoPayPayment(Mage_Sales_Model_Order $order, $rc, PlacetoPay $p2p)
    {
        // modifique la orden acorde a la respuesta de PlacetoPay
        $wasCancelled = false;
        switch ($rc) {
            case PlacetoPay::P2P_ERROR:
                if ($order->getStatus() != Mage_Sales_Model_Order::STATE_CANCELED) {
                    $comment = Mage::helper('placetopay')->__('Transaction Failed');
                    $state = Mage_Sales_Model_Order::STATE_CANCELED;
                    $status = Mage_Payment_Model_Method_Abstract::STATUS_ERROR;
                }
                break;
            case PlacetoPay::P2P_DECLINED:
                if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
                    $comment = Mage::helper('placetopay')->__('Transaction Rejected');
                    $state = Mage_Sales_Model_Order::STATE_CANCELED;
                    $status = Mage_Payment_Model_Method_Abstract::STATUS_DECLINED;
                }
                break;
            case PlacetoPay::P2P_APPROVED:
            case PlacetoPay::P2P_DUPLICATE:
                // verifica que no se haya completado para no reprocesar el pedido
                if ($order->getState() != Mage_Sales_Model_Order::STATE_PROCESSING) {
                    $comment = Mage::helper('placetopay')->__('Transaction Approved');
                    $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                    $status = Mage_Payment_Model_Method_Abstract::STATUS_APPROVED;
                }
                if ($order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
                    $wasCancelled = true;
                }

                break;
            case PlacetoPay::P2P_PENDING:
                if (($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) || ($order->getState() == Mage_Sales_Model_Order::STATE_NEW)) {
                    $comment = Mage::helper('placetopay')->__('Transaction Pending');
                    $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                    $status = Mage_Payment_Model_Method_Abstract::STATUS_UNKNOWN;
                }
                break;
        }

        // determina si realiza la actualizaci�n de la orden
        if (!empty($comment)) {
            // asocia los valores retornados al medio de pago para los metodos de captura y cancelacion
            $this->p2pStatus = $status;
            $wasPaymentInformationChanged = $this->_importPaymentInformation($order->getPayment(), $p2p, $status);

            // si el estado es procesado, remite el email
            if ($state == Mage_Sales_Model_Order::STATE_PROCESSING) {
                // almacena el n�mero de autorizacion
                $order->getPayment()->setLastTransId($p2p->getAuthorization());
                $order->getPayment()->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);
                $order->setState($state, $status, $comment)
                    ->save();
                // agrega la factura
                $this->_createInvoice($order);
                // envia el correo con la orden
                $order->sendNewOrderEmail()
                    ->setEmailSent(true)
                    ->save();

                if ($wasCancelled) {
                    // fetch write database connection that is used in Mage_Core module
                    $write = Mage::getSingleton('core/resource')->getConnection('core_write');

                    /**
                     * Set the product ID
                     */
                    $EntityId = $order->getEntityId();

                    $query = "UPDATE `sales_flat_order_grid` SET `status` = 'processing' 
							WHERE `sales_flat_order_grid`.`entity_id` = '" . (int)$EntityId . "';
							UPDATE `sales_flat_order` SET `status` = 'APPROVED', `state` = 'processing' 
							WHERE `sales_flat_order`.`entity_id` = '" . (int)$EntityId . "';
							INSERT INTO `sales_flat_order_status_history` 
							(`parent_id`, `is_customer_notified`, `is_visible_on_front`, `comment`, `status`, `created_at`, `entity_name`) 
							VALUES ('" . (int)$EntityId . "', '2', '0', 'Transaction Approved', '" . Mage_Sales_Model_Order::STATE_PROCESSING . "', '" . date('Y-m-d H:i:s') . "', 'order');";

                    /**
                     * Execute the query
                     */
                    $write->query($query);
                }

                $wasPaymentInformationChanged = true;
            } elseif ($state == Mage_Sales_Model_Order::STATE_CANCELED) {
                // establece el pago como declinado y cancela la orden
                $order
                    ->cancel()
                    ->addStatusToHistory($status, $comment)
                    ->save();
                $wasPaymentInformationChanged = true;
            } elseif ($state == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $order->getPayment()->setLastTransId($p2p->getAuthorization());
                // agrega un comentario a la historia
                $order
                    ->addStatusToHistory($status, $comment)
                    ->save();
                $wasPaymentInformationChanged = true;
            }
            if ($wasPaymentInformationChanged)
                $order->getPayment()->save();
        }
    }

    /**
     * Procesa la respuesta de PlacetoPay
     * Thorws Mage_Core_Exception if error
     * @param Mage_Sales_Model_Order $order
     * @param array $post
     * @return mixed false en caso de error, el numero de la orden en otro caso
     */
    public function processPayment($order, $post)
    {
        // verifica que se obtengan los datos requeridos por PlacetoPay
        if (empty($post['CustomerSiteID']) || empty($post['PaymentResponse']))
            Mage::throwException(Mage::helper('placetopay')->__('Request does not contain the required PlacetoPay elements.'));

        // verifica que el identificador de sitio corresponda al de la tienda
        if ($post['CustomerSiteID'] != $this->getConfigData('customersiteid'))
            Mage::throwException(Mage::helper('placetopay')->__('The CustomerSiteID does not correspond to the store setting.'));

        // obtiene los datos base de configuracion del GPG
        $gpgConf = Mage::getStoreConfig('placetopay/gpg');

        // procesa la respuesta con el componente
        $p2p = new PlacetoPay();
        $p2p->setGPGProgramPath($gpgConf['gpgpath']);
        $p2p->setGPGHomeDirectory($gpgConf['gpghomedir']);
        $rc = $p2p->getPaymentResponse($gpgConf['keyid'], $gpgConf['passphrase'], $post['PaymentResponse']);

        // informe si hay un error asociado al GnuPG
        if (($rc == PlacetoPay::P2P_ERROR) && ($p2p->getErrorCode() == 'GPG'))
            Mage::throwException($p2p->getErrorCode() . ' - ' . $p2p->getErrorMessage());

        // valida que la referencia corresponda a la orden
        if ($p2p->getReference() != $order->getRealOrderId())
            Mage::throwException(Mage::helper('placetopay')->__('Invalid order ID.'));

        // procesa el asiento de la orden acorde al resultado dado por PlacetoPay
        $this->settlePlacetoPayPayment($order, $rc, $p2p);

        return $order->getEntityId();
    }

    /**
     * Asocia la informaci�n del pago retornada por PlacetoPay al objeto de pago
     * Retorna true si hubo cambios en la informaci�n
     *
     * @param Mage_Payment_Model_Info $payment
     * @param PlacetoPay $p2p
     * @param string $status
     * @return bool
     */
    protected function _importPaymentInformation(Mage_Payment_Model_Info $payment, PlacetoPay $p2p, $status)
    {
        $was = $payment->getAdditionalInformation();
        $from = array(
            EGM_PlacetoPay_Model_Info::RESPONSE_STATUS => $status,
            EGM_PlacetoPay_Model_Info::TRANSACTION_DATE => $p2p->getTransactionDate(),
            EGM_PlacetoPay_Model_Info::RESPONSE_CODE => $p2p->getErrorCode(),
            EGM_PlacetoPay_Model_Info::RESPONSE_MESSAGE => html_entity_decode($p2p->getErrorMessage()),
            EGM_PlacetoPay_Model_Info::REFERENCE => $p2p->getReference()
        );
        if ($p2p->getFranchise()) {
            $from[EGM_PlacetoPay_Model_Info::FRANCHISE] = $p2p->getFranchise();
            $from[EGM_PlacetoPay_Model_Info::FRANCHISE_NAME] = html_entity_decode($p2p->getFranchiseName());
        }
        if ($p2p->getBankName())
            $from[EGM_PlacetoPay_Model_Info::BANK_NAME] = $p2p->getBankName();
        if ($p2p->getCreditCardNumber())
            $from[EGM_PlacetoPay_Model_Info::CREDIT_CARD] = $p2p->getCreditCardNumber();
        if ($p2p->getAuthorization())
            $from[EGM_PlacetoPay_Model_Info::AUTH_CODE] = $p2p->getAuthorization();
        if ($p2p->getReceipt())
            $from[EGM_PlacetoPay_Model_Info::RECEIPT] = $p2p->getReceipt();
        if ($p2p->getShopperName())
            $from[EGM_PlacetoPay_Model_Info::PAYER_NAME] = $p2p->getShopperName();
        if ($p2p->getShopperEmail())
            $from[EGM_PlacetoPay_Model_Info::PAYER_EMAIL] = $p2p->getShopperEmail();
        if (!empty($_SERVER['REMOTE_ADDR']))
            $from[EGM_PlacetoPay_Model_Info::IP_ADDRESS] = $_SERVER['REMOTE_ADDR'];
        if ($p2p->getPlatformConversionFactor() <> 1)
            $from[EGM_PlacetoPay_Model_Info::CONVERSION_RATE] = Mage::getModel('directory/currency')->load($p2p->getPlatformCurrency())->format($p2p->getPlatformConversionFactor(), array(), false, false);

        Mage::getSingleton('placetopay/info')->importToPayment($from, $payment);
        return $was != $payment->getAdditionalInformation();
    }

    /**
     * Obtiene la informaci�n de configuraci�n espec�fica para el medio de pago
     *
     * @param   string $field
     * @return  mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . $this->getCode() . '/' . $field;
        return Mage::getStoreConfig($path, $storeId);
    }
}
