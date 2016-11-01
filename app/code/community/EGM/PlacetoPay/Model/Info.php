<?php

use Dnetix\Redirection\Entities\Status;
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
            'status_message' => $response->status()->message(),
            'status_date' => $response->status()->date(),
            'environment' => EGM_PlacetoPay_Model_Abstract::getModuleConfig('environment')
        ]);
        $payment->save();
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Status $status
     */
    public function updateStatus(&$payment, $status)
    {
        $this->importToPayment($payment, [
            'status' => $status->status(),
            'status_reason' => $status->reason(),
            'status_message' => $status->message(),
            'status_date' => $status->date(),
        ]);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param $data
     */
    public function importToPayment(&$payment, $data)
    {
        $actual = $payment->getAdditionalInformation() ? $payment->getAdditionalInformation() : [];
        $payment->setAdditionalInformation(array_merge($actual, $data));
        $payment->save();
    }

}
