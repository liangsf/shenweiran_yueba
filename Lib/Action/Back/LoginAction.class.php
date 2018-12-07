<?php


class LoginAction extends Action {

    public function __construct()
    {


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
        //header('Content-Type: application/json;');

        $log = getPostPayload()?getPostPayload():$_GET;
        $str = date('Y-m-d H:i:s',time()).'----------'.$_SERVER['REQUEST_URI'].':'.$_SERVER['HTTP_ORIGIN'].':'.json_encode($log)."\r\n";
        file_put_contents('./back_log.txt',$str , FILE_APPEND);
    }

    public function index(){

        $json = file_get_contents('php://input');
        $data =  (array)json_decode($json);
        // if(empty($data)) {
        //     $data = $_POST;
        // }
        $userinfo = session('user');

        if(!empty($userinfo) && $userinfo['username'] == $data['username']) {

            $this->ajaxReturn($userinfo, '登陆成功1', 200);

        } else {

            $where['username'] = strval($data['username']);
            $where['status'] = 1;
            //$where['password'] = md5($data['password']);
            $user = M('AdminUser')->where($where)->find();

            if($user['password'] === md5($data['password'])) {
                $_SESSION['user']['id']     = $user['id'];
                $_SESSION['user']['username']    = $user['username'];
                $_SESSION['user']['nikename']   = $user['nikename'];
                $_SESSION['user']['phone']   = $user['phone'];
                $this->ajaxReturn($user, '登陆成功', 200);
            } else {
                $this->ajaxReturn('', '失败', 400);
            }
        }


    }

}
