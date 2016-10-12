<?php

class EGM_PlacetoPay_Block_Info extends Mage_Payment_Block_Info
{

    /**
     * Display the payment information on the checkout panel
     * TODO: DC Obtain the information
     * @param null $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        /**
         * @var EGM_PlacetoPay_Model_Abstract $payment
         */
        $payment = $this->getInfo()->getMethodInstance();

        return $transport->addData([
            $payment::trans('merchantname') => $payment::getModuleConfig('merchantname'),
            $payment::trans('merchantdocument') => $payment::getModuleConfig('merchantdocument'),
            $payment::trans('description') => $payment->getConfig('description'),
        ]);
    }
}
