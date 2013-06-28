-- TABLE qq_dingshi_weibo

CREATE TABLE IF NOT EXISTS `qq_dingshi_weibo` (
    `qq_dingshi_weibo_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `uid` CHAR(64) NOT NULL COMMENT 'weibo user id',
    `app_key` CHAR(20) NOT NULL COMMENT 'app key',
    `text` TEXT NOT NULL COMMENT '微博文本内容',
    `image_url` VARCHAR(2083) COMMENT '图片URL',
    `rt_id` BIGINT(20) NOT NULL DEFAULT 0 COMMENT '要转发的微博id',
    `rt_json` TEXT COMMENT '微要转发微博的json内容',
    `send_time` DATETIME NOT NULL COMMENT '发送时间',
    `weibo_url` CHAR(64) COMMENT '微博URL地址',
    `ip_address` CHAR(15) COMMENT '登录地址',
    `errno` INT(10) NOT NULL DEFAULT 0 COMMENT '错误号。非0值标志有错误',
    `error` TEXT COMMENT '错误信息',
    `status` VARCHAR(20) NOT NULL DEFAULT 'tosend' COMMENT '发送状态',
    PRIMARY KEY (`qq_dingshi_weibo_id`),
    UNIQUE KEY `uk_qq_dingshi_weibo_k_uid_send_time` (`uid`,`send_time`),
    KEY `ak_qq_dingshi_weibo_k_send_time` (`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='腾讯定时微博' AUTO_INCREMENT=1;