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
    protected $noNeedLogin = ['test']; // 只有test方法不需要登录

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

        // ====== 新增首页统计数据 ======
        $userId = $this->auth->id;
        $today = date('Y-m-d');
        $startTime = strtotime($today . ' 00:00:00');
        $endTime = strtotime($today . ' 23:59:59');
        // 今日任务数（今日新分配）
        $todayTaskCount = Db::name('scanwork_allocation')
            ->where('user_id', $userId)
            ->where('createtime', 'between', [$startTime, $endTime])
            ->count();
        // 今日报工数（已确认）
        $todayReportCount = Db::name('scanwork_report')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->where('createtime', 'between', [$startTime, $endTime])
            ->sum('quantity');
        // 今日工资（已确认）
        $todayWage = Db::name('scanwork_report')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->where('createtime', 'between', [$startTime, $endTime])
            ->sum('wage');
        $this->view->assign([
            'todayTaskCount' => $todayTaskCount,
            'todayReportCount' => $todayReportCount,
            'todayWage' => $todayWage ?: 0,
        ]);
        // ====== END ======
        return $this->view->fetch('worker/index');
    }

    /**
     * 我的任务页面（只渲染视图）
     */
    public function tasks()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $tasks = Db::name('scanwork_allocation')
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
        // 计算已报数量和待报数量
        foreach ($tasks as &$task) {
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', $task['id'])
                ->where('status', 1)
                ->sum('quantity');
            $task['reported_quantity'] = intval($reportedQuantity);
            $task['remaining_quantity'] = max(0, $task['quantity'] - $task['reported_quantity']);
        }
        $this->view->assign('tasks', $tasks);
        return $this->view->fetch('worker/tasks');
    }

    /**
     * 任务数据接口（AJAX专用）
     */
    public function tasksData()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, ['code' => 401]);
        }
        try {
            $workerId = $this->auth->id;
            $allocations = Db::name('scanwork_allocation')
                ->where('user_id', $workerId)
                ->where('status', 0)
                ->order('createtime desc')
                ->select();
            $this->success('', null, $allocations);
        } catch (Exception $e) {
            $this->error('获取任务列表失败：' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
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
        $error = '';
        $success = '';
        if ($this->request->isPost()) {
            $allocationId = $this->request->post('allocation_id');
            $quantity = intval($this->request->post('quantity'));
            $remark = $this->request->post('remark');
            if (!$allocationId || $quantity <= 0) {
                $error = '参数错误: allocation_id=' . var_export($allocationId,true) . ', quantity=' . var_export($quantity,true);
            } else {
                $allocation = Db::name('scanwork_allocation')
                    ->alias('a')
                    ->join('scanwork_order o', 'a.order_id = o.id')
                    ->join('scanwork_model m', 'a.model_id = m.id')
                    ->join('scanwork_product p', 'm.product_id = p.id')
                    ->join('scanwork_process pr', 'a.process_id = pr.id')
                    ->where('a.id', $allocationId)
                    ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                    ->find();
                if (!$allocation) {
                    $error = '分工不存在: allocation_id=' . var_export($allocationId,true);
                } else {
                    $reportedQuantity = Db::name('scanwork_report')
                        ->where('allocation_id', $allocationId)
                        ->where('status', 1)
                        ->sum('quantity');
                    $pendingQuantity = Db::name('scanwork_report')
                        ->where('allocation_id', $allocationId)
                        ->where('status', 0)
                        ->sum('quantity');
                    $remainingQuantity = max(0, $allocation['quantity'] - $reportedQuantity - $pendingQuantity);
                    if ($quantity > $remainingQuantity) {
                        $error = '报工数量不能超过待报数量，已报：' . $reportedQuantity . '，待审核：' . $pendingQuantity . '，分配：' . $allocation['quantity'] . '，本次报工：' . $quantity;
                    } else {
                        try {
                            Db::startTrans();
                            Db::name('scanwork_report')->insert([
                                'allocation_id' => $allocationId,
                                'user_id' => $workerId,
                                'quantity' => $quantity,
                                'remark' => $remark,
                                'status' => 0, // 待确认
                                'wage' => 0, // 待计算
                                'createtime' => time(),
                                'updatetime' => time()
                            ]);
                            // 立即增加分工表的已报数量
                            Db::name('scanwork_allocation')->where('id', $allocationId)->setInc('reported_quantity', $quantity);
                            Db::commit();
                            $success = '报工提交成功，等待审核确认';
                        } catch (\Exception $e) {
                            Db::rollback();
                            $error = '异常: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
                        }
                    }
                }
            }
            // 回显任务信息
            if ($allocationId) {
                $allocation = Db::name('scanwork_allocation')
                    ->alias('a')
                    ->join('scanwork_order o', 'a.order_id = o.id')
                    ->join('scanwork_model m', 'a.model_id = m.id')
                    ->join('scanwork_product p', 'm.product_id = p.id')
                    ->join('scanwork_process pr', 'a.process_id = pr.id')
                    ->where('a.id', $allocationId)
                    ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                    ->find();
                if ($allocation) {
                    $reportedQuantity = Db::name('scanwork_report')
                        ->where('allocation_id', $allocation['id'])
                        ->where('status', 1)
                        ->sum('quantity');
                    $pendingQuantity = Db::name('scanwork_report')
                        ->where('allocation_id', $allocation['id'])
                        ->where('status', 0)
                        ->sum('quantity');
                    $allocation['reported_quantity'] = intval($reportedQuantity) + intval($pendingQuantity);
                    $allocation['remaining_quantity'] = max(0, $allocation['quantity'] - $allocation['reported_quantity']);
                    $this->view->assign('allocation', $allocation);
                }
            }
            $this->view->assign('error', $error);
            $this->view->assign('success', $success);
            return $this->view->fetch('worker/report');
        }
        // GET请求逻辑
        if ($allocationId) {
            $allocation = Db::name('scanwork_allocation')
                ->alias('a')
                ->join('scanwork_order o', 'a.order_id = o.id')
                ->join('scanwork_model m', 'a.model_id = m.id')
                ->join('scanwork_product p', 'm.product_id = p.id')
                ->join('scanwork_process pr', 'a.process_id = pr.id')
                ->where('a.id', $allocationId)
                ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
                ->find();
            if ($allocation) {
                $reportedQuantity = Db::name('scanwork_report')
                    ->where('allocation_id', $allocation['id'])
                    ->where('status', 1)
                    ->sum('quantity');
                $allocation['reported_quantity'] = intval($reportedQuantity);
                $allocation['remaining_quantity'] = max(0, $allocation['quantity'] - $allocation['reported_quantity']);
                $this->view->assign('allocation', $allocation);
            }
        } else {
            $tasks = Db::name('scanwork_allocation')
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
            foreach ($tasks as &$task) {
                $reportedQuantity = Db::name('scanwork_report')
                    ->where('allocation_id', $task['id'])
                    ->where('status', 1)
                    ->sum('quantity');
                $task['reported_quantity'] = intval($reportedQuantity);
                $task['remaining_quantity'] = max(0, $task['quantity'] - $task['reported_quantity']);
            }
            $this->view->assign('tasks', $tasks);
        }
        $this->view->assign('error', $error);
        $this->view->assign('success', $success);
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
                $this->error('参数错误: allocation_id=' . var_export($allocationId,true) . ', quantity=' . var_export($quantity,true));
            }
            // 验证分配记录
            $allocation = Db::name('scanwork_allocation')->where('id', $allocationId)->where('user_id', $workerId)->find();
            if (!$allocation) {
                $this->error('无权操作此任务: allocation_id=' . var_export($allocationId,true) . ', user_id=' . var_export($workerId,true));
            }
            // 计算剩余数量
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', $allocationId)
                ->where('status', 1)
                ->sum('quantity');
            $remainingQuantity = max(0, $allocation['quantity'] - $reportedQuantity);
            if ($quantity > $remainingQuantity) {
                $this->error('报工数量不能超过待报数量，已报：' . $reportedQuantity . '，分配：' . $allocation['quantity'] . '，本次报工：' . $quantity);
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
                file_put_contents('/tmp/report_error.log', date('Y-m-d H:i:s') . ' ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL, FILE_APPEND);
                Db::rollback();
                $this->error('异常: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            }
        }
        $this->error('请求方式错误: method=' . $this->request->method());
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
                    ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name, a.model_id, a.process_id')
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
                ->field('r.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name, a.model_id, a.process_id')
                ->order('r.createtime desc')
                ->select();
            // 计算工价和工资金额
            foreach ($reports as &$report) {
                $price = Db::name('scanwork_process_price')->where([
                    'model_id' => $report['model_id'],
                    'process_id' => $report['process_id']
                ])->value('price');
                $report['price'] = $price ? floatval($price) : 0;
                // 只有已确认才显示工资金额，否则为0
                if ($report['status'] == 1) {
                    $report['wage'] = round($report['price'] * $report['quantity'], 2);
                } else {
                    $report['wage'] = 0;
                }
            }
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
    
   
    /**
     * 我的计时分工任务列表
     */
    public function ttasks()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $tasks = Db::name('scanwork_tallocationtime')
            ->where('user_id', $workerId)
            ->where('status', 0)
            ->order('work_date desc')
            ->select();
        $this->view->assign('tasks', $tasks);
        return $this->view->fetch('worker/ttasks');
    }

    /**
     * 计时报工页面
     */
    public function treport()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $taskId = $this->request->get('id');
        $error = '';
        $success = '';
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $tallocationtime_id = $params['tallocationtime_id'];
            $work_date = $params['work_date'];
            $start_time = $params['start_time'];
            $end_time = $params['end_time'];
            $total_hours = $params['total_hours'];
            $remark = $params['remark'];
            if (
                !$tallocationtime_id || !$work_date || !$total_hours
            ) {
                $error = '参数不完整';
            } else {
                // 查找分工任务
                $tallocation = Db::name('scanwork_tallocationtime')->where('id', $tallocationtime_id)->find();
                if (!$tallocation) {
                    $error = '分工任务不存在';
                } else {
                    // 查找工序工时单价
                    $processPrice = Db::name('scanwork_process_price')->where([
                        'model_id' => $tallocation['model_id'],
                        'process_id' => $tallocation['process_id']
                    ])->find();
                    if (!$processPrice) {
                        throw new \Exception('工序工价记录未找到，model_id=' . $tallocation['model_id'] . ', process_id=' . $tallocation['process_id']);
                    }                
                    $time_price = $processPrice ? floatval($processPrice['time_price']) : 0;
                    if ($time_price <= 0) {
                        $error = '工序工资未设置';
                    } else {
                        $wage = round($total_hours * $time_price, 2);
                        try {
                            Db::startTrans();
                            Db::name('scanwork_treporttime')->insert([
                                'tallocationtime_id' => $tallocationtime_id,
                                'user_id' => $workerId,
                                'work_date' => $work_date,
                                'start_time' => $start_time,
                                'end_time' => $end_time,
                                'total_hours' => $total_hours,
                                'wage' => $wage,
                                'remark' => $remark,
                                'status' => 0, // 待确认
                                'createtime' => time(),
                                'updatetime' => time()
                            ]);
                            Db::commit();
                            $success = '报工提交成功，等待审核';
                        } catch (Exception $e) {
                            Db::rollback();
                            $error = '异常: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
        // 获取分工任务
        $tasks = Db::name('scanwork_tallocationtime')
            ->where('user_id', $workerId)
            ->where('status', 0)
            ->order('work_date desc')
            ->select();
        $this->view->assign('tasks', $tasks);
        $this->view->assign('error', $error);
        $this->view->assign('success', $success);
        return $this->view->fetch('worker/treport');
    }

    /**
     * 我的计时报工记录
     */
    public function trecords()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $records = Db::name('scanwork_treporttime')
            ->where('user_id', $workerId)
            ->order('work_date desc')
            ->select();
        $this->view->assign('records', $records);
        return $this->view->fetch('worker/trecords');
    }

    /**
     * 我的计时工资统计
     */
    public function twage()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $wages = Db::name('scanwork_twage')
            ->where('user_id', $workerId)
            ->order('work_date desc')
            ->select();
        $this->view->assign('wages', $wages);
        return $this->view->fetch('worker/twage');
    }

} 
