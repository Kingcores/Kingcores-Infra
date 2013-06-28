<?php

namespace WBT\Business;

use Bluefin\App;
use Bluefin\Data\Model;
use WBT\Business\TaskQueueBusiness;

class SystemBusiness
{
    public static function postTimerCall(Model $caller, $executionTime, $methodName, array $params = null)
    {
        isset($params) || ($params = []);
        array_unshift($params, $methodName);

        $taskid = TaskQueueBusiness::addTask($executionTime, json_encode($params));

        $taskidField = $caller->metadata()->getFeatureContext('scheduled_task');

        $caller->__set($taskidField, $taskid);

        $caller->update();
    }

    public static function cancelTimerCall(Model $caller)
    {
        $taskidField = $caller->metadata()->getFeatureContext('scheduled_task');

        $taskid = $caller->__get($taskidField);

        if (isset($taskid))
        {
            $caller->__set($taskidField, null);

            $caller->update();

            TaskQueueBusiness::deleteTask($taskid);
        }
    }

    public static function timerCallback($taskid, $param)
    {
        $result = ['errno'=>0, 'taskid'=>$taskid];

        $params = json_decode($param, true);

        $methodName = array_shift($params);

        App::getInstance()->setRegistry(\Bluefin\Convention::KEYWORD_SYSTEM_ROLE, true);

        try
        {
            call_user_func_array($methodName, $params);
        }
        catch (\Exception $e)
        {
            App::getInstance()->setRegistry(\Bluefin\Convention::KEYWORD_SYSTEM_ROLE, false);
            throw $e;
        }

        App::getInstance()->setRegistry(\Bluefin\Convention::KEYWORD_SYSTEM_ROLE, false);

        return $result;
    }
}