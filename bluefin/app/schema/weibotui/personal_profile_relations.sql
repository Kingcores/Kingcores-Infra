-- TABLE personal_profile

ALTER TABLE `personal_profile`
ADD CONSTRAINT `fk_personal_profile_user` FOREIGN KEY (`user`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

ALTER TABLE `personal_profile`
ADD CONSTRAINT `fk_personal_profile_address` FOREIGN KEY (`address`)
REFERENCES `address` (`address_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

