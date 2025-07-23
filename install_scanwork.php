<?php
/**
 * 工厂报工管理系统安装脚本
 * 使用方法：将此文件放在FastAdmin根目录，然后通过浏览器访问
 */

// 检查是否在FastAdmin环境中
if (!file_exists('application/admin/controller/Index.php')) {
    die('请在FastAdmin根目录下运行此安装脚本');
}

echo "<h1>工厂报工管理系统安装向导</h1>";

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('PHP版本必须 >= 7.0.0，当前版本：' . PHP_VERSION);
}

echo "<p>✓ PHP版本检查通过：" . PHP_VERSION . "</p>";

// 检查必要的PHP扩展
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("缺少必要的PHP扩展：{$ext}");
    }
}

echo "<p>✓ PHP扩展检查通过</p>";

// 检查数据库连接
try {
    $config = include 'application/database.php';
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    echo "<p>✓ 数据库连接成功</p>";
} catch (Exception $e) {
    die("数据库连接失败：" . $e->getMessage());
}

// 检查是否已安装
$sql = "SHOW TABLES LIKE 'fa_scanwork_%'";
$stmt = $pdo->query($sql);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($tables)) {
    echo "<p>⚠ 检测到已存在工厂报工管理系统的数据表</p>";
    echo "<p>是否要重新安装？这将删除现有数据！</p>";
    echo "<form method='post'>";
    echo "<input type='submit' name='reinstall' value='重新安装' style='background: #f56c6c; color: white; padding: 10px 20px; border: none; cursor: pointer;'>";
    echo "</form>";
    
    if (isset($_POST['reinstall'])) {
        // 删除现有表
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        echo "<p>✓ 已删除现有数据表</p>";
    } else {
        exit;
    }
}

// 读取SQL文件
$sql_file = 'database/migrations/scanwork_tables.sql';
if (!file_exists($sql_file)) {
    die("SQL文件不存在：{$sql_file}");
}

$sql_content = file_get_contents($sql_file);

// 分割SQL语句
$sql_statements = array_filter(array_map('trim', explode(';', $sql_content)));

echo "<h2>开始安装数据表...</h2>";

$success_count = 0;
$error_count = 0;

foreach ($sql_statements as $sql) {
    if (empty($sql) || strpos($sql, '--') === 0) {
        continue;
    }
    
    try {
        $pdo->exec($sql);
        $success_count++;
        echo "<p>✓ 执行SQL成功</p>";
    } catch (Exception $e) {
        $error_count++;
        echo "<p style='color: red;'>✗ SQL执行失败：" . $e->getMessage() . "</p>";
        echo "<p style='color: gray;'>SQL: " . substr($sql, 0, 100) . "...</p>";
    }
}

echo "<h2>安装结果</h2>";
echo "<p>成功执行：{$success_count} 条SQL</p>";
echo "<p>执行失败：{$error_count} 条SQL</p>";

if ($error_count == 0) {
    echo "<h2>✓ 安装完成！</h2>";
    echo "<p>接下来您需要：</p>";
    echo "<ol>";
    echo "<li>在FastAdmin后台添加菜单权限</li>";
    echo "<li>配置路由规则</li>";
    echo "<li>测试各功能模块</li>";
    echo "</ol>";
    
    echo "<h3>菜单配置建议：</h3>";
    echo "<ul>";
    echo "<li>产品管理：/admin/scanwork/product</li>";
    echo "<li>型号管理：/admin/scanwork/productmodel</li>";
    echo "<li>工序管理：/admin/scanwork/process</li>";
    echo "<li>工价管理：/admin/scanwork/processprice</li>";
    echo "<li>订单管理：/admin/scanwork/order</li>";
    echo "<li>分工分配：/admin/scanwork/allocation</li>";
    echo "<li>报工管理：/admin/scanwork/report</li>";
    echo "</ul>";
    
    echo "<p><strong>注意：</strong>安装完成后请删除此安装脚本文件</p>";
} else {
    echo "<h2 style='color: red;'>✗ 安装失败</h2>";
    echo "<p>请检查错误信息并手动执行SQL语句</p>";
}

// 显示系统信息
echo "<h2>系统信息</h2>";
echo "<p>FastAdmin版本：" . (defined('FASTADMIN_VERSION') ? FASTADMIN_VERSION : '未知') . "</p>";
echo "<p>PHP版本：" . PHP_VERSION . "</p>";
echo "<p>MySQL版本：" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
echo "<p>安装时间：" . date('Y-m-d H:i:s') . "</p>";
?> 