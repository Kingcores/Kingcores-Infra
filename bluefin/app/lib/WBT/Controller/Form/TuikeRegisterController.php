<?php

namespace WBT\Controller\Form;

use Bluefin\Controller;
use Bluefin\Data\Model;
use Bluefin\HTML\Form;
use Bluefin\HTML\Button;
use WBT\Model\Weibotui\PersonalProfile;

class TuikeRegisterController extends Controller
{
    public function showAction()
    {
        $auth = $this->_requireAuth('weibotui');

        //表单的字段
        $fields = [
            'user.profile.mobile' => [
                Form::FIELD_REQUIRED => true,
            ],

            'user.profile.qq',

        ];

        $form = Form::fromModelMetadata(
            PersonalProfile::s_metadata(),
            $fields,
            $auth->getData('profile'),
            ['class' => 'form-horizontal']
        );

        $form->legend = _APP_('form.wbt.personal_description');
        $form->ajaxForm = true;

        $sucessMessage = _APP_('Operation succeeded.');
        $form->submitAction = <<<JS
            wbtAPI.call('user_profile/update', '{$auth->getData('profile')}', PARAMS, function() { bluefinBH.closeDialog(FORM); bluefinBH.showInfo('{$sucessMessage}', function(){ location.reload(); } ) });
JS;

        //设置表单按钮
        $form->addButtons([
            new Button('保存', null, ['type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        echo $form;
        echo \Bluefin\HTML\SimpleComponent::$scripts;
    }
}
