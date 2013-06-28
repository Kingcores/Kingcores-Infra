<?php

use Bluefin\App;
use Common\Helper\OAuthService;

use WBT\Business\PaymentBusiness;

class PaymentService extends OAuthService
{
    public function getStatus()
    {
        $this->_requireOAuthToken('payment');

        $type = $this->_app->request()->getPostParam('type');
        _ARG_IS_SET(_APP_('api.open.payment.get_status.type'), $type);

        $billId = $this->_app->request()->getPostParam('bill_id');
        _ARG_IS_SET(_APP_('api.open.payment.get_status.bill_id'), $billId);

        return PaymentBusiness::getIncomeRecord($type, $billId);
    }
}
