-- TABLE country

CREATE TABLE IF NOT EXISTS `country` (
    `code` VARCHAR(32) NOT NULL COMMENT '编码',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `name` VARCHAR(40) COMMENT '名称',
    `phone_area_code` SMALLINT(4) COMMENT '电话区号',
    `capital_city` VARCHAR(32) COMMENT '首都',
    PRIMARY KEY (`code`),
    KEY `fk_country_capital_city` (`capital_city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='国家';