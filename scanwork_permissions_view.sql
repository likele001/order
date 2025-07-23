-- 工厂报工管理系统 - 权限查看和管理SQL
-- 用于查看和管理系统权限

-- 1. 查看所有菜单结构
SELECT 
    '=== 菜单结构 ===' as info;
    
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

-- 2. 查看角色权限分配
SELECT 
    '=== 角色权限分配 ===' as info;
    
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

-- 3. 查看用户角色分配
SELECT 
    '=== 用户角色分配 ===' as info;
    
SELECT 
    u.id as user_id,
    u.username,
    u.nickname,
    u.email,
    u.status as user_status,
    g.name as group_name,
    g.title as group_title
FROM `fa_admin` u
LEFT JOIN `fa_auth_group_access` ga ON u.id = ga.uid
LEFT JOIN `fa_auth_group` g ON ga.group_id = g.id
WHERE g.name IN ('scanwork_admin', 'scanwork_worker')
ORDER BY u.id ASC;

-- 4. 查看菜单权限统计
SELECT 
    '=== 权限统计 ===' as info;
    
SELECT 
    COUNT(*) as total_rules,
    SUM(CASE WHEN ismenu = 1 THEN 1 ELSE 0 END) as menu_count,
    SUM(CASE WHEN ismenu = 0 THEN 1 ELSE 0 END) as permission_count,
    SUM(CASE WHEN status = 'normal' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_count
FROM `fa_auth_rule` 
WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%';

-- 5. 查看各模块权限数量
SELECT 
    '=== 各模块权限数量 ===' as info;
    
SELECT 
    CASE 
        WHEN `name` LIKE 'scanwork/product%' THEN '产品管理'
        WHEN `name` LIKE 'scanwork/productmodel%' THEN '型号管理'
        WHEN `name` LIKE 'scanwork/process%' THEN '工序管理'
        WHEN `name` LIKE 'scanwork/processprice%' THEN '工序工价'
        WHEN `name` LIKE 'scanwork/order%' THEN '订单管理'
        WHEN `name` LIKE 'scanwork/allocation%' THEN '分工分配'
        WHEN `name` LIKE 'scanwork/report%' THEN '报工管理'
        WHEN `name` LIKE 'scanwork/progress%' THEN '生产进度'
        WHEN `name` LIKE 'scanwork/qrcode%' THEN '二维码管理'
        WHEN `name` LIKE 'index/worker%' THEN '员工端'
        ELSE '其他'
    END as module_name,
    COUNT(*) as permission_count,
    SUM(CASE WHEN ismenu = 1 THEN 1 ELSE 0 END) as menu_count,
    SUM(CASE WHEN ismenu = 0 THEN 1 ELSE 0 END) as action_count
FROM `fa_auth_rule` 
WHERE `name` LIKE 'scanwork/%' OR `name` LIKE 'index/worker/%'
GROUP BY 
    CASE 
        WHEN `name` LIKE 'scanwork/product%' THEN '产品管理'
        WHEN `name` LIKE 'scanwork/productmodel%' THEN '型号管理'
        WHEN `name` LIKE 'scanwork/process%' THEN '工序管理'
        WHEN `name` LIKE 'scanwork/processprice%' THEN '工序工价'
        WHEN `name` LIKE 'scanwork/order%' THEN '订单管理'
        WHEN `name` LIKE 'scanwork/allocation%' THEN '分工分配'
        WHEN `name` LIKE 'scanwork/report%' THEN '报工管理'
        WHEN `name` LIKE 'scanwork/progress%' THEN '生产进度'
        WHEN `name` LIKE 'scanwork/qrcode%' THEN '二维码管理'
        WHEN `name` LIKE 'index/worker%' THEN '员工端'
        ELSE '其他'
    END
ORDER BY permission_count DESC;

-- 6. 常用管理SQL语句

-- 6.1 为指定用户分配管理员角色
-- UPDATE `fa_auth_group_access` SET group_id = (SELECT id FROM `fa_auth_group` WHERE name = 'scanwork_admin') WHERE uid = 用户ID;

-- 6.2 为指定用户分配员工角色
-- UPDATE `fa_auth_group_access` SET group_id = (SELECT id FROM `fa_auth_group` WHERE name = 'scanwork_worker') WHERE uid = 用户ID;

-- 6.3 添加新用户并分配角色
-- INSERT INTO `fa_admin` (username, nickname, password, salt, email, status) VALUES ('用户名', '昵称', '密码', '盐值', '邮箱', 'normal');
-- INSERT INTO `fa_auth_group_access` (uid, group_id) VALUES (LAST_INSERT_ID(), (SELECT id FROM `fa_auth_group` WHERE name = 'scanwork_admin'));

-- 6.4 禁用某个菜单
-- UPDATE `fa_auth_rule` SET status = 'hidden' WHERE name = 'scanwork/模块名';

-- 6.5 启用某个菜单
-- UPDATE `fa_auth_rule` SET status = 'normal' WHERE name = 'scanwork/模块名';

-- 6.6 查看特定用户的权限
-- SELECT r.name, r.title, r.ismenu FROM `fa_auth_rule` r
-- INNER JOIN `fa_auth_group` g ON FIND_IN_SET(r.id, g.rules)
-- INNER JOIN `fa_auth_group_access` ga ON g.id = ga.group_id
-- WHERE ga.uid = 用户ID AND (r.name LIKE 'scanwork/%' OR r.name LIKE 'index/worker/%')
-- ORDER BY r.weigh DESC, r.id ASC;

SELECT '=== 权限查看完成 ===' as info; 