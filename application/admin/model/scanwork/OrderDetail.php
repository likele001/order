<?php

namespace app\admin\model\scanwork;

use think\Model;

class OrderDetail extends Model
{
    // 表名
    protected $name = 'scanwork_orderdetail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

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
} 