-- TABLE service_income_record

CREATE TABLE IF NOT EXISTS `service_income_record` (
    `service_income_record_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `service_charge` DECIMAL(15,2) NOT NULL COMMENT '服务费金额',
    `income_balance` DECIMAL(15,2) NOT NULL COMMENT '收入余额',
    `consumer_expense` CHAR(20) NOT NULL COMMENT '用户支出记录',
    `supplier_income` CHAR(20) NOT NULL COMMENT '用户收入记录',
    PRIMARY KEY (`service_income_record_id`),
    KEY `fk_service_income_record_consumer_expense` (`consumer_expense`),
    KEY `fk_service_income_record_supplier_income` (`supplier_income`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='平台收入' AUTO_INCREMENT=1;