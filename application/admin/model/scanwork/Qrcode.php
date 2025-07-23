<?php

namespace app\admin\model\scanwork;

use think\Model;

class Qrcode extends Model
{
    // 表名
    protected $name = 'scanwork_qrcode';
    
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
        return ['0' => __('未使用'), '1' => __('已使用')];
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
        return $this->belongsTo('Allocation', 'allocation_id', 'id');
    }
} 