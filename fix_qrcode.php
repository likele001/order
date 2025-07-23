<?php
// 用于批量补齐历史分工分配二维码数据

define('APP_PATH', __DIR__ . '/application/');
require __DIR__ . '/thinkphp/base.php';

use think\Db;

// 查询所有没有二维码的分工分配
$allocations = Db::name('scanwork_allocation')
    ->alias('a')
    ->leftJoin('scanwork_qrcode q', 'a.id = q.allocation_id')
    ->whereNull('q.id')
    ->select();

if (!$allocations) {
    echo "没有需要补齐的分工分配。\n";
    exit;
}

foreach ($allocations as $allocation) {
    // 关联数据
    $order = Db::name('scanwork_order')->where('id', $allocation['order_id'])->find();
    $model = Db::name('scanwork_model')->where('id', $allocation['model_id'])->find();
    $product = Db::name('scanwork_product')->where('id', $model['product_id'])->find();
    $process = Db::name('scanwork_process')->where('id', $allocation['process_id'])->find();
    $user = Db::name('user')->where('id', $allocation['user_id'])->find();

    $qrData = [
        'type' => 'allocation',
        'id' => $allocation['id'],
        'order_no' => $order['order_no'],
        'product_name' => $product['name'],
        'model_name' => $model['name'],
        'process_name' => $process['name'],
        'user_name' => $user['nickname'],
        'quantity' => $allocation['quantity'],
        'remaining' => $allocation['quantity'] - $allocation['reported_quantity'],
        'timestamp' => time()
    ];
    $qrContent = json_encode($qrData, JSON_UNESCAPED_UNICODE);

    // 生成二维码图片
    $qrImage = \app\admin\controller\scanwork\Qrcode::generateQrCodeImageStatic($qrContent, $allocation['id']);

    Db::name('scanwork_qrcode')->insert([
        'allocation_id' => $allocation['id'],
        'qr_content' => $qrContent,
        'qr_image' => $qrImage,
        'scan_count' => 0,
        'status' => 0,
        'createtime' => time(),
        'updatetime' => time()
    ]);
    echo "分配ID {$allocation['id']} 补齐二维码成功\n";
}
echo "全部补齐完成！\n"; 