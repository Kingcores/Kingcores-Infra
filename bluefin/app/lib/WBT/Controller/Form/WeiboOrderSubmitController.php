<?php

namespace WBT\Controller\Form;

use Bluefin\Controller;
use Bluefin\HTML\Form;
use Bluefin\HTML\Button;

use WBT\Model\Weibotui\WeiboOrder;

class WeiboOrderSubmitController extends Controller
{
    public function showAction()
    {
        $weibotuiAuth = $this->_requireAuth('weibotui');

        $order = $this->_request->getQueryParam('order');
        if (!isset($order))
        {
            echo '<span><i class="icon-warning-sign"></i> 错误请求。</span>';
            return;
        }

        //表单的字段
        $fields = [
            'finish_url' => [
                Form::FIELD_MESSAGE => _APP_('Please fill in the link address related to the task.'),
                Form::FIELD_REQUIRED => true,
            ],
            'snapshot_url' => [
                Form::FIELD_TAG => Form::COM_FILE_UPLOAD,
                Form::FIELD_MESSAGE => _APP_('You can upload a snapshot image related to the task if any.'),
                'image' => true,
                'action' => '/api/upload.php?cat=woss'
            ],
            'tuike_comment'
        ];

        $form = Form::fromModelMetadata(
            WeiboOrder::s_metadata(),
            $fields,
            null,
            ['class' => 'form-horizontal']
        );

        $form->legend = _APP_('Task Submission');
        $form->ajaxForm = true;

        $sucessMessage = _APP_('The marketing order is successfully submitted.');
        $form->submitAction = "wbtAPI.call('weibo_order/do_submit', '{$order}', PARAMS, function() { bluefinBH.closeDialog(FORM); bluefinBH.showInfo('{$sucessMessage}', function(){ location.reload(); } ) });";

        //设置表单按钮
        $form->addButtons([
            new Button('提交', null, ['type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        echo $form;
        echo \Bluefin\HTML\SimpleComponent::$scripts;
    }
}
