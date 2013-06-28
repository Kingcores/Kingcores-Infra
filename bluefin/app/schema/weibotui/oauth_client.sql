-- TABLE oauth_client

CREATE TABLE IF NOT EXISTS `oauth_client` (
    `oauth_client_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name` VARCHAR(20) COMMENT '客户端名称',
    `secret` CHAR(32) NOT NULL COMMENT '密钥',
    `redirect_uri` VARCHAR(2083) NOT NULL COMMENT '回调地址',
    `level` VARCHAR(20) NOT NULL DEFAULT 'public_client' COMMENT '客户端等级',
    PRIMARY KEY (`oauth_client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='OAuth客户' AUTO_INCREMENT=10000;