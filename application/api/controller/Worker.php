<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Request;

class Worker extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        $userId = $user['id'];
        $today = date('Y-m-d');
        $startTime = strtotime($today . ' 00:00:00');
        $endTime = strtotime($today . ' 23:59:59');
        $todayTaskCount = \think\Db::name('scanwork_allocation')->where('user_id', $userId)->where('createtime', 'between', [$startTime, $endTime])->count();
        $todayReportCount = \think\Db::name('scanwork_report')->where('user_id', $userId)->where('status', 1)->where('createtime', 'between', [$startTime, $endTime])->sum('quantity');
        $todayWage = \think\Db::name('scanwork_report')->where('user_id', $userId)->where('status', 1)->where('createtime', 'between', [$startTime, $endTime])->sum('wage');
        $this->success('获取成功', [
            'user' => $user,
            'todayTaskCount' => $todayTaskCount,
            'todayReportCount' => $todayReportCount,
            'todayWage' => $todayWage ?: 0,
            'today' => date('Y-m-d')
        ]);
    }

    /**
     * 获取任务列表
     */
    public function tasks()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        $workerId = $user['id'];
        $tasks = \think\Db::name('scanwork_allocation')
            ->alias('a')
            ->join('scanwork_order o', 'a.order_id = o.id')
            ->join('scanwork_model m', 'a.model_id = m.id')
            ->join('scanwork_product p', 'm.product_id = p.id')
            ->join('scanwork_process pr', 'a.process_id = pr.id')
            ->where('a.user_id', $workerId)
            ->where('a.status', 0)
            ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
            ->order('a.createtime desc')
            ->select();
        $this->success('获取成功', $tasks);
    }

    /**
     * 获取报工任务详情
     */
    public function report()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        $allocationId = $this->request->param('id');
        if (!$allocationId) {
            $this->error('未指定任务ID，无法报工');
        }
        $allocation = \think\Db::name('scanwork_allocation')
            ->alias('a')
            ->join('scanwork_order o', 'a.order_id = o.id')
            ->join('scanwork_model m', 'a.model_id = m.id')
            ->join('scanwork_product p', 'm.product_id = p.id')
            ->join('scanwork_process pr', 'a.process_id = pr.id')
            ->where('a.id', $allocationId)
            ->where('a.user_id', $user['id'])
            ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
            ->find();
        if (!$allocation) {
            $this->error('任务不存在或不属于当前用户');
        }
        $this->success('获取成功', $allocation);
    }

    /**
     * 提交报工
     */
    public function submit()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        $allocationId = $this->request->post('allocation_id');
        $quantity = $this->request->post('quantity');
        $remark = $this->request->post('remark');
        if (!$allocationId || !$quantity) {
            $this->error('参数不完整');
        }
        $allocation = \think\Db::name('scanwork_allocation')->where('id', $allocationId)->where('user_id', $user['id'])->find();
        if (!$allocation) {
            $this->error('任务不存在或不属于当前用户');
        }
        if ($allocation['status'] != 0) {
            $this->error('任务已完成，无法重复报工');
        }
        // 获取工序工价
        $processPrice = \think\Db::name('scanwork_process_price')
            ->where('model_id', $allocation['model_id'])
            ->where('process_id', $allocation['process_id'])
            ->find();
        if (!$processPrice) {
            $this->error('工序工价记录未找到，model_id=' . $allocation['model_id'] . ', process_id=' . $allocation['process_id']);
        }
        $wage = $quantity * $processPrice['price'];
        $reportData = [
            'allocation_id' => $allocationId,
            'user_id' => $user['id'],
            'quantity' => $quantity,
            'wage' => $wage,
            'remark' => $remark,
            'status' => 0,
            'createtime' => time()
        ];
        $reportId = \think\Db::name('scanwork_report')->insertGetId($reportData);
        if (!$reportId) {
            $this->error('报工失败');
        }
        // 更新任务状态
        \think\Db::name('scanwork_allocation')->where('id', $allocationId)->update(['status' => 1]);
        $this->success('报工成功', ['report_id' => $reportId]);
    }

    /**
     * 上传报工图片
     */
    public function uploadImage()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        $reportId = $this->request->post('report_id');
        if (!$reportId) {
            $this->error('缺少报工ID');
        }
        if (!empty($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] == 0) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = date('YmdHis') . '_' . \fast\Random::alnum(8) . '.' . $ext;
                $dateDir = date('Y-m-d');
                $uploadDir = 'uploads/baogong/' . $dateDir . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    \think\Db::name('scanwork_report_image')->insert([
                        'report_id' => $reportId,
                        'image_url' => '/uploads/baogong/' . $dateDir . '/' . $filename,
                        'createtime' => time()
                    ]);
                    $this->success('上传成功', ['url' => '/uploads/baogong/' . $dateDir . '/' . $filename]);
                } else {
                    $this->error('文件上传失败');
                }
            } else {
                $this->error('文件上传错误');
            }
        } else {
            $this->error('未接收到文件');
        }
    }

    /**
     * 获取报工记录
     */
    public function records()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        
        // 先查询基本的报工记录
        $records = \think\Db::name('scanwork_report')
            ->where('user_id', $user['id'])
            ->order('createtime desc')
            ->select();
        
        // 为每条记录补充详细信息
        foreach ($records as &$record) {
            // 获取分配信息
            $allocation = \think\Db::name('scanwork_allocation')->where('id', $record['allocation_id'])->find();
            if ($allocation) {
                // 获取订单信息
                $order = \think\Db::name('scanwork_order')->where('id', $allocation['order_id'])->find();
                $record['order_no'] = $order ? $order['order_no'] : '';
                
                // 获取型号信息
                $model = \think\Db::name('scanwork_model')->where('id', $allocation['model_id'])->find();
                $record['model_name'] = $model ? $model['name'] : '';
                
                // 获取产品信息
                if ($model) {
                    $product = \think\Db::name('scanwork_product')->where('id', $model['product_id'])->find();
                    $record['product_name'] = $product ? $product['name'] : '';
                } else {
                    $record['product_name'] = '';
                }
                
                // 获取工序信息
                $process = \think\Db::name('scanwork_process')->where('id', $allocation['process_id'])->find();
                $record['process_name'] = $process ? $process['name'] : '';
            } else {
                $record['order_no'] = '';
                $record['model_name'] = '';
                $record['product_name'] = '';
                $record['process_name'] = '';
            }
            
            // 获取图片
            $images = \think\Db::name('scanwork_report_image')
                ->where('report_id', $record['id'])
                ->field('image_url')
                ->select();
            // 为图片URL添加完整域名前缀
            $record['images'] = array_map(function($img) {
                return 'https://order.023ent.net' . $img['image_url'];
            }, $images);
        }
        
        $this->success('获取成功', $records);
    }

    /**
     * 获取工资统计
     */
    public function wages()
    {
        if (!$this->auth->isLogin()) {
            $this->error('未登录', null, 401);
        }
        $user = $this->auth->getUserinfo();
        $month = $this->request->param('month', date('Y-m'));
        $startTime = strtotime($month . '-01 00:00:00');
        $endTime = strtotime($month . '-' . date('t', $startTime) . ' 23:59:59');
        $wages = \think\Db::name('scanwork_report')
            ->alias('r')
            ->join('scanwork_allocation a', 'r.allocation_id = a.id')
            ->join('scanwork_order o', 'a.order_id = o.id')
            ->join('scanwork_model m', 'a.model_id = m.id')
            ->join('scanwork_product p', 'm.product_id = p.id')
            ->join('scanwork_process pr', 'a.process_id = pr.id')
            ->where('r.user_id', $user['id'])
            ->where('r.status', 1)
            ->where('r.createtime', 'between', [$startTime, $endTime])
            ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
            ->order('r.createtime desc')
            ->select();
        $totalWage = array_sum(array_column($wages, 'wage'));
        $this->success('获取成功', [
            'wages' => $wages,
            'totalWage' => $totalWage,
            'month' => $month
        ]);
    }
}