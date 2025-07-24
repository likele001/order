<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;

/**
 * 计时工资统计
 *
 * @icon fa fa-money
 */
class Twage extends Backend
{
    /**
     * Twage模型对象
     * @var \app\admin\model\scanwork\Twage
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Twage;
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
                ->with(['user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
    }

    public function summary()
    {
        return $this->view->fetch();
    }

    public function chart()
    {
        return $this->view->fetch();
    }
} 