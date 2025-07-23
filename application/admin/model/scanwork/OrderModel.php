<?php

namespace app\admin\model\scanwork;

use think\Model;

class OrderModel extends Model
{
    // 表名
    protected $name = 'scanwork_order_model';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 关联订单
    public function order()
    {
        return $this->belongsTo('Order', 'order_id', 'id');
    }

    // 关联型号
    public function model()
    {
        return $this->belongsTo('ProductModel', 'model_id', 'id');
    }

    // 关联分工分配
    public function allocations()
    {
        return $this->hasMany('Allocation', 'model_id', 'model_id')->where('order_id', $this->order_id);
    }

    // 获取已分配数量
    public function getAllocatedQuantityAttr($value, $data)
    {
        return $this->allocations()->sum('quantity');
    }

    // 获取已报工数量
    public function getReportedQuantityAttr($value, $data)
    {
        return $this->allocations()->sum('reported_quantity');
    }

    // 获取进度百分比
    public function getProgressAttr($value, $data)
    {
        $allocated = $this->allocated_quantity;
        $reported = $this->reported_quantity;
        
        if ($allocated == 0) {
            return 0;
        }
        
        return round(($reported / $allocated) * 100, 2);
    }
} 