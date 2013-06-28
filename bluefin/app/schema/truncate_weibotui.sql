SET @@foreign_key_checks = 0;

-- TABLE oauth_token
TRUNCATE TABLE `oauth_token`;

-- TABLE oauth_client
TRUNCATE TABLE `oauth_client`;

-- TABLE user
TRUNCATE TABLE `user`;

-- TABLE country
TRUNCATE TABLE `country`;

-- TABLE province
TRUNCATE TABLE `province`;

-- TABLE city
TRUNCATE TABLE `city`;

-- TABLE district
TRUNCATE TABLE `district`;

-- TABLE address
TRUNCATE TABLE `address`;

-- TABLE personal_profile
TRUNCATE TABLE `personal_profile`;

-- TABLE oauth_code
TRUNCATE TABLE `oauth_code`;

-- TABLE admin
TRUNCATE TABLE `admin`;

-- TABLE tuike
TRUNCATE TABLE `tuike`;

-- TABLE user_with_role
TRUNCATE TABLE `user_with_role`;

-- TABLE admin_with_role
TRUNCATE TABLE `admin_with_role`;

-- TABLE admin_role
TRUNCATE TABLE `admin_role`;

-- TABLE user_login_record
TRUNCATE TABLE `user_login_record`;

-- TABLE admin_login_record
TRUNCATE TABLE `admin_login_record`;

-- TABLE weibo
TRUNCATE TABLE `weibo`;

-- TABLE weibo_token
TRUNCATE TABLE `weibo_token`;

-- TABLE weibo_topic
TRUNCATE TABLE `weibo_topic`;

-- TABLE topic_category
TRUNCATE TABLE `topic_category`;

-- TABLE weibo_login_record
TRUNCATE TABLE `weibo_login_record`;

-- TABLE corporate
TRUNCATE TABLE `corporate`;

-- TABLE staff_in_corporate
TRUNCATE TABLE `staff_in_corporate`;

-- TABLE user_asset
TRUNCATE TABLE `user_asset`;

-- TABLE user_deposit_record
TRUNCATE TABLE `user_deposit_record`;

-- TABLE income
TRUNCATE TABLE `income`;

-- TABLE invoice
TRUNCATE TABLE `invoice`;

-- TABLE user_income_record
TRUNCATE TABLE `user_income_record`;

-- TABLE service_income_record
TRUNCATE TABLE `service_income_record`;

-- TABLE user_expense_record
TRUNCATE TABLE `user_expense_record`;

-- TABLE payout
TRUNCATE TABLE `payout`;

-- TABLE weibo_order
TRUNCATE TABLE `weibo_order`;

-- TABLE weibo_campaign
TRUNCATE TABLE `weibo_campaign`;

-- TABLE weibo_inventory
TRUNCATE TABLE `weibo_inventory`;

-- TABLE sina_dingshi_weibo
TRUNCATE TABLE `sina_dingshi_weibo`;

-- TABLE qq_dingshi_weibo
TRUNCATE TABLE `qq_dingshi_weibo`;

-- TABLE task_queue
TRUNCATE TABLE `task_queue`;

-- TABLE system_property
TRUNCATE TABLE `system_property`;


SET @@foreign_key_checks = 1;