<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use think\Db;

/**
 * 生产进度可视化
 *
 * @icon fa fa-chart-line
 * @remark 生产进度统计和图表展示
 */
class Progress extends Backend
{
    /**
     * 进度总览
     */
    public function index()
    {
        // 实时数据
        $today = date('Y-m-d');
        $quantity = \app\admin\model\scanwork\Report::where('status', 1)->whereTime('createtime', 'today')->sum('quantity');
        $wage = \app\admin\model\scanwork\Report::where('status', 1)->whereTime('createtime', 'today')->sum('wage');
        $tasks = \app\admin\model\scanwork\Allocation::where('status', 0)->where('quantity', '>', 'reported_quantity')->count();
        $pending = \app\admin\model\scanwork\Report::where('status', 0)->count();
        $realTime = [
            'quantity' => $quantity ?: 0,
            'wage' => number_format($wage ?: 0, 2),
            'tasks' => $tasks ?: 0,
            'pending' => $pending ?: 0
        ];

        // 订单进度
        $orders = \app\admin\model\scanwork\Order::order('createtime desc')->limit(10)->select();

        $this->view->assign('realTime', $realTime);
        $this->view->assign('orders', $orders);
        return $this->view->fetch();
    }

    /**
     * 获取总体进度数据（修正版，返回结构与前端一致）
     */
    public function getOverallProgress()
    {
        $statusList = [
            0 => '待生产',
            1 => '生产中',
            2 => '已完成',
        ];

        $orderStats = \app\admin\model\scanwork\Order::group('status')
            ->field('status, COUNT(*) as value')
            ->select();

        $data = [];
        foreach ($orderStats as $stat) {
            $data[] = [
                'name' => $statusList[$stat->status] ?? '未知状态',
                'value' => $stat->value,
            ];
        }

        $this->success('', null, $data);
    }

    /**
     * 获取订单进度数据
     */
    public function getOrderProgress()
    {
        $orders = \app\admin\model\scanwork\Order::with(['orderModels.model.product'])
            ->order('createtime desc')
            ->limit(10)
            ->select();
        
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'order_no' => $order->order_no,
                'customer_name' => $order->customer_name,
                'total_quantity' => $order->total_quantity,
                'progress' => $order->progress,
                'status' => $order->status_text,
                'createtime' => date('Y-m-d', $order->createtime)
            ];
        }
        
        $this->success('', null, $data);
    }

    /**
     * 获取员工工作量统计（修正版）
     */
    public function getWorkerStats()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = \app\admin\model\scanwork\Report::with(['user'])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('user_id, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('user_id')
            ->order('total_wage desc')
            ->select();
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'user_name' => $report->user ? $report->user->nickname : '',
                'total_quantity' => $report->total_quantity,
                'total_wage' => $report->total_wage,
                'report_count' => $report->report_count
            ];
        }
        $this->success('', null, $data);
    }

    /**
     * 获取工序效率统计（修正版）
     */
    public function getProcessStats()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = \app\admin\model\scanwork\Report::with(['allocation.process'])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('allocation_id, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('allocation_id')
            ->order('total_quantity desc')
            ->select();
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'process_name' => $report->allocation && $report->allocation->process ? $report->allocation->process->name : '',
                'total_quantity' => $report->total_quantity,
                'total_wage' => $report->total_wage,
                'report_count' => $report->report_count
            ];
        }
        $this->success('', null, $data);
    }

    /**
     * 获取产品型号统计
     */
    public function getModelStats()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $reports = \app\admin\model\scanwork\Report::with(['allocation.model.product'])
            ->where([
                ['status', '=', 1],
                ['createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]]
            ])
            ->field([
                'allocation.model_id',
                'SUM(quantity) as total_quantity',
                'SUM(wage) as total_wage',
                'COUNT(*) as report_count'
            ])
            ->group('allocation.model_id')
            ->order('total_quantity desc')
            ->select();
        
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'model_name' => $report->allocation->model->product->name . ' - ' . $report->allocation->model->name,
                'total_quantity' => $report->total_quantity,
                'total_wage' => $report->total_wage,
                'report_count' => $report->report_count
            ];
        }
        
        $this->success('', null, $data);
    }

    /**
     * 获取日报工趋势（修正版）
     */
    public function getDailyTrend()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = \app\admin\model\scanwork\Report::where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('date')
            ->order('date asc')
            ->select();
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'date' => $report['date'],
                'total_quantity' => $report['total_quantity'],
                'total_wage' => $report['total_wage'],
                'report_count' => $report['report_count']
            ];
        }
        $this->success('', null, $data);
    }

    /**
     * 获取实时生产数据
     */
    public function getRealTimeData()
    {
        // 今日报工统计
        $today = date('Y-m-d');
        $todayReports = \app\admin\model\scanwork\Report::where([
            ['status', '=', 1],
            ['createtime', 'between', [strtotime($today), strtotime($today . ' 23:59:59')]]
        ])->field([
            'SUM(quantity) as total_quantity',
            'SUM(wage) as total_wage',
            'COUNT(*) as report_count'
        ])->find();
        
        // 进行中的分配
        $activeAllocations = \app\admin\model\scanwork\Allocation::where('status', 0)
            ->where('quantity', '>', 'reported_quantity')
            ->count();
        
        // 待确认的报工
        $pendingReports = \app\admin\model\scanwork\Report::where('status', 0)->count();
        
        $data = [
            'today_quantity' => $todayReports['total_quantity'] ?: 0,
            'today_wage' => $todayReports['total_wage'] ?: 0,
            'today_reports' => $todayReports['report_count'] ?: 0,
            'active_allocations' => $activeAllocations,
            'pending_reports' => $pendingReports
        ];
        
        $this->success('', null, $data);
    }

    /**
     * 获取生产看板数据
     */
    public function getDashboard()
    {
        // 获取所有数据
        $overallProgress = $this->getOverallProgressData();
        $orderProgress = $this->getOrderProgressData();
        $workerStats = $this->getWorkerStatsData();
        $realTimeData = $this->getRealTimeDataArray();
        
        $data = [
            'overall' => $overallProgress,
            'orders' => $orderProgress,
            'workers' => $workerStats,
            'realtime' => $realTimeData
        ];
        
        $this->success('', null, $data);
    }

    /**
     * 进度统计页面
     */
    public function stats()
    {
        $orders = [
            'total' => \app\admin\model\scanwork\Order::count(),
            'in_progress' => \app\admin\model\scanwork\Order::where('status', 1)->count(),
            'completed' => \app\admin\model\scanwork\Order::where('status', 2)->count()
        ];
        $allocations = [
            'total' => \app\admin\model\scanwork\Allocation::count(),
            'in_progress' => \app\admin\model\scanwork\Allocation::where('status', 0)->count(),
            'completed' => \app\admin\model\scanwork\Allocation::where('status', 1)->count()
        ];
        $this->view->assign('orders', $orders);
        $this->view->assign('allocations', $allocations);
        return $this->view->fetch();
    }
    /**
     * 订单进度页面（多维度筛选+工序进度统计）
     */
    public function orderProgress()
    {
        $orderId = $this->request->get('order_id');
        $productId = $this->request->get('product_id');
        $processId = $this->request->get('process_id');

        // 下拉选项
        $orderList = \app\admin\model\scanwork\Order::field('id,order_no')->select();
        $productList = \app\admin\model\scanwork\Product::field('id,name')->select();
        $processList = \app\admin\model\scanwork\Process::field('id,name')->select();

        // 查询工序进度数据
        $where = [];
        if ($orderId) $where['a.order_id'] = $orderId;
        if ($productId) $where['m.product_id'] = $productId;
        if ($processId) $where['a.process_id'] = $processId;

        $processStats = Db::name('scanwork_allocation')
        ->alias('a')
        ->join('scanwork_model m', 'a.model_id = m.id')
        ->join('scanwork_process p', 'a.process_id = p.id')
        ->field('p.id as process_id, p.name as process_name, sum(a.quantity) as total_quantity')
        ->where($where)
        ->group('a.process_id')
        ->select();
    
    foreach ($processStats as &$row) {
        // 统计该工序下所有 allocation 的已报数量
        $allocationIds = Db::name('scanwork_allocation')
            ->alias('a')
            ->join('scanwork_model m', 'a.model_id = m.id')
            ->where($where)
            ->where('a.process_id', $row['process_id'])
            ->column('a.id');
        $reportedQuantity = 0;
        if ($allocationIds) {
            $reportedQuantity = Db::name('scanwork_report')
                ->where('allocation_id', 'in', $allocationIds)
                ->where('status', 1)
                ->sum('quantity');
        }
        $row['reported_quantity'] = $reportedQuantity;
        $row['completion_rate'] = $row['total_quantity'] > 0 ? round($reportedQuantity / $row['total_quantity'] * 100, 2) : 0;
    }

        // 原有 orderStats 结构
        $orders = \app\admin\model\scanwork\Order::with([
            'orderModels.model.product',
            'allocations.process',
            'allocations.model.product',
            'allocations.reports.user'
        ])->select();
        $orderStats = [];
        foreach ($orders as $order) {
            $orderArr = [
                'order_no' => $order->order_no,
                'products' => []
            ];
            foreach ($order->orderModels as $orderModel) {
                $model = $orderModel->model;
                $product = $model->product;
                $modelKey = $model->id;
                $orderArr['products'][$modelKey] = [
                    'product_name' => $product->name,
                    'model_name' => $model->name,
                    'processes' => []
                ];
                foreach ($order->allocations as $allocation) {
                    if ($allocation->model_id != $model->id) continue;
                    $process = $allocation->process;
                    $processKey = $process->id;
                    if (!isset($orderArr['products'][$modelKey]['processes'][$processKey])) {
                        $orderArr['products'][$modelKey]['processes'][$processKey] = [
                            'process_name' => $process->name,
                            'users' => []
                        ];
                    }
                    foreach ($allocation->reports as $report) {
                        $user = $report->user;
                        $orderArr['products'][$modelKey]['processes'][$processKey]['users'][] = [
                            'user_name' => $user ? $user->nickname : '',
                            'quantity' => $report->quantity,
                            'status' => $report->status,
                            'status_text' => $report->status_text
                        ];
                    }
                }
            }
            $orderStats[] = $orderArr;
        }
        $this->view->assign([
            'orderStats' => $orderStats,
            'processStats' => $processStats,
            'orderList' => $orderList,
            'productList' => $productList,
            'processList' => $processList,
            'orderId' => $orderId,
            'productId' => $productId,
            'processId' => $processId
        ]);
        return $this->view->fetch();
    }
    /**
     * 员工进度页面
     */
    public function workerProgress()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = \app\admin\model\scanwork\Report::with(['user'])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('user_id, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('user_id')
            ->order('total_wage desc')
            ->select();
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'user_name' => $report->user ? $report->user->nickname : '',
                'total_quantity' => $report->total_quantity,
                'total_wage' => $report->total_wage,
                'report_count' => $report->report_count
            ];
        }
        $this->view->assign('workerStats', $data);
        return $this->view->fetch();
    }
    /**
     * 工序进度页面
     */
    public function processProgress()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = \app\admin\model\scanwork\Report::with(['allocation.process', 'allocation.model.product'])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('allocation_id, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('allocation_id')
            ->order('total_quantity desc')
            ->select();
        $data = [];
        foreach ($reports as $report) {
            $product_name = $report->allocation && $report->allocation->model && $report->allocation->model->product ? $report->allocation->model->product->name : '';
            $model_name = $report->allocation && $report->allocation->model ? $report->allocation->model->name : '';
            $process_name = $report->allocation && $report->allocation->process ? $report->allocation->process->name : '';
            $data[] = [
                'product_name' => $product_name,
                'model_name' => $model_name,
                'process_name' => $process_name,
                'total_quantity' => $report->total_quantity,
                'total_wage' => $report->total_wage,
                'report_count' => $report->report_count
            ];
        }
        $this->view->assign('processStats', $data);
        return $this->view->fetch();
    }
    /**
     * 日报工趋势页面
     */
    public function dailyReport()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = \app\admin\model\scanwork\Report::where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('date')
            ->order('date asc')
            ->select();
        $this->view->assign('dailyStats', $reports);
        return $this->view->fetch();
    }

    /**
     * 获取总体进度数据（数组格式）
     */
    private function getOverallProgressData()
    {
        $orderCount = \app\admin\model\scanwork\Order::count();
        $orderInProgress = \app\admin\model\scanwork\Order::where('status', 1)->count();
        $orderCompleted = \app\admin\model\scanwork\Order::where('status', 2)->count();
        
        $allocationCount = \app\admin\model\scanwork\Allocation::count();
        $allocationInProgress = \app\admin\model\scanwork\Allocation::where('status', 0)->count();
        $allocationCompleted = \app\admin\model\scanwork\Allocation::where('status', 1)->count();
        
        return [
            'orders' => [
                'total' => $orderCount,
                'in_progress' => $orderInProgress,
                'completed' => $orderCompleted
            ],
            'allocations' => [
                'total' => $allocationCount,
                'in_progress' => $allocationInProgress,
                'completed' => $allocationCompleted
            ]
        ];
    }

    /**
     * 获取订单进度数据（数组格式）
     */
    private function getOrderProgressData()
    {
        $orders = \app\admin\model\scanwork\Order::with(['orderModels.model.product'])
            ->order('createtime desc')
            ->limit(5)
            ->select();
        
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'order_no' => $order->order_no,
                'customer_name' => $order->customer_name,
                'progress' => $order->progress,
                'status' => $order->status_text
            ];
        }
        
        return $data;
    }

    /**
     * 获取员工统计数据（数组格式）
     */
    private function getWorkerStatsData()
    {
        $today = date('Y-m-d');
        $reports = \app\admin\model\scanwork\Report::with(['user'])
            ->where([
                ['status', '=', 1],
                ['createtime', 'between', [strtotime($today), strtotime($today . ' 23:59:59')]]
            ])
            ->field([
                'user_id',
                'SUM(quantity) as total_quantity',
                'SUM(wage) as total_wage'
            ])
            ->group('user_id')
            ->order('total_wage desc')
            ->limit(5)
            ->select();
        
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'user_name' => $report->user->nickname,
                'quantity' => $report['total_quantity'],
                'wage' => $report['total_wage']
            ];
        }
        
        return $data;
    }

    /**
     * 获取实时数据（数组格式）
     */
    private function getRealTimeDataArray()
    {
        $today = date('Y-m-d');
        $todayReports = \app\admin\model\scanwork\Report::where([
            ['status', '=', 1],
            ['createtime', 'between', [strtotime($today), strtotime($today . ' 23:59:59')]]
        ])->field([
            'SUM(quantity) as total_quantity',
            'SUM(wage) as total_wage',
            'COUNT(*) as report_count'
        ])->find();
        
        return [
            'today_quantity' => $todayReports['total_quantity'] ?: 0,
            'today_wage' => $todayReports['total_wage'] ?: 0,
            'today_reports' => $todayReports['report_count'] ?: 0
        ];
    }
}