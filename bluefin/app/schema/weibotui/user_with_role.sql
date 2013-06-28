-- TABLE user_with_role

CREATE TABLE IF NOT EXISTS `user_with_role` (
    `user_with_role_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `user` INT(10) NOT NULL COMMENT '用户',
    `role` VARCHAR(20) NOT NULL DEFAULT 'sn_user' COMMENT '用户角色',
    PRIMARY KEY (`user_with_role_id`),
    UNIQUE KEY `uk_user_with_role` (`user`,`role`),
    KEY `fk_user_with_role_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户组包含的用户' AUTO_INCREMENT=1;