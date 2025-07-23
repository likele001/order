<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 订单管理
 *
 * @icon fa fa-shopping-cart
 * @remark 管理工厂生产订单信息
 */
class Order extends Backend
{
    /**
     * Order模型对象
     * @var \app\admin\model\scanwork\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Order;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->with(['orderModels.model.product'])
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
        // 获取产品型号列表
        $modelList = [];
        $models = \app\admin\model\scanwork\ProductModel::with('product')->select();
        foreach ($models as $model) {
            $displayName = $model->product->name . ' - ' . $model->name;
            if ($model->model_code) {
                $displayName .= ' (' . $model->model_code . ')';
            }
            $modelList[$model->id] = $displayName;
        }
        $this->view->assign('modelList', $modelList);
        
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $modelData = $this->request->post("models");
            
            // 处理JSON格式的型号数据
            if (is_string($modelData)) {
                $modelData = json_decode($modelData, true);
            }
            
            if ($params && $modelData) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                
                // 生成订单号
                $params['order_no'] = $this->generateOrderNo();
                
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    
                    // 保存订单
                    $result = $this->model->allowField(true)->save($params);
                    
                    // 保存订单型号
                    $totalQuantity = 0;
                    foreach ($modelData as $modelId => $quantity) {
                        if ($quantity > 0) {
                            \app\admin\model\scanwork\OrderModel::create([
                                'order_id' => $this->model->id,
                                'model_id' => $modelId,
                                'quantity' => $quantity
                            ]);
                            $totalQuantity += $quantity;
                        }
                    }
                    
                    // 更新订单总数量
                    $this->model->save(['total_quantity' => $totalQuantity]);
                    
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
        
        // 获取产品型号列表
        $modelList = [];
        $models = \app\admin\model\scanwork\ProductModel::with('product')->select();
        foreach ($models as $model) {
            $displayName = $model->product->name . ' - ' . $model->name;
            if ($model->model_code) {
                $displayName .= ' (' . $model->model_code . ')';
            }
            $modelList[$model->id] = $displayName;
        }
        $this->view->assign('modelList', $modelList);
        
        // 获取订单型号数据
        $orderModels = \app\admin\model\scanwork\OrderModel::where('order_id', $ids)->select();
        $this->view->assign('orderModels', $orderModels);
        
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $modelData = $this->request->post("models");
            
            // 处理JSON格式的型号数据
            if (is_string($modelData)) {
                $modelData = json_decode($modelData, true);
            }
            
            if ($params && $modelData) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    
                    // 更新订单
                    $result = $row->allowField(true)->save($params);
                    
                    // 删除原有订单型号
                    \app\admin\model\scanwork\OrderModel::where('order_id', $ids)->delete();
                    
                    // 保存新的订单型号
                    $totalQuantity = 0;
                    foreach ($modelData as $modelId => $quantity) {
                        if ($quantity > 0) {
                            \app\admin\model\scanwork\OrderModel::create([
                                'order_id' => $ids,
                                'model_id' => $modelId,
                                'quantity' => $quantity
                            ]);
                            $totalQuantity += $quantity;
                        }
                    }
                    
                    // 更新订单总数量
                    $row->save(['total_quantity' => $totalQuantity]);
                    
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
                    // 检查是否有关联的分工分配
                    $allocationCount = \app\admin\model\scanwork\Allocation::where('order_id', $item->id)->count();
                    if ($allocationCount > 0) {
                        throw new Exception("订单【{$item->order_no}】下还有{$allocationCount}个分工分配，请先删除相关分工");
                    }
                    
                    // 删除订单型号
                    \app\admin\model\scanwork\OrderModel::where('order_id', $item->id)->delete();
                    
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
     * 查看详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 获取订单型号详情
        $orderModels = \app\admin\model\scanwork\OrderModel::with(['model.product'])->where('order_id', $ids)->select();
        
        // 获取分工分配详情
        $allocations = \app\admin\model\scanwork\Allocation::with(['model.product', 'process', 'user'])
            ->where('order_id', $ids)
            ->select();
        
        $this->view->assign('row', $row);
        $this->view->assign('orderModels', $orderModels);
        $this->view->assign('allocations', $allocations);
        
        return $this->view->fetch();
    }

    /**
     * 生成订单号
     */
    private function generateOrderNo()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return $prefix . $date . $random;
    }
} 