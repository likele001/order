<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class InstallScanwork extends Command
{
    protected function configure()
    {
        $this->setName('install:scanwork')
            ->setDescription('Install scanwork module');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始安装工厂报工管理系统...');
        
        try {
            // 检查数据库表是否存在
            $this->checkTables($output);
            
            // 安装菜单
            $this->installMenus($output);
            
            // 安装权限规则
            $this->installRules($output);
            
            $output->writeln('工厂报工管理系统安装完成！');
            
        } catch (\Exception $e) {
            $output->writeln('安装失败：' . $e->getMessage());
        }
    }
    
    private function checkTables($output)
    {
        $tables = [
            'scanwork_product',
            'scanwork_model', 
            'scanwork_process',
            'scanwork_process_price',
            'scanwork_order',
            'scanwork_order_model',
            'scanwork_allocation',
            'scanwork_report'
        ];
        
        foreach ($tables as $table) {
            $exists = Db::query("SHOW TABLES LIKE 'fa_{$table}'");
            if (empty($exists)) {
                throw new \Exception("数据表 fa_{$table} 不存在，请先运行数据库迁移脚本");
            }
        }
        
        $output->writeln('数据库表检查完成');
    }
    
    private function installMenus($output)
    {
        // 检查是否已存在主菜单
        $mainMenu = Db::name('auth_rule')->where('name', 'scanwork')->find();
        if (!$mainMenu) {
            // 创建主菜单
            $mainMenuId = Db::name('auth_rule')->insertGetId([
                'pid' => 0,
                'name' => 'scanwork',
                'title' => '工厂报工管理',
                'icon' => 'fa fa-industry',
                'condition' => '',
                'remark' => '工厂生产报工管理系统',
                'ismenu' => 1,
                'createtime' => time(),
                'updatetime' => time(),
                'weigh' => 100,
                'status' => 'normal'
            ]);
        } else {
            $mainMenuId = $mainMenu['id'];
        }
        
        // 子菜单配置
        $subMenus = [
            [
                'name' => 'scanwork/product',
                'title' => '产品管理',
                'icon' => 'fa fa-cube',
                'weigh' => 1
            ],
            [
                'name' => 'scanwork/productmodel',
                'title' => '型号管理', 
                'icon' => 'fa fa-tags',
                'weigh' => 2
            ],
            [
                'name' => 'scanwork/process',
                'title' => '工序管理',
                'icon' => 'fa fa-cogs',
                'weigh' => 3
            ],
            [
                'name' => 'scanwork/processprice',
                'title' => '工价管理',
                'icon' => 'fa fa-money',
                'weigh' => 4
            ],
            [
                'name' => 'scanwork/order',
                'title' => '订单管理',
                'icon' => 'fa fa-file-text',
                'weigh' => 5
            ],
            [
                'name' => 'scanwork/allocation',
                'title' => '分工分配',
                'icon' => 'fa fa-users',
                'weigh' => 6
            ],
            [
                'name' => 'scanwork/report',
                'title' => '报工管理',
                'icon' => 'fa fa-check-square-o',
                'weigh' => 7
            ],
            [
                'name' => 'scanwork/qrcode',
                'title' => '二维码管理',
                'icon' => 'fa fa-qrcode',
                'weigh' => 8
            ]
        ];
        
        foreach ($subMenus as $menu) {
            $exists = Db::name('auth_rule')->where('name', $menu['name'])->find();
            if (!$exists) {
                Db::name('auth_rule')->insert([
                    'pid' => $mainMenuId,
                    'name' => $menu['name'],
                    'title' => $menu['title'],
                    'icon' => $menu['icon'],
                    'condition' => '',
                    'remark' => '',
                    'ismenu' => 1,
                    'createtime' => time(),
                    'updatetime' => time(),
                    'weigh' => $menu['weigh'],
                    'status' => 'normal'
                ]);
            }
        }
        
        $output->writeln('菜单安装完成');
    }
    
    private function installRules($output)
    {
        // 权限规则配置
        $rules = [
            // 产品管理权限
            ['name' => 'scanwork/product/index', 'title' => '产品列表'],
            ['name' => 'scanwork/product/add', 'title' => '添加产品'],
            ['name' => 'scanwork/product/edit', 'title' => '编辑产品'],
            ['name' => 'scanwork/product/del', 'title' => '删除产品'],
            
            // 型号管理权限
            ['name' => 'scanwork/productmodel/index', 'title' => '型号列表'],
            ['name' => 'scanwork/productmodel/add', 'title' => '添加型号'],
            ['name' => 'scanwork/productmodel/edit', 'title' => '编辑型号'],
            ['name' => 'scanwork/productmodel/del', 'title' => '删除型号'],
            
            // 工序管理权限
            ['name' => 'scanwork/process/index', 'title' => '工序列表'],
            ['name' => 'scanwork/process/add', 'title' => '添加工序'],
            ['name' => 'scanwork/process/edit', 'title' => '编辑工序'],
            ['name' => 'scanwork/process/del', 'title' => '删除工序'],
            
            // 工价管理权限
            ['name' => 'scanwork/processprice/index', 'title' => '工价列表'],
            ['name' => 'scanwork/processprice/add', 'title' => '添加工价'],
            ['name' => 'scanwork/processprice/edit', 'title' => '编辑工价'],
            ['name' => 'scanwork/processprice/del', 'title' => '删除工价'],
            ['name' => 'scanwork/processprice/batchset', 'title' => '批量设置工价'],
            
            // 订单管理权限
            ['name' => 'scanwork/order/index', 'title' => '订单列表'],
            ['name' => 'scanwork/order/add', 'title' => '添加订单'],
            ['name' => 'scanwork/order/edit', 'title' => '编辑订单'],
            ['name' => 'scanwork/order/del', 'title' => '删除订单'],
            ['name' => 'scanwork/order/detail', 'title' => '订单详情'],
            
            // 分工分配权限
            ['name' => 'scanwork/allocation/index', 'title' => '分配列表'],
            ['name' => 'scanwork/allocation/add', 'title' => '添加分配'],
            ['name' => 'scanwork/allocation/edit', 'title' => '编辑分配'],
            ['name' => 'scanwork/allocation/del', 'title' => '删除分配'],
            ['name' => 'scanwork/allocation/batchallocate', 'title' => '批量分配'],
            
            // 报工管理权限
            ['name' => 'scanwork/report/index', 'title' => '报工列表'],
            ['name' => 'scanwork/report/add', 'title' => '添加报工'],
            ['name' => 'scanwork/report/edit', 'title' => '编辑报工'],
            ['name' => 'scanwork/report/del', 'title' => '删除报工'],
            ['name' => 'scanwork/report/confirm', 'title' => '确认报工'],
            ['name' => 'scanwork/report/unconfirm', 'title' => '取消确认'],
            ['name' => 'scanwork/report/statistics', 'title' => '报工统计'],
            ['name' => 'scanwork/report/export', 'title' => '导出统计'],
            
            // 二维码管理权限
            ['name' => 'scanwork/qrcode/index', 'title' => '二维码列表'],
            ['name' => 'scanwork/qrcode/generate', 'title' => '生成二维码'],
            ['name' => 'scanwork/qrcode/batchGenerate', 'title' => '批量生成二维码'],
            ['name' => 'scanwork/qrcode/download', 'title' => '下载二维码'],
            ['name' => 'scanwork/qrcode/batchDownload', 'title' => '批量下载二维码'],
            ['name' => 'scanwork/qrcode/del', 'title' => '删除二维码']
        ];
        
        foreach ($rules as $rule) {
            $exists = Db::name('auth_rule')->where('name', $rule['name'])->find();
            if (!$exists) {
                Db::name('auth_rule')->insert([
                    'pid' => 0,
                    'name' => $rule['name'],
                    'title' => $rule['title'],
                    'icon' => '',
                    'condition' => '',
                    'remark' => '',
                    'ismenu' => 0,
                    'createtime' => time(),
                    'updatetime' => time(),
                    'weigh' => 0,
                    'status' => 'normal'
                ]);
            }
        }
        
        $output->writeln('权限规则安装完成');
    }
} 