<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 分工分配管理
 *
 * @icon fa fa-tasks
 * @remark 管理生产任务的分工分配
 */
class Allocation extends Backend
{
    /**
     * Allocation模型对象
     * @var \app\admin\model\scanwork\Allocation
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\scanwork\Allocation;
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
                ->with(['order', 'model.product', 'process', 'user', 'reports'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            // 确保每行数据都包含 id 字段
            $rows = [];
            foreach ($list as $item) {
                $row = $item->toArray();
                // 强制确保 id 字段存在
                if (!isset($row['id'])) {
                    $row['id'] = $item->id;
                }
                $rows[] = $row;
            }

            $result = array("total" => $list->total(), "rows" => $rows);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        // 获取订单列表
        $orderList = \app\admin\model\scanwork\Order::where('status', '<>', 2)->column('order_no', 'id');
        $this->view->assign('orderList', $orderList);
        
        // 获取工序列表
        $processList = \app\admin\model\scanwork\Process::where('status', 1)->column('name', 'id');
        $this->view->assign('processList', $processList);
        
        // 获取员工列表
        $userList = \app\common\model\User::where('status', 'normal')->column('nickname', 'id');
        $this->view->assign('userList', $userList);
        
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
                    // 验证数据
                    $this->validateAllocation($params);
                    
                    // 是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? 
                            ($this->modelSceneValidate ? $name . '.add' : $name) : 
                            $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    
                    // 保存数据并获取结果
                    $result = $this->model->allowField(true)->save($params);
                    $allocationId = $this->model->id; // 获取新增记录的ID
                    
                    // 验证记录是否真正创建成功
                    if (!$allocationId) {
                        throw new Exception('分工分配记录创建失败');
                    }
                    
                    // 更新订单状态
                    $order = \app\admin\model\scanwork\Order::find($params['order_id']);
                    if ($order) {
                        $order->updateStatus();
                    } else {
                        throw new Exception('关联的订单不存在');
                    }
                    
                    // 新增分工分配后，直接在分工表写入二维码内容和图片
                    // 使用find()替代get()，确保返回模型实例
                    $allocation = \app\admin\model\scanwork\Allocation::with([
                        'order', 
                        'model.product', 
                        'process', 
                        'user'
                    ])->find($allocationId);
                    
                    if ($allocation) {
                        // 检查所有必要的关联数据
                        if (!$allocation->order) {
                            throw new Exception('未找到关联的订单信息');
                        }
                        if (!$allocation->model || !$allocation->model->product) {
                            throw new Exception('未找到关联的产品型号信息');
                        }
                        if (!$allocation->process) {
                            throw new Exception('未找到关联的工序信息');
                        }
                        if (!$allocation->user) {
                            throw new Exception('未找到关联的用户信息');
                        }
                        
                        $qrData = [
                            'type' => 'allocation',
                            'id' => $allocation->id,
                            'order_no' => $allocation->order->order_no,
                            'product_name' => $allocation->model->product->name,
                            'model_name' => $allocation->model->name,
                            'process_name' => $allocation->process->name,
                            'user_name' => $allocation->user->nickname,
                            'quantity' => $allocation->quantity,
                            'remaining' => $allocation->remaining_quantity,
                            'timestamp' => time()
                        ];
                        $qrContent = json_encode($qrData, JSON_UNESCAPED_UNICODE);
                        
                        // 验证二维码生成方法是否存在
                        if (!method_exists(\app\admin\controller\scanwork\Qrcode::class, 'generateQrCodeImageStatic')) {
                            throw new Exception('Qrcode类中不存在generateQrCodeImageStatic静态方法');
                        }
                        $qrImage = \app\admin\controller\scanwork\Qrcode::generateQrCodeImageStatic($qrContent, $allocation->id);
                        
                        // 直接更新分工表，使用模型更新而非Db类
                        $updateResult = $allocation->save([
                            'qr_content' => $qrContent,
                            'qr_image' => $qrImage,
                            'updatetime' => time() // 增加更新时间戳
                        ]);
                        
                        if ($updateResult === false) {
                            throw new Exception('更新二维码信息失败');
                        }
                    } else {
                        throw new Exception("未找到ID为{$allocationId}的分工分配记录");
                    }
        
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
                
                if ($result !== false && $allocationId) {
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
        
        // 获取订单列表
        $orderList = \app\admin\model\scanwork\Order::where('status', '<>', 2)->column('order_no', 'id');
        $this->view->assign('orderList', $orderList);
        
        // 获取员工列表
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
                $result = false;
                Db::startTrans();
                try {
                    // 验证数据
                    $this->validateAllocation($params, $ids);
                    
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    
                    // 更新订单状态
                    $order = \app\admin\model\scanwork\Order::get($params['order_id']);
                    $order->updateStatus();
                    
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
                    // 检查是否有关联的报工记录
                    $reportCount = \app\admin\model\scanwork\Report::where('allocation_id', $item->id)->count();
                    if ($reportCount > 0) {
                        throw new Exception("分工分配【{$item->id}】下还有{$reportCount}个报工记录，请先删除相关报工");
                    }
                    
                    $count += $item->delete();
                }
                
                // 更新相关订单状态
                $orderIds = array_unique(array_column($list, 'order_id'));
                foreach ($orderIds as $orderId) {
                    $order = \app\admin\model\scanwork\Order::get($orderId);
                    if ($order) {
                        $order->updateStatus();
                    }
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
     * 批量分配
     */
    public function batch()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            if (!$params['order_id'] || !$params['allocations']) {
                $this->error('参数不完整');
            }

            $orderId = $params['order_id'];
            $allocations = $params['allocations'];
            
            // 处理JSON格式的分配数据
            if (is_string($allocations)) {
                $allocations = json_decode($allocations, true);
            }
            
            if (!is_array($allocations)) {
                $this->error('分配数据格式错误');
            }

            Db::startTrans();
            try {
                foreach ($allocations as $allocation) {
                    if ($allocation['quantity'] > 0) {
                        // 验证数据
                        $this->validateAllocation([
                            'order_id' => $orderId,
                            'model_id' => $allocation['model_id'],
                            'process_id' => $allocation['process_id'],
                            'user_id' => $allocation['user_id'],
                            'quantity' => $allocation['quantity']
                        ]);
                        
                        // 创建分配记录
                        $new = $this->model->create([
                            'order_id' => $orderId,
                            'model_id' => $allocation['model_id'],
                            'process_id' => $allocation['process_id'],
                            'user_id' => $allocation['user_id'],
                            'quantity' => $allocation['quantity'],
                            'reported_quantity' => 0,
                            'status' => 0
                        ]);
                        // 新增分工分配后，写入二维码表
                        if ($new && $new->id) {
                            $allocationObj = \app\admin\model\scanwork\Allocation::with(['order', 'model.product', 'process', 'user'])->get($new->id);
                            if ($allocationObj && $allocationObj->order && $allocationObj->model && $allocationObj->model->product && $allocationObj->process && $allocationObj->user) {
                                $qrData = [
                                    'type' => 'allocation',
                                    'id' => $allocationObj->id,
                                    'order_no' => $allocationObj->order->order_no,
                                    'product_name' => $allocationObj->model->product->name,
                                    'model_name' => $allocationObj->model->name,
                                    'process_name' => $allocationObj->process->name,
                                    'user_name' => $allocationObj->user->nickname,
                                    'quantity' => $allocationObj->quantity,
                                    'remaining' => $allocationObj->remaining_quantity,
                                    'timestamp' => time()
                                ];
                                $qrContent = json_encode($qrData, JSON_UNESCAPED_UNICODE);
                                $qrImage = \app\admin\controller\scanwork\Qrcode::generateQrCodeImageStatic($qrContent, $allocationObj->id);
                                \think\Db::name('scanwork_qrcode')->insert([
                                    'allocation_id' => $allocationObj->id,
                                    'qr_content' => $qrContent,
                                    'qr_image' => $qrImage,
                                    'scan_count' => 0,
                                    'status' => 0,
                                    'createtime' => time(),
                                    'updatetime' => time()
                                ]);
                            }
                        }
                    }
                }
                
                // 更新订单状态
                $order = \app\admin\model\scanwork\Order::get($orderId);
                $order->updateStatus();
                
                Db::commit();
                $this->success('批量分配成功');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }

        // 获取订单信息
        $orderId = $this->request->get('order_id');
        if ($orderId) {
            $order = \app\admin\model\scanwork\Order::with(['orderModels.model.product'])->find($orderId);
            $this->view->assign('order', $order);
        }

        // 获取工序列表
        $processList = \app\admin\model\scanwork\Process::where('status', 1)->column('name', 'id');
        $this->view->assign('processList', $processList);

        // 获取员工列表
        $userList = \app\common\model\User::where('status', 'normal')->column('nickname', 'id');
        $this->view->assign('userList', $userList);

        return $this->view->fetch();
    }

    /**
     * 获取订单型号
     */
    public function getOrderModels()
    {
        $orderId = $this->request->get('order_id');
        if (!$orderId) {
            $this->error('参数错误');
        }

        $orderModels = \app\admin\model\scanwork\OrderModel::with(['model.product'])
            ->where('order_id', $orderId)
            ->select();

        // 计算每个型号的剩余可分配数量
        foreach ($orderModels as $orderModel) {
            // 获取该型号已分配的总数量
            $allocatedQuantity = $this->model->where([
                'order_id' => $orderId,
                'model_id' => $orderModel->model_id
            ])->sum('quantity');
            
            // 计算剩余可分配数量
            $remainingQuantity = $orderModel->quantity - $allocatedQuantity;
            $orderModel->remaining_quantity = max(0, $remainingQuantity);
            
            // 调试信息
            \think\Log::write("型号ID: {$orderModel->model_id}, 订单数量: {$orderModel->quantity}, 已分配: {$allocatedQuantity}, 剩余: {$orderModel->remaining_quantity}", 'debug');
        }

        $this->success('', null, $orderModels);
    }

    /**
     * 验证分配数据
     */
    private function validateAllocation($params, $excludeId = null)
    {
        // 验证订单是否存在
        $order = \app\admin\model\scanwork\Order::get($params['order_id']);
        if (!$order) {
            throw new Exception('订单不存在');
        }

        // 验证型号是否属于该订单
        $orderModel = \app\admin\model\scanwork\OrderModel::where([
            'order_id' => $params['order_id'],
            'model_id' => $params['model_id']
        ])->find();
        if (!$orderModel) {
            throw new Exception('所选型号不属于该订单');
        }

        // 验证工序工价是否存在
        $price = \app\admin\model\scanwork\ProcessPrice::where([
            'model_id' => $params['model_id'],
            'process_id' => $params['process_id']
        ])->find();
        if (!$price) {
            throw new Exception('该型号的此工序未设置工价');
        }

        // 验证分配数量不超过订单数量
        $allocatedQuantity = $this->model->where([
            'order_id' => $params['order_id'],
            'model_id' => $params['model_id'],
            'process_id' => $params['process_id']
        ]);
        if ($excludeId) {
            $allocatedQuantity->where('id', '<>', $excludeId);
        }
        $allocatedQuantity = $allocatedQuantity->sum('quantity');
        
        if (($allocatedQuantity + $params['quantity']) > $orderModel->quantity) {
            throw new Exception('分配数量超过订单数量');
        }
    }
} 