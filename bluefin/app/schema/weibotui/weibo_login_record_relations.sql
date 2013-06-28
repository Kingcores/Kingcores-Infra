-- TABLE weibo_login_record

ALTER TABLE `weibo_login_record`
ADD CONSTRAINT `fk_weibo_login_record_weibo` FOREIGN KEY (`weibo`)
REFERENCES `weibo` (`weibo_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

