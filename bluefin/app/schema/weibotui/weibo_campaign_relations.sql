-- TABLE weibo_campaign

ALTER TABLE `weibo_campaign`
ADD CONSTRAINT `fk_weibo_campaign_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

