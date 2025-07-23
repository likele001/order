<?php

namespace app\admin\model\scanwork;

use think\Model;

class ProcessPrice extends Model
{
    // 表名
    protected $name = 'scanwork_process_price';
    
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
} 