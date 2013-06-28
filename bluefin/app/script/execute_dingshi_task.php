<?php
require_once '../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use WBT\Business\TaskQueueBusiness;
use WBT\Business\SystemBusiness;
use WBT\Model\Weibotui\TaskQueue;
use WBT\Model\Weibotui\TaskStatus;

try
{
    process();
}
catch (ServerErrorException $srvEx)
{
    //转到服务器错误页
    $errorCode = $srvEx->getCode();
    $message = \Bluefin\Common::getStatusCodeMessage($errorCode);

    log_error('Server Error: ' . $srvEx->getMessage() . "\n" . $srvEx->getTraceAsString());
}
catch (Exception $e)
{
    $errorCode = \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE;
    $message = \Bluefin\Common::getStatusCodeMessage($errorCode);
    log_error('Unknown Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
}


function process()
{
    $app = \Bluefin\App::getInstance();

    $endTime = time();

    // 近2分钟内的任务
    $startTime = time() - 2 * 60;

    $taskIDArray=  TaskQueueBusiness::getDingshiTaskToDoAndUpdateStatusToDoing($startTime, $endTime);

    $taskCount= count($taskIDArray);
    $taskFailedCount = 0;
    foreach($taskIDArray as $taskid)
    {
        try
        {
            $task = new TaskQueue([TaskQueue::TASK_QUEUE_ID => $taskid]);
            if($task->isEmpty())
            {
                log_error("could not find task(id=$taskid)");
                continue;
            }
            $res =  SystemBusiness::timerCallback($taskid,$task->getParam());
            if($res['errno'])
            {
                $taskFailedCount++;
            }
            $task->setErrno($res['errno'])->setStatus(\WBT\Model\Weibotui\TaskStatus::FINISHED);
            $task->save();
        }
        catch (ServerErrorException $srvEx)
        {
            //转到服务器错误页
            $errorCode = $srvEx->getCode();
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);
            log_error('Server Error: ' . $srvEx->getMessage() . "\n" . $srvEx->getTraceAsString());
        }
        catch (\Exception $e)
        {
            $errorCode = \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE;
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);
            log_error('Unknown Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

    }

    log_info("[CRONTAB_TASK_QUEUE][taskCount:$taskCount][taskFailedCount:$taskFailedCount]");
}
