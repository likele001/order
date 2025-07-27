<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($row->group_id == 1) {
            $this->error(__('Administrator group cannot be deleted'));
        }
        $row->delete();
        $this->success();
    }

    /**
     * 生成绑定二维码
     */
    public function generate_qr()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            
            if (!$username || !$password) {
                $this->error('请选择员工并输入密码');
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
            $qrContent = "bind:{$user['username']}:{$password}";
            
            $this->success('二维码生成成功', [
                'qr_content' => $qrContent,
                'username' => $user['username'],
                'nickname' => $user['nickname']
            ]);
        }
        
        // 获取员工列表
        $employees = \think\Db::name('user')
            ->where('group_id', 2)
            ->field('id,username,nickname,mobile')
            ->select();
        
        $this->view->assign('employees', $employees);
        return $this->view->fetch();
    }
}
