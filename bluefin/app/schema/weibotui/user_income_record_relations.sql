-- TABLE user_income_record

ALTER TABLE `user_income_record`
ADD CONSTRAINT `fk_user_income_record_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

