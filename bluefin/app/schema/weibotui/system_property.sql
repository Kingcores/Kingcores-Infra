-- TABLE system_property

CREATE TABLE IF NOT EXISTS `system_property` (
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `name` VARCHAR(40) COMMENT '名称',
    `value` TEXT NOT NULL COMMENT '属性值',
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统全局属性';