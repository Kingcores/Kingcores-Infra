-- TABLE oauth_token

ALTER TABLE `oauth_token`
ADD CONSTRAINT `fk_oauth_token_client` FOREIGN KEY (`client`)
REFERENCES `oauth_client` (`oauth_client_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `oauth_token`
ADD CONSTRAINT `fk_oauth_token_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

