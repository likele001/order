-- 添加工资统计管理菜单
-- 执行此SQL文件将添加工资统计管理模块到后台菜单

-- 获取主菜单ID
SET @main_menu_id = (SELECT `id` FROM `fa_auth_rule` WHERE `name` = 'scanwork' LIMIT 1);

-- 添加工资统计管理菜单
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@main_menu_id, 'scanwork/wage', '工资统计管理', 'fa fa-money', '', '员工工资统计管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 85, 'normal');

-- 获取工资统计菜单ID
SET @wage_id = LAST_INSERT_ID();

-- 添加工资统计管理权限
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@wage_id, 'scanwork/wage/index', '工资明细', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@wage_id, 'scanwork/wage/summary', '工资汇总', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@wage_id, 'scanwork/wage/chart', '工资图表', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@wage_id, 'scanwork/wage/export', '导出明细', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@wage_id, 'scanwork/wage/exportSummary', '导出汇总', 'fa fa-circle-o', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 更新管理员角色权限
UPDATE `fa_auth_group` SET `rules` = (
    SELECT GROUP_CONCAT(`id` ORDER BY `id` ASC SEPARATOR ',')
    FROM `fa_auth_rule` 
    WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%'
) WHERE `name` = 'scanwork_admin';

-- 显示添加结果
SELECT '工资统计管理菜单添加完成' as result;
SELECT `id`, `name`, `title`, `ismenu` FROM `fa_auth_rule` WHERE `name` LIKE 'scanwork/wage%' ORDER BY `id`; 