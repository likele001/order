-- 工厂报工管理系统 - 后台权限菜单SQL
-- 执行此SQL文件将创建完整的菜单和权限结构

-- 清空相关菜单（如果存在）
DELETE FROM `fa_auth_rule` WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%';

-- 1. 创建主菜单：工厂报工管理
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(0, 'scanwork', '工厂报工管理', 'fa fa-industry', '', '工厂生产报工管理系统', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 'normal');

-- 获取主菜单ID
SET @main_menu_id = LAST_INSERT_ID();

-- 2. 第一阶段：基础数据模块
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@main_menu_id, 'scanwork/product', '产品管理', 'fa fa-cube', '', '产品信息管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
(@main_menu_id, 'scanwork/productmodel', '型号管理', 'fa fa-tags', '', '产品型号管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 20, 'normal'),
(@main_menu_id, 'scanwork/process', '工序管理', 'fa fa-cogs', '', '生产工序管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 30, 'normal'),
(@main_menu_id, 'scanwork/processprice', '工序工价', 'fa fa-money', '', '工序工价设置', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 40, 'normal');

-- 3. 第二阶段：订单与分工模块
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@main_menu_id, 'scanwork/order', '订单管理', 'fa fa-file-text', '', '生产订单管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 50, 'normal'),
(@main_menu_id, 'scanwork/allocation', '分工分配', 'fa fa-tasks', '', '生产任务分配', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 60, 'normal');

-- 4. 第三阶段：报工与进度模块
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@main_menu_id, 'scanwork/report', '报工管理', 'fa fa-check-square-o', '', '员工报工记录管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 70, 'normal'),
(@main_menu_id, 'scanwork/progress', '生产进度', 'fa fa-bar-chart', '', '生产进度可视化', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal');

-- 5. 第四阶段：二维码扫码功能
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@main_menu_id, 'scanwork/qrcode', '二维码管理', 'fa fa-qrcode', '', '任务二维码生成管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal');

-- 6. 员工端权限（前台）
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@main_menu_id, 'index/worker', '员工端管理', 'fa fa-users', '', '员工端报工管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 95, 'normal');

-- 获取各子菜单ID
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

-- 7. 产品管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@product_id, 'scanwork/product/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@product_id, 'scanwork/product/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 8. 型号管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@model_id, 'scanwork/productmodel/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@model_id, 'scanwork/productmodel/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 9. 工序管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@process_id, 'scanwork/process/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@process_id, 'scanwork/process/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 10. 工序工价权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@processprice_id, 'scanwork/processprice/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/batch', '批量设置', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@processprice_id, 'scanwork/processprice/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 11. 订单管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@order_id, 'scanwork/order/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/detail', '详情', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@order_id, 'scanwork/order/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 12. 分工分配权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@allocation_id, 'scanwork/allocation/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/add', '添加', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/edit', '编辑', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/del', '删除', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/batch', '批量分配', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@allocation_id, 'scanwork/allocation/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 13. 报工管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
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
(@report_id, 'scanwork/report/multi', '批量更新', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 14. 生产进度权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@progress_id, 'scanwork/progress/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/stats', '统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/orderProgress', '订单进度', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/workerProgress', '员工进度', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/processProgress', '工序进度', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@progress_id, 'scanwork/progress/dailyReport', '日报工趋势', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 15. 二维码管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@qrcode_id, 'scanwork/qrcode/index', '查看', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/generate', '生成二维码', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/batchGenerate', '批量生成', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/download', '下载二维码', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/print', '打印标签', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@qrcode_id, 'scanwork/qrcode/stats', '统计', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 16. 员工端权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
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

-- 17. 创建管理员角色（如果不存在）
INSERT INTO `fa_auth_group` (`pid`, `name`, `title`, `status`, `rules`) VALUES
(0, 'scanwork_admin', '工厂报工管理员', 'normal', '') ON DUPLICATE KEY UPDATE `title` = '工厂报工管理员';

-- 获取管理员角色ID
SET @admin_group_id = (SELECT `id` FROM `fa_auth_group` WHERE `name` = 'scanwork_admin' LIMIT 1);

-- 18. 为管理员角色分配所有权限
UPDATE `fa_auth_group` SET `rules` = (
    SELECT GROUP_CONCAT(`id` ORDER BY `id` ASC SEPARATOR ',')
    FROM `fa_auth_rule` 
    WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%'
) WHERE `id` = @admin_group_id;

-- 19. 创建员工角色（如果不存在）
INSERT INTO `fa_auth_group` (`pid`, `name`, `title`, `status`, `rules`) VALUES
(0, 'scanwork_worker', '工厂员工', 'normal', '') ON DUPLICATE KEY UPDATE `title` = '工厂员工';

-- 获取员工角色ID
SET @worker_group_id = (SELECT `id` FROM `fa_auth_group` WHERE `name` = 'scanwork_worker' LIMIT 1);

-- 20. 为员工角色分配前台权限
UPDATE `fa_auth_group` SET `rules` = (
    SELECT GROUP_CONCAT(`id` ORDER BY `id` ASC SEPARATOR ',')
    FROM `fa_auth_rule` 
    WHERE `name` LIKE 'index/worker/%'
) WHERE `id` = @worker_group_id;

-- 21. 创建查询视图，方便查看权限结构
CREATE OR REPLACE VIEW `v_scanwork_menu` AS
SELECT 
    r1.id,
    r1.pid,
    r1.name,
    r1.title,
    r1.icon,
    r1.ismenu,
    r1.weigh,
    r1.status,
    CASE 
        WHEN r1.ismenu = 1 THEN '菜单'
        ELSE '权限'
    END as type,
    CASE 
        WHEN r1.pid = 0 THEN r1.title
        WHEN r2.pid = 0 THEN CONCAT(r2.title, ' > ', r1.title)
        ELSE CONCAT(r3.title, ' > ', r2.title, ' > ', r1.title)
    END as full_path
FROM `fa_auth_rule` r1
LEFT JOIN `fa_auth_rule` r2 ON r1.pid = r2.id
LEFT JOIN `fa_auth_rule` r3 ON r2.pid = r3.id
WHERE r1.name LIKE 'scanwork/%' OR r1.name LIKE 'index/worker/%'
ORDER BY r1.weigh DESC, r1.id ASC;

-- 22. 创建角色权限视图
CREATE OR REPLACE VIEW `v_scanwork_group_rules` AS
SELECT 
    g.id as group_id,
    g.name as group_name,
    g.title as group_title,
    g.status as group_status,
    COUNT(r.id) as rule_count,
    GROUP_CONCAT(r.name ORDER BY r.id ASC SEPARATOR ', ') as rule_names
FROM `fa_auth_group` g
LEFT JOIN `fa_auth_rule` r ON FIND_IN_SET(r.id, g.rules)
WHERE g.name IN ('scanwork_admin', 'scanwork_worker')
GROUP BY g.id, g.name, g.title, g.status;

-- 输出执行结果
SELECT '工厂报工管理系统权限菜单创建完成！' as message;

-- 显示菜单结构
SELECT '=== 菜单结构 ===' as info;
SELECT * FROM `v_scanwork_menu` WHERE `type` = '菜单' ORDER BY `weigh` DESC, `id` ASC;

-- 显示角色权限
SELECT '=== 角色权限 ===' as info;
SELECT * FROM `v_scanwork_group_rules`;

-- 显示使用说明
SELECT '=== 使用说明 ===' as info;
SELECT 
    '1. 执行此SQL后，在FastAdmin后台的"权限管理"中可以看到新增的菜单' as step1,
    '2. 可以为不同用户分配"工厂报工管理员"或"工厂员工"角色' as step2,
    '3. 管理员角色拥有所有后台管理权限' as step3,
    '4. 员工角色只有前台报工权限' as step4,
    '5. 可以通过视图 v_scanwork_menu 查看完整的菜单结构' as step5,
    '6. 可以通过视图 v_scanwork_group_rules 查看角色权限分配' as step6; 