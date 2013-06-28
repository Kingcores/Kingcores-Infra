-- TABLE admin_with_role

ALTER TABLE `admin_with_role`
ADD CONSTRAINT `fk_admin_with_role_admin` FOREIGN KEY (`admin`)
REFERENCES `admin` (`admin_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `admin_with_role`
ADD CONSTRAINT `fk_admin_with_role` FOREIGN KEY (`role`)
REFERENCES `admin_role` (`role_name`) ON UPDATE RESTRICT ON DELETE RESTRICT;

