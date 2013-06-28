-- TABLE tuike

CREATE TABLE IF NOT EXISTS `tuike` (
    `alipay` VARCHAR(320) COMMENT '支付宝账号',
    `tenpay` VARCHAR(320) COMMENT '财付通账号',
    `user` INT(10) NOT NULL COMMENT '用户',
    `status` VARCHAR(32) NOT NULL DEFAULT 'unverified' COMMENT '推客状态',
    `unverified_time` DATETIME COMMENT '未审核时间',
    `wait_verify_time` DATETIME COMMENT '待审核时间',
    `verified_time` DATETIME COMMENT '已审核时间',
    `verify_failed_time` DATETIME COMMENT '审核未通过时间',
    `status_log` TEXT COMMENT '推客状态历史',
    PRIMARY KEY (`user`),
    KEY `fk_tuike_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推客';