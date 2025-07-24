<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;

/**
 * 计时报工管理
 *
 * @icon fa fa-clipboard
 */
class Treporttime extends Backend
{
    /**
     * Treporttime模型对象
     * @var \app\admin\model\scanwork\Treporttime
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Treporttime;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

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
                    'tallocationtime' => function($query) {
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

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $tallocationtime = \app\admin\model\scanwork\Tallocationtime::get($params['tallocationtime_id']);
            if (!$tallocationtime) {
                $this->error('分配不存在');
            }
            $processPrice = \app\admin\model\scanwork\ProcessPrice::where([
                'model_id' => $tallocationtime->model_id,
                'process_id' => $tallocationtime->process_id
            ])->find();
            if (!$processPrice) {
                $this->error('工序工资未设置');
            }
            $total_hours = $params['total_hours'];
            $wage = $total_hours * $processPrice->time_price;
            $data = [
                'tallocationtime_id' => $tallocationtime->id,
                'user_id' => $tallocationtime->user_id,
                'total_hours' => $total_hours,
                'wage' => $wage,
                'remark' => isset($params['remark']) ? $params['remark'] : '',
                'createtime' => time()
            ];
            \app\admin\model\scanwork\Treporttime::create($data);
            $this->success('报工成功');
        }
        return $this->view->fetch();
    }

    public function daily_report()
    {
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $row->save($params);
            $this->success('编辑成功');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function statistics()
    {
        return $this->view->fetch();
    }

    public function worker()
    {
        return $this->view->fetch();
    }

    /**
     * 审核通过
     */
    public function confirm($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error('报工记录不存在');
        }
        $row->status = 1; // 已确认
        $row->save();

        // 工资自动汇总
        $user_id = $row->user_id;
        $work_date = $row->work_date;
        // 汇总该员工当天所有已确认的计时报工
        $total_hours = $this->model->where([
            'user_id' => $user_id,
            'work_date' => $work_date,
            'status' => 1
        ])->sum('total_hours');
        $total_wage = $this->model->where([
            'user_id' => $user_id,
            'work_date' => $work_date,
            'status' => 1
        ])->sum('wage');

        // 写入或更新工资统计表
        $wageModel = \app\admin\model\scanwork\Twage::where([
            'user_id' => $user_id,
            'work_date' => $work_date
        ])->find();
        if ($wageModel) {
            $wageModel->total_hours = $total_hours;
            $wageModel->wage = $total_wage;
            $wageModel->save();
        } else {
            \app\admin\model\scanwork\Twage::create([
                'user_id' => $user_id,
                'work_date' => $work_date,
                'total_hours' => $total_hours,
                'wage' => $total_wage
            ]);
        }

        $this->success('审核通过');
    }
    /**
     * 拒绝报工
     */
    public function reject($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error('报工记录不存在');
        }
        $row->status = 2; // 已拒绝
        $row->save();
        $this->success('已拒绝');
    }
}