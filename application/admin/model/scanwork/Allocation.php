<?php

namespace app\admin\model\scanwork;
use think\Log;

use think\Model;

class Allocation extends Model
{
    // 表名
    protected $name = 'scanwork_allocation';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'work_type_text',
    ];
    
    public function getStatusList()
    {
        return ['0' => __('进行中'), '1' => __('已完成')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getWorkTypeList()
    {
        return ['piece' => '计件', 'time' => '计时'];
    }

    public function getWorkTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['work_type']) ? $data['work_type'] : '');
        $list = $this->getWorkTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 关联订单
    public function order()
    {
        return $this->belongsTo('app\admin\model\scanwork\Order', 'order_id', 'id');
    }

    // 关联产品型号
    public function model()
    {
        return $this->belongsTo('app\admin\model\scanwork\ProductModel', 'model_id', 'id');
    }

    // 关联工序
    public function process()
    {
        return $this->belongsTo('app\admin\model\scanwork\Process', 'process_id', 'id');
    }

    // 关联员工
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id');
    }

    // 关联报工记录
    public function reports()
    {
        return $this->hasMany('Report', 'allocation_id', 'id');
    }

    // 关联二维码
    public function qrcodes()
    {
        return $this->hasMany('Qrcode', 'allocation_id', 'id');
    }
    
    // 获取工价
    public function getPriceAttr($value, $data)
    {
        if (isset($data['process_id']) && isset($data['model_id'])) {
            $processPrice = \app\admin\model\scanwork\ProcessPrice::where([
                'process_id' => $data['process_id'],
                'model_id' => $data['model_id']
            ])->find();
            
            return $processPrice ? $processPrice->price : 0;
        }
        return 0;
    }
    
    // 获取已报数量
    public function getReportedQuantityAttr($value, $data)
    {
        $allocationId = isset($data['id']) ? $data['id'] : $this->id;
        if ($allocationId) {
            $sum = \app\admin\model\scanwork\Report::where('allocation_id', $allocationId)
                ->where('status', 1)
                ->sum('quantity');
            return $sum ? intval($sum) : 0;
        }
        return 0;
    }
    
    // 获取待报数量
    public function getRemainingQuantityAttr($value, $data)
    {
        $reported = $this->getReportedQuantityAttr($value, $data);
        $total = isset($data['quantity']) ? intval($data['quantity']) : 0;
        $remaining = max(0, $total - $reported);
        return $remaining;
    }
} 