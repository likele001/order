-- 工厂报工管理系统 - 快速权限安装脚本
-- 执行此脚本将快速创建所有必要的权限和菜单

-- 开始事务
START TRANSACTION;

-- 1. 删除已存在的权限（如果存在）
DELETE FROM `fa_auth_rule` WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%';

-- 2. 创建主菜单
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(0, 'scanwork', '工厂报工管理', 'fa fa-industry', '', '工厂生产报工管理系统', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 'normal');

SET @main_id = LAST_INSERT_ID();

-- 3. 创建子菜单
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
-- 基础数据模块
(@main_id, 'scanwork/product', '产品管理', 'fa fa-cube', '', '产品信息管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
(@main_id, 'scanwork/productmodel', '型号管理', 'fa fa-tags', '', '产品型号管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 20, 'normal'),
(@main_id, 'scanwork/process', '工序管理', 'fa fa-cogs', '', '生产工序管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 30, 'normal'),
(@main_id, 'scanwork/processprice', '工序工价', 'fa fa-money', '', '工序工价设置', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 40, 'normal'),

-- 订单与分工模块
(@main_id, 'scanwork/order', '订单管理', 'fa fa-file-text', '', '生产订单管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 50, 'normal'),
(@main_id, 'scanwork/allocation', '分工分配', 'fa fa-tasks', '', '生产任务分配', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 60, 'normal'),

-- 报工与进度模块
(@main_id, 'scanwork/report', '报工管理', 'fa fa-check-square-o', '', '员工报工记录管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 70, 'normal'),
(@main_id, 'scanwork/progress', '生产进度', 'fa fa-bar-chart', '', '生产进度可视化', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal'),

-- 二维码功能
(@main_id, 'scanwork/qrcode', '二维码管理', 'fa fa-qrcode', '', '任务二维码生成管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),

-- 员工端
(@main_id, 'index/worker', '员工端管理', 'fa fa-users', '', '员工端报工管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 95, 'normal');

-- 4. 获取子菜单ID
SET @product_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/product' LIMIT 1);
SET @model_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/productmodel' LIMIT 1);
SET @process_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/process' LIMIT 1);
SET @processprice_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/processprice' LIMIT 1);
SET @order_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/order' LIMIT 1);
SET @allocation_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/allocation' LIMIT 1);
SET @report_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/report' LIMIT 1);
SET @progress_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/progress' LIMIT 1);
SET @qrcode_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork/qrcode' LIMIT 1);
SET @worker_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'index/worker' LIMIT 1);

-- 5. 创建权限节点
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
-- 产品管理权限
(@product_id, 'scanwork/product/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 型号管理权限
(@model_id, 'scanwork/productmodel/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 工序管理权限
(@process_id, 'scanwork/process/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 工序工价权限
(@processprice_id, 'scanwork/processprice/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/batch', '批量设置', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 订单管理权限
(@order_id, 'scanwork/order/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/detail', '详情', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 分工分配权限
(@allocation_id, 'scanwork/allocation/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/batch', '批量分配', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 报工管理权限
(@report_id, 'scanwork/report/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/confirm', '确认报工', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/detail', '详情', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/stats', '统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/wage', '工资统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/wageStats', '工资图表', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/dailyReport', '日报工趋势', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/export', '导出', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@report_id, 'scanwork/report/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 生产进度权限
(@progress_id, 'scanwork/progress/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/stats', '统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/orderProgress', '订单进度', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/workerProgress', '员工进度', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/processProgress', '工序进度', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/dailyReport', '日报工趋势', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 二维码管理权限
(@qrcode_id, 'scanwork/qrcode/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/generate', '生成二维码', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/batchGenerate', '批量生成', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/download', '下载二维码', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/print', '打印标签', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/stats', '统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),

-- 员工端权限
(@worker_id, 'index/worker/index', '员工首页', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/tasks', '我的任务', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/report', '报工', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/submit', '提交报工', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/scan', '扫码报工', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/records', '报工记录', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/wage', '工资统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/stats', '统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/wageChart', '工资图表', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/dailyReport', '日报工趋势', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/taskDetail', '任务详情', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@worker_id, 'index/worker/reportRecords', '报工记录', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 6. 创建角色
INSERT INTO `fa_auth_group` (`pid`, `name`, `title`, `status`, `rules`) VALUES
(0, 'scanwork_admin', '工厂报工管理员', 'normal', ''),
(0, 'scanwork_worker', '工厂员工', 'normal', '')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 7. 获取角色ID
SET @admin_group_id = (SELECT `id` FROM `fa_auth_group` WHERE `name` = 'scanwork_admin' LIMIT 1);
SET @worker_group_id = (SELECT `id` FROM `fa_auth_group` WHERE `name` = 'scanwork_worker' LIMIT 1);

-- 8. 分配权限
UPDATE `fa_auth_group` SET `rules` = (
    SELECT GROUP_CONCAT(`id` ORDER BY `id` ASC SEPARATOR ',')
    FROM `fa_auth_rule` 
    WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%'
) WHERE `id` = @admin_group_id;

UPDATE `fa_auth_group` SET `rules` = (
    SELECT GROUP_CONCAT(`id` ORDER BY `id` ASC SEPARATOR ',')
    FROM `fa_auth_rule` 
    WHERE `name` LIKE 'index/worker/%'
) WHERE `id` = @worker_group_id;

-- 提交事务
COMMIT;

-- 输出结果
SELECT '工厂报工管理系统权限安装完成！' as message;
SELECT '管理员角色：scanwork_admin' as admin_role;
SELECT '员工角色：scanwork_worker' as worker_role;
SELECT '请在FastAdmin后台为用户分配相应角色' as next_step; 