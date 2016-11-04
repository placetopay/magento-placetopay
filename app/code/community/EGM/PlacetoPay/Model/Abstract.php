<?php

use Dnetix\Redirection\Entities\Person;
use Dnetix\Redirection\Entities\Status;
use Dnetix\Redirection\Message\RedirectRequest;
use Dnetix\Redirection\Message\RedirectResponse;
use Dnetix\Redirection\PlacetoPay;
use Dnetix\Redirection\Validators\Currency;

require_once(__DIR__ . '/../bootstrap.php');

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
    const VERSION = '2.0.0';
    const WS_URL = 'https://test.placetopay.com/redirection/';

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
                'production' => 'https://secure.placetopay.com/redirection/',
                'testing' => 'https://test.placetopay.com/redirection/',
                'development' => 'http://redirection.dnetix.co/',
            ];
            $url = isset($envs[self::getModuleConfig('environment')]) ? $envs[self::getModuleConfig('environment')] : self::WS_URL;

            $this->gateway = new PlacetoPay([
                'login' => $this->getConfig('login'),
                'tranKey' => $this->getConfig('trankey'),
                'url' => $url,
            ]);
        }
        return $this->gateway;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Checkout_Model_Session $checkout
     * @return RedirectResponse
     */
    public function getPaymentRedirect($order, $checkout)
    {
        $request = $this->getRedirectRequestFromOrder($order, $checkout);
        return $this->gateway()->request($request);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getCheckoutRedirect($order)
    {
        $this->_order = $order;
        $response = $this->getPaymentRedirect($order, $this->getCheckout());

        if ($response->isSuccessful()) {
            $payment = $order->getPayment();
            $info = $this->getInfoModel();

            $info->loadInformationFromRedirectResponse($payment, $response);
        } else {
            Mage::log($response->status()->reason() . '-' . $response->status()->message());
            Mage::throwException(Mage::helper('placetopay')->__($response->status()->message()));
        }

        return $response->processUrl();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Checkout_Model_Session $checkout
     * @return RedirectRequest
     */
    public function getRedirectRequestFromOrder($order, $checkout)
    {
        $reference = $checkout->getLastRealOrderId();
        $total = $order->getTotalDue();
        $subtotal = $order->getSubtotal();
        $discount = (int) $order->getDiscountAmount() != 0 ? ($order->getDiscountAmount() * -1) : 0;
        $taxAmount = $order->getTaxAmount();
        $shipping = $order->getShippingAmount();

        if (!$taxAmount || (int)$taxAmount === 0)
            $devolutionBase = 0;
        else
            $devolutionBase = $subtotal - $discount;

        /**
         * @var Mage_Sales_Model_Order_Item[] $visibleItems
         */
        $visibleItems = $order->getAllVisibleItems();
        $items = [];
        foreach ($visibleItems as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
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
                    'taxes' => [
                        [
                            'kind' => 'valueAddedTax',
                            'amount' => $taxAmount,
                            'base' => $devolutionBase,
                        ],
                    ],
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
            'expiration' => date('c', strtotime('+2 days')),
            'ipAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'userAgent' => Mage::helper('core/http')->getHttpUserAgent(),
        ];

        return new RedirectRequest($data);
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
     * @return Person
     */
    public function parseAddressPerson($address)
    {
        // When there is no person it comes as boolean
        if ($address) {
            return new Person([
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
            ]);
        }

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

        if (!$info || !isset($info['request_id']))
            Mage::throwException('No additional information for this order:' . $order->getRealOrderId());

        $response = $this->gateway()->query($info['request_id']);

        if ($response->isSuccessful()) {
            $this->settleOrderStatus($response->status(), $order);
        }

        return $response;
    }

    /**
     * @param Status $status
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function settleOrderStatus(Status $status, &$order, $payment = null)
    {
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
            $info->updateStatus($payment, $status);

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

}
