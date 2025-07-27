<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\User;
use think\Db;
use think\Request;
use think\Config;

/**
 * 微信小程序登录API
 */
class Wechat extends Api
{
    protected $noNeedLogin = ['login', 'getOpenId'];
    protected $noNeedRight = ['*'];

    /**
     * 微信小程序配置
     */
    private $appId = 'wxccf32ba082446a3d';  // 请替换为您的小程序AppID
    private $appSecret = '8e915b95f27972e1b64f2891d18186c5';  // 请替换为您的小程序AppSecret

    /**
     * 微信小程序登录
     */
    public function login()
    {
        $code = $this->request->post('code');
        $userInfo = $this->request->post('userInfo');
        
        if (!$code) {
            $this->error('缺少code参数');
        }

        // 获取微信openid
        $openidResult = $this->getWechatOpenId($code);
        if (!$openidResult['success']) {
            $this->error($openidResult['message']);
        }

        $openid = $openidResult['data']['openid'];
        $sessionKey = $openidResult['data']['session_key'];

        // 查找是否已存在该openid的用户
        $user = User::where('wechat_openid', $openid)->find();
        
        if (!$user) {
            // 创建新用户
            $userData = [
                'username' => 'wx_' . substr($openid, -8),
                'nickname' => isset($userInfo['nickName']) ? $userInfo['nickName'] : '微信用户',
                'avatar' => isset($userInfo['avatarUrl']) ? $userInfo['avatarUrl'] : '',
                'wechat_openid' => $openid,
                'wechat_session_key' => $sessionKey,
                'status' => 'normal',
                'createtime' => time(),
                'updatetime' => time()
            ];
            
            $user = User::create($userData);
        } else {
            // 更新session_key
            $user->wechat_session_key = $sessionKey;
            $user->updatetime = time();
            $user->save();
        }

        // 生成token
        $token = $this->auth->direct($user->id);

        $this->success('登录成功', [
            'token' => $token,
            'user' => $user,
            'is_bound' => !empty($user->employee_no)
        ]);
    }

    /**
     * 绑定员工号
     */
    public function bindEmployee()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $employeeNo = $this->request->post('employee_no');
        $password = $this->request->post('password', ''); // 可选的验证密码
        
        if (!$employeeNo) {
            $this->error('请输入员工号');
        }

        $user = $this->auth->getUserinfo();
        
        // 检查员工号是否已被其他用户绑定
        $existUser = User::where('employee_no', $employeeNo)
                        ->where('id', '<>', $user['id'])
                        ->find();
        
        if ($existUser) {
            $this->error('该员工号已被其他用户绑定');
        }

        // 这里可以添加员工号验证逻辑，比如检查员工号是否存在于员工表中
        // $employee = Db::name('employee')->where('employee_no', $employeeNo)->find();
        // if (!$employee) {
        //     $this->error('员工号不存在');
        // }

        // 更新用户信息
        $updateData = [
            'employee_no' => $employeeNo,
            'updatetime' => time()
        ];

        $result = User::where('id', $user['id'])->update($updateData);
        
        if ($result) {
            $this->success('绑定成功');
        } else {
            $this->error('绑定失败');
        }
    }

    /**
     * 解绑员工号
     */
    public function unbindEmployee()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $user = $this->auth->getUserinfo();
        
        $result = User::where('id', $user['id'])->update([
            'employee_no' => '',
            'updatetime' => time()
        ]);
        
        if ($result) {
            $this->success('解绑成功');
        } else {
            $this->error('解绑失败');
        }
    }

    /**
     * 获取用户绑定状态
     */
    public function getBindStatus()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $user = $this->auth->getUserinfo();
        
        $this->success('获取成功', [
            'is_bound' => !empty($user['employee_no']),
            'employee_no' => $user['employee_no'] ?? '',
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar']
        ]);
    }

    /**
     * 获取微信OpenID
     */
    private function getWechatOpenId($code)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $params = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $response = $this->httpGet($url, $params);
        $result = json_decode($response, true);

        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return [
                'success' => false,
                'message' => '获取OpenID失败: ' . ($result['errmsg'] ?? '未知错误')
            ];
        }

        return [
            'success' => true,
            'data' => $result
        ];
    }

    /**
     * HTTP GET请求
     */
    private function httpGet($url, $params = [])
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}
