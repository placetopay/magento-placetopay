<?php

use Dnetix\Redirection\Entities\Status;
use Dnetix\Redirection\Message\RedirectResponse;
use Dnetix\Redirection\PlacetoPay;
use Dnetix\Redirection\Validators\Currency;

require_once(__DIR__ . '/../bootstrap.php');

/**
 * Procesa las peticiones de PlacetoPay, generando las tramas e interpretandolas
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Place to Pay. <desarrollo@placetopay.com>
 * @since      martes, 17 de noviembre de 2009
 */
abstract class EGM_PlacetoPay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    const VERSION = '2.4.1.0';
    const WS_URL = 'https://secure.placetopay.com/redirection/';

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
    protected $_supportedLocales = ['en', 'es', 'fr'];
    protected $_p2pStatus = self::STATUS_UNKNOWN;

    /*
     * @var Mage_Sales_Model_Order
     */
    protected $_order;
    protected $gateway;

    /**
     * Determina si puede procesar usando la moneda
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return Currency::isValidCurrency($currencyCode);
    }

    public function isInitializeNeeded()
    {
        return true;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_UNKNOWN);
        $stateObject->setIsNotified(false);
        return $this;
    }

    /**
     * @return EGM_PlacetoPay_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('placetopay/session');
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return EGM_PlacetoPay_Model_Info
     */
    public function getInfoModel()
    {
        return Mage::getModel('placetopay/info');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
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

    public static function getModuleConfig($key)
    {
        return Mage::getStoreConfig('placetopay/' . $key);
    }

    public function getConfig($key)
    {
        return Mage::getStoreConfig('payment/' . $this->_code . '/' . $key);
    }

    public static function trans($value)
    {
        return Mage::helper('placetopay')->__($value);
    }

    /**
     * Retorna la version del componente
     * @return string
     */
    function getVersion()
    {
        return 'PlacetoPay PHP Component ' . self::VERSION;
    }

    /**
     * URL a la cual ir una vez se pone la orden
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('placetopay/processing/redirect', ['_secure' => true]);
    }

    /**
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
     * @param Mage_Sales_Model_Order $order
     * @return Status
     */
    public function parseOrderState($order)
    {
        $status = null;
        switch ($order->getStatus()) {
            case Mage_Sales_Model_Order::STATE_PROCESSING:
                $status = Status::ST_APPROVED;
                break;
            case Mage_Sales_Model_Order::STATE_CANCELED:
                $status = Status::ST_REJECTED;
                break;
            case Mage_Sales_Model_Order::STATE_NEW:
                $status = Status::ST_PENDING;
                break;
            default:
                $status = Status::ST_PENDING;
        }
        return new Status([
            'status' => $status,
        ]);
    }

    /**
     * @return PlacetoPay
     */
    public function gateway()
    {
        if (!$this->gateway) {
            $envs = [
                'CO' => [
                    'production' => 'https://secure.placetopay.com/redirection/',
                    'testing' => 'https://test.placetopay.com/redirection/',
                    'development' => 'https://dev.placetopay.com/redirection/',
                ],
                'EC' => [
                    'production' => 'https://secure.placetopay.ec/redirection/',
                    'testing' => 'https://test.placetopay.ec/redirection/',
                    'development' => 'https://dev.placetopay.ec/redirection/',
                ],
            ];

            $url = $envs[$this->getConfig('country')][$this->getConfig('environment')];

            $this->gateway = new PlacetoPay([
                'login' => $this->getConfig('login'),
                'tranKey' => $this->getConfig('trankey'),
                'url' => $url,
                'soap' => [
                    'cache_wsdl' => self::getModuleConfig('cache_wsdl'),
                ],
                'type' => self::getModuleConfig('connection_type'),
            ]);
        }
        return $this->gateway;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return RedirectResponse
     * @throws \Dnetix\Redirection\Exceptions\PlacetoPayException
     */
    public function getPaymentRedirect($order)
    {
        $data = $this->getRedirectRequestDataFromOrder($order);
        Mage::log('P2P_LOG: CheckoutRedirect/Failure [' . $order->getRealOrderId() . '] ' . $this->serialize($data));
        return $this->gateway()->request($data);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     * @throws Exception
     */
    public function getCheckoutRedirect($order)
    {
        $this->_order = $order;

        try {
            $response = $this->getPaymentRedirect($order);

            if ($response->isSuccessful()) {
                $payment = $order->getPayment();
                $info = $this->getInfoModel();

                $info->loadInformationFromRedirectResponse($payment, $response);
            } else {
                Mage::log('P2P_LOG: CheckoutRedirect/Failure [' . $order->getRealOrderId() . '] ' . $response->status()->message() . ' - ' . $response->status()->reason() . ' ' . $response->status()->status());
                Mage::throwException(Mage::helper('placetopay')->__($response->status()->message()));
            }

            return $response->processUrl();
        } catch (Exception $e) {
            Mage::log('P2P_LOG: CheckoutRedirect/Exception [' . $order->getRealOrderId() . '] ' . $e->getMessage() . ' ON ' . $e->getFile() . ' LINE ' . $e->getLine() . ' -- ' . get_class($e));
            throw $e;
        }

    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getRedirectRequestDataFromOrder($order)
    {
        $reference = $order->getRealOrderId();
        $total = self::getModuleConfig('grandtotal') ? $order->getGrandTotal() : $order->getTotalDue();

        if (!$total)
            $total = self::getModuleConfig('grandtotal') ? $order->getTotalDue() : $order->getGrandTotal();

        $subtotal = $order->getSubtotal();
        $discount = (int)$order->getDiscountAmount() != 0 ? ($order->getDiscountAmount() * -1) : 0;
        $shipping = $order->getShippingAmount();

        /**
         * @var Mage_Sales_Model_Order_Item[] $visibleItems
         */
        $visibleItems = $order->getAllVisibleItems();
        $items = [];
        foreach ($visibleItems as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $this->cleanText($item->getName()),
                'category' => $item->getProductType(),
                'qty' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'tax' => $item->getTaxAmount(),
            ];
        }

        $data = [
            'locale' => Mage::app()->getLocale()->getLocaleCode(),
            'buyer' => $this->parseAddressPerson($order->getBillingAddress()),
            'payment' => [
                'reference' => $reference,
                'description' => $this->getConfig('description'),
                'amount' => [
                    'details' => [
                        [
                            'kind' => 'subtotal',
                            'amount' => $subtotal,
                        ],
                        [
                            'kind' => 'discount',
                            'amount' => $discount,
                        ],
                        [
                            'kind' => 'shipping',
                            'amount' => $shipping,
                        ],
                    ],
                    'currency' => $order->getOrderCurrencyCode(),
                    'total' => $total,
                ],
                'items' => $items,
                'shipping' => $this->parseAddressPerson($order->getShippingAddress()),
            ],
            'returnUrl' => Mage::getUrl('placetopay/processing/response') . '?reference=' . $reference,
            'expiration' => date('c', strtotime('+' . self::getModuleConfig('expiration') . ' minutes')),
            'ipAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'userAgent' => Mage::helper('core/http')->getHttpUserAgent(),
            'skipResult' => $this->getConfig('skip_result') ? true : false,
            'noBuyerFill' => self::getModuleConfig('no_buyer_fill') ? true : false,
        ];

        if (!self::getModuleConfig('ignoretaxes')) {
            try {
                $map = [];
                if ($mapping = self::getModuleConfig('tax_rate_parsing')) {
                    foreach (explode('|', $mapping) as $item) {
                        $t = explode(':', $item);
                        if (is_array($t) && sizeof($t) == 2) {
                            $map[$t[0]] = $t[1];
                        }
                    }
                }
                $taxInformation = $order->getFullTaxInfo();
                if (is_array($taxInformation) && sizeof($taxInformation) > 0) {
                    $taxes = [];
                    while ($compound = array_pop($taxInformation)) {
                        $taxAmount = $compound['amount'];
                        $taxPercent = $compound['percent'];
                        foreach ($compound['rates'] as $rate) {
                            $taxes[] = [
                                'kind' => isset($map[$rate['code']]) ? $map[$rate['code']] : 'valueAddedTax',
                                'amount' => $taxAmount * ($rate['percent'] / $taxPercent),
                            ];
                        }
                    }
                    $data['payment']['amount']['taxes'] = $taxes;
                }
            } catch (Exception $e) {
                Mage::log('P2P_LOG: Error calculating taxes: [' . $order->getRealOrderId() . '] ' . serialize($order->getFullTaxInfo()));
            }
        }

        if (!self::getModuleConfig('ignorepaymentmethod') && !$this->isDefault()) {
            if ($pm = $this->getConfig('payment_method')) {

                $parsingsCountry = [
                    'CO' => [],
                    'EC' => [
                        'CR_VS' => 'ID_VS',
                        'RM_MC' => 'ID_MC',
                        'CR_DN' => 'ID_DN',
                        'CR_DS' => 'ID_DS',
                        'CR_AM' => 'ID_AM',
                        'CR_CR' => 'ID_CR',
                        'CR_VE' => 'ID_VE',
                    ],
                ];

                $paymentMethods = [];

                foreach (explode(',', $pm) as $paymentMethod) {
                    if (isset($parsingsCountry[$this->getConfig('country')][$paymentMethod])) {
                        $paymentMethods[] = $parsingsCountry[$this->getConfig('country')][$paymentMethod];
                    } else {
                        $paymentMethods[] = $paymentMethod;
                    }
                }

                $data['paymentMethod'] = implode(',', $paymentMethods);
            }
        }

        return $data;
    }

    /**
     * @param $documentType
     * @return string|null
     */
    public function parseDocumentType($documentType)
    {
        $documentTypes = [
            '1' => 'CC',
            '2' => 'CE',
            '3' => 'NIT',
            '4' => 'TI',
            '5' => 'PPN',
            '6' => null,
            '7' => 'SSN',
            '8' => 'LIC',
            '9' => 'TAX',
        ];
        return isset($documentTypes[$documentType]) ? $documentTypes[$documentType] : null;
    }

    /**
     * @param Mage_Sales_Model_Order_Address $address
     * @return array
     */
    public function parseAddressPerson($address)
    {
        if ($address) {
            if ($mapping = self::getModuleConfig('addressmap')) {
                $data = [
                    'name' => $address->getFirstname(),
                    'surname' => $address->getLastname(),
                    'email' => $address->getEmail(),
                ];

                $map = [];
                foreach (explode('|', $mapping) as $item) {
                    $t = explode(':', $item);
                    if (is_array($t) && sizeof($t) == 2) {
                        $map[$t[0]] = $t[1];
                    }
                }

                $uAddress = [];
                foreach ($map as $key => $value) {
                    $uAddress[$key] = $address->getData($value);
                }

                $data['address'] = $uAddress;
            } else {
                $data = [
                    'name' => $address->getFirstname(),
                    'surname' => $address->getLastname(),
                    'email' => $address->getEmail(),
                    'address' => [
                        'country' => $address->getCountryId(),
                        'state' => $address->getRegion(),
                        'city' => $address->getCity(),
                        'street' => implode(' ', $address->getStreet()),
                        'phone' => $address->getTelephone(),
                        'postalCode' => $address->getPostcode(),
                    ],
                ];
            }

            if ($field = self::getModuleConfig('mobilemap'))
                $data['mobile'] = $address->getData($field);

            return $data;
        }
        // When there is no person it comes as boolean
        return null;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isPendingOrder($order)
    {
        return $order->getStatus() == 'pending' || $order->getStatus() == 'pending_payment';
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
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return \Dnetix\Redirection\Message\RedirectInformation
     */
    public function resolve($order, $payment = null)
    {
        if (!$payment)
            $payment = $order->getPayment();

        $info = $payment->getAdditionalInformation();

        if (!$info || !isset($info['request_id'])) {
            Mage::log('P2P_LOG: Abstract/Resolve No additional information for order: ' . $order->getRealOrderId());
            Mage::throwException('No additional information for order: ' . $order->getRealOrderId());
        }

        $response = $this->gateway()->query($info['request_id']);

        if ($response->isSuccessful()) {
            $this->settleOrderStatus($response, $order, $payment);
        } else {
            Mage::log('P2P_LOG: Abstract/Resolve Non successful: ' . $response->status()->message() . ' ' . $response->status()->reason());
        }

        return $response;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return \Dnetix\Redirection\Message\RedirectInformation
     */
    public function query($order, $payment = null)
    {
        if (!$payment)
            $payment = $order->getPayment();

        $info = $payment->getAdditionalInformation();

        if (!$info || !isset($info['request_id'])) {
            Mage::log('P2P_LOG: Abstract/Resolve No additional information for order: ' . $order->getRealOrderId());
            Mage::throwException('No additional information for order: ' . $order->getRealOrderId());
        }

        return $this->gateway()->query($info['request_id']);
    }

    /**
     * @param \Dnetix\Redirection\Message\RedirectInformation $information
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function settleOrderStatus(\Dnetix\Redirection\Message\RedirectInformation $information, &$order, $payment = null)
    {
        $status = $information->status();

        switch ($status->status()) {
            case Status::ST_APPROVED:
                $comment = self::trans('transaction_approved');
                $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                $orderStatus = Mage_Sales_Model_Order::STATE_PROCESSING;
                break;
            case Status::ST_REJECTED:
                $comment = self::trans('transaction_rejected');
                $state = Mage_Sales_Model_Order::STATE_CANCELED;
                $orderStatus = Mage_Sales_Model_Order::STATE_CANCELED;
                break;
            case Status::ST_PENDING:
                $comment = self::trans('transaction_pending');
                $state = Mage_Sales_Model_Order::STATE_NEW;
                $orderStatus = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                break;
            default:
                $state = $orderStatus = $comment = null;
        }

        if ($state !== null) {

            if (!$payment)
                $payment = $order->getPayment();

            $info = $this->getInfoModel();
            $transactions = $information->payment();
            $info->updateStatus($payment, $status, $transactions);

            if ($status->isApproved()) {
                $this->_createInvoice($order);
                $order->sendNewOrderEmail()
                    ->setEmailSent(true);
                $order->setState($state, $orderStatus, $comment)
                    ->save();
            } else if ($status->isRejected()) {
                $order->cancel()
                    ->save();
            } else {
                $order->setState($state, $orderStatus, $comment)
                    ->save();
            }

        }
    }

    public function cleanText($text)
    {
        return preg_replace('/[\(\)\,\.\#\!\-]/', '', $text);
    }

    public abstract function isDefault();
}
