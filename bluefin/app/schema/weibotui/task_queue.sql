-- TABLE task_queue

CREATE TABLE IF NOT EXISTS `task_queue` (
    `task_queue_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `param` TEXT NOT NULL COMMENT '任务的参数',
    `execute_time` DATETIME NOT NULL COMMENT '任务执行时间',
    `errno` INT(10) NOT NULL DEFAULT 0 COMMENT '错误号，非0表示任务执行错误',
    `status` VARCHAR(20) NOT NULL DEFAULT 'todo' COMMENT '任务状态',
    PRIMARY KEY (`task_queue_id`),
    KEY `ak_task_queue_k_execure_time` (`execute_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='任务队列' AUTO_INCREMENT=1;