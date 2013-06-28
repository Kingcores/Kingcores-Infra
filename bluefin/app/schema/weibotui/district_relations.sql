-- TABLE district

ALTER TABLE `district`
ADD CONSTRAINT `fk_district_city` FOREIGN KEY (`city`)
REFERENCES `city` (`code`) ON UPDATE RESTRICT ON DELETE RESTRICT;

