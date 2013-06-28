-- TABLE income

CREATE TABLE IF NOT EXISTS `income` (
    `serial_no` CHAR(20) NOT NULL COMMENT '流水号',
    `bill_id` CHAR(20) NOT NULL COMMENT '订单号',
    `vendor_no` CHAR(32) COMMENT '交易号',
    `total_amount` DECIMAL(15,2) NOT NULL COMMENT '总金额',
    `type` VARCHAR(20) NOT NULL DEFAULT 'advertiser_deposit' COMMENT '收入类型',
    `payment_method` VARCHAR(20) NOT NULL DEFAULT 'alipay' COMMENT '支付方式',
    `status` VARCHAR(32) NOT NULL DEFAULT 'ongoing' COMMENT '交易状态',
    `ongoing_time` DATETIME COMMENT '进行中时间',
    `done_time` DATETIME COMMENT '已完成时间',
    `failed_time` DATETIME COMMENT '失败时间',
    `cancelled_time` DATETIME COMMENT '已撤销时间',
    `status_log` TEXT COMMENT '交易状态历史',
    PRIMARY KEY (`serial_no`),
    UNIQUE KEY `uk_income_bill` (`bill_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='进款交易';