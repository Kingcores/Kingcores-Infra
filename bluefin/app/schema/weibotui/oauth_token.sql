-- TABLE oauth_token

CREATE TABLE IF NOT EXISTS `oauth_token` (
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `access_token` CHAR(40) NOT NULL COMMENT '访问令牌',
    `expires` DATETIME NOT NULL COMMENT '有效期',
    `scope` TEXT COMMENT '授权范围',
    `session_data` TEXT COMMENT '会话数据',
    `client` INT(10) NOT NULL COMMENT 'OAuth客户',
    `user` INT(10) COMMENT '用户',
    PRIMARY KEY (`access_token`),
    KEY `fk_oauth_token_client` (`client`),
    KEY `fk_oauth_token_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='OAuth令牌';