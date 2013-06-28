<?php

namespace WBT\Controller\ThirdParty;

use Bluefin\Convention;
use WBT\Model\Weibotui\Income;
use WBT\Model\Weibotui\UserDepositRecord;
use WBT\Model\Weibotui\PaymentMethod;
use WBT\Model\Weibotui\TransactionStatus;
use WBT\Controller\WBTControllerBase;
use Common\Data\Event;

class AlipayController extends WBTControllerBase
{
    public function submitAction()
    {
        $incomeType = $this->_request->get('income_type');
        _ARG_IS_SET(_META_('weibotui.income.income_type'), $incomeType);

        $amount = $this->_request->get('amount');
        _ARG_IS_SET(_META_('weibotui.income.total_amount'), $amount);

        $billId = $this->_request->get('bill_id');
        _ARG_IS_SET(_META_('weibotui.income.bill_id'), $billId);

        if (ENV == 'dev')
        {
            $mockPay = $this->_request->get('mock_pay');
        }
        else
        {
            $mockPay = null;
        }

        $db = Income::s_metadata()->getDatabase()->getAdapter();
        $db->beginTransaction();

        try
        {
            $income = new Income([Income::TYPE => $incomeType, Income::BILL_ID => $billId]);
            if (!$income->isEmpty())
            {
                //已成功订单
                if ($income->getStatus() == TransactionStatus::DONE)
                {
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_('The bill [#%bill%] has already been paid.', ['%bill%' => $billId])
                    );
                }

                //正在处理
                if ($income->getStatus() == TransactionStatus::ONGOING)
                {
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_('The bill [#%bill%] is being processing.', ['%bill%' => $billId])
                    );
                }

                //失效订单
                throw new \Bluefin\Exception\InvalidRequestException(
                    _APP_('The bill [#%bill%] is invalid.', ['%bill%' => $billId])
                );
            }

            $income->setStatus(TransactionStatus::ONGOING)
                ->setType($incomeType)
                ->setTotalAmount($amount)
                ->setBillID($billId)
                ->setPaymentMethod(PaymentMethod::ALIPAY)
                ->save();

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();
            throw $e;
        }

        $alipayParams = _C('config.pay.alipay');
        $alipayConfig = $alipayParams['config'];

         //支付类型
        $payment_type = "1";
        //必填，不能修改
        //服务器异步通知页面路径
        $notify_url = \Bluefin\VarText::parseVarText($alipayParams['notifyUrl']);
        //需http://格式的完整路径，不能加?id=123这类自定义参数

        //页面跳转同步通知页面路径
        $return_url = \Bluefin\VarText::parseVarText($alipayParams['returnUrl']);
        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

        //卖家支付宝帐户
        $seller_email = $alipayParams['sellerEmail'];
        //必填

        //商户订单号
        $out_trade_no = $income->getSerialNo();
        //商户网站订单系统中唯一订单号，必填

        //订单名称
        $subject = $income->getType_EnumValue();
        //必填

        //付款金额
        $total_fee = $income->getTotalAmount();
        //必填

        /************************************************************/
        //构造要请求的参数数组，无需改动
        $parameter = array(
                "service" => "create_direct_pay_by_user",
                "partner" => $alipayConfig['partner'],
                "payment_type"	=> $payment_type,
                "notify_url"	=> $notify_url,
                "return_url"	=> $return_url,
                "seller_email"	=> $seller_email,
                "out_trade_no"	=> $out_trade_no,
                "subject" => $subject,
                "total_fee"	=> $total_fee,
                "_input_charset" => $alipayConfig['input_charset']
        );

        //建立请求
        require_once("Alipay/lib/alipay_submit.class.php");
        $alipaySubmit = new \AlipaySubmit($alipayConfig);
        $form = $alipaySubmit->buildRequestForm($parameter, 'get', '');

        if (isset($mockPay))
        {
            $action = $this->_app->basePath() . 'third_party/alipay/mock';
            $form = preg_replace("/action='.+?'/", "action='{$action}'", $form);
            $form = preg_replace("/<\\/form>/", "<input type='hidden' name='mock_flag' value='{$mockPay}'></form>", $form);
        }

        $this->_view->set('form', $form);
    }

    public function returnAction()
    {
        $alipayParams = _C('config.pay.alipay');
        $alipayConfig = $alipayParams['config'];

        require_once("Alipay/lib/alipay_notify.class.php");
        $alipayNotify = new \AlipayNotify($alipayConfig);

        if (ENV != 'dev')
        {
            $verify_result = $alipayNotify->verifyReturn();
        }
        else
        {
            $verify_result = true;
        }

        $params = $this->_app->request()->getQueryParams();

        if ($verify_result)
        {
            //商户订单号
            $out_trade_no = $params['out_trade_no'];

            //交易状态
            $trade_status = $params['trade_status'];

            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS')
            {
                $income = new Income([Income::SERIAL_NO => $out_trade_no]);
                $incomeType = $income->getType();
                $redirect = _C("config.custom.url.payment_redirect.{$incomeType}");

                $this->_gateway->redirect(build_uri($redirect, [ 'bill_id' => $income->getBillID(), 'serial_no' => $income->getSerialNo() ]));
            }
            else
            {
                $this->_showEventMessage(Event::E_ALIPAY_FAIL, Event::SRC_PAYMENT);
            }
        }
        else
        {
            $this->_showEventMessage(Event::E_ALIPAY_INVALID, Event::SRC_PAYMENT);
        }
    }

    public function notifyAction()
    {
        $alipayParams = _C('config.pay.alipay');
        $alipayConfig = $alipayParams['config'];

        require_once("Alipay/lib/alipay_notify.class.php");

        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipayConfig);
        if (ENV != 'dev')
        {
            $verify_result = $alipayNotify->verifyNotify();
            $params = $this->_app->request()->getPostParams();
        }
        else
        {
            $verify_result = true;
            $params = $this->_app->request()->getQueryParams();
        }

        if ($verify_result)
        {//验证成功
            //商户订单号
            $out_trade_no = $params['out_trade_no'];
            //支付宝交易号
            $trade_no = $params['trade_no'];
            //交易状态
            $trade_status = $params['trade_status'];

            if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS')
            {
                $error = null;

                $this->_app->setRegistry(Convention::KEYWORD_VENDOR_ROLE, true);

                try
                {
                    Income::doSucceed($out_trade_no, [Income::VENDOR_NO => $trade_no]);
                }
                catch (\Exception $e)
                {
                    $error = $e->getMessage();
                }

                $this->_app->setRegistry(Convention::KEYWORD_VENDOR_ROLE, false);

                if (isset($error))
                {
                    $this->_app->log('alipay')->error(['status' => $trade_status, 'text' => $error, 'data' => ['serial_no' => $out_trade_no, 'verdor_no' => $trade_no]]);
                }
                else
                {
                    $this->_app->log('alipay')->info(['status' => $trade_status, 'data' => ['serial_no' => $out_trade_no, 'verdor_no' => $trade_no]]);
                }
            }
            else
            {
                $this->_app->log('alipay')->warning(['text' => 'unknown status', 'data' => $params]);
            }

            try
            {
                $income = new Income([Income::SERIAL_NO => $out_trade_no]);
                $incomeType = $income->getType();
                $notify = _C("config.custom.url.payment_notify.{$incomeType}");

                if (isset($notify))
                {
                    $notifyUrl = build_uri($notify, [ 'bill_id' => $income->getBillID(), 'serial_no' => $income->getSerialNo() ]);

                    $snoopy = new \Snoopy\Snoopy();

                    $retryTimes = 0;

                    while ($retryTimes < 3)
                    {
                        if ($snoopy->fetch($notifyUrl))
                        {
                            $response = trim($snoopy->results);
                            if ($response == 'success') break;

                            $response = json_decode($response, true);
                            if (isset($response['errno']) &&  $response['errno'] == 0)
                            {
                                break;
                            }
                        }

                        $retryTimes++;
                    }

                    if ($retryTimes == 3)
                    {
                        \Common\Helper\Alert::sendAlertEmail('Alipay Error Alert', '[pay@WBT]Failed to get response from ' . $notifyUrl);
                    }
                }
            }
            catch (\Exception $e)
            {
                $this->_app->log('alipay')->error(['text'=> 'Failed to notify client', 'serial_no' => $out_trade_no]);
            }

            //请不要修改或删除
            if (ENV != 'dev')
            {
                echo "success";
            }
            else
            {
                $return_url = \Bluefin\VarText::parseVarText($alipayParams['returnUrl']);

                echo "success<hr>2.Front-end Redirect: <a href='{$return_url}?out_trade_no={$out_trade_no}&trade_status={$trade_status}&trade_no={$trade_no}'>{$return_url}</a>";
            }
        }
        else
        {
            $this->_app->log('alipay')->verbose(['text'=> 'Failed to verify', 'data' => $this->_app->request()->getPostParams()]);

            //验证失败
            echo "fail";
        }
    }

    public function mockAction()
    {
        if (ENV != 'dev')
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        echo "<p>SUBMIT FORM:<hr>\n";
        $params = \Symfony\Component\Yaml\Yaml::dump($this->_request->getQueryParams(), 10);
        echo nl2br(str_replace(' ', '&nbsp;', $params)) . "</p>";

        $alipayParams = _C('config.pay.alipay');
        $notify_url = \Bluefin\VarText::parseVarText($alipayParams['notifyUrl']);
        $tradeStatus = $this->_request->getQueryParam('mock_flag', '1') == '1' ? 'TRADE_SUCCESS' : 'TRADE_FAILURE';
        $tradeNo = date('YmdHisu', time()) . rand(100, 999);

        echo "<p>MOCK CALLBACK:<hr>\n";
        echo "1.Back-end Notify: <a href='{$notify_url}?out_trade_no={$this->_request->getQueryParam('out_trade_no')}&trade_status={$tradeStatus}&trade_no={$tradeNo}'>{$notify_url}</a><br><br>";
        echo "</p>";
    }
}
