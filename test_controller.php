<?php
// 简单的控制器测试文件
echo "测试开始...\n";

// 检查控制器文件是否存在
$controllerFile = __DIR__ . '/application/admin/controller/scanwork/ProcessPrice.php';
if (file_exists($controllerFile)) {
    echo "✓ 控制器文件存在: $controllerFile\n";
} else {
    echo "✗ 控制器文件不存在: $controllerFile\n";
}

// 检查模型文件是否存在
$modelFile = __DIR__ . '/application/admin/model/scanwork/ProcessPrice.php';
if (file_exists($modelFile)) {
    echo "✓ 模型文件存在: $modelFile\n";
} else {
    echo "✗ 模型文件不存在: $modelFile\n";
}

// 检查视图文件夹是否存在
$viewDir = __DIR__ . '/application/admin/view/scanwork/processprice';
if (is_dir($viewDir)) {
    echo "✓ 视图文件夹存在: $viewDir\n";
    $files = scandir($viewDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "✗ 视图文件夹不存在: $viewDir\n";
}

// 检查JS文件是否存在
$jsFile = __DIR__ . '/public/assets/js/backend/scanwork/processprice.js';
if (file_exists($jsFile)) {
    echo "✓ JS文件存在: $jsFile\n";
} else {
    echo "✗ JS文件不存在: $jsFile\n";
}

echo "测试完成。\n";
?> 