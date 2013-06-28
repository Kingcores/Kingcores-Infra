-- TABLE district

CREATE TABLE IF NOT EXISTS `district` (
    `code` VARCHAR(32) NOT NULL COMMENT '编码',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    `name` VARCHAR(40) COMMENT '名称',
    `admin_code` VARCHAR(32) COMMENT '行政区号',
    `postcode` VARCHAR(10) COMMENT '邮编',
    `city` VARCHAR(32) NOT NULL COMMENT '市',
    PRIMARY KEY (`code`),
    KEY `fk_district_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='区';