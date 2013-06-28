-- TABLE corporate

CREATE TABLE IF NOT EXISTS `corporate` (
    `corporate_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `name` VARCHAR(40) COMMENT '名称',
    `short_name` VARCHAR(20) COMMENT '简称',
    `website` VARCHAR(2083) NOT NULL COMMENT '企业网址',
    `description` TEXT NOT NULL COMMENT '企业简介',
    `address` INT(10) NOT NULL COMMENT '地址',
    `admin` INT(10) NOT NULL COMMENT '管理员',
    `status` VARCHAR(32) NOT NULL DEFAULT 'unverified' COMMENT '企业状态',
    `unverified_time` DATETIME COMMENT '未审核时间',
    `verified_time` DATETIME COMMENT '已审核时间',
    `status_log` TEXT COMMENT '企业状态历史',
    PRIMARY KEY (`corporate_id`),
    UNIQUE KEY `uk_corporate_name` (`name`),
    UNIQUE KEY `uk_corporate_short_name` (`short_name`),
    UNIQUE KEY `uk_corporate_admin` (`admin`),
    KEY `fk_corporate_address` (`address`),
    KEY `fk_corporate_admin` (`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业' AUTO_INCREMENT=1;