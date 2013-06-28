-- TABLE staff_in_corporate

ALTER TABLE `staff_in_corporate`
ADD CONSTRAINT `fk_staff_in_corporate_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

ALTER TABLE `staff_in_corporate`
ADD CONSTRAINT `fk_staff_in_corporate_company` FOREIGN KEY (`company`)
REFERENCES `corporate` (`corporate_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

