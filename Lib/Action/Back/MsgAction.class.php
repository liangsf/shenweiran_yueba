<?php

/**
 * author lsf880101@foxmail.com
 */
class MsgAction extends MyAction
{

    //获取消息列表
    public function lists()
    {
        $page = $_GET['page']?$_GET['page']:1;
		$pageSize = $_GET['size']?$_GET['size']:20;

        $msgMod = D('Message');
        $count = $msgMod->count();
        $list = $msgMod->page($page, $pageSize)->select();
        $data['list'] = $list;
        $data['count'] = $count;
        $this->ajaxReturn($data, '', 200);
    }

    //获取单条数据
    public function find()
    {
        $id = intval($_GET['id']);
        if($id) {
            $where['id'] = $id;
        } else {
            $this->ajaxReturn($rs, '没有找到数据', 401);
        }

        $msgMod = D('Message');
        $rs = $msgMod->where($where)->find();
        $this->ajaxReturn($rs, 'ok', 200);
    }


    //回复内容
    public function reply()
    {
        $body = getPostPayload();
        $where['id'] = $body['id'];
        $data['reply'] = $body['reply'];
        $data['reply_time'] = date('Y-m-d H:i:s', time());
        $ok = D('Message')->where($where)->save($data);
        if($ok) {

            //发送模板消息
            import("yueba.Action.JssdkAction");
            $jssdk = new JssdkAction(C('WX_AppID'), C('WX_AppSecret'));
            $rs = D('Message')->where($where)->find();
            $xx = $jssdk->sendReplyMsg($rs['open_id'], $rs['reply']);

            $this->ajaxReturn($ok, '修改成功', 200);
        } else {
            $this->ajaxReturn('', '修改失败', 402);
        }
    }
}
