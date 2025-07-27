<?php

namespace app\index\controller;

use addons\wechat\model\WechatCaptcha;
use app\common\controller\Frontend;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Attachment;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Session;
use think\Validate;
use think\Db;

/**
 * 会员中心
 */
class User extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login', 'register', 'third'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'), '/');
        }

        //监听注册登录退出的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_register_successed', function ($user) use ($auth) {
            Cookie::set('uid', $user->id);
            Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->view->assign('title', __('User center'));
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $user = $this->auth->getUser();
    
        $userId = $this->auth->id;
        $today = date('Y-m-d');
        $startTime = strtotime($today . ' 00:00:00');
        $endTime = strtotime($today . ' 23:59:59');
    
        // 今日报工数量（已确认）
        $todayReportCount = Db::name('scanwork_report')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->where('createtime', 'between', [$startTime, $endTime])
            ->sum('quantity');
    
        // 今日工资总额（已确认）
        $todayWage = Db::name('scanwork_report')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->where('createtime', 'between', [$startTime, $endTime])
            ->sum('wage');
    
        // 待确认报工数量
        $pendingReportCount = Db::name('scanwork_report')
            ->where('user_id', $userId)
            ->where('status', 0)
            ->sum('quantity');
    
        // 用户自己的报工任务（可根据实际需求调整字段）
        $tasks = Db::name('scanwork_allocation')
            ->alias('a')
            ->join('scanwork_order o', 'a.order_id = o.id')
            ->join('scanwork_model m', 'a.model_id = m.id')
            ->join('scanwork_product p', 'm.product_id = p.id')
            ->join('scanwork_process pr', 'a.process_id = pr.id')
            ->where('a.user_id', $userId)
            ->where('a.status', 0)
            ->field('a.*, o.order_no, p.name as product_name, m.name as model_name, pr.name as process_name')
            ->order('a.createtime desc')
            ->select();
    
        $this->view->assign([
            'user' => $user,
            'todayReportCount' => $todayReportCount,
            'todayWage' => $todayWage ?: 0,
            'pendingReportCount' => $pendingReportCount,
            'tasks' => $tasks,
        ]);

        return $this->view->fetch();
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $url = $this->request->request('url', '', 'url_clean');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ? $url : url('user/index'));
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password', '', null);
            $email = $this->request->post('email');
            $mobile = $this->request->post('mobile', '');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:6,30',
                'email'     => 'require|email',
                'mobile'    => 'regex:/^1\d{10}$/',
                '__token__' => 'require|token',
            ];

            $msg = [
                'username.require' => 'Username can not be empty',
                'username.length'  => 'Username must be 3 to 30 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
                'email'            => 'Email is incorrect',
                'mobile'           => 'Mobile is incorrect',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'email'     => $email,
                'mobile'    => $mobile,
                '__token__' => $token,
            ];
            //验证码
            $captchaResult = true;
            $captchaType = config("fastadmin.user_register_captcha");
            if ($captchaType) {
                if ($captchaType == 'mobile') {
                    $captchaResult = Sms::check($mobile, $captcha, 'register');
                } elseif ($captchaType == 'email') {
                    $captchaResult = Ems::check($email, $captcha, 'register');
                } elseif ($captchaType == 'wechat') {
                    $captchaResult = WechatCaptcha::check($captcha, 'register');
                } elseif ($captchaType == 'text') {
                    $captchaResult = \think\Validate::is($captcha, 'captcha');
                }
            }
            if (!$captchaResult) {
                $this->error(__('Captcha is incorrect'));
            }
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            if ($this->auth->register($username, $password, $email, $mobile)) {
                $this->success(__('Sign up successful'), $url ? $url : url('user/index'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER', '', 'url_clean');
        if (!$url && $referer && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('captchaType', config('fastadmin.user_register_captcha'));
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Register'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $url = $this->request->request('url', '', 'url_clean');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ?: url('user/index'));
        }
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password', '', null);
            $keeplogin = (int)$this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'account'   => 'require|length:3,50',
                'password'  => 'require|length:6,30',
                '__token__' => 'require|token',
            ];

            $msg = [
                'account.require'  => 'Account can not be empty',
                'account.length'   => 'Account must be 3 to 50 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'account'   => $account,
                'password'  => $password,
                '__token__' => $token,
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            if ($this->auth->login($account, $password)) {
                $this->success(__('Logged in successful'), $url ? $url : url('user/index'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER', '', 'url_clean');
        if (!$url && $referer && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if ($this->request->isPost()) {
            $this->token();
            //退出本站
            $this->auth->logout();
            $this->success(__('Logout successful'), url('user/index'));
        }
        $html = "<form id='logout_submit' name='logout_submit' action='' method='post'>" . token() . "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['logout_submit'].submit();</script>";

        return $html;
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $this->view->assign('title', __('Profile'));
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post("oldpassword", '', null);
            $newpassword = $this->request->post("newpassword", '', null);
            $renewpassword = $this->request->post("renewpassword", '', null);
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword'   => 'require|regex:\S{6,30}',
                'newpassword'   => 'require|regex:\S{6,30}',
                'renewpassword' => 'require|regex:\S{6,30}|confirm:newpassword',
                '__token__'     => 'token',
            ];

            $msg = [
                'renewpassword.confirm' => __('Password and confirm password don\'t match')
            ];
            $data = [
                'oldpassword'   => $oldpassword,
                'newpassword'   => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__'     => $token,
            ];
            $field = [
                'oldpassword'   => __('Old password'),
                'newpassword'   => __('New password'),
                'renewpassword' => __('Renew password')
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }

            $ret = $this->auth->changepwd($newpassword, $oldpassword);
            if ($ret) {
                $this->success(__('Reset password successful'), url('user/login'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('title', __('Change password'));
        return $this->view->fetch();
    }

    public function attachment()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $mimetypeQuery = [];
            $where = [];
            $filter = $this->request->request('filter');
            $filterArr = (array)json_decode($filter, true);
            if (isset($filterArr['mimetype']) && preg_match("/(\/|\,|\*)/", $filterArr['mimetype'])) {
                $this->request->get(['filter' => json_encode(array_diff_key($filterArr, ['mimetype' => '']))]);
                $mimetypeQuery = function ($query) use ($filterArr) {
                    $mimetypeArr = array_filter(explode(',', $filterArr['mimetype']));
                    foreach ($mimetypeArr as $index => $item) {
                        $query->whereOr('mimetype', 'like', '%' . str_replace("/*", "/", $item) . '%');
                    }
                };
            } elseif (isset($filterArr['mimetype'])) {
                $where['mimetype'] = ['like', '%' . $filterArr['mimetype'] . '%'];
            }

            if (isset($filterArr['filename'])) {
                $where['filename'] = ['like', '%' . $filterArr['filename'] . '%'];
            }

            if (isset($filterArr['createtime'])) {
                $timeArr = explode(' - ', $filterArr['createtime']);
                $where['createtime'] = ['between', [strtotime($timeArr[0]), strtotime($timeArr[1])]];
            }
            $search = $this->request->get('search');
            if ($search) {
                $where['filename'] = ['like', '%' . $search . '%'];
            }

            $model = new Attachment();
            $offset = $this->request->get("offset", 0);
            $limit = $this->request->get("limit", 0);
            $total = $model
                ->where($where)
                ->where($mimetypeQuery)
                ->where('user_id', $this->auth->id)
                ->order("id", "DESC")
                ->count();

            $list = $model
                ->where($where)
                ->where($mimetypeQuery)
                ->where('user_id', $this->auth->id)
                ->order("id", "DESC")
                ->limit($offset, $limit)
                ->select();
            $cdnurl = preg_replace("/\/(\w+)\.php$/i", '', $this->request->root());
            foreach ($list as $k => &$v) {
                $v['fullurl'] = ($v['storage'] == 'local' ? $cdnurl : $this->view->config['upload']['cdnurl']) . $v['url'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $mimetype = $this->request->get('mimetype', '');
        $mimetype = substr($mimetype, -1) === '/' ? $mimetype . '*' : $mimetype;
        $this->view->assign('mimetype', $mimetype);
        $this->view->assign("mimetypeList", \app\common\model\Attachment::getMimetypeList());
        return $this->view->fetch();
    }

    /**
     * 微信绑定页面
     */
    public function wechatbind()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login');
        }
        
        $user = $this->auth->getUser();
        
        $this->view->assign([
            'user' => $user,
            'title' => '微信绑定'
        ]);
        
        return $this->view->fetch();
    }

    /**
     * 生成微信绑定二维码
     */
    public function generateWechatQr()
    {
        try {
            if (!$this->auth->isLogin()) {
                $this->error('请先登录');
            }
            
            $user = $this->auth->getUser();
            
            // 添加类型检查和调试
            if (!$user || !isset($user->id)) {
                $this->error('用户信息获取失败');
            }
            
            // 确保用户ID是数字
            $userId = (int)$user->id;
            if ($userId <= 0) {
                $this->error('用户ID无效');
            }
            
            // 生成绑定码（临时标识）
            $bindCode = md5($userId . time() . rand(1000, 9999));
            
            // 将绑定码存储到缓存中，有效期5分钟
            $cacheKey = 'wechat_bind_' . $bindCode;
            cache($cacheKey, $userId, 300);
            
            // 生成小程序码的参数
            $scene = 'bind=' . $bindCode;
            
            // 简化URL生成，避免复杂调用
            $baseUrl = 'https://order.023ent.net';
            $qrUrl = $baseUrl . '/index/user/wechatBindCallback?code=' . $bindCode;
            
            // 构建返回数据
            $data = [
                'bind_code' => $bindCode,
                'qr_url' => $qrUrl,
                'scene' => $scene
            ];
            
            // 使用JSON直接输出，避免框架方法
            header('Content-Type: application/json');
            echo json_encode([
                'code' => 1,
                'msg' => '生成成功',
                'data' => $data
            ]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'code' => 0,
                'msg' => '错误: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * 微信绑定回调
     */
    public function wechatBindCallback()
    {
        $code = $this->request->param('code');
        
        if (!$code) {
            $this->error('参数错误');
        }
        
        // 从缓存中获取用户ID
        $userId = cache('wechat_bind_' . $code);
        
        if (!$userId) {
            $this->error('绑定码已过期或无效');
        }
        
        $this->view->assign([
            'bind_code' => $code,
            'title' => '微信绑定确认'
        ]);
        
        return $this->view->fetch();
    }

    /**
     * 处理微信绑定
     */
    public function doWechatBind()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        
        $bindCode = $this->request->post('bind_code');
        $openid = $this->request->post('openid');
        $userInfo = $this->request->post('user_info');
        
        if (!$bindCode || !$openid) {
            $this->error('参数错误');
        }
        
        // 从缓存中获取用户ID
        $userId = cache('wechat_bind_' . $bindCode);
        
        if (!$userId) {
            $this->error('绑定码已过期或无效');
        }
        
        // 检查openid是否已被其他用户绑定
        $existUser = \app\common\model\User::where('wechat_openid', $openid)
                                          ->where('id', '<>', $userId)
                                          ->find();
        
        if ($existUser) {
            $this->error('该微信号已被其他用户绑定');
        }
        
        // 更新用户微信信息
        $user = \app\common\model\User::get($userId);
        if (!$user) {
            $this->error('用户不存在');
        }
        
        $updateData = [
            'wechat_openid' => $openid,
            'updatetime' => time()
        ];
        
        // 如果有用户信息，也更新头像和昵称
        if ($userInfo) {
            $userInfoArray = json_decode($userInfo, true);
            if ($userInfoArray) {
                if (isset($userInfoArray['avatarUrl'])) {
                    $updateData['avatar'] = $userInfoArray['avatarUrl'];
                }
                if (isset($userInfoArray['nickName']) && empty($user->nickname)) {
                    $updateData['nickname'] = $userInfoArray['nickName'];
                }
            }
        }
        
        $result = $user->save($updateData);
        
        if ($result) {
            // 清除缓存
            cache('wechat_bind_' . $bindCode, null);
            $this->success('绑定成功');
        } else {
            $this->error('绑定失败');
        }
    }

    /**
     * 解绑微信
     */
    public function unbindWechat()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        
        $user = $this->auth->getUser();
        
        $result = $user->save([
            'wechat_openid' => '',
            'wechat_session_key' => '',
            'updatetime' => time()
        ]);
        
        if ($result) {
            $this->success('解绑成功');
        } else {
            $this->error('解绑失败');
        }
    }

    /**
     * 绑定员工号
     */
    public function bindEmployee()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录');
        }
        
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        
        $employeeNo = $this->request->post('employee_no');
        
        if (!$employeeNo) {
            $this->error('请输入员工号');
        }
        
        $user = $this->auth->getUser();
        
        // 检查员工号是否已被其他用户绑定
        $existUser = \app\common\model\User::where('employee_no', $employeeNo)
                                          ->where('id', '<>', $user->id)
                                          ->find();
        
        if ($existUser) {
            $this->error('该员工号已被其他用户绑定');
        }
        
        // 这里可以添加员工号验证逻辑
        // $employee = Db::name('employee')->where('employee_no', $employeeNo)->find();
        // if (!$employee) {
        //     $this->error('员工号不存在');
        // }
        
        $result = $user->save([
            'employee_no' => $employeeNo,
            'updatetime' => time()
        ]);
        
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
            $this->error('请先登录');
        }
        
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        
        $user = $this->auth->getUser();
        
        $result = $user->save([
            'employee_no' => '',
            'updatetime' => time()
        ]);
        
        if ($result) {
            $this->success('解绑成功');
        } else {
            $this->error('解绑失败');
        }
    }
}
