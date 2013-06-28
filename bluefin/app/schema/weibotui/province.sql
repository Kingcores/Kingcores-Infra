-- TABLE province

CREATE TABLE IF NOT EXISTS `province` (
    `code` VARCHAR(32) NOT NULL COMMENT '编码',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `name` VARCHAR(40) COMMENT '名称',
    `short_name` VARCHAR(20) COMMENT '简称',
    `admin_code` VARCHAR(32) COMMENT '行政区号',
    `type` VARCHAR(20) DEFAULT 'province' COMMENT '地区类型',
    `country` VARCHAR(32) NOT NULL COMMENT '国家',
    `capital_city` VARCHAR(32) COMMENT '市',
    PRIMARY KEY (`code`),
    KEY `fk_province_country` (`country`),
    KEY `fk_province_capital_city` (`capital_city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='省';