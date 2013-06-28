-- TABLE province

ALTER TABLE `province`
ADD CONSTRAINT `fk_province_country` FOREIGN KEY (`country`)
REFERENCES `country` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `province`
ADD CONSTRAINT `fk_province_capital_city` FOREIGN KEY (`capital_city`)
REFERENCES `city` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

