-- TABLE admin_role

CREATE TABLE IF NOT EXISTS `admin_role` (
    `role_name` VARCHAR(32) NOT NULL COMMENT 'weibotui.admin_role.role_name',
    `display_name` VARCHAR(20) NOT NULL COMMENT 'weibotui.admin_role.display_name',
    PRIMARY KEY (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='内部用户角色';