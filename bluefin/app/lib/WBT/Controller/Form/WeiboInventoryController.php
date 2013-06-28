<?php

namespace WBT\Controller\Form;

use Bluefin\Controller;
use Bluefin\Data\Model;
use WBT\Model\Weibotui\WeiboInventory;

class WeiboInventoryController extends Controller
{
    public function showAction()
    {
        WeiboInventory::requireActionPermission(Model::OP_CREATE);

        //表单的字段
        $fields = [
            'type',
            'num_audience',
            'original_price',
            'current_price',
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
