-- TABLE corporate

ALTER TABLE `corporate`
ADD CONSTRAINT `fk_corporate_address` FOREIGN KEY (`address`)
REFERENCES `address` (`address_id`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `corporate`
ADD CONSTRAINT `fk_corporate_admin` FOREIGN KEY (`admin`)
REFERENCES `user` (`user_id`) ON UPDATE RESTRICT ON DELETE CASCADE;

