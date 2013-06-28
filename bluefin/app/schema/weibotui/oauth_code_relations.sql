-- TABLE oauth_code

ALTER TABLE `oauth_code`
ADD CONSTRAINT `fk_oauth_code_client` FOREIGN KEY (`client`)
REFERENCES `oauth_client` (`oauth_client_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `oauth_code`
ADD CONSTRAINT `fk_oauth_code_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

