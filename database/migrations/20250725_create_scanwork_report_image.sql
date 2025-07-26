CREATE TABLE IF NOT EXISTS `fa_scanwork_report_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL COMMENT '报工ID',
  `image_url` varchar(255) NOT NULL COMMENT '图片路径',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='计件报工图片表'; 