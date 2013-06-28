-- TABLE topic_category

CREATE TABLE IF NOT EXISTS `topic_category` (
    `code` VARCHAR(32) NOT NULL COMMENT '编码',
    `name` VARCHAR(40) NOT NULL COMMENT '名称',
    `_is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'IsDeleted',
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='话题类别';