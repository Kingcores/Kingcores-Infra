-- TABLE user_login_record

ALTER TABLE `user_login_record`
ADD CONSTRAINT `fk_user_login_record_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

