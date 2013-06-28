-- TABLE city

CREATE TABLE IF NOT EXISTS `city` (
    `code` VARCHAR(32) NOT NULL COMMENT '编码',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `name` VARCHAR(40) COMMENT '名称',
    `admin_code` VARCHAR(32) COMMENT '行政区号',
    `postcode` VARCHAR(10) COMMENT '邮编',
    `phone_area_code` SMALLINT(4) COMMENT '电话区号',
    `province` VARCHAR(32) NOT NULL COMMENT '省',
    PRIMARY KEY (`code`),
    KEY `fk_city_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='市';