-- TABLE weibo_topic

ALTER TABLE `weibo_topic`
ADD CONSTRAINT `fk_weibo_topic_weibo` FOREIGN KEY (`weibo`)
REFERENCES `weibo` (`weibo_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `weibo_topic`
ADD CONSTRAINT `fk_weibo_topic_catetory` FOREIGN KEY (`catetory`)
REFERENCES `topic_category` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

