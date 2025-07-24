<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 工序工价管理
 *
 * @icon fa fa-money
 * @remark 管理型号与工序的工价设置
 */
class ProcessPrice extends Backend
{
    /**
     * ProcessPrice模型对象
     * @var \app\admin\model\scanwork\ProcessPrice
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\ProcessPrice;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        // 获取型号和工序数据用于批量设置
        $models = \app\admin\model\scanwork\ProductModel::with('product')->select();
        $processes = \app\admin\model\scanwork\Process::select();
        $this->view->assign('models', $models);
        $this->view->assign('processes', $processes);
        
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->with(['model.product', 'process'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        // 获取型号和工序列表
        $modelList = [];
        $models = \app\admin\model\scanwork\ProductModel::with('product')->select();
        foreach ($models as $model) {
            $displayName = $model->product->name . ' - ' . $model->name;
            if ($model->model_code) {
                $displayName .= ' (' . $model->model_code . ')';
            }
            $modelList[$model->id] = $displayName;
        }
        $processList = \app\admin\model\scanwork\Process::where('status', 1)->column('name', 'id');
        
        $this->view->assign('modelList', $modelList);
        $this->view->assign('processList', $processList);
        
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    // 检查是否已存在相同的型号+工序组合
                    $exists = $this->model->where([
                        'model_id' => $params['model_id'],
                        'process_id' => $params['process_id']
                    ])->find();
                    if ($exists) {
                        throw new Exception('该型号的此工序工价已存在，请勿重复设置');
                    }

                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
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
        
        // 获取型号和工序列表
        $modelList = [];
        $models = \app\admin\model\scanwork\ProductModel::with('product')->select();
        foreach ($models as $model) {
            $displayName = $model->product->name . ' - ' . $model->name;
            if ($model->model_code) {
                $displayName .= ' (' . $model->model_code . ')';
            }
            $modelList[$model->id] = $displayName;
        }
        $processList = \app\admin\model\scanwork\Process::where('status', 1)->column('name', 'id');
        
        $this->view->assign('modelList', $modelList);
        $this->view->assign('processList', $processList);
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
                $result = false;
                Db::startTrans();
                try {
                    // 检查是否已存在相同的型号+工序组合（排除当前记录）
                    $exists = $this->model->where([
                        'model_id' => $params['model_id'],
                        'process_id' => $params['process_id']
                    ])->where('id', '<>', $ids)->find();
                    if ($exists) {
                        throw new Exception('该型号的此工序工价已存在，请勿重复设置');
                    }

                    //是否采用模型验证
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
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
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
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    /**
     * 批量设置工价
     */
    public function batch()
    {
        if ($this->request->isPost()) {
            $modelId = $this->request->post('model_id');
            $prices = $this->request->post('prices/a');
            $timePrices = $this->request->post('time_prices/a');
            
            if (!$modelId) {
                $this->error('请选择型号');
            }
            
            if (empty($prices)) {
                $this->error('请至少设置一个工序的工价');
            }
            
            Db::startTrans();
            try {
                foreach ($prices as $processId => $price) {
                    if ($price && $price > 0) {
                        // 检查是否已存在
                        $exists = $this->model->where([
                            'model_id' => $modelId,
                            'process_id' => $processId
                        ])->find();
                        $time_price = isset($timePrices[$processId]) ? $timePrices[$processId] : 0;
                        if ($exists) {
                            // 更新现有记录
                            $exists->save(['price' => $price, 'time_price' => $time_price]);
                        } else {
                            // 创建新记录
                            $this->model->create([
                                'model_id' => $modelId,
                                'process_id' => $processId,
                                'price' => $price,
                                'time_price' => $time_price,
                                'status' => 1
                            ]);
                        }
                    }
                }
                Db::commit();
                $this->success('批量设置成功');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        $this->error('无效的请求');
    }

    /**
     * 获取型号的工价列表
     */
    public function getModelPrices()
    {
        $modelId = $this->request->get('model_id');
        if (!$modelId) {
            $this->error('请选择型号');
        }
        
        $prices = $this->model->with(['process'])
            ->where('model_id', $modelId)
            ->select();
            
        $result = [];
        foreach ($prices as $price) {
            $result[$price->process_id] = $price->price;
        }
        
        $this->success('', null, $result);
    }
} 