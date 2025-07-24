<?php

namespace app\admin\model\scanwork;

use think\Model;

class Treporttime extends Model
{
    protected $name = 'scanwork_treporttime';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $append = [
        'status_text',
    ];

    public function getStatusList()
    {
        return ['0' => __('待确认'), '1' => __('已确认')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 关联分工
    public function tallocationtime()
    {
        return $this->belongsTo('app\admin\model\scanwork\Tallocationtime', 'tallocationtime_id', 'id');
    }

    // 关联员工
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id');
    }
}