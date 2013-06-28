-- TABLE admin_with_role

CREATE TABLE IF NOT EXISTS `admin_with_role` (
    `admin_with_role_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `admin` INT(10) NOT NULL COMMENT '内部用户',
    `role` VARCHAR(32) NOT NULL COMMENT '内部用户角色',
    PRIMARY KEY (`admin_with_role_id`),
    UNIQUE KEY `uk_admin_with_role` (`admin`,`role`),
    KEY `fk_admin_with_role_admin` (`admin`),
    KEY `fk_admin_with_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理用户组包含的管理员' AUTO_INCREMENT=1;