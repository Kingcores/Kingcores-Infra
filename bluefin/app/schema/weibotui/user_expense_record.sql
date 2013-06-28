-- TABLE user_expense_record

CREATE TABLE IF NOT EXISTS `user_expense_record` (
    `serial_no` CHAR(20) NOT NULL COMMENT '流水号',
    `batch_id` CHAR(20) NOT NULL COMMENT '批次',
    `amount` DECIMAL(15,2) NOT NULL COMMENT '支出金额',
    `user` INT(10) NOT NULL COMMENT '用户',
    `usage` VARCHAR(20) NOT NULL DEFAULT 'weibo_order' COMMENT '支出用途',
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT '状态',
    `pending_time` DATETIME COMMENT '冻结时间',
    `paid_time` DATETIME COMMENT '已付时间',
    `cancelled_time` DATETIME COMMENT '取消时间',
    `status_log` TEXT COMMENT '状态历史',
    PRIMARY KEY (`serial_no`),
    KEY `fk_user_expense_record_user` (`user`),
    KEY `ak_user_expense_record_batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户支出记录';