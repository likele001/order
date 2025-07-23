<?php

namespace app\admin\model\scanwork;

use think\Model;

class ProductModel extends Model
{
    // 表名
    protected $name = 'scanwork_model';
    
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
        return ['0' => __('禁用'), '1' => __('正常')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 关联产品
    public function product()
    {
        return $this->belongsTo('app\admin\model\scanwork\Product', 'product_id', 'id');
    }

    // 关联工序工价
    public function processPrices()
    {
        return $this->hasMany('ProcessPrice', 'model_id', 'id');
    }

    // 关联订单明细
    public function orderDetails()
    {
        return $this->hasMany('OrderDetail', 'model_id', 'id');
    }

    // 关联分工分配
    public function allocations()
    {
        return $this->hasMany('Allocation', 'model_id', 'id');
    }

    // 获取完整的型号显示名称（产品名-型号名-型号编号）
    public function getFullNameAttr($value, $data)
    {
        $productName = isset($data['product']['name']) ? $data['product']['name'] : '';
        $modelName = isset($data['name']) ? $data['name'] : '';
        $modelCode = isset($data['model_code']) ? $data['model_code'] : '';
        
        $fullName = $productName . ' - ' . $modelName;
        if ($modelCode) {
            $fullName .= ' (' . $modelCode . ')';
        }
        
        return $fullName;
    }
}