-- TABLE weibo_inventory

CREATE TABLE IF NOT EXISTS `weibo_inventory` (
    `weibo_inventory_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `num_audience` INT(10) NOT NULL COMMENT '受众人数',
    `original_price` DECIMAL(15,2) NOT NULL COMMENT '原价',
    `current_price` DECIMAL(15,2) NOT NULL COMMENT '现价',
    `service_charge_rate` FLOAT NOT NULL DEFAULT 0.1 COMMENT '服务费率',
    `advertiser_rating` TINYINT(1) DEFAULT 0 COMMENT '广告主评价',
    `auditor_comment` CHAR(200) COMMENT '审核备注',
    `weibo` BINARY(16) NOT NULL COMMENT '微博账号',
    `user` INT(10) NOT NULL COMMENT '推客',
    `type` VARCHAR(20) NOT NULL DEFAULT 'post' COMMENT '活动类型',
    `status` VARCHAR(32) NOT NULL DEFAULT 'unaudit' COMMENT '状态',
    `unaudit_time` DATETIME COMMENT '未审核时间',
    `audit_failed_time` DATETIME COMMENT '不合格时间',
    `available_time` DATETIME COMMENT '可用时间',
    `unavailable_time` DATETIME COMMENT '暂停时间',
    `status_log` TEXT COMMENT '状态历史',
    `auditor` INT(10) COMMENT '审核员',
    PRIMARY KEY (`weibo_inventory_id`),
    KEY `fk_weibo_inventory_weibo` (`weibo`),
    KEY `fk_weibo_inventory_user` (`user`),
    KEY `fk_weibo_inventory_auditor` (`auditor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微博渠道' AUTO_INCREMENT=1;