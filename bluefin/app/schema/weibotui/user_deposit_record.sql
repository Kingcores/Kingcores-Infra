-- TABLE user_deposit_record

CREATE TABLE IF NOT EXISTS `user_deposit_record` (
    `user_deposit_record_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `amount` DECIMAL(15,2) NOT NULL COMMENT '充值金额',
    `deposit_bonus` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '充值奖励',
    `points_bonus` INT(10) NOT NULL DEFAULT 0 COMMENT '积分奖励',
    `invoice_issued` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '发票已开',
    `user` INT(10) NOT NULL COMMENT '用户',
    `transaction` CHAR(20) COMMENT '进款交易',
    `invoice` INT(10) COMMENT '发票',
    `status` VARCHAR(32) NOT NULL DEFAULT 'unpaid' COMMENT '状态',
    `unpaid_time` DATETIME COMMENT '未支付时间',
    `waiting_time` DATETIME COMMENT '支付中时间',
    `cancelled_time` DATETIME COMMENT '已取消时间',
    `paid_time` DATETIME COMMENT '已支付时间',
    `status_log` TEXT COMMENT '状态历史',
    PRIMARY KEY (`user_deposit_record_id`),
    KEY `fk_user_deposit_record_user` (`user`),
    KEY `fk_user_deposit_record_transaction` (`transaction`),
    KEY `fk_user_deposit_record_invoice` (`invoice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户充值记录' AUTO_INCREMENT=1;