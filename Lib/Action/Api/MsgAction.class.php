<?php

class MsgAction extends MyAction {

    public function __construct() {
        parent::__construct();
    }

    //添加留言
    public function addMsg () {
        $data = $_POST;


        $data['open_id'] = strval($this->openid);

        $rs = D('Message')->addMsg($data);

        if($rs) {
            $this->ajaxReturn($rs, '', 200);
        } else {
            $this->ajaxReturn('', '获取数据失败', 402);
        }
    }

    

}
