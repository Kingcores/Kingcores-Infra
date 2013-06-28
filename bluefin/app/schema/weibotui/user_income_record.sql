-- TABLE user_income_record

CREATE TABLE IF NOT EXISTS `user_income_record` (
    `serial_no` CHAR(20) NOT NULL COMMENT '流水号',
    `amount` DECIMAL(15,2) NOT NULL COMMENT '收入金额',
    `user` INT(10) NOT NULL COMMENT '用户',
    `source` VARCHAR(20) NOT NULL DEFAULT 'weibo_order' COMMENT '收入来源',
    PRIMARY KEY (`serial_no`),
    KEY `fk_user_income_record_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户收入记录';