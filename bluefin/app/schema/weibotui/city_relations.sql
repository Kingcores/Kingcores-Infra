-- TABLE city

ALTER TABLE `city`
ADD CONSTRAINT `fk_city_province` FOREIGN KEY (`province`)
REFERENCES `province` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

