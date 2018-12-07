<?php

import("ORG.Util.Util");

class MyAction extends Action {
    public $user;
    public function __construct(){

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origin = array(
            'http://localhost:8083',
            'http://192.168.1.37:8083',
            'http://192.168.0.25:8083'
        );
        //if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);
        //}
        //header('Access-Control-Allow-Origin:http://localhost:8083');
        header('Access-Control-Allow-Method:GET,POST,PATCH,PUT,OPTIOINS');//允许访问的方式
        header('Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept');
        //header('access-control-expose-headers: Authorization');
        header("Access-Control-Allow-Credentials: true");

        $this->user = session('user');

        //print_r($GLOBALS['HTTP_RAW_POST_DATA']);

        $log = getPostPayload()?getPostPayload():$_GET;
        $str = date('Y-m-d H:i:s',time()).'----------'.$_SERVER['REQUEST_URI'].':'.$_SERVER['HTTP_ORIGIN'].':'.json_encode($log)."\r\n";
        file_put_contents('./back_log.txt',$str , FILE_APPEND);



        if (!$this->user) {
            $this->ajaxReturn('', '请登录', 400);
        }




    }



}
