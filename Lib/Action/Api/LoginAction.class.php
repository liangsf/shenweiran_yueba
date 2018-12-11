<?php


import("yueba.Action.AuthAPIAction");
import("ORG.Util.Util");
class LoginAction extends MyAction {
    public function __construct() {
        parent::__construct();
    }
    public function index(){
        try {
            $code = Util::getHttpHeader(WX_HEADER_CODE);
            $encryptedData = Util::getHttpHeader(WX_HEADER_ENCRYPTED_DATA);
            $iv = Util::getHttpHeader(WX_HEADER_IV);

            if (!$code) {
                throw new Exception("请求头未包含 code，请配合客户端 SDK 登录后再进行请求");
            }

            $data =  AuthAPIAction::login($code, $encryptedData, $iv);
            $this->ajaxReturn([
                'code' => 0,
                'data' => $data['userinfo']
            ]);
        } catch (Exception $e) {

            $this->ajaxReturn([
                'loginState' => E_AUTH,
                'error' => $e->getMessage()
            ],'','200');
        }

    }


    public function login(){
        //$json = file_get_contents('php://input', 'r');
        //$data = (array)json_decode($json);

        $data = $_POST;

        $userMod = D('User');

        //生成uuid    12位
        $charid = md5(uniqid(mt_rand(), true));
        $uuid = 'xz'.substr($charid, 8, 5).substr($charid,16, 5);

        $data['uuid'] = $uuid;
        $data['nickname'] = strval($data['nickName']);
        $data['open_id'] = strval($this->openid);
        $data['city'] = strval($data['city']);
        $data['province'] = strval($data['province']);
        $data['country'] = strval($data['country']);
        $data['avatarurl'] = strval($data['avatarUrl']);
        $data['gender'] = strval($data['gender']);
        $data['user_info'] = json_encode($data);

        $res = $userMod->addUser($data);
        if($res) {
            $this->ajaxReturn($res, 'ok', 200);
        } else {
            $this->ajaxReturn('', '登陆失败', 402);
        }




    }

    //根据code获取openid
    public function getOpenIdByCode() {
        try {
            //$code = Util::getHttpHeader(WX_HEADER_CODE);
            $code = strval($_GET['code']);
            if (!$code) {
                throw new Exception("请求头未包含 code，请配合客户端 SDK 登录后再进行请求");
            }
            $data = AuthAPIAction::getSessionKey($code);
            $this->ajaxReturn($data, '', 200);
        } catch (Exception $e) {
            $this->ajaxReturn([
                'loginState' => E_AUTH,
                'error' => $e->getMessage()
            ],'', '444');
        }

    }


    public static function check() {
        return false;
        try {
            $skey = Util::getHttpHeader(WX_HEADER_SKEY);

            if (!$skey) {
                throw new Exception("请求头未包含 skey，请配合客户端 SDK 登录后再进行请求");
            }

            return AuthAPIAction::checkLogin($skey);
        } catch (Exception $e) {
            $this->ajaxReturn([
                'loginState' => E_AUTH,
                'error' => $e->getMessage()
            ],'','200');
        }
    }
}
