-- TABLE address

CREATE TABLE IF NOT EXISTS `address` (
    `address_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `detail_locality` VARCHAR(80) COMMENT '详细地址',
    `district_address` VARCHAR(100) NOT NULL COMMENT '地区地址',
    `full_address` VARCHAR(200) NOT NULL COMMENT '完整地址',
    `country` VARCHAR(32) COMMENT '国家',
    `province` VARCHAR(32) COMMENT '省',
    `city` VARCHAR(32) COMMENT '市',
    `district` VARCHAR(32) COMMENT '区',
    PRIMARY KEY (`address_id`),
    KEY `fk_address_country` (`country`),
    KEY `fk_address_province` (`province`),
    KEY `fk_address_city` (`city`),
    KEY `fk_address_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='地址' AUTO_INCREMENT=1;