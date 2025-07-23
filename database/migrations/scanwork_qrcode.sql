-- 二维码表
CREATE TABLE `fa_scanwork_qrcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `allocation_id` int(11) NOT NULL COMMENT '分配任务ID',
  `qr_content` varchar(255) NOT NULL COMMENT '二维码内容',
  `qr_image` varchar(500) DEFAULT NULL COMMENT '二维码图片路径',
  `order_no` varchar(100) NOT NULL COMMENT '订单号',
  `product_name` varchar(200) NOT NULL COMMENT '产品名称',
  `model_name` varchar(200) NOT NULL COMMENT '型号名称',
  `process_name` varchar(200) NOT NULL COMMENT '工序名称',
  `employee_id` int(11) NOT NULL COMMENT '员工ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0=禁用,1=正常',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_allocation_id` (`allocation_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='二维码表'; 