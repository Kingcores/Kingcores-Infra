-- TABLE weibo_inventory

ALTER TABLE `weibo_inventory`
ADD CONSTRAINT `fk_weibo_inventory_weibo` FOREIGN KEY (`weibo`)
REFERENCES `weibo` (`weibo_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_inventory`
ADD CONSTRAINT `fk_weibo_inventory_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

ALTER TABLE `weibo_inventory`
ADD CONSTRAINT `fk_weibo_inventory_auditor` FOREIGN KEY (`auditor`)
REFERENCES `admin` (`admin_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

