<?php

namespace app\admin\controller;

use app\common\Model\User;
use app\common\Model\WechatBind;
use think\Controller;
use think\Db;
use think\Exception;

class UserBind extends Controller
{
    // 初始化方法，验证登录
    public function initialize()
    {
        parent::initialize();
        // 验证用户是否登录
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', 'user/login');
        }
    }
    
    /**
     * 微信绑定页面
     */
    public function wechat()
    {
        $user = $this->auth->getUser();
        
        // 生成绑定凭证
        $token = WechatBind::generateToken($user['id']);
        
        // 生成二维码内容
        $qrcodeContent = url('api/UserBind/verify', ['token' => $token], true, true);
        
        $this->assign([
            'user' => $user,
            'qrcode' => $qrcodeContent,
            'token' => $token
        ]);
        
        return $this->fetch();
    }
    
    /**
     * 检查绑定状态
     */
    public function checkStatus()
    {
        $user = $this->auth->getUser();
        
        if (!empty($user['wechat_openid'])) {
            return json([
                'code' => 0,
                'msg' => '绑定成功',
                'data' => true
            ]);
        }
        
        return json([
            'code' => 1,
            'msg' => '未绑定',
            'data' => false
        ]);
    }
    
    /**
     * 解除绑定
     */
    public function unbindWechat()
    {
        try {
            $user = $this->auth->getUser();
            $result = $user->unbindWechat();
            
            if ($result) {
                return json(['code' => 0, 'msg' => '解除绑定成功']);
            }
            
            return json(['code' => 1, 'msg' => '解除绑定失败']);
            
        } catch (Exception $e) {
            return json(['code' => 1, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }
}
