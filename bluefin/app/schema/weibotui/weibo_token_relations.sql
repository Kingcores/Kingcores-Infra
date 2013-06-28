-- TABLE weibo_token

ALTER TABLE `weibo_token`
ADD CONSTRAINT `fk_weibo_token_weibo` FOREIGN KEY (`weibo`)
REFERENCES `weibo` (`weibo_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

