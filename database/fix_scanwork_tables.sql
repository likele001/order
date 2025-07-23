-- 工厂报工管理系统数据库修复脚本
-- 用于修复表结构问题

-- 1. 检查并修复报工表结构
-- 如果表不存在，创建表
CREATE TABLE IF NOT EXISTS `fa_scanwork_report` (
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
  KEY `idx_report_time` (`report_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='报工记录表';

-- 2. 检查并添加缺失的字段
-- 检查 employee_id 字段是否存在
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'fa_scanwork_report' 
     AND COLUMN_NAME = 'employee_id') > 0,
    'SELECT "employee_id field exists" as status',
    'ALTER TABLE fa_scanwork_report ADD COLUMN employee_id int(11) NOT NULL COMMENT "员工ID" AFTER allocation_id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查 model_id 字段是否存在
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'fa_scanwork_report' 
     AND COLUMN_NAME = 'model_id') > 0,
    'SELECT "model_id field exists" as status',
    'ALTER TABLE fa_scanwork_report ADD COLUMN model_id int(11) NOT NULL COMMENT "型号ID" AFTER employee_id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查 process_id 字段是否存在
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'fa_scanwork_report' 
     AND COLUMN_NAME = 'process_id') > 0,
    'SELECT "process_id field exists" as status',
    'ALTER TABLE fa_scanwork_report ADD COLUMN process_id int(11) NOT NULL COMMENT "工序ID" AFTER model_id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查 price 字段是否存在
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'fa_scanwork_report' 
     AND COLUMN_NAME = 'price') > 0,
    'SELECT "price field exists" as status',
    'ALTER TABLE fa_scanwork_report ADD COLUMN price decimal(10,2) NOT NULL COMMENT "工价（元/件）" AFTER quantity'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查 report_time 字段是否存在
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'fa_scanwork_report' 
     AND COLUMN_NAME = 'report_time') > 0,
    'SELECT "report_time field exists" as status',
    'ALTER TABLE fa_scanwork_report ADD COLUMN report_time int(11) NOT NULL COMMENT "报工时间" AFTER status'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 添加索引
ALTER TABLE `fa_scanwork_report` 
ADD INDEX IF NOT EXISTS `idx_employee_id` (`employee_id`),
ADD INDEX IF NOT EXISTS `idx_model_id` (`model_id`),
ADD INDEX IF NOT EXISTS `idx_process_id` (`process_id`),
ADD INDEX IF NOT EXISTS `idx_report_time` (`report_time`);

-- 4. 检查其他表是否存在
CREATE TABLE IF NOT EXISTS `fa_scanwork_product` (
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

CREATE TABLE IF NOT EXISTS `fa_scanwork_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '型号ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `name` varchar(100) NOT NULL COMMENT '型号名称',
  `description` text COMMENT '型号描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0=禁用，1=正常',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='型号表';

CREATE TABLE IF NOT EXISTS `fa_scanwork_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '工序ID',
  `name` varchar(100) NOT NULL COMMENT '工序名称',
  `description` text COMMENT '工序描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0=禁用，1=正常',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工序表';

CREATE TABLE IF NOT EXISTS `fa_scanwork_allocation` (
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
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分工分配表';

-- 5. 显示修复结果
SELECT 'Database tables fixed successfully!' as message; 