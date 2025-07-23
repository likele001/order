<?php

namespace app\admin\model\scanwork;

use think\Model;

class Product extends Model
{
    // 表名
    protected $name = 'scanwork_product';
    
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

    // 关联型号
    public function models()
    {
        return $this->hasMany('ProductModel', 'product_id', 'id');
    }
} 