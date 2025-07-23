<?php

namespace app\admin\model\scanwork;
use think\facade\Db;


use think\Model;

class Process extends Model
{
    // 表名
    protected $name = 'scanwork_process';
    
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

    // 关联工序工价
    public function processPrices()
    {
        return $this->hasMany('app\admin\model\scanwork\ProcessPrice', 'process_id', 'id');
    }

    // 关联分工分配
    public function allocations()
    {
        return $this->hasMany('app\admin\model\scanwork\Allocation', 'process_id', 'id');
    }

    // 关联报工记录
    public function reports()
    {
        return $this->hasMany('app\admin\model\scanwork\Report', 'process_id', 'id');
    }
} 