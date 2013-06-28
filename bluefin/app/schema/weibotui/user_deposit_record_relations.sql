-- TABLE user_deposit_record

ALTER TABLE `user_deposit_record`
ADD CONSTRAINT `fk_user_deposit_record_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

ALTER TABLE `user_deposit_record`
ADD CONSTRAINT `fk_user_deposit_record_transaction` FOREIGN KEY (`transaction`)
REFERENCES `income` (`serial_no`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `user_deposit_record`
ADD CONSTRAINT `fk_user_deposit_record_invoice` FOREIGN KEY (`invoice`)
REFERENCES `invoice` (`invoice_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

