<?php

/**
 * author lsf880101@foxmail.com
 */
class IndexAction extends MyAction
{

    function __construct()
    {
        // code...
        parent::__construct();
    }

    public function index()
    {
        $this->ajaxReturn('', '欢迎', 200);
    }
}
