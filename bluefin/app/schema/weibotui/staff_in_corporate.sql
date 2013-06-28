-- TABLE staff_in_corporate

CREATE TABLE IF NOT EXISTS `staff_in_corporate` (
    `staff_in_corporate_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `user` INT(10) NOT NULL COMMENT '用户',
    `company` INT(10) NOT NULL COMMENT '企业',
    PRIMARY KEY (`staff_in_corporate_id`),
    UNIQUE KEY `uk_staff_in_corporate` (`user`,`company`),
    KEY `fk_staff_in_corporate_user` (`user`),
    KEY `fk_staff_in_corporate_company` (`company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业包含的员工用户' AUTO_INCREMENT=1;