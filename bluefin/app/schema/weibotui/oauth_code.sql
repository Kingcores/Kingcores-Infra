-- TABLE oauth_code

CREATE TABLE IF NOT EXISTS `oauth_code` (
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `code` CHAR(40) NOT NULL COMMENT '授权码',
    `redirect_uri` VARCHAR(2083) NOT NULL COMMENT '回调地址',
    `expires` DATETIME NOT NULL COMMENT '有效期',
    `scope` TEXT COMMENT '授权范围',
    `client` INT(10) NOT NULL COMMENT 'OAuth客户',
    `user` INT(10) COMMENT '用户',
    PRIMARY KEY (`code`),
    KEY `fk_oauth_code_client` (`client`),
    KEY `fk_oauth_code_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='OAuth授权码';