<?php
// 检查数据库权限设置
require_once 'application/database.php';

try {
    $config = require 'application/database.php';
    $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset=utf8";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    
    echo "数据库连接成功\n";
    
    // 检查scanwork相关权限
    $sql = "SELECT id, name, title, status FROM fa_auth_rule WHERE name LIKE 'scanwork/%' ORDER BY name";
    $stmt = $pdo->query($sql);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== scanwork权限规则 ===\n";
    foreach ($rules as $rule) {
        echo "ID: {$rule['id']}, 名称: {$rule['name']}, 标题: {$rule['title']}, 状态: {$rule['status']}\n";
    }
    
    // 检查processprice相关权限
    $sql = "SELECT id, name, title, status FROM fa_auth_rule WHERE name LIKE 'scanwork/processprice%' ORDER BY name";
    $stmt = $pdo->query($sql);
    $processpriceRules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== processprice权限规则 ===\n";
    if (empty($processpriceRules)) {
        echo "没有找到processprice相关权限规则\n";
    } else {
        foreach ($processpriceRules as $rule) {
            echo "ID: {$rule['id']}, 名称: {$rule['name']}, 标题: {$rule['title']}, 状态: {$rule['status']}\n";
        }
    }
    
    // 检查process_price相关权限（旧路径）
    $sql = "SELECT id, name, title, status FROM fa_auth_rule WHERE name LIKE 'scanwork/process_price%' ORDER BY name";
    $stmt = $pdo->query($sql);
    $oldRules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== process_price权限规则（旧路径） ===\n";
    if (empty($oldRules)) {
        echo "没有找到process_price相关权限规则\n";
    } else {
        foreach ($oldRules as $rule) {
            echo "ID: {$rule['id']}, 名称: {$rule['name']}, 标题: {$rule['title']}, 状态: {$rule['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
}
?> 