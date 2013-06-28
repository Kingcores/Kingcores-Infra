<?php

namespace WBT\Business;

require_once 'PHPMailer/class.phpmailer.php';

/**
 * 发送email相关的业务
 */
class MailBusiness
{
    public static function sendMail($to, $subject, $htmlContent, $from='no-reply@mail.weibotui.com')
    {
        $mail = new \PHPMailer();

        $body = $htmlContent;

        $mail->CharSet = 'UTF-8';
        $mail->Hostname = 'mail.weibotui.com';
        $mail->SetFrom($from, $from);
        $mail->AddReplyTo($from, $from);
        $mail->AddAddress($to, $to);
        $mail->Subject = $subject;
        $mail->MsgHTML($body);

        if (!$mail->Send())
        {
            log_error("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
        else
        {
            return true;
        }
    }

    static function sendMailWithReply($to,$reply,$subject, $htmlContent, $from='no-reply@mail.weibotui.com')
    {
        $mail             = new \PHPMailer();

        //$body             = preg_replace('/[\]/','',$htmlContent);
        $body = $htmlContent;

        $mail->CharSet = 'UTF-8';
        $mail->Hostname = 'mail.weibotui.com';
        $mail->SetFrom($from, $from);
        $mail->AddReplyTo($reply, $reply);
        $mail->AddAddress($to, $to);
        $mail->AddAddress($reply,$reply); // 同时给回复地址发送邮件, 供微邮箱使用
        $mail->Subject    = $subject;
        $mail->MsgHTML($body);

        if(! $mail->Send())
        {
            log_error("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
        else
        {
            return true;
        }
    }
}
