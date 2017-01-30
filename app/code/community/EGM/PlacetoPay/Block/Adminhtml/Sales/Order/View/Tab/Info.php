<?php


use Dnetix\Dates\DateHelper;

class EGM_PlacetoPay_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info
{

    public function getPaymentHtml()
    {
        $payment = $this->getOrder()->getPayment();
        /**
         * @var EGM_PlacetoPay_Model_Abstract $p2p
         */
        $p2p = $payment->getMethodInstance();
        $information = $payment->getAdditionalInformation();

        $html = '<p class="subtitle"><strong>' . $p2p->getConfig('title') . '</strong></p>';

        $html .= '<dl class="payment-info">';
        if (isset($information['request_id']))
            $html .= '<dt>' . $this->_t('request_id') . ' <span>' . $information['request_id'] . '</span></dt>';
        if (isset($information['status_date']))
            $html .= '<dt>' . $this->_t('request_date') . ' <span>' . DateHelper::create($information['status_date'])->format('Y-m-d H:i:s') . '</span></dt>';
        if (isset($information['status']))
            $html .= '<dt>' . $this->_t('Status') . ' <span>' . $this->_t($information['status']) . '</span></dt>';
        if (isset($information['status_message']))
            $html .= '<dt>' . $this->_t('Reason') . ' <span>' . $this->_t($information['status_message']) . '</span></dt>';
        if (isset($information['process_url'])) {
            $action = $this->_t('Finish payment');
            $status = new \Dnetix\Redirection\Entities\Status(['status' => isset($information['status']) ? $information['status'] : 'PENDING']);
            if ($status->isApproved() || $status->isRejected()) {
                $action = $this->_t('View payment details');
            }
            $html .= '<div style="padding: 0 0 5px 0;"><a class="button" href="' . $information['process_url'] . '" target="_blank">' . $action . '</a></div>';
        }
        if (isset($information['transactions']) && sizeof($information['transactions']) > 0) {
            $html .= '<p class="transactions"><strong>' . $this->_t('Transactions') . '</strong></p>';
            foreach ($information['transactions'] as $transaction) {
                $html .= '<div class="transaction">' . $this->_t($transaction['franchise']) . ' (CUS ' . $transaction['authorization'] . ') ' . '<span class="' . $transaction['status'] . '">' . $this->_t($transaction['status']) . '</span></div>';
            }
        }
        $html .= '</dl>';

        return $html;
    }

    public function _t($text)
    {
        return EGM_PlacetoPay_Model_Abstract::trans($text);
    }

}