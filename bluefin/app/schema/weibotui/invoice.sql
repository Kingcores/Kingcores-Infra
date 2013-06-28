-- TABLE invoice

CREATE TABLE IF NOT EXISTS `invoice` (
    `invoice_id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `invoice_no` INT(10) ZEROFILL NOT NULL COMMENT '发票号码',
    `title` CHAR(100) NOT NULL COMMENT '发票抬头',
    `issued_date` DATE NOT NULL COMMENT '开票日期',
    `total_amount` DECIMAL(15,2) NOT NULL COMMENT '总金额',
    PRIMARY KEY (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='发票' AUTO_INCREMENT=1;