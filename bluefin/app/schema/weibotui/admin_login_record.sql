-- TABLE admin_login_record

CREATE TABLE IF NOT EXISTS `admin_login_record` (
    `admin_login_record_id` BINARY(16) NOT NULL COMMENT 'UUID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `ip_address` CHAR(15) NOT NULL COMMENT '登录地址',
    `admin` INT(10) NOT NULL COMMENT '内部用户',
    PRIMARY KEY (`admin_login_record_id`),
    KEY `fk_admin_login_record_admin` (`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员登录记录';