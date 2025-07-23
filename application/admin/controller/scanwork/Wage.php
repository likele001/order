<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 工资统计管理
 */
class Wage extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Report;
        
        // 设置JS文件
        $this->view->assign('js', ['backend/scanwork/wage']);
    }

    /**
     * 工资统计列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $filter = $this->request->get("filter", '');
            $filterArr = (array)json_decode($filter, true);
            $filterArr = $filterArr ? $filterArr : [];
            
            $op = $this->request->get("op", '', 'trim');
            $opArr = (array)json_decode($op, true);
            $opArr = $opArr ? $opArr : [];
            
            $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
            $order = $this->request->get("order", "DESC");
            $offset = $this->request->get("offset", 0);
            $limit = $this->request->get("limit", 10);
            
            $list = $this->model
                ->with([
                    'allocation' => function($query) {
                        $query->with(['order', 'model.product', 'process']);
                    },
                    'user'
                ])
                ->where($filterArr)
                ->order($sort, $order)
                ->paginate($limit);
            
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        
        return $this->view->fetch();
    }

    /**
     * 工资统计汇总
     */
    public function summary()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-01'));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            $userId = $this->request->get('user_id', '');
            
            $where = [];
            $where[] = ['status', '=', 1]; // 只统计已确认的报工
            $where[] = ['createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]];
            
            if ($userId && $userId !== '') {
                $where[] = ['user_id', '=', intval($userId)];
            }
            
            // 按员工分组统计
            $summary = $this->model
                ->with(['user'])
                ->where('status', 1)
                ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]);
            
            if ($userId && $userId !== '') {
                $summary = $summary->where('user_id', intval($userId));
            }
            
            $summary = $summary->field('user_id, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
                ->group('user_id')
                ->select();
            
            // 计算总计
            $totalQuantity = 0;
            $totalWage = 0;
            $totalCount = 0;
            
            foreach ($summary as $item) {
                $totalQuantity += $item['total_quantity'];
                $totalWage += $item['total_wage'];
                $totalCount += $item['report_count'];
            }
            
            $result = [
                'summary' => $summary,
                'total' => [
                    'quantity' => $totalQuantity,
                    'wage' => $totalWage,
                    'count' => $totalCount
                ]
            ];
            
            $this->success('', null, $result);
        }
        
        // 设置JS文件
        $this->view->assign('js', ['backend/scanwork/wage']);
        return $this->view->fetch();
    }

    /**
     * 工资趋势图表
     */
    public function chart()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-01'));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            $userId = $this->request->get('user_id', '');
            
            $where = [];
            $where[] = ['status', '=', 1]; // 只统计已确认的报工
            $where[] = ['createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]];
            
            if ($userId && $userId !== '') {
                $where[] = ['user_id', '=', intval($userId)];
            }
            
            // 按日期分组统计
            $dailyData = $this->model
                ->where('status', 1)
                ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]);
            
            if ($userId && $userId !== '') {
                $dailyData = $dailyData->where('user_id', intval($userId));
            }
            
            $dailyData = $dailyData->field('DATE(FROM_UNIXTIME(createtime)) as date, SUM(quantity) as quantity, SUM(wage) as wage')
                ->group('DATE(FROM_UNIXTIME(createtime))')
                ->order('date ASC')
                ->select();
            
            // 按员工分组统计
            $userData = $this->model
                ->with(['user'])
                ->where('status', 1)
                ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                ->field('user_id, SUM(wage) as total_wage')
                ->group('user_id')
                ->select();
            
            // 按工序分组统计
            $processData = $this->model
                ->with(['allocation.process'])
                ->where('status', 1)
                ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                ->field('allocation_id, SUM(wage) as total_wage')
                ->group('allocation_id')
                ->select();
            
            $dates = [];
            $quantities = [];
            $wages = [];
            
            foreach ($dailyData as $item) {
                $dates[] = $item['date'];
                $quantities[] = intval($item['quantity']);
                $wages[] = floatval($item['wage']);
            }
            
            // 处理员工数据
            $users = [];
            foreach ($userData as $item) {
                $users[] = [
                    'value' => floatval($item['total_wage']),
                    'name' => $item['user']['nickname'] ?? '未知员工'
                ];
            }
            
            // 处理工序数据
            $processes = [];
            $processWages = [];
            foreach ($processData as $item) {
                if ($item['allocation'] && $item['allocation']['process']) {
                    $processes[] = $item['allocation']['process']['name'];
                    $processWages[] = floatval($item['total_wage']);
                }
            }
            
            $result = [
                'dates' => $dates,
                'quantities' => $quantities,
                'wages' => $wages,
                'users' => $users,
                'processes' => $processes,
                'processWages' => $processWages
            ];
            
            $this->success('', null, $result);
        }
        
        // 设置JS文件
        $this->view->assign('js', ['backend/scanwork/wage']);
        return $this->view->fetch();
    }

    /**
     * 导出工资数据
     */
    public function export()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $userId = $this->request->get('user_id', '');
        
        $where = [];
        $where[] = ['status', '=', 1]; // 只导出已确认的报工
        $where[] = ['createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]];
        
        if ($userId && $userId !== '') {
            $where[] = ['user_id', '=', intval($userId)];
        }
        
        $list = $this->model
            ->with([
                'allocation' => function($query) {
                    $query->with(['order', 'model.product', 'process']);
                },
                'user'
            ])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')]);
        
        if ($userId && $userId !== '') {
            $list = $list->where('user_id', intval($userId));
        }
        
        $list = $list->order('createtime DESC')
            ->select();
        
        $filename = '工资统计_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // 输出CSV头部
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "员工姓名,订单号,产品名称,型号名称,工序名称,报工数量,工价,工资金额,报工时间,状态\n";
        
        foreach ($list as $item) {
            $status = $item['status'] == 1 ? '已确认' : '待确认';
            $time = date('Y-m-d H:i:s', $item['createtime']);
            
            echo sprintf(
                "%s,%s,%s,%s,%s,%d,%.2f,%.2f,%s,%s\n",
                $item['user']['nickname'],
                $item['allocation']['order']['order_no'],
                $item['allocation']['model']['product']['name'],
                $item['allocation']['model']['name'],
                $item['allocation']['process']['name'],
                $item['quantity'],
                $item['allocation']['price'],
                $item['wage'],
                $time,
                $status
            );
        }
        
        exit;
    }

    /**
     * 导出汇总数据
     */
    public function exportSummary()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        // 按员工分组统计
        $summary = $this->model
            ->with(['user'])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('user_id, SUM(quantity) as total_quantity, SUM(wage) as total_wage, COUNT(*) as report_count')
            ->group('user_id')
            ->select();
        
        $filename = '工资汇总_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // 输出CSV头部
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "员工姓名,总报工数量,总工资金额,报工次数,平均工价\n";
        
        foreach ($summary as $item) {
            $avgWage = $item['total_quantity'] > 0 ? $item['total_wage'] / $item['total_quantity'] : 0;
            
            echo sprintf(
                "%s,%d,%.2f,%d,%.2f\n",
                $item['user']['nickname'],
                $item['total_quantity'],
                $item['total_wage'],
                $item['report_count'],
                $avgWage
            );
        }
        
        exit;
    }
} 