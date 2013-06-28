<?php

namespace WBT\Controller;

use Bluefin\App;

use WBT\Business\AuthBusiness;
use WBT\Business\UserBusiness;

class HomeController extends WBTControllerBase
{
    public function indexAction()
    {
        if (AuthBusiness::isLoggedIn())
        {
            $this->_gateway->redirect($this->_gateway->path('home/weibotui/index'));
        }
    }

    public function sessionAction()
    {
        if (ENV != 'dev')
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        echo '<pre>' . nl2br(\Symfony\Component\Yaml\Yaml::dump($_SESSION, 10)) . '</pre>';
    }

    public function cacheAction()
    {
        if (ENV != 'dev')
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        $cacheID = $this->_request->getQueryParam('id');

        $cache = App::getInstance()->cache($cacheID);

        if ($this->_request->has('info'))
        {
            $data = $cache->getHandlerObject()->info();
        }
        else if ($this->_request->has('keyspace'))
        {
            $data = $cache->getHandlerObject()->info('keyspace');
        }
        else if ($this->_request->has('size'))
        {
            $data = $cache->getHandlerObject()->dbSize();
        }
        else if ($this->_request->has('keys'))
        {
            $data = $cache->getHandlerObject()->keys('*');
        }
        else
        {
            $data = $cache->get();
        }

        echo '<pre>' . nl2br(\Symfony\Component\Yaml\Yaml::dump($data, 10)) . '</pre>';
    }

    public function testPayAction()
    {
        //长微博支付测试页
    }

    public function testPayOkAction()
    {
        //长微博支付结果页范例
        $billId = $this->_request->getQueryParam('bill_id');
        $serialNo = $this->_request->getQueryParam('serial_no');

        require_once 'WBTSdk/WBTPrivateClient.php';
        $cwbClient = new \WBTPrivateClient('10001', 'b084ff59d3588b62e40e81c06186a709');
        $payment = $cwbClient->payment_get_status('changweibo_deposit', $billId);

        if (isset($payment['serial_no']) && $payment['serial_no'] == $serialNo)
        {//账单和流水号是匹配的
            if ($payment['status'] == 'done')
            {//支付成功

            }
            else if ($payment['status'] == 'ongoing')
            {//正在处理中，请等待，一般不会发生，除非支付供应商不能保证通知的时序

            }
            else
            {//支付失败或用户自己取消了支付

            }
        }
        else
        {//非法请求，用户恶意改了账单号或流水号

        }

        echo '<pre>' . nl2br(\Symfony\Component\Yaml\Yaml::dump($payment, 10)) . '</pre>';
    }
}