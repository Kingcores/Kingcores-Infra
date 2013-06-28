-- TABLE user

ALTER TABLE `user`
ADD CONSTRAINT `fk_user_profile` FOREIGN KEY (`profile`)
REFERENCES `personal_profile` (`personal_profile_id`) ON UPDATE RESTRICT ON DELETE SET NULL;

