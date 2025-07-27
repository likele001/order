<?php

namespace app\common\Model;

use think\Model;

class WechatBind extends Model
{
    // 表名
    protected $name = 'wechat_bind';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 过期时间：5分钟
    const EXPIRE_TIME = 300;
    
    /**
     * 生成绑定凭证
     * @param int $userId
     * @return string
     */
    public static function generateToken($userId)
    {
        $token = md5(uniqid() . $userId . time() . mt_rand(1000, 9999));
        
        self::create([
            'user_id' => $userId,
            'token' => $token,
            'expire_time' => time() + self::EXPIRE_TIME,
            'status' => 0
        ]);
        
        return $token;
    }
    
    /**
     * 验证绑定凭证
     * @param string $token
     * @return array|bool
     */
    public static function verifyToken($token)
    {
        $bind = self::where('token', $token)
            ->where('status', 0)
            ->where('expire_time', '>', time())
            ->find();
            
        if (!$bind) {
            return false;
        }
        
        // 标记为已使用
        $bind->status = 1;
        $bind->save();
        
        return [
            'user_id' => $bind->user_id,
            'token' => $bind->token
        ];
    }
}
