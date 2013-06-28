-- TABLE user_login_record

CREATE TABLE IF NOT EXISTS `user_login_record` (
    `user_login_record_id` BINARY(16) NOT NULL COMMENT 'UUID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `ip_address` CHAR(15) NOT NULL COMMENT '登录地址',
    `user` INT(10) NOT NULL COMMENT '用户',
    `type` VARCHAR(20) NOT NULL DEFAULT 'weibotui' COMMENT '登录来源类型',
    PRIMARY KEY (`user_login_record_id`),
    KEY `fk_user_login_record_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户登录记录';