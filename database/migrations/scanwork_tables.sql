-- 工厂报工管理系统数据库表结构
-- 表前缀：fa_scanwork_

-- 1. 产品表
CREATE TABLE `fa_scanwork_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '产品ID',
  `name` varchar(100) NOT NULL COMMENT '产品名称',
  `specification` varchar(200) DEFAULT NULL COMMENT '产品规格',
  `description` text COMMENT '产品描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0=禁用，1=正常',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品表';

-- 2. 型号表
CREATE TABLE `fa_scanwork_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '型号ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `name` varchar(100) NOT NULL COMMENT '型号名称',
  `description` text COMMENT '型号描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0=禁用，1=正常',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_model_product` FOREIGN KEY (`product_id`) REFERENCES `fa_scanwork_product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='型号表';

-- 3. 工序表
CREATE TABLE `fa_scanwork_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '工序ID',
  `name` varchar(100) NOT NULL COMMENT '工序名称',
  `description` text COMMENT '工序描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0=禁用，1=正常',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工序表';

-- 4. 工价表
CREATE TABLE `fa_scanwork_process_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '工价ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `process_id` int(11) NOT NULL COMMENT '工序ID',
  `price` decimal(10,2) NOT NULL COMMENT '工价（元/件）',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_process` (`model_id`,`process_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_process_id` (`process_id`),
  CONSTRAINT `fk_price_model` FOREIGN KEY (`model_id`) REFERENCES `fa_scanwork_model` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_price_process` FOREIGN KEY (`process_id`) REFERENCES `fa_scanwork_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工价表';

-- 5. 订单表
CREATE TABLE `fa_scanwork_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `customer_name` varchar(100) NOT NULL COMMENT '客户名称',
  `customer_contact` varchar(100) DEFAULT NULL COMMENT '客户联系方式',
  `total_quantity` int(11) DEFAULT '0' COMMENT '总数量',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0=待生产，1=生产中，2=已完成',
  `remark` text COMMENT '备注',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_status` (`status`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- 6. 订单型号明细表
CREATE TABLE `fa_scanwork_order_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '明细ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `quantity` int(11) NOT NULL COMMENT '数量',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_model_id` (`model_id`),
  CONSTRAINT `fk_order_model_order` FOREIGN KEY (`order_id`) REFERENCES `fa_scanwork_order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_model_model` FOREIGN KEY (`model_id`) REFERENCES `fa_scanwork_model` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单型号明细表';

-- 7. 分工分配表
CREATE TABLE `fa_scanwork_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分配ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `process_id` int(11) NOT NULL COMMENT '工序ID',
  `employee_id` int(11) NOT NULL COMMENT '员工ID',
  `allocated_quantity` int(11) NOT NULL COMMENT '分配数量',
  `reported_quantity` int(11) DEFAULT '0' COMMENT '已报数量',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0=未开始，1=进行中，2=已完成',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_process_id` (`process_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_allocation_order` FOREIGN KEY (`order_id`) REFERENCES `fa_scanwork_order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_allocation_model` FOREIGN KEY (`model_id`) REFERENCES `fa_scanwork_model` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_allocation_process` FOREIGN KEY (`process_id`) REFERENCES `fa_scanwork_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分工分配表';

-- 8. 报工记录表
CREATE TABLE `fa_scanwork_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '报工ID',
  `allocation_id` int(11) NOT NULL COMMENT '分配ID',
  `employee_id` int(11) NOT NULL COMMENT '员工ID',
  `model_id` int(11) NOT NULL COMMENT '型号ID',
  `process_id` int(11) NOT NULL COMMENT '工序ID',
  `quantity` int(11) NOT NULL COMMENT '报工数量',
  `price` decimal(10,2) NOT NULL COMMENT '工价（元/件）',
  `wage` decimal(10,2) NOT NULL COMMENT '工资（元）',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0=待确认，1=已确认',
  `report_time` int(11) NOT NULL COMMENT '报工时间',
  `confirm_time` int(11) DEFAULT NULL COMMENT '确认时间',
  `confirm_user_id` int(11) DEFAULT NULL COMMENT '确认人ID',
  `remark` text COMMENT '备注',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_allocation_id` (`allocation_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_process_id` (`process_id`),
  KEY `idx_status` (`status`),
  KEY `idx_report_time` (`report_time`),
  CONSTRAINT `fk_report_allocation` FOREIGN KEY (`allocation_id`) REFERENCES `fa_scanwork_allocation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='报工记录表';

-- 插入示例数据
INSERT INTO `fa_scanwork_product` (`name`, `specification`, `description`, `status`, `createtime`) VALUES
('智能手机', '6.1英寸', '高端智能手机产品', 1, UNIX_TIMESTAMP()),
('平板电脑', '10.1英寸', '商务平板电脑', 1, UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_model` (`product_id`, `name`, `description`, `status`, `createtime`) VALUES
(1, 'X1', '旗舰版智能手机', 1, UNIX_TIMESTAMP()),
(1, 'X2', '标准版智能手机', 1, UNIX_TIMESTAMP()),
(2, 'P1', '商务平板电脑', 1, UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_process` (`name`, `description`, `status`, `createtime`) VALUES
('组装', '产品组装工序', 1, UNIX_TIMESTAMP()),
('质检', '质量检测工序', 1, UNIX_TIMESTAMP()),
('包装', '产品包装工序', 1, UNIX_TIMESTAMP());

INSERT INTO `fa_scanwork_process_price` (`model_id`, `process_id`, `price`, `createtime`) VALUES
(1, 1, 8.00, UNIX_TIMESTAMP()),
(1, 2, 5.00, UNIX_TIMESTAMP()),
(1, 3, 3.00, UNIX_TIMESTAMP()),
(2, 1, 6.00, UNIX_TIMESTAMP()),
(2, 2, 4.00, UNIX_TIMESTAMP()),
(2, 3, 2.50, UNIX_TIMESTAMP()),
(3, 1, 10.00, UNIX_TIMESTAMP()),
(3, 2, 6.00, UNIX_TIMESTAMP()),
(3, 3, 4.00, UNIX_TIMESTAMP()); 