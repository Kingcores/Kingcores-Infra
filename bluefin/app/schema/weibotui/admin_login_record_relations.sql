-- TABLE admin_login_record

ALTER TABLE `admin_login_record`
ADD CONSTRAINT `fk_admin_login_record_admin` FOREIGN KEY (`admin`)
REFERENCES `admin` (`admin_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

