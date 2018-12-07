<?php

import("ORG.Util.Request");
class AuthAPIAction extends Action{

    /**
     * 用户登录接口
     * @param {string} $code        wx.login 颁发的 code
     * @param {string} $encryptData 加密过的用户信息
     * @param {string} $iv          解密用户信息的向量
     * @return {array} { loginState, userinfo }
     */
    public static function login($code, $encryptData, $iv) {
        // 1. 获取 session key
        $pack = self::getSessionKey($code);

        $session_key = $pack['session_key'];
        $openid = $pack['openid'];


        // 2. 生成 3rd key (skey)
        $skey = sha1($session_key . mt_rand());


        $userMod = D('User');

        // 如果只提供了 code
        // 就用 code 解出来的 openid 去查数据库
        if ($code && !$encryptData && !$iv) {
            //$userInfo = User::findUserByOpenId($openid);
            $userInfo = $userMod->findUserByOpenId($openId);
            // $wxUserInfo = json_decode($userInfo->user_info);
            $wxUserInfo = $userInfo;

            // 更新登录态
            //User::storeUserInfo($wxUserInfo, $skey, $session_key);

            return [
                'loginState' => S_AUTH,
                'userinfo' => [
                    'userinfo' => $wxUserInfo,
                    'skey' => $skey
                ]
            ];
        }

        /**
         * 3. 解密数据
         * 由于官方的解密方法不兼容 PHP 7.1+ 的版本
         * 这里弃用微信官方的解密方法
         * 采用推荐的 openssl_decrypt 方法（支持 >= 5.3.0 的 PHP）
         * @see http://php.net/manual/zh/function.openssl-decrypt.php
         */
        $decryptData = \openssl_decrypt(
            base64_decode($encryptData),
            'AES-128-CBC',
            base64_decode($session_key),
            OPENSSL_RAW_DATA,
            base64_decode($iv)
        );
        $userinfo = json_decode($decryptData);


        // 4. 储存到数据库中
        //User::storeUserInfo($userinfo, $skey, $session_key);
        $userData = (array)$userinfo;
        $data['nickname'] = strval($userData['nickName']);
        $data['open_id'] = strval($userData['openId']);
        $data['city'] = strval($userData['city']);
        $data['province'] = strval($userData['province']);
        $data['country'] = strval($userData['country']);
        $data['avatarurl'] = strval($userData['avatarUrl']);
        $data['gender'] = strval($userData['gender']);
        $data['user_info'] = json_encode($userinfo);

        $res = $userMod->addUser($data);
        //$rs->id = $res['id'];

        return [
            'loginState' => S_AUTH,
            'userinfo' => compact('userinfo', 'skey')
        ];
    }

    public static function checkLogin($skey) {
        //$userinfo = User::findUserBySKey($skey);
        if ($userinfo === NULL) {
            return [
                'loginState' => E_AUTH,
                'userinfo' => []
            ];
        }

        $wxLoginExpires = Conf::getWxLoginExpires();
        $timeDifference = time() - strtotime($userinfo->last_visit_time);

        if ($timeDifference > $wxLoginExpires) {
            return [
                'loginState' => E_AUTH,
                'userinfo' => []
            ];
        } else {
            return [
                'loginState' => S_AUTH,
                'userinfo' => json_decode($userinfo->user_info, true)
            ];
        }
    }

    /**
     * 通过 code 换取 session key
     * @param {string} $code
     */
    public static function getSessionKey ($code) {

        $appId = C('WX_AppID');
        $appSecret = C('WX_AppSecret');
        return self::getSessionKeyDirectly($appId, $appSecret, $code);

    }

    /**
     * 直接请求微信获取 session key
     * @param {string} $secretId  腾讯云的 secretId
     * @param {string} $secretKey 腾讯云的 secretKey
     * @param {string} $code
     * @return {array} { $session_key, $openid }
     */
    private static function getSessionKeyDirectly ($appId, $appSecret, $code) {
        $requestParams = [
            'appid' => $appId,
            'secret' => $appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];

        list($status, $body) = array_values(Request::get([
            'url' => 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query($requestParams),
            'timeout' => C('NetworkTimeout')
        ]));

        if ($status !== 200 || !$body || isset($body['errcode'])) {
            //throw new Exception(E_PROXY_LOGIN_FAILED . ': ' . json_encode($body));
        }

        return $body;
    }


}
