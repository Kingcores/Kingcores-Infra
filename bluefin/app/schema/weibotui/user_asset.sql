-- TABLE user_asset

CREATE TABLE IF NOT EXISTS `user_asset` (
    `_created_at` TIMESTAMP NOT NULL DEFAULT 0 COMMENT 'CreatedAt',
    `_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'UpdatedAt',
    `deposit_balance` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '储值余额',
    `income_balance` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '收入余额',
    `points` INT(10) NOT NULL DEFAULT 0 COMMENT '积分',
    `user` INT(10) NOT NULL COMMENT '用户',
    PRIMARY KEY (`user`),
    KEY `fk_user_asset_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户资产';