<?php

/**
 * author lsf880101@foxmail.com
 */
class UserAction extends MyAction
{

    public function __construct() {
        parent::__construct();
    }

    //获取用户信息
    public function find()
    {
        $id = intval($_GET['id']);
        if($id) {
            $where['id'] = $id;
        } else {
            $where['id'] = $this->user['id'];
        }

        $userMod = M('AdminUser');
        $rs = $userMod->where($where)->find();
        $this->ajaxReturn($rs, '', 200);
    }

    public function lists()
    {
        $msgMod = D('Message');
        $list = $msgMod->select();
        $this->ajaxReturn($list, '', 200);
    }

    //修改用户信息
    public function upPwd() {
        $json = file_get_contents('php://input');
        $body =  (array)json_decode($json);

        // if($body['oldpwd'] != $body['newpwd']) {
        //     $this->ajaxReturn('', '两次密码不一致', 402);
        //     return ;
        // }

        $where['id'] = strval($this->user['id']);

        $data['password'] = md5($body['newpwd']);
        $ok = M('AdminUser')->where($where)->save($data);
        if($ok) {
            $this->ajaxReturn('ok', '修改成功', 200);
        } else {
            $this->ajaxReturn('ok', '修改失败', 401);
        }

    }

    // 退出登录
    public function logout() {

        session('user', null);
        $this->ajaxReturn('成功退出', '退出成功', 200);
        exit;
    }
}
