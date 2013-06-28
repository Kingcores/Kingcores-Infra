-- TABLE user_asset

ALTER TABLE `user_asset`
ADD CONSTRAINT `fk_user_asset_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

