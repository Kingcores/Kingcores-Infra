-- TABLE weibo_topic

CREATE TABLE IF NOT EXISTS `weibo_topic` (
    `weibo_topic_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `confidence` FLOAT NOT NULL COMMENT '置信度',
    `weibo` BINARY(16) NOT NULL COMMENT '微博账号',
    `catetory` VARCHAR(32) NOT NULL COMMENT '话题类别',
    PRIMARY KEY (`weibo_topic_id`),
    UNIQUE KEY `uk_weibo_topic` (`weibo`,`catetory`),
    KEY `fk_weibo_topic_weibo` (`weibo`),
    KEY `fk_weibo_topic_catetory` (`catetory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微博话题标定' AUTO_INCREMENT=1;