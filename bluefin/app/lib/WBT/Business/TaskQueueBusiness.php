<?php

namespace WBT\Business;

use Bluefin\App;
use Bluefin\Data\DbCondition;
use WBT\Model\Weibotui\TaskQueue;
use WBT\Model\Weibotui\TaskStatus;


class TaskQueueBusiness
{
    /**
     * 添加任务
     * @param datetime execute_time
     * @param $param
     * @return int taskid
     */
    public static function addTask($execute_time, $param)
    {
        $task = new TaskQueue();

        $task->setParam($param)
            ->setStatus(TaskStatus::TODO)
            ->setExecuteTime($execute_time);

        $task->insert();

        return $task->getTaskQueueID();
    }


    /**
     * 删除任务
     * @param int taskid
     */
    public static function deleteTask($taskid)
    {
        $task = new TaskQueue($taskid);
        $task->delete();
    }

    /* 获取指定时间范围获取需要执行的定时任务，并将任务状态更新为执行中
    * @param datetime startTime
    * @param datetime endTime
    */
    public static function getDingshiTaskToDoAndUpdateStatusToDoing($startTime, $endTime)
    {
        $taskIdArray = [];

        $sql_condition = sprintf("'%s' < execute_time and execute_time <= '%s'",
            datetime_to_str($startTime),datetime_to_str($endTime));

        $condition = [TaskQueue::STATUS => TaskStatus::TODO ,new DbCondition($sql_condition)];

        $taskIdArray = TaskQueue::fetchColumn(TaskQueue::TASK_QUEUE_ID,$condition);

        if(empty($taskIdArray) )
        {
            return  $taskIdArray;
        }

        $task  = new TaskQueue();
        $task->setStatus(TaskStatus::DOING)->update([TaskQueue::TASK_QUEUE_ID => $taskIdArray]);

        return $taskIdArray;
    }
}

