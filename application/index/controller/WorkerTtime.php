<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Db;
use Exception;

/**
 * 员工计时工单前台控制器
 */
class WorkerTtime extends Frontend
{
    protected $noNeedRight = ['*'];
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->view->assign('title', '员工计时工单系统');
    }

    
    /**
     * 我的计时分工任务列表
     */
    public function tasks()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $tasks = Db::name('scanwork_tallocationtime')
            ->where('user_id', $workerId)
            ->where('status', 0)
            ->order('work_date desc')
            ->select();
        $this->view->assign('tasks', $tasks);
        return $this->view->fetch('worker/ttasks');
    }

    /**
     * 计时报工页面
     */
    public function report()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $taskId = $this->request->param('id');
        $error = '';
        $success = '';
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $tallocationtime_id = $params['tallocationtime_id'];
            $work_date = $params['work_date'];
            $start_time = $params['start_time'];
            $end_time = $params['end_time'];
            $total_hours = $params['total_hours'];
            $wage = $params['wage'];
            $remark = $params['remark'];
            if (!$tallocationtime_id || !$work_date || !$total_hours) {
                $error = '参数不完整';
            } else {
                try {
                    Db::startTrans();
                    Db::name('scanwork_treporttime')->insert([
                        'tallocationtime_id' => $tallocationtime_id,
                        'user_id' => $workerId,
                        'work_date' => $work_date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'total_hours' => $total_hours,
                        'wage' => $wage,
                        'remark' => $remark,
                        'status' => 0, // 待确认
                        'createtime' => time(),
                        'updatetime' => time()
                    ]);
                    Db::commit();
                    $success = '报工提交成功，等待审核';
                } catch (Exception $e) {
                    Db::rollback();
                    $error = '异常: ' . $e->getMessage();
                }
            }
        }
        // 获取分工任务
        $tasks = Db::name('scanwork_tallocationtime')
            ->where('user_id', $workerId)
            ->where('status', 0)
            ->order('work_date desc')
            ->select();
        $this->view->assign('tasks', $tasks);
        $this->view->assign('error', $error);
        $this->view->assign('success', $success);
        return $this->view->fetch('worker/treport');
    }

    /**
     * 我的计时报工记录
     */
    public function records()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $records = Db::name('scanwork_treporttime')
            ->where('user_id', $workerId)
            ->order('work_date desc')
            ->select();
        $this->view->assign('records', $records);
        return $this->view->fetch('worker/trecords');
    }

    /**
     * 我的计时工资统计
     */
    public function wage()
    {
        if (!$this->auth->isLogin()) {
            $this->redirect('index/user/login?url=' . urlencode($this->request->url()));
        }
        $workerId = $this->auth->id;
        $wages = Db::name('scanwork_twage')
            ->where('user_id', $workerId)
            ->order('work_date desc')
            ->select();
        $this->view->assign('wages', $wages);
        return $this->view->fetch('worker/twage');
    }
} 