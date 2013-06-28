-- TABLE weibo

CREATE TABLE IF NOT EXISTS `weibo` (
    `weibo_id` BINARY(16) NOT NULL COMMENT 'UUID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `uid` CHAR(64) NOT NULL COMMENT '微博ID',
    `display_name` VARCHAR(40) COMMENT '名称',
    `url` VARCHAR(2083) NOT NULL COMMENT '微博URL',
    `avatar_s` VARCHAR(2083) NOT NULL COMMENT '小头像',
    `avatar_l` VARCHAR(2083) NOT NULL COMMENT '大头像',
    `location` VARCHAR(100) NOT NULL COMMENT '位置',
    `description` VARCHAR(200) NOT NULL COMMENT '简介',
    `profile` TEXT COMMENT '微博档案',
    `wbt_home` VARCHAR(255) NOT NULL COMMENT '微博推主页',
    `num_follower` INT(10) NOT NULL COMMENT '粉丝',
    `num_following` INT(10) NOT NULL COMMENT '关注',
    `num_post` INT(10) NOT NULL COMMENT '博文',
    `num_like` INT(10) NOT NULL COMMENT '赞',
    `type` VARCHAR(20) NOT NULL DEFAULT 'weibo' COMMENT '微博类型',
    `user` INT(10) COMMENT '用户',
    `gender` VARCHAR(20) NOT NULL DEFAULT 'male' COMMENT '性别',
    PRIMARY KEY (`weibo_id`),
    UNIQUE KEY `uk_weibo_uid_per_weibo` (`type`,`uid`),
    KEY `fk_weibo_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微博账号';