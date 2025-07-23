<?php

namespace app\admin\model\scanwork;

use think\Model;

class Order extends Model
{
    // 表名
    protected $name = 'scanwork_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    // 订单状态
    public function getStatusList()
    {
        return [
            '0' => __('待生产'),
            '1' => __('生产中'),
            '2' => __('已完成')
        ];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 关联订单型号
    public function orderModels()
    {
        return $this->hasMany('app\admin\model\scanwork\OrderModel', 'order_id', 'id');
    }

    // 关联分工分配
    public function allocations()
    {
        return $this->hasMany('app\admin\model\scanwork\Allocation', 'order_id', 'id');
    }

    // 获取订单总数量
    public function getTotalQuantityAttr($value, $data)
    {
        return $this->orderModels()->sum('quantity');
    }

    // 获取订单进度
    public function getProgressAttr($value, $data)
    {
        $totalAllocated = $this->allocations()->sum('quantity');
        $totalReported = $this->allocations()->sum('reported_quantity');
        
        if ($totalAllocated == 0) {
            return 0;
        }
        
        return round(($totalReported / $totalAllocated) * 100, 2);
    }

    // 自动更新订单状态
    public function updateStatus()
    {
        $totalAllocated = $this->allocations()->sum('quantity');
        $totalReported = $this->allocations()->sum('reported_quantity');
        
        if ($totalAllocated == 0) {
            $this->save(['status' => 0]); // 待生产
        } elseif ($totalReported >= $totalAllocated) {
            $this->save(['status' => 2]); // 已完成
        } else {
            $this->save(['status' => 1]); // 生产中
        }
    }
} 