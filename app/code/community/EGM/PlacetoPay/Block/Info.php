<?php

class EGM_PlacetoPay_Block_Info extends Mage_Payment_Block_Info
{

    /**
     * Display the payment information on the checkout panel
     * @param null $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $data = $payment->getAdditionalInformation();
        /**
         * @var EGM_PlacetoPay_Model_Abstract $p2p
         */
        $p2p = $payment->getMethodInstance();

        return $transport->addData(array_filter([
            $p2p::trans('request_id') => isset($data['request_id']) ? $data['request_id'] : null,
            $p2p::trans('request_date') => isset($data['status_date']) ? $data['status_date'] : null,
            $p2p::trans('request_status') => isset($data['status']) ? $p2p::trans($data['status']) : null,
            $p2p::trans('request_view') => isset($data['process_url']) ? $data['process_url'] : null,
            $p2p::trans('authorization') => isset($data['authorization']) ? $data['authorization'] : null,
        ]));
    }
}
