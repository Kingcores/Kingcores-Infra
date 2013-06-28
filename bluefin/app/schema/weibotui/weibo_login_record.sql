-- TABLE weibo_login_record

CREATE TABLE IF NOT EXISTS `weibo_login_record` (
    `weibo_login_record_id` BINARY(16) NOT NULL COMMENT 'UUID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `ip_address` CHAR(15) NOT NULL COMMENT '登录地址',
    `weibo` BINARY(16) NOT NULL COMMENT '微博账号',
    PRIMARY KEY (`weibo_login_record_id`),
    KEY `fk_weibo_login_record_weibo` (`weibo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微博登录记录';