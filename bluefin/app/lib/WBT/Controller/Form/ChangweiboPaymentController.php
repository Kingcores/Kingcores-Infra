<?php

namespace WBT\Controller\Form;

use Bluefin\Controller;
use Bluefin\Data\Model;
use WBT\Model\Weibotui\UserDepositRecord;
use WBT\Model\Weibotui\PaymentMethod;
use WBT\Model\Weibotui\IncomeType;
use Bluefin\HTML\Form;
use Bluefin\HTML\Button;

class ChangweiboPaymentController extends Controller
{
    public function showAction()
    {
        $billId = $this->_request->getQueryParam('bill_id');
        _ARG_IS_SET('订单编号', $billId);

        //表单的字段
        $fields = [
            'amount' => [
                Form::FIELD_TAG => Form::COM_RADIO_GROUP,
                Form::FIELD_LABEL => '服务类型',
                Form::FIELD_DATA => [
                    '1' => '1元体验',
                    '12' => '12元/月',
                    '120' =>  '120元/年'
                ],
                Form::FIELD_CLASS => 'inline',
                Form::FIELD_REQUIRED => true
            ],
            'income_type' => [
                Form::FIELD_TAG => Form::COM_HIDDEN,
                Form::FIELD_VALUE => IncomeType::CHANGWEIBO_DEPOSIT
            ],
            'bill_id' => [
                Form::FIELD_TAG => Form::COM_HIDDEN,
                Form::FIELD_VALUE => $billId
            ],
            'payment_method' => [
                Form::FIELD_TAG => Form::COM_RADIO_GROUP,
                Form::FIELD_LABEL => _META_('weibotui.payment_method'),
                Form::FIELD_DATA => PaymentMethod::getDictionary(),
                Form::FIELD_CLASS => 'inline',
                Form::FIELD_REQUIRED => true
            ]
        ];

        //$mockPay = false; //;
        $mockPay = ENV == 'dev';
        if ($mockPay)
        {
            $fields['mock_pay'] = [
                Form::FIELD_TAG => Form::COM_RADIO_GROUP,
                Form::FIELD_LABEL => '模拟支付',
                Form::FIELD_DATA => [ '1' => '成功', '0' => '失败' ],
                Form::FIELD_VALUE => '1',
                Form::FIELD_CLASS => 'inline'
            ];
        }

        $form = Form::fromModelMetadata(
            UserDepositRecord::s_metadata(),
            $fields,
            null,
            ['class' => 'form-horizontal']
        );

        $form->legend = '长微博高级版增值服务';

        if ($mockPay)
        {
            $form->legend .= '模拟';
        }

        //设置表单按钮
        $form->addButtons([
            new Button('付款', null, ['type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        $form->submitAction = <<<'JS'
            var payment = FORM.find('[name="payment_method"]').val();

            if (payment == 'alipay')
            {
                FORM.attr('action', '/third_party/alipay/submit');
            }
JS;

        echo $form;
        echo \Bluefin\HTML\SimpleComponent::$scripts;
    }
}
