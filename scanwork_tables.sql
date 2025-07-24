-- 工厂报工管理系统数据库表结构
-- 表前缀：fa_scanwork_

-- 产品表
CREATE TABLE `fa_scanwork_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL COMMENT '产品名称',
  `specification` text COMMENT '产品规格',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1=正常,0=禁用',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品表';

-- 型号表
CREATE TABLE `fa_scanwork_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `name` varchar(100) NOT NULL COMMENT '型号名称',
  `description` text COMMENT '型号描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1=正常,0=禁用',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='型号表';

-- 工序表
CREATE TABLE `fa_scanwork_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL COMMENT '工序名称',
  `description` text COMMENT '工序描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1=正常,0=禁用',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工序表';

-- 工序工价表
CREATE TABLE `fa_scanwork_process_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `process_id` int(11) NOT NULL COMMENT '工序ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '工价(元/件)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1=正常,0=禁用',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_process` (`model_id`,`process_id`),
  KEY `model_id` (`model_id`),
  KEY `process_id` (`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工序工价表';

-- 订单表
CREATE TABLE `fa_scanwork_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `customer_name` varchar(100) NOT NULL COMMENT '客户名称',
  `customer_phone` varchar(20) DEFAULT NULL COMMENT '客户电话',
  `total_quantity` int(11) NOT NULL DEFAULT '0' COMMENT '总数量',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=待生产,1=生产中,2=已完成',
  `remark` text COMMENT '备注',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- 订单型号表
CREATE TABLE `fa_scanwork_order_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `quantity` int(11) NOT NULL COMMENT '数量',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `model_id` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单型号表';

-- 分工分配表
CREATE TABLE `fa_scanwork_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `process_id` int(11) NOT NULL COMMENT '工序ID',
  `user_id` int(11) NOT NULL COMMENT '员工ID',
  `quantity` int(11) NOT NULL COMMENT '分配数量',
  `reported_quantity` int(11) NOT NULL DEFAULT '0' COMMENT '已报数量',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=进行中,1=已完成',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `model_id` (`model_id`),
  KEY `process_id` (`process_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分工分配表';

-- 报工记录表
CREATE TABLE `fa_scanwork_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `allocation_id` int(11) NOT NULL COMMENT '分工ID',
  `user_id` int(11) NOT NULL COMMENT '员工ID',
  `quantity` int(11) NOT NULL COMMENT '报工数量',
  `wage` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '工资(元)',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=待确认,1=已确认',
  `remark` text COMMENT '备注',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `allocation_id` (`allocation_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='报工记录表';

-- 二维码表
CREATE TABLE `fa_scanwork_qrcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `allocation_id` int(11) NOT NULL COMMENT '分工分配ID',
  `qr_content` text NOT NULL COMMENT '二维码内容',
  `qr_image` varchar(255) NOT NULL COMMENT '二维码图片路径',
  `scan_count` int(11) NOT NULL DEFAULT '0' COMMENT '扫码次数',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=未使用,1=已使用',
  `createtime` int(10) DEFAULT NULL COMMENT '生成时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `allocation_id` (`allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='二维码表';


-- 插入示例数据
INSERT INTO `fa_scanwork_product` (`name`, `specification`, `status`, `createtime`) VALUES
('智能手机', '5.5寸屏幕，4GB内存，64GB存储', 1, UNIX_TIMESTAMP()),
('平板电脑', '10.1寸屏幕，8GB内存，128GB存储', 1, UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_model` (`product_id`, `name`, `description`, `status`, `createtime`) VALUES
(1, 'X1', '高端旗舰机型', 1, UNIX_TIMESTAMP()),
(1, 'X2', '中端性价比机型', 1, UNIX_TIMESTAMP()),
(2, 'P1', '专业版平板', 1, UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_process` (`name`, `description`, `status`, `createtime`) VALUES
('组装', '产品组装工序', 1, UNIX_TIMESTAMP()),
('质检', '质量检测工序', 1, UNIX_TIMESTAMP()),
('包装', '产品包装工序', 1, UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_process_price` (`model_id`, `process_id`, `price`, `status`, `createtime`) VALUES
(1, 1, 8.00, 1, UNIX_TIMESTAMP()),
(1, 2, 5.00, 1, UNIX_TIMESTAMP()),
(1, 3, 3.00, 1, UNIX_TIMESTAMP()),
(2, 1, 6.00, 1, UNIX_TIMESTAMP()),
(2, 2, 4.00, 1, UNIX_TIMESTAMP()),
(2, 3, 2.50, 1, UNIX_TIMESTAMP()),
(3, 1, 10.00, 1, UNIX_TIMESTAMP()),
(3, 2, 6.00, 1, UNIX_TIMESTAMP()),
(3, 3, 4.00, 1, UNIX_TIMESTAMP());

-- 插入示例订单数据
INSERT INTO `fa_scanwork_order` (`order_no`, `customer_name`, `customer_phone`, `total_quantity`, `status`, `remark`, `createtime`) VALUES
('ORD20241201001', '张三', '13800138001', 100, 0, '测试订单1', UNIX_TIMESTAMP()),
('ORD20241201002', '李四', '13800138002', 150, 0, '测试订单2', UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_order_model` (`order_id`, `model_id`, `quantity`, `createtime`) VALUES
(1, 1, 50, UNIX_TIMESTAMP()),
(1, 2, 50, UNIX_TIMESTAMP()),
(2, 1, 80, UNIX_TIMESTAMP()),
(2, 3, 70, UNIX_TIMESTAMP());

-- 插入示例分工分配数据
INSERT INTO `fa_scanwork_allocation` (`order_id`, `model_id`, `process_id`, `user_id`, `quantity`, `reported_quantity`, `status`, `createtime`) VALUES
(1, 1, 1, 1, 30, 20, 0, UNIX_TIMESTAMP()),
(1, 1, 2, 1, 20, 15, 0, UNIX_TIMESTAMP()),
(1, 2, 1, 2, 25, 25, 1, UNIX_TIMESTAMP()),
(1, 2, 3, 2, 25, 20, 0, UNIX_TIMESTAMP()),
(2, 1, 1, 1, 40, 35, 0, UNIX_TIMESTAMP()),
(2, 3, 2, 2, 35, 30, 0, UNIX_TIMESTAMP());

-- 插入示例报工数据
INSERT INTO `fa_scanwork_report` (`allocation_id`, `user_id`, `quantity`, `wage`, `status`, `createtime`) VALUES
(1, 1, 20, 160.00, 1, UNIX_TIMESTAMP()),
(2, 1, 15, 75.00, 1, UNIX_TIMESTAMP()),
(3, 2, 25, 150.00, 1, UNIX_TIMESTAMP()),
(4, 2, 20, 80.00, 1, UNIX_TIMESTAMP()),
(5, 1, 35, 280.00, 1, UNIX_TIMESTAMP()),
(6, 2, 30, 180.00, 1, UNIX_TIMESTAMP()); 


CREATE TABLE `fa_scanwork_tallocationtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `process_id` int(11) NOT NULL COMMENT '工序ID',
  `user_id` int(11) NOT NULL COMMENT '员工ID',
  `work_date` date NOT NULL COMMENT '工作日期',
  `start_time` time DEFAULT NULL COMMENT '开始时间',
  `end_time` time DEFAULT NULL COMMENT '结束时间',
  `total_hours` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '工时',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=进行中,1=已完成',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `model_id` (`model_id`),
  KEY `process_id` (`process_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='计时分工表';

CREATE TABLE `fa_scanwork_treporttime` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tallocationtime_id` int(11) NOT NULL COMMENT '计时分工ID',
  `user_id` int(11) NOT NULL COMMENT '员工ID',
  `work_date` date NOT NULL COMMENT '工作日期',
  `start_time` time DEFAULT NULL COMMENT '开始时间',
  `end_time` time DEFAULT NULL COMMENT '结束时间',
  `total_hours` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '工时',
  `wage` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '工资(元)',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=待确认,1=已确认',
  `remark` text COMMENT '备注',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `tallocationtime_id` (`tallocationtime_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='计时报工表';

CREATE TABLE `fa_scanwork_twage` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL COMMENT '员工ID',
  `work_date` date NOT NULL COMMENT '工作日期',
  `total_hours` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '工时',
  `wage` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '工资(元)',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='计时工资统计表'; 

