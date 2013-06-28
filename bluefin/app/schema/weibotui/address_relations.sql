-- TABLE address

ALTER TABLE `address`
ADD CONSTRAINT `fk_address_country` FOREIGN KEY (`country`)
REFERENCES `country` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `address`
ADD CONSTRAINT `fk_address_province` FOREIGN KEY (`province`)
REFERENCES `province` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `address`
ADD CONSTRAINT `fk_address_city` FOREIGN KEY (`city`)
REFERENCES `city` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `address`
ADD CONSTRAINT `fk_address_district` FOREIGN KEY (`district`)
REFERENCES `district` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

