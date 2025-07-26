<?php

namespace app\common\model;

use think\Model;

class ScanworkAllocation extends Model
{
    // 表名
    protected $name = 'scanwork_allocation';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [];

    // 关联或其他方法可以根据需要添加
}