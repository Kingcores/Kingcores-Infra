-- TABLE service_income_record

ALTER TABLE `service_income_record`
ADD CONSTRAINT `fk_service_income_record_consumer_expense` FOREIGN KEY (`consumer_expense`)
REFERENCES `user_expense_record` (`serial_no`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `service_income_record`
ADD CONSTRAINT `fk_service_income_record_supplier_income` FOREIGN KEY (`supplier_income`)
REFERENCES `user_income_record` (`serial_no`) ON UPDATE RESTRICT ON DELETE RESTRICT;

