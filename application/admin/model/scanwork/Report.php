<?php

namespace app\admin\model\scanwork;

use think\Model;

class Report extends Model
{
    // 表名
    protected $name = 'scanwork_report';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    public function getStatusList()
    {
        return ['0' => __('待审核'), '1' => __('已确认'), '2' => __('已拒绝')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 关联分工分配
    public function allocation()
    {
        return $this->belongsTo('app\admin\model\scanwork\Allocation', 'allocation_id', 'id');
    }

    // 关联员工
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id');
    }

    // 关联订单
    public function order()
    {
        return $this->belongsTo('Order', 'order_id', 'id');
    }

    // 关联产品型号
    public function model()
    {
        return $this->belongsTo('ProductModel', 'model_id', 'id');
    }

    // 关联工序
    public function process()
    {
        return $this->belongsTo('Process', 'process_id', 'id');
    }
    
    /**
     * 确认报工
     */
    public function confirm()
    {
        if ($this->status == 1) {
            return true; // 已经确认过了
        }
        
        // 获取分配记录
        $allocation = $this->allocation;
        if (!$allocation) {
            throw new \Exception('分配记录不存在');
        }
        
        // 计算工资
        $price = $allocation->price; // 使用Allocation模型的getPriceAttr方法
        $this->wage = $price * $this->quantity;
        
        // 更新状态
        $this->status = 1;
        
        // 保存
        $result = $this->save();
        
        if ($result) {
            // 检查是否完成
            $reportedQuantity = $allocation->reports()->where('status', 1)->sum('quantity');
            if ($reportedQuantity >= $allocation->quantity) {
                $allocation->status = 1; // 已完成
                $allocation->save();
            }
        }
        
        return $result;
    }
    
    /**
     * 拒绝报工
     */
    public function reject($reason = '')
    {
        if ($this->status == 2) {
            return true; // 已经拒绝过了
        }
        
        // 更新状态为拒绝
        $this->status = 2;
        $this->reject_reason = $reason;
        
        return $this->save();
    }
} 