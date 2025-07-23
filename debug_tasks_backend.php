<?php
// 直接测试Worker控制器的tasks方法
require_once 'thinkphp/start.php';

use think\Db;

echo "=== 直接测试Worker控制器tasks方法 ===\n";

try {
    // 模拟用户ID为2（lsj5492）
    $workerId = 2;
    echo "模拟用户ID: {$workerId}\n";
    
    // 直接执行Worker控制器中的查询逻辑
    echo "\n1. 查询分配记录...\n";
    $allocations = Db::name('scanwork_allocation')
        ->alias('a')
        ->join('scanwork_order o', 'a.order_id = o.id')
        ->join('scanwork_model m', 'a.model_id = m.id')
        ->join('scanwork_product p', 'm.product_id = p.id')
        ->join('scanwork_process pr', 'a.process_id = pr.id')
        ->where('a.user_id', $workerId)
        ->where('a.status', 0) // 进行中的任务
        ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
        ->order('a.createtime desc')
        ->select();
    
    echo "查询到的分配记录数: " . count($allocations) . "\n";
    
    if (count($allocations) > 0) {
        echo "\n2. 处理每条分配记录...\n";
        foreach ($allocations as &$allocation) {
            echo "处理分配ID: {$allocation['id']}\n";
            
            // 查询已报数量
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', $allocation['id'])
                ->where('status', 1) // 已确认的报工
                ->sum('quantity');
            
            $allocation['reported_quantity'] = intval($reportedQuantity);
            $allocation['remaining_quantity'] = max(0, $allocation['quantity'] - $allocation['reported_quantity']);
            
            echo "  订单号: {$allocation['order_no']}\n";
            echo "  产品: {$allocation['product_name']}\n";
            echo "  模型: {$allocation['model_name']}\n";
            echo "  工序: {$allocation['process_name']}\n";
            echo "  分配数量: {$allocation['quantity']}\n";
            echo "  已报数量: {$allocation['reported_quantity']}\n";
            echo "  待报数量: {$allocation['remaining_quantity']}\n";
            echo "  ---\n";
        }
        
        echo "\n3. 最终数据:\n";
        echo json_encode($allocations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        echo "\n\n4. 模拟FastAdmin的success方法返回:\n";
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
        
        // 检查是否有其他用户的分配记录
        echo "\n检查所有分配记录:\n";
        $allAllocations = Db::name('scanwork_allocation')->select();
        foreach ($allAllocations as $alloc) {
            echo "ID: {$alloc['id']}, 用户ID: {$alloc['user_id']}, 订单ID: {$alloc['order_id']}, 状态: {$alloc['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "错误文件: " . $e->getFile() . "\n";
    echo "错误行号: " . $e->getLine() . "\n";
}

echo "\n=== 测试完成 ===\n"; 