-- TABLE weibo_token

CREATE TABLE IF NOT EXISTS `weibo_token` (
    `weibo_token_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `access_token` VARCHAR(128) NOT NULL COMMENT '令牌',
    `refresh_token` VARCHAR(128) COMMENT '刷新令牌',
    `remind_in` INT(10) NOT NULL COMMENT '提醒秒数',
    `expires_in` INT(10) NOT NULL COMMENT '过期秒数',
    `app_key` VARCHAR(20) NOT NULL COMMENT '应用密钥',
    `app_secret` VARCHAR(128) NOT NULL COMMENT '应用密码',
    `expires_at` DATETIME NOT NULL COMMENT '过期时间',
    `weibo` BINARY(16) NOT NULL COMMENT '微博账号',
    PRIMARY KEY (`weibo_token_id`),
    UNIQUE KEY `uk_weibo_token_per_app` (`weibo`,`app_key`),
    KEY `fk_weibo_token_weibo` (`weibo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微博令牌' AUTO_INCREMENT=1;