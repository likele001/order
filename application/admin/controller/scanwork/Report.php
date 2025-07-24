<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;

/**
 * 报工管理
 *
 * @icon fa fa-clipboard
 */
class Report extends Backend
{
    /**
     * Report模型对象
     * @var \app\admin\model\scanwork\Report
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Report;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 报工管理列表，with 关联 allocation（含 order、model.product、process）和 user
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with([
                    'allocation' => function($query) {
                        $query->with(['order', 'model.product', 'process']);
                    },
                    'user'
                ])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 新增报工
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $allocation = \app\admin\model\scanwork\Allocation::get($params['allocation_id']);
            if (!$allocation) {
                $this->error('分配不存在');
            }
            
            $processPrice = \app\admin\model\scanwork\ProcessPrice::where([
                'model_id' => $allocation->model_id,
                'process_id' => $allocation->process_id
            ])->find();
            if (!$processPrice) {
                $this->error('工序工资未设置');
            }
            if ($workType == 'piece') {
                $quantity = $params['quantity'];
                $wage = $quantity * $processPrice->piece_price;
                $data = [
                    'allocation_id' => $allocation->id,
                    'user_id' => $allocation->user_id,
                    'quantity' => $quantity,
                    'wage' => $wage,
                    'remark' => isset($params['remark']) ? $params['remark'] : '',
                    'createtime' => time()
                ];
            } else {
                $work_hours = $params['work_hours'];
                $wage = $work_hours * $processPrice->time_price;
                $data = [
                    'allocation_id' => $allocation->id,
                    'user_id' => $allocation->user_id,
                 
                    'work_hours' => $work_hours,
                    'wage' => $wage,
                    'remark' => isset($params['remark']) ? $params['remark'] : '',
                    'createtime' => time()
                ];
            }
            \app\admin\model\scanwork\Report::create($data);
            $this->success('报工成功');
        }
        return $this->view->fetch();
    }

    /**
     * 日报工页面
     */
    public function dailyReport()
    {
        return $this->view->fetch();
    }

    /**
     * 审核报工（通过/拒绝）
     */
    public function audit()
    {
        $ids = $this->request->post('ids');
        $status = $this->request->post('status');
        $reason = $this->request->post('reason', '');
        if (!$ids || !in_array($status, ['1', '2'])) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        $idsArr = is_array($ids) ? $ids : explode(',', $ids);
        $success = 0;
        $fail = 0;
        foreach ($idsArr as $id) {
            $report = $this->model->get($id);
            if (!$report) {
                $fail++;
                continue;
            }
            try {
                if ($status == '1') {
                    $report->confirm();
                } else {
                    $report->reject($reason);
                }
                $success++;
            } catch (\Exception $e) {
                $fail++;
            }
        }
        if ($success > 0) {
            return json(['code' => 1, 'msg' => "审核成功：{$success} 条，失败：{$fail} 条"]);
        } else {
            return json(['code' => 0, 'msg' => '审核失败']);
        }
    }

    /**
     * 获取报工日报数据（日期+员工+报工数+总工资）
     */
    public function getDailyReport()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        $reports = $this->model
            ->with(['user'])
            ->where('status', 1)
            ->where('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
            ->field('user_id, FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, SUM(quantity) as quantity, SUM(wage) as wage')
            ->group('date,user_id')
            ->order('date asc')
            ->select();
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'date' => $report['date'],
                'user' => $report->user ? $report->user->nickname : '',
                'quantity' => $report['quantity'],
                'wage' => $report['wage']
            ];
        }
        return json(['code' => 1, 'data' => $data]);
    }
}
