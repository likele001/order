<?php
/**
 * 独立的微信绑定API
 * 用于绕过ThinkPHP框架可能的兼容性问题
 */

// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 0, 'msg' => '只允许POST请求']);
    exit;
}

try {
    // 引入ThinkPHP的基础文件来使用缓存功能
    require_once __DIR__ . '/thinkphp/start.php';
    
    // 简单的会话检查（这里需要根据实际情况调整）
    session_start();
    
    // 检查用户是否登录（简化版本）
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo json_encode(['code' => 0, 'msg' => '请先登录']);
        exit;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    // 生成绑定码
    $bindCode = md5($userId . time() . rand(1000, 9999));
    
    // 存储到缓存（使用文件缓存作为备选）
    $cacheFile = __DIR__ . '/runtime/cache/wechat_bind_' . $bindCode . '.cache';
    $cacheDir = dirname($cacheFile);
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    file_put_contents($cacheFile, json_encode([
        'user_id' => $userId,
        'expire_time' => time() + 300 // 5分钟过期
    ]));
    
    // 生成二维码URL
    $baseUrl = 'https://order.023ent.net';
    $qrUrl = $baseUrl . '/index/user/wechatBindCallback?code=' . $bindCode;
    
    // 返回成功响应
    echo json_encode([
        'code' => 1,
        'msg' => '生成成功',
        'data' => [
            'bind_code' => $bindCode,
            'qr_url' => $qrUrl,
            'scene' => 'bind=' . $bindCode
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'code' => 0,
        'msg' => '系统错误: ' . $e->getMessage()
    ]);
}
