-- TABLE user_with_role

ALTER TABLE `user_with_role`
ADD CONSTRAINT `fk_user_with_role_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

