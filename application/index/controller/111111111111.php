<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Db;
use Exception;

/**
 * 员工报工前台控制器
 */
class Worker extends Frontend
{
    protected $noNeedRight = ['*'];
    protected $noNeedLogin = ['test', 'tasks']; // 添加tasks方法，在方法内部检查登录

    public function _initialize()
    {
        parent::_initialize();
        $this->view->assign('title', '员工报工系统');
    }
    
    /**
     * 测试方法
     */
    public function test()
    {
        $this->success('Worker控制器工作正常', null, ['worker_id' => $this->auth->id]);
    }

    /**
     * 员工首页
     */
    public function index()
    {
        // 检查是否已登录
        if (!$this->auth->isLogin()) {
            // 未登录，重定向到登录页面
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        
        // 获取员工信息
        $worker = $this->auth->getUser();
        $this->view->assign('worker', $worker);
        
        return $this->view->fetch('worker/index');
    }

    /**
     * 我的任务列表
     */
    public function tasks()
    {
        // 检查是否已登录
        if (!$this->auth->isLogin()) {
            if ($this->request->isAjax()) {
                $this->error('请先登录', null, ['code' => 401]);
            } else {
                $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
            }
        }
        
        try {
            $workerId = $this->auth->id;
            
            if ($this->request->isAjax()) {
                // 直接使用Db查询，避免模型关联问题
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
                
                // 计算已报数量和剩余数量
                foreach ($allocations as &$allocation) {
                    // 查询已报数量
                    $reportedQuantity = Db::name('scanwork_report')
                        ->where('allocation_id', $allocation['id'])
                        ->where('status', 1) // 已确认的报工
                        ->sum('quantity');
                    
                    $allocation['reported_quantity'] = intval($reportedQuantity);
                    $allocation['remaining_quantity'] = max(0, $allocation['quantity'] - $allocation['reported_quantity']);
                }
                
                $this->success('', null, $allocations);
            }
            
            return $this->view->fetch('worker/tasks');
        } catch (Exception $e) {
            $this->error('获取任务列表失败：' . $e->getMessage());
        }
    }

    /**
     * 报工页面
     */
    public function report()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        
        $workerId = $this->auth->id;
        $allocationId = $this->request->get('id');
        
        if ($allocationId) {
            $allocation = Db::name('scanwork_allocation')
                ->alias('a')
                ->join('scanwork_order o', 'a.order_id = o.id')
                ->join('scanwork_model m', 'a.model_id = m.id')
                ->join('scanwork_product p', 'm.product_id = p.id')
                ->join('scanwork_process pr', 'a.process_id = pr.id')
                ->where('a.id', $allocationId)
                ->where('a.user_id', $workerId)
                ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                ->find();
            
            if (!$allocation) {
                $this->error('任务不存在或无权限');
            }
            
            // 计算已报数量和剩余数量
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', $allocation['id'])
                ->where('status', 1)
                ->sum('quantity');
            
            $allocation['reported_quantity'] = intval($reportedQuantity);
            $allocation['remaining_quantity'] = max(0, $allocation['quantity'] - $allocation['reported_quantity']);
            
            $this->view->assign('allocation', $allocation);
        }
        
        return $this->view->fetch('worker/report');
    }

    /**
     * 提交报工
     */
    public function submit()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $workerId = $this->auth->id;
        
        if ($this->request->isPost()) {
            $allocationId = $this->request->post('allocation_id');
            $quantity = intval($this->request->post('quantity'));
            
            if (!$allocationId || $quantity <= 0) {
                $this->error('参数错误');
            }
            
            // 验证分配记录
            $allocation = Db::name('scanwork_allocation')->where('id', $allocationId)->where('user_id', $workerId)->find();
            if (!$allocation) {
                $this->error('无权操作此任务');
            }
            
            // 计算剩余数量
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', $allocationId)
                ->where('status', 1)
                ->sum('quantity');
            
            $remainingQuantity = max(0, $allocation['quantity'] - $reportedQuantity);
            
            if ($quantity > $remainingQuantity) {
                $this->error('报工数量不能超过待报数量');
            }
            
            Db::startTrans();
            try {
                // 创建报工记录
                $reportId = Db::name('scanwork_report')->insertGetId([
                    'allocation_id' => $allocationId,
                    'user_id' => $workerId,
                    'quantity' => $quantity,
                    'status' => 0, // 待确认
                    'wage' => 0, // 待计算
                    'createtime' => time(),
                    'updatetime' => time()
                ]);
                
                Db::commit();
                $this->success('报工提交成功，等待审核确认');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 扫码报工
     */
    public function scan()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $workerId = $this->auth->id;
        
        if ($this->request->isPost()) {
            $qrCode = $this->request->post('qr_code');
            
            if (!$qrCode) {
                $this->error('二维码数据不能为空');
            }
            
            // 解析二维码数据（格式：allocation_id）
            $allocationId = $qrCode;
            
            // 验证分配记录
            $allocation = Db::name('scanwork_allocation')
                ->alias('a')
                ->join('scanwork_order o', 'a.order_id = o.id')
                ->join('scanwork_model m', 'a.model_id = m.id')
                ->join('scanwork_product p', 'm.product_id = p.id')
                ->join('scanwork_process pr', 'a.process_id = pr.id')
                ->where('a.id', $allocationId)
                ->where('a.user_id', $workerId)
                ->where('a.status', 0)
                ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                ->find();
            
            if (!$allocation) {
                $this->error('无效的二维码或任务已完成');
            }
            
            // 计算剩余数量
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', $allocationId)
                ->where('status', 1)
                ->sum('quantity');
            
            $remainingQuantity = max(0, $allocation['quantity'] - $reportedQuantity);
            
            if ($remainingQuantity <= 0) {
                $this->error('该任务已无待报数量');
            }
            
            $allocation['remaining_quantity'] = $remainingQuantity;
            $this->success('', null, $allocation);
        }
        
        return $this->view->fetch('worker/scan');
    }

    /**
     * 我的报工记录
     */
    public function records()
    {
        if (!$this->auth->isLogin()) {
            if ($this->request->isAjax()) {
                $this->error('请先登录', null, ['code' => 401]);
            } else {
                $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
            }
        }
        
        try {
            $workerId = $this->auth->id;
            
            if ($this->request->isAjax()) {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                
                $reports = Db::name('scanwork_report')
                    ->alias('r')
                    ->join('scanwork_allocation a', 'r.allocation_id = a.id')
                    ->join('scanwork_order o', 'a.order_id = o.id')
                    ->join('scanwork_model m', 'a.model_id = m.id')
                    ->join('scanwork_product p', 'm.product_id = p.id')
                    ->join('scanwork_process pr', 'a.process_id = pr.id')
                    ->where('r.user_id', $workerId)
                    ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                    ->order('r.createtime desc')
                    ->page($page, $limit)
                    ->select();
                
                $total = Db::name('scanwork_report')->where('user_id', $workerId)->count();
                
                $this->success('', null, [
                    'list' => $reports,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ]);
            }
            
            // 获取报工记录数据
            $reports = Db::name('scanwork_report')
                ->alias('r')
                ->join('scanwork_allocation a', 'r.allocation_id = a.id')
                ->join('scanwork_order o', 'a.order_id = o.id')
                ->join('scanwork_model m', 'a.model_id = m.id')
                ->join('scanwork_product p', 'm.product_id = p.id')
                ->join('scanwork_process pr', 'a.process_id = pr.id')
                ->where('r.user_id', $workerId)
                ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                ->order('r.createtime desc')
                ->select();
            
            $this->view->assign([
                'reports' => $reports
            ]);
            
            return $this->view->fetch('worker/records');
        } catch (Exception $e) {
            $this->error('获取报工记录失败：' . $e->getMessage());
        }
    }

    /**
     * 工资统计
     */
    public function wage()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        
        try {
            $workerId = $this->auth->id;
            
            if ($this->request->isAjax()) {
                $startDate = $this->request->get('start_date', date('Y-m-01'));
                $endDate = $this->request->get('end_date', date('Y-m-d'));
                
                $reports = Db::name('scanwork_report')
                    ->alias('r')
                    ->join('scanwork_allocation a', 'r.allocation_id = a.id')
                    ->join('scanwork_order o', 'a.order_id = o.id')
                    ->join('scanwork_model m', 'a.model_id = m.id')
                    ->join('scanwork_product p', 'm.product_id = p.id')
                    ->join('scanwork_process pr', 'a.process_id = pr.id')
                    ->where('r.user_id', $workerId)
                    ->where('r.status', 1)
                    ->where('r.createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                    ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                    ->order('r.createtime desc')
                    ->select();
                
                $totalQuantity = 0;
                $totalWage = 0;
                
                foreach ($reports as $report) {
                    $totalQuantity += $report['quantity'];
                    $totalWage += $report['wage'];
                }
                
                $this->success('', null, [
                    'reports' => $reports,
                    'total_quantity' => $totalQuantity,
                    'total_wage' => $totalWage
                ]);
            }
            
            // 获取默认日期范围的数据
            $startDate = $this->request->get('start_date', date('Y-m-01'));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            $reports = Db::name('scanwork_report')
                ->alias('r')
                ->join('scanwork_allocation a', 'r.allocation_id = a.id')
                ->join('scanwork_order o', 'a.order_id = o.id')
                ->join('scanwork_model m', 'a.model_id = m.id')
                ->join('scanwork_product p', 'm.product_id = p.id')
                ->join('scanwork_process pr', 'a.process_id = pr.id')
                ->where('r.user_id', $workerId)
                ->where('r.status', 1)
                ->where('r.createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                ->order('r.createtime desc')
                ->select();
            
            $totalQuantity = 0;
            $totalWage = 0;
            $confirmedWage = 0;
            $pendingWage = 0;
            
            foreach ($reports as $report) {
                $totalQuantity += $report['quantity'];
                $totalWage += $report['wage'];
                if ($report['status'] == 1) {
                    $confirmedWage += $report['wage'];
                } else {
                    $pendingWage += $report['wage'];
                }
            }
            
            $this->view->assign([
                'reports' => $reports,
                'total_quantity' => $totalQuantity,
                'total_wage' => $totalWage,
                'confirmed_wage' => $confirmedWage,
                'pending_wage' => $pendingWage,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            return $this->view->fetch('worker/wage');
        } catch (Exception $e) {
            $this->error('工资统计出错：' . $e->getMessage());
        }
    }
    
    /**
     * 统计图表数据
     */
    public function stats()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $workerId = $this->auth->id;
        
        // 获取最近7天的数据
        $dates = [];
        $quantities = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $date;
            
            $quantity = Db::name('scanwork_report')
                ->where('user_id', $workerId)
                ->where('status', 1)
                ->where('createtime', 'between', [strtotime($date), strtotime($date . ' 23:59:59')])
                ->sum('quantity');
            
            $quantities[] = intval($quantity);
        }
        
        $this->success('', null, [
            'dates' => $dates,
            'quantities' => $quantities
        ]);
    }
    
    /**
     * 工资图表数据
     */
    public function wageChart()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $workerId = $this->auth->id;
        
        // 获取最近7天的工资数据
        $dates = [];
        $wages = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $date;
            
            $wage = Db::name('scanwork_report')
                ->where('user_id', $workerId)
                ->where('status', 1)
                ->where('createtime', 'between', [strtotime($date), strtotime($date . ' 23:59:59')])
                ->sum('wage');
            
            $wages[] = floatval($wage);
        }
        
        $this->success('', null, [
            'dates' => $dates,
            'wages' => $wages
        ]);
    }
    
    /**
     * 日报工数据
     */
    public function dailyReport()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $workerId = $this->auth->id;
        $date = $this->request->get('date', date('Y-m-d'));
        
        $reports = Db::name('scanwork_report')
            ->alias('r')
            ->join('scanwork_allocation a', 'r.allocation_id = a.id')
            ->join('scanwork_order o', 'a.order_id = o.id')
            ->join('scanwork_model m', 'a.model_id = m.id')
            ->join('scanwork_product p', 'm.product_id = p.id')
            ->join('scanwork_process pr', 'a.process_id = pr.id')
            ->where('r.user_id', $workerId)
            ->where('r.status', 1)
            ->where('r.createtime', 'between', [strtotime($date), strtotime($date . ' 23:59:59')])
            ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
            ->order('r.createtime desc')
            ->select();
        
        $totalQuantity = 0;
        $totalWage = 0;
        
        foreach ($reports as $report) {
            $totalQuantity += $report['quantity'];
            $totalWage += $report['wage'];
        }
        
        $this->success('', null, [
            'reports' => $reports,
            'total_quantity' => $totalQuantity,
            'total_wage' => $totalWage,
            'date' => $date
        ]);
    }
} 