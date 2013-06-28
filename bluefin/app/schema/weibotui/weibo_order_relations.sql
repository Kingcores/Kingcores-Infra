-- TABLE weibo_order

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_campaign` FOREIGN KEY (`campaign`)
REFERENCES `weibo_campaign` (`weibo_campaign_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_inventory` FOREIGN KEY (`inventory`)
REFERENCES `weibo_inventory` (`weibo_inventory_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_advertiser` FOREIGN KEY (`advertiser`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_tuike` FOREIGN KEY (`tuike`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_advertiser_expense_record` FOREIGN KEY (`advertiser_expense_record`)
REFERENCES `user_expense_record` (`serial_no`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_tuike_income_record` FOREIGN KEY (`tuike_income_record`)
REFERENCES `user_income_record` (`serial_no`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_service_income_record` FOREIGN KEY (`service_income_record`)
REFERENCES `service_income_record` (`service_income_record_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_order`
ADD CONSTRAINT `fk_weibo_order_verifier` FOREIGN KEY (`verifier`)
REFERENCES `admin` (`admin_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

