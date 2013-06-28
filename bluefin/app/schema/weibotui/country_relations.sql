-- TABLE country

ALTER TABLE `country`
ADD CONSTRAINT `fk_country_capital_city` FOREIGN KEY (`capital_city`)
REFERENCES `province` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

