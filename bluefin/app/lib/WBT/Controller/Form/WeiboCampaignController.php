<?php

namespace WBT\Controller\Form;

use Bluefin\Controller;
use Bluefin\Data\Model;
use Bluefin\HTML\Form;
use Bluefin\HTML\Button;
use WBT\Model\Weibotui\WeiboCampaign;

class WeiboCampaignController extends Controller
{
    public function showAction()
    {
        WeiboCampaign::requireActionPermission(Model::OP_CREATE);

        $weiboCampaignID = $this->_request->getQueryParam('campaign');

        if (isset($weiboCampaignID))
        {
            $data = new WeiboCampaign($weiboCampaignID);
        }
        else
        {
            $data = null;
        }

        //表单的字段
        $fields = [
            'name',
            'type' => [ Form::FIELD_TAG => Form::COM_RADIO_GROUP, Form::FIELD_CLASS => 'inline', 'style' => 'width:35px;' ],
            'start_time',
            'end_time',
            'budget',
            'text',
            'image',
            'video',
            'comment',
        ];

        $form = Form::fromModelMetadata(
            WeiboCampaign::s_metadata(),
            $fields,
            $data,
            ['class' => 'form-horizontal']
        );

        $form->legend = '创建广告活动';
        $form->ajaxForm = true;
        $form->submitAction = "wbtAPI.call('weibo_campaign/create', PARAMS, function() { bluefinBH.closeDialog(FORM); });";

        //设置表单按钮
        $form->addButtons([
            new Button('保存', null, ['type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        echo $form;
        echo \Bluefin\HTML\SimpleComponent::$scripts;
    }
}
