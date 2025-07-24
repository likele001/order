<?php

namespace app\admin\model\scanwork;

use think\Model;

class Twage extends Model
{
    protected $name = 'scanwork_twage';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 关联员工
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id');
    }
} 