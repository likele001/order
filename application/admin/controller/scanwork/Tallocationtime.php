<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 计时分工分配管理
 *
 * @icon fa fa-tasks
 * @remark 管理计时生产任务的分工分配
 */
class Tallocationtime extends Backend
{
    /**
     * Tallocationtime模型对象
     * @var \app\admin\model\scanwork\Tallocationtime
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Tallocationtime;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
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
                ->with(['order', 'model.product', 'process', 'user', 'reports'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $rows = [];
            foreach ($list as $item) {
                $row = $item->toArray();
                if (!isset($row['id'])) {
                    $row['id'] = $item->id;
                }
                $rows[] = $row;
            }
            $result = array("total" => $list->total(), "rows" => $rows);
            return json($result);
        }

        // 查询所有可用的分工分配（可根据业务加筛选条件）
$tallocationtimeList = \app\admin\model\scanwork\Tallocationtime::with(['order', 'model.product', 'process', 'user'])
->where('status', 0)
->select();

$list = [];
foreach ($tallocationtimeList as $item) {
$list[$item['id']] = $item['order']['order_no'] . ' - ' . $item['model']['product']['name'] . ' - ' . $item['model']['name'] . ' - ' . $item['process']['name'] . ' - ' . $item['user']['nickname'];
}
$this->view->assign('tallocationtimeList', $list);

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $orderList = \app\admin\model\scanwork\Order::where('status', '<>', 2)->column('order_no', 'id');
        $this->view->assign('orderList', $orderList);
        $processList = \app\admin\model\scanwork\Process::where('status', 1)->column('name', 'id');
        $this->view->assign('processList', $processList);
        $userList = \app\common\model\User::where('status', 'normal')->column('nickname', 'id');
        $this->view->assign('userList', $userList);
        // 分配分工分配下拉（订单号-型号-工序-员工）
        $tallocationList = \app\admin\model\scanwork\Tallocationtime::with(['order', 'model', 'process', 'user'])
            ->where('status', 0)
            ->select();
        $tallocationtimeList = [];
        foreach ($tallocationList as $item) {
            $tallocationtimeList[$item['id']] =
                ($item['order']['order_no'] ?? '-') . ' - ' .
                ($item['model']['name'] ?? '-') . ' - ' .
                ($item['process']['name'] ?? '-') . ' - ' .
                ($item['user']['nickname'] ?? '-');
        }
        $this->view->assign('tallocationtimeList', $tallocationtimeList);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['work_type'] = 'time';
                $params['total_hours'] = isset($params['total_hours']) ? $params['total_hours'] : 0;
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    // 验证数据
                    // 这里可加自定义验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? 
                            ($this->modelSceneValidate ? $name . '.add' : $name) : 
                            $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error('数据库错误: ' . $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $orderList = \app\admin\model\scanwork\Order::where('status', '<>', 2)->column('order_no', 'id');
        $this->view->assign('orderList', $orderList);
        $userList = \app\common\model\User::where('status', 'normal')->column('nickname', 'id');
        $this->view->assign('userList', $userList);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['work_type'] = 'time';
                $params['total_hours'] = isset($params['total_hours']) ? $params['total_hours'] : 0;
                $result = false;
                Db::startTrans();
                try {
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $item) {
                    $reportCount = \app\admin\model\scanwork\Treporttime::where('tallocationtime_id', $item->id)->count();
                    if ($reportCount > 0) {
                        throw new Exception("分工分配【{$item->id}】下还有{$reportCount}个报工记录，请先删除相关报工");
                    }
                    $count += $item->delete();
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        } else {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
    }

    /**
     * 获取指定订单下的型号列表（AJAX接口）
     */
    public function getModelList()
    {
        $order_id = $this->request->get('order_id');
        $list = [];
        if ($order_id) {
            $orderModels = \app\admin\model\scanwork\OrderModel::where('order_id', $order_id)->select();
            $modelIds = [];
            foreach ($orderModels as $om) {
                $modelIds[] = $om['model_id'];
            }
            if ($modelIds) {
                $models = \app\admin\model\scanwork\ProductModel::with('product')->where('id', 'in', $modelIds)->select();
                foreach ($models as $model) {
                    $list[] = [
                        'id' => $model['id'],
                        'name' => ($model['product']['name'] ?? '') . ' - ' . $model['name']
                    ];
                }
            }
        }
        return json(['rows' => $list]);
    }
} 