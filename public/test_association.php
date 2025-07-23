<?php
// 测试模型关联
require_once 'thinkphp/start.php';

use app\admin\model\scanwork\Report;
use app\admin\model\scanwork\Allocation;

echo "<h2>测试模型关联</h2>";

// 测试报工记录关联
$report = Report::with([
    'allocation' => function($query) {
        $query->with(['order', 'model.product', 'process']);
    },
    'user'
])->find(1);

if ($report) {
    echo "<h3>报工记录 #{$report->id}</h3>";
    echo "分工ID: {$report->allocation_id}<br>";
    echo "员工ID: {$report->user_id}<br>";
    echo "报工数量: {$report->quantity}<br>";
    
    if ($report->allocation) {
        echo "<h4>分工信息</h4>";
        echo "订单ID: {$report->allocation->order_id}<br>";
        echo "型号ID: {$report->allocation->model_id}<br>";
        echo "工序ID: {$report->allocation->process_id}<br>";
        
        if ($report->allocation->order) {
            echo "订单号: {$report->allocation->order->order_no}<br>";
        } else {
            echo "订单关联失败<br>";
        }
        
        if ($report->allocation->model) {
            echo "型号名称: {$report->allocation->model->name}<br>";
            
            if ($report->allocation->model->product) {
                echo "产品名称: {$report->allocation->model->product->name}<br>";
            } else {
                echo "产品关联失败<br>";
            }
        } else {
            echo "型号关联失败<br>";
        }
        
        if ($report->allocation->process) {
            echo "工序名称: {$report->allocation->process->name}<br>";
        } else {
            echo "工序关联失败<br>";
        }
    } else {
        echo "分工关联失败<br>";
    }
    
    if ($report->user) {
        echo "员工姓名: {$report->user->nickname}<br>";
    } else {
        echo "员工关联失败<br>";
    }
    
    echo "<h4>完整数据结构</h4>";
    echo "<pre>";
    print_r($report->toArray());
    echo "</pre>";
    
} else {
    echo "没有找到报工记录<br>";
}
?> 