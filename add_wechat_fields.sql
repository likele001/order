-- 为用户表添加微信小程序相关字段
ALTER TABLE `fa_user` 
ADD COLUMN `wechat_openid` varchar(100) DEFAULT NULL COMMENT '微信OpenID',
ADD COLUMN `wechat_session_key` varchar(100) DEFAULT NULL COMMENT '微信SessionKey',
ADD COLUMN `employee_no` varchar(50) DEFAULT NULL COMMENT '员工号';

-- 为微信OpenID添加唯一索引
ALTER TABLE `fa_user` ADD UNIQUE INDEX `idx_wechat_openid` (`wechat_openid`);

-- 为员工号添加唯一索引
ALTER TABLE `fa_user` ADD UNIQUE INDEX `idx_employee_no` (`employee_no`);

-- 添加注释
ALTER TABLE `fa_user` COMMENT = '用户表(已扩展微信小程序登录功能)';
