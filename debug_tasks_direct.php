<?php
// 直接连接数据库测试查询
$host = 'localhost';
$dbname = 'order';
$username = 'root';
$password = 'c346b75626e31ad1';

echo "=== 直接数据库查询测试 ===\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "数据库连接成功\n";
    
    // 模拟用户ID为2
    $workerId = 2;
    echo "模拟用户ID: {$workerId}\n";
    
    // 查询分配记录
    $sql = "
        SELECT 
            a.*, 
            o.order_no, 
            p.name as product_name, 
            m.name as model_name, 
            pr.name as process_name
        FROM fa_scanwork_allocation a
        LEFT JOIN fa_scanwork_order o ON a.order_id = o.id
        LEFT JOIN fa_scanwork_model m ON a.model_id = m.id
        LEFT JOIN fa_scanwork_product p ON m.product_id = p.id
        LEFT JOIN fa_scanwork_process pr ON a.process_id = pr.id
        WHERE a.user_id = :worker_id 
        AND a.status = 0
        ORDER BY a.createtime DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['worker_id' => $workerId]);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "查询到的分配记录数: " . count($allocations) . "\n";
    
    if (count($allocations) > 0) {
        echo "\n分配记录详情:\n";
        foreach ($allocations as $allocation) {
            echo "分配ID: {$allocation['id']}\n";
            echo "  订单号: {$allocation['order_no']}\n";
            echo "  产品: {$allocation['product_name']}\n";
            echo "  模型: {$allocation['model_name']}\n";
            echo "  工序: {$allocation['process_name']}\n";
            echo "  分配数量: {$allocation['quantity']}\n";
            echo "  状态: {$allocation['status']}\n";
            
            // 查询已报数量
            $reportSql = "
                SELECT SUM(quantity) as reported_quantity 
                FROM fa_scanwork_report 
                WHERE allocation_id = :allocation_id 
                AND status = 1
            ";
            $reportStmt = $pdo->prepare($reportSql);
            $reportStmt->execute(['allocation_id' => $allocation['id']]);
            $reportResult = $reportStmt->fetch(PDO::FETCH_ASSOC);
            
            $reportedQuantity = intval($reportResult['reported_quantity']);
            $remainingQuantity = max(0, $allocation['quantity'] - $reportedQuantity);
            
            echo "  已报数量: {$reportedQuantity}\n";
            echo "  待报数量: {$remainingQuantity}\n";
            echo "  ---\n";
        }
        
        echo "\nJSON格式数据:\n";
        $result = [
            'code' => 1,
            'msg' => '',
            'data' => $allocations,
            'url' => '',
            'wait' => 3
        ];
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } else {
        echo "没有找到分配记录\n";
        
        // 检查所有分配记录
        echo "\n检查所有分配记录:\n";
        $allSql = "SELECT id, user_id, order_id, model_id, process_id, quantity, status FROM fa_scanwork_allocation";
        $allStmt = $pdo->query($allSql);
        $allAllocations = $allStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allAllocations as $alloc) {
            echo "ID: {$alloc['id']}, 用户ID: {$alloc['user_id']}, 订单ID: {$alloc['order_id']}, 状态: {$alloc['status']}\n";
        }
        
        // 检查用户表
        echo "\n检查用户表:\n";
        $userSql = "SELECT id, username, status FROM fa_user";
        $userStmt = $pdo->query($userSql);
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            echo "ID: {$user['id']}, 用户名: {$user['username']}, 状态: {$user['status']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "其他错误: " . $e->getMessage() . "\n";
}

echo "\n=== 测试完成 ===\n"; 