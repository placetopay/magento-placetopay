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
            'environment' => EGM_PlacetoPay_Model_Abstract::getModuleConfig('environment'),
            'transactions' => [],
        ]);
        $payment->save();
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Status $status
     * @param \Dnetix\Redirection\Entities\Transaction[] $transactions
     */
    public function updateStatus(&$payment, $status, $transactions = null)
    {
        $information = $payment->getAdditionalInformation();
        $parsedTransactions = $information['transactions'];
        $lastTransaction = null;

        if ($transactions && is_array($transactions) && sizeof($transactions) > 0) {
            $lastTransaction = $transactions[0];
            foreach ($transactions as $transaction) {
                $parsedTransactions[$transaction->internalReference()] = [
                    'authorization' => $transaction->authorization(),
                    'status' => $transaction->status()->status(),
                    'status_date' => $transaction->status()->date(),
                    'status_message' => $transaction->status()->message(),
                    'status_reason' => $transaction->status()->reason(),
                    'franchise' => $transaction->franchise(),
                    'payment_method_name' => $transaction->paymentMethodName(),
                    'payment_method' => $transaction->paymentMethod(),
                    'amount' => $transaction->amount()->from()->total(),
                ];
            }
        }

        $this->importToPayment($payment, [
            'status' => $status->status(),
            'status_reason' => $status->reason(),
            'status_message' => $status->message(),
            'status_date' => $status->date(),
            'authorization' => $lastTransaction ? $lastTransaction->authorization() : null,
            'transactions' => $parsedTransactions,
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
