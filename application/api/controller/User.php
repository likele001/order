<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'miniprogram_login', 'bind_account', 'employee_list'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录（账号+密码）
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $email = $this->request->post('email');
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @ApiParams (name="avatar", type="string", required=true, description="头像地址")
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="nickname", type="string", required=true, description="昵称")
     * @ApiParams (name="bio", type="string", required=true, description="个人简介")
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="platform", type="string", required=true, description="平台名称")
     * @ApiParams (name="code", type="string", required=true, description="Code码")
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="newpassword", type="string", required=true, description="新密码")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function resetpwd()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 小程序登录（微信code换openid，查找/注册用户，标准token写入）
     */
    public function miniprogram_login()
    {
        $code = $this->request->post('code');
        if (!$code) {
            $this->error('缺少code');
        }
        $appid = 'wxccf32ba082446a3d';
        $secret = '8e915b95f27972e1b64f2891d18186c5';
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        $res = json_decode(file_get_contents($url), true);
        if (empty($res['openid'])) {
            $this->error('微信登录失败', null, $res);
        }
        $openid = $res['openid'];
        $user = \app\common\model\User::get(['openid' => $openid]);
        if (!$user) {
            // 注册新用户
            $username = 'wx_' . \fast\Random::alnum(8);
            $user = \app\common\model\User::create([
                'username' => $username,
                'nickname' => '微信用户',
                'openid' => $openid,
                'createtime' => time(),
                'logintime' => time(),
                'status' => 'normal'
            ]);
        }
        // 标准token写入
        $this->auth->direct($user->id);
        $data = ['userinfo' => $this->auth->getUserinfo()];
        $this->success('登录成功', $data, 0);
    }

    /**
     * 微信用户绑定已有员工账号（标准token写入）
     */
    public function bind_account()
    {
        $openid = $this->request->post('openid');
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        
        // 添加调试日志
        file_put_contents('/tmp/bind_debug.log', date('Y-m-d H:i:s') . ' 绑定请求: ' . json_encode([
            'openid' => $openid,
            'account' => $account,
            'password' => $password ? '***' : 'empty'
        ]) . PHP_EOL, FILE_APPEND);
        
        if (!$openid || !$account || !$password) {
            $this->error('参数不完整');
        }
        $user = \app\common\model\User::where('group_id', 2)->where(function($query) use ($account) {
            $query->where('username', $account)->whereOr('mobile', $account);
        })->find();
        
        // 添加调试日志
        file_put_contents('/tmp/bind_debug.log', date('Y-m-d H:i:s') . ' 查找用户结果: ' . ($user ? json_encode(['id' => $user['id'], 'username' => $user['username'], 'group_id' => $user['group_id']]) : 'null') . PHP_EOL, FILE_APPEND);
        
        if (!$user) {
            $this->error('员工账号不存在或不属于员工组');
        }
        
        $inputPasswordHash = md5(md5($password) . $user['salt']);
        $storedPasswordHash = $user['password'];
        
        // 添加调试日志
        file_put_contents('/tmp/bind_debug.log', date('Y-m-d H:i:s') . ' 密码验证: ' . json_encode([
            'input_hash' => $inputPasswordHash,
            'stored_hash' => $storedPasswordHash,
            'match' => $inputPasswordHash === $storedPasswordHash
        ]) . PHP_EOL, FILE_APPEND);
        
        if ($inputPasswordHash !== $storedPasswordHash) {
            $this->error('密码错误');
        }
        // 绑定 openid
        $user->openid = $openid;
        $user->save();
        
        // 添加调试日志
        file_put_contents('/tmp/bind_debug.log', date('Y-m-d H:i:s') . ' 绑定成功: ' . json_encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'openid' => $openid
        ]) . PHP_EOL, FILE_APPEND);
        
        // 标准token写入
        $this->auth->direct($user->id);
        $data = ['userinfo' => $this->auth->getUserinfo()];
        $this->success('绑定成功', $data, 0);
    }

    /**
     * 获取员工账号列表（group_id=2）
     * @ApiMethod (GET)
     */
    public function employee_list()
    {
        $list = \think\Db::name('user')
            ->where('group_id', 2)
            ->field('id,username,nickname,mobile')
            ->select();
        $this->success('员工列表', $list, 0);
    }

    /**
     * 生成员工绑定二维码
     * @ApiMethod (POST)
     */
    public function generate_bind_qr()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        
        if (!$username || !$password) {
            $this->error('参数不完整');
        }
        
        // 验证员工账号
        $user = \think\Db::name('user')
            ->where('group_id', 2)
            ->where(function($query) use ($username) {
                $query->where('username', $username)->whereOr('mobile', $username);
            })
            ->find();
            
        if (!$user) {
            $this->error('员工账号不存在或不属于员工组');
        }
        
        if ($user['password'] !== md5(md5($password) . $user['salt'])) {
            $this->error('密码错误');
        }
        
        // 生成绑定二维码内容
        $qrContent = "bind:{$username}:{$password}";
        
        // 这里可以集成二维码生成库，暂时返回内容
        $data = [
            'qr_content' => $qrContent,
            'username' => $user['username'],
            'nickname' => $user['nickname'],
            'expire_time' => time() + 300 // 5分钟有效期
        ];
        
        $this->success('二维码生成成功', $data, 0);
    }

    /**
     * 生成绑定token（PC端调用）
     * @ApiMethod (POST)
     */
    public function generate_bind_token()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $user_id = $this->auth->id;
        
        // 检查是否已经是员工
        $user = \think\Db::name('user')->where('id', $user_id)->find();
        if ($user['group_id'] != 2) {
            $this->error('只有员工账号才能生成绑定token');
        }
        
        // 生成唯一token
        $token = md5(uniqid() . time() . rand(1000, 9999));
        $expire_time = time() + 300; // 5分钟有效期
        
        // 保存token
        \think\Db::name('user_bind_token')->insert([
            'token' => $token,
            'user_id' => $user_id,
            'status' => 0,
            'expire_time' => $expire_time,
            'createtime' => time(),
            'updatetime' => time()
        ]);
        
        // 生成二维码内容
        $qrContent = "bind_token:{$token}";
        
        $this->success('生成成功', [
            'token' => $token,
            'qr_content' => $qrContent,
            'expire_time' => $expire_time,
            'user_info' => [
                'username' => $user['username'],
                'nickname' => $user['nickname']
            ]
        ], 0);
    }

    /**
     * 通过token绑定（小程序调用）
     * @ApiMethod (POST)
     */
    public function bind_by_token()
    {
        $token = $this->request->post('token');
        $openid = $this->request->post('openid');
        
        if (!$token || !$openid) {
            $this->error('参数不完整');
        }
        
        // 查找并验证token
        $bindToken = \think\Db::name('user_bind_token')
            ->where('token', $token)
            ->where('status', 0)
            ->where('expire_time', '>', time())
            ->find();
            
        if (!$bindToken) {
            $this->error('绑定token无效或已过期');
        }
        
        // 获取员工信息
        $user = \think\Db::name('user')->where('id', $bindToken['user_id'])->find();
        if (!$user || $user['group_id'] != 2) {
            $this->error('员工账号不存在');
        }
        
        try {
            \think\Db::startTrans();
            
            // 更新token状态
            \think\Db::name('user_bind_token')
                ->where('id', $bindToken['id'])
                ->update([
                    'openid' => $openid,
                    'status' => 1,
                    'updatetime' => time()
                ]);
            
            // 更新用户openid
            \think\Db::name('user')
                ->where('id', $user['id'])
                ->update([
                    'openid' => $openid,
                    'updatetime' => time()
                ]);
            
            \think\Db::commit();
            
            $this->success('绑定成功', [
                'userinfo' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nickname' => $user['nickname'],
                    'group_id' => $user['group_id'],
                    'openid' => $openid
                ]
            ], 0);
            
        } catch (\Exception $e) {
            \think\Db::rollback();
            $this->error('绑定失败：' . $e->getMessage());
        }
    }

    /**
     * 查询绑定状态（PC端轮询）
     * @ApiMethod (GET)
     */
    public function bind_status()
    {
        $token = $this->request->get('token');
        
        if (!$token) {
            $this->error('参数不完整');
        }
        
        $bindToken = \think\Db::name('user_bind_token')
            ->where('token', $token)
            ->find();
            
        if (!$bindToken) {
            $this->error('token不存在');
        }
        
        if ($bindToken['expire_time'] < time()) {
            $this->error('token已过期');
        }
        
        $status = $bindToken['status'];
        $data = [
            'status' => $status, // 0未使用，1已使用，2已过期
            'expire_time' => $bindToken['expire_time']
        ];
        
        if ($status == 1) {
            // 已绑定，返回用户信息
            $user = \think\Db::name('user')->where('id', $bindToken['user_id'])->find();
            $data['user_info'] = [
                'username' => $user['username'],
                'nickname' => $user['nickname']
            ];
        }
        
        $this->success('查询成功', $data, 0);
    }

    /**
     * 解绑账号
     * @ApiMethod (POST)
     */
    public function unbind_account()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        $user_id = $this->auth->id;
        
        // 清除openid
        \think\Db::name('user')
            ->where('id', $user_id)
            ->update([
                'openid' => '',
                'updatetime' => time()
            ]);
        
        $this->success('解绑成功');
    }
}
