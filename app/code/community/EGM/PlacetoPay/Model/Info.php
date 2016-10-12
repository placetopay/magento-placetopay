<?php

use Dnetix\Redirection\Message\RedirectResponse;

class EGM_PlacetoPay_Model_Info
{

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param RedirectResponse $response
     */
    public function loadInformationFromRedirectResponse(&$payment, $response)
    {
        $payment->setLastTransId($response->requestId());
        $payment->setAdditionalInformation([
            'request_id' => $response->requestId(),
            'process_url' => $response->processUrl(),
            'status' => $response->status()->status(),
            'status_reason' => $response->status()->reason(),
            'status_message' => $response->status()->message()
        ]);
        $payment->save();
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param $data
     */
    public function importToPayment(&$payment, $data)
    {
        $payment->setAdditionalInformation(serialize($data));
        $payment->save();
    }

}
