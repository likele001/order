-- 用户绑定token表
CREATE TABLE IF NOT EXISTS `fa_user_bind_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `token` varchar(64) NOT NULL COMMENT '绑定token',
  `user_id` int(11) NOT NULL COMMENT '员工用户ID',
  `openid` varchar(64) DEFAULT NULL COMMENT '微信openid',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0未使用，1已使用，2已过期',
  `expire_time` int(11) NOT NULL COMMENT '过期时间',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `openid` (`openid`),
  KEY `status` (`status`),
  KEY `expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户绑定token表'; 