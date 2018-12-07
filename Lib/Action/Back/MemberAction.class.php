<?php

/**
 * author lsf880101@foxmail.com
 */
class MemberAction extends MyAction
{

    //获取用户信息
    public function find()
    {
        $openid = strval($_GET['openid']);
        if($openid) {
            $where['open_id'] = $openid;
        } else {
            $this->ajaxReturn($rs, '用户id不能为空', 402);
        }

        $userMod = D('User');
        $rs = $userMod->where($where)->find();
        $this->ajaxReturn($rs, '', 200);
    }

    public function lists()
    {
        $page = $_GET['page']?$_GET['page']:1;
		$pageSize = $_GET['size']?$_GET['size']:20;

        $userMod = D('User');
        $count = $userMod->count();
        $list = $userMod->page($page, $pageSize)->select();
        $data['list'] = $list;
        $data['count'] = $count;
        $this->ajaxReturn($data, '', 200);
    }


}
