-- TABLE user_expense_record

ALTER TABLE `user_expense_record`
ADD CONSTRAINT `fk_user_expense_record_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

