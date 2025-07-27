<?php

namespace app\api\controller;

use app\common\Model\User;
use app\common\Model\WechatBind;
use think\Controller;
use think\Exception;
use think\facade\Request;

class UserBind extends Controller
{
    /**
     * 验证绑定凭证并完成绑定
     */
    public function verify()
    {
        $params = Request::param();
        
        // 验证参数
        if (empty($params['token']) || empty($params['openid'])) {
            return json(['code' => 1, 'msg' => '参数不完整']);
        }
        
        try {
            // 验证绑定凭证
            $verifyResult = WechatBind::verifyToken($params['token']);
            if (!$verifyResult) {
                return json(['code' => 1, 'msg' => '绑定凭证无效或已过期']);
            }
            
            // 检查openid是否已被绑定
            if (User::isOpenidBound($params['openid'], $verifyResult['user_id'])) {
                return json(['code' => 1, 'msg' => '该微信账号已绑定其他用户']);
            }
            
            // 获取用户并绑定
            $user = User::get($verifyResult['user_id']);
            if (!$user) {
                return json(['code' => 1, 'msg' => '用户不存在']);
            }
            
            $user->bindWechat($params['openid'], $params['unionid'] ?? '');
            
            return json([
                'code' => 0,
                'msg' => '绑定成功',
                'data' => [
                    'user_id' => $user->id
                ]
            ]);
            
        } catch (Exception $e) {
            return json(['code' => 1, 'msg' => '绑定失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取微信openid
     */
    public function getOpenid()
    {
        $code = Request::param('code');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => '缺少code参数']);
        }
        
        // 获取配置
        $config = config('wechat.mini_program');
        if (empty($config['app_id']) || empty($config['secret'])) {
            return json(['code' => 1, 'msg' => '请先配置小程序AppID和AppSecret']);
        }
        
        // 调用微信接口
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$config['app_id']}&secret={$config['secret']}&js_code={$code}&grant_type=authorization_code";
        
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        
        if (isset($result['errcode'])) {
            return json(['code' => 1, 'msg' => '获取openid失败：' . ($result['errmsg'] ?? '未知错误')]);
        }
        
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'openid' => $result['openid'] ?? '',
                'unionid' => $result['unionid'] ?? ''
            ]
        ]);
    }
}
