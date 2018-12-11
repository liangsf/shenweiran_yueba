<?php
/**
 * 企业付款
 * @author lsf <lsf880101@foxmail.com>
 */

class WxTransModel extends CommonModel {

	public function WxTransfers($openid,$money=0)
    {

        $money = $money*100; //最低1元，单位分

        $sender = "朽煮";

        $obj2 = array();

        $obj2['mch_appid'] = C('WX_AppID');//"wx486d78adc2a70186"; //appid

        $obj2['mchid'] = C('WX_MchID');//"1487094692";//商户id

        $obj2['partner_trade_no'] = "1487094692".date('YmdHis').rand(1000,9999);//组合成28位，根据官方开发文档，可以自行设置

        $obj2['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];

        $obj2['openid'] = $openid;//"ovABH4_ex4yiyUNLHEc086r5wQ1U";//接收红包openid

        $obj2['check_name'] = 'NO_CHECK';
        $obj2['amount'] = $money;
        $obj2['desc'] = $sender."红包";


        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";


        $res = $this->pay($url, $obj2);

        // $unifiedOrder = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
		$unifiedOrder = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

		$res = array();
		$res['data'] = '';
		$res['status'] = false;
		$res['info'] = '领取失败';
        if ($unifiedOrder === false) {
            //die('parse xml error');
			return $res;
        }
        if ($unifiedOrder['return_code'] != 'SUCCESS') {
            //die($unifiedOrder->return_msg);
			$res['info'] = '领取失败';
			return $res;
        }
        if ($unifiedOrder['result_code'] != 'SUCCESS') {
            //die($unifiedOrder->err_code);
			$res['info'] = $unifiedOrder['err_code_des'];
			return $res;
        }

        //print_R($unifiedOrder);
		$data['payment_no'] = $unifiedOrder['payment_no'];
		$data['payment_time'] = $unifiedOrder['payment_time'];
		$data['partner_trade_no'] = $unifiedOrder['partner_trade_no'];
		$data['wx_response'] = json_encode($unifiedOrder);
		$res['data'] = $data;
		$res['info'] = '领取成功';
		$res['status'] = true;
        return $res;


    }

	//企业付款
    private function pay($url,$obj) {

        $obj['nonce_str'] = $this->create_noncestr();  //创建随机字符串

        $stringA = $this->create_qianming($obj,false);  //创建签名

        $stringSignTemp = $stringA."&key=".C('WX_MchKey');  //签名后加api

        $sign = strtoupper(md5($stringSignTemp));  //签名加密并大写

        $obj['sign'] = $sign;  //将签名传入数组

        $postXml = $this->arrayToXml($obj);  //将参数转为xml格式

        //print_r($postXml);

        $responseXml = $this->curl_post_ssl($url,$postXml);  //提交请求

        //print_r($responseXml);

        return $responseXml;

  }

  //生成签名,参数：生成签名的参数和是否编码

  private function create_qianming($arr,$urlencode) {

	  $buff = "";

	  ksort($arr); //对传进来的数组参数里面的内容按照字母顺序排序，a在前面，z在最后（字典序）

	foreach ($arr as $k=>$v) {

		if(null!=$v && "null" != $v && "sign" != $k) {  //签名不要转码

			if ($urlencode) {

			  $v = urlencode($v);

			}

			$buff.=$k."=".$v."&";

		}

	}

    if (strlen($buff)>0) {

      $reqPar = substr($buff,0,strlen($buff)-1); //去掉末尾符号“&”

    }

    return $reqPar;

  }

  //生成随机字符串，默认32位

  private function create_noncestr($length=32) {

    //创建随机字符

	$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

	$str = "";

	for($i=0;$i<$length;$i++) {

		$str.=substr($chars, mt_rand(0,strlen($chars)-1),1);

	}

	return $str;

  }


  //数组转xml

  private function arrayToXml($arr) {

    $xml = "<xml>";

    foreach ($arr as $key=>$val) {

      if (is_numeric($val)) {

        $xml.="<".$key.">".$val."</".$key.">";

      } else {

        $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";

      }

    }

    $xml.="</xml>";

    return $xml;

  }

  //post请求网站，需要证书

  private function curl_post_ssl($url, $vars, $second=30,$aHeader=array())

  {

    $ch = curl_init();

    //超时时间

    curl_setopt($ch,CURLOPT_TIMEOUT,$second);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    //这里设置代理，如果有的话

    curl_setopt($ch,CURLOPT_URL,$url);

    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

    //cert 与 key 分别属于两个.pem文件

    //请确保您的libcurl版本是否支持双向认证，版本高于7.20.1

    $sslCertPath = "D:\wamp\www\dong\code\cert\apiclient_cert.pem";
			$sslKeyPath = "D:\wamp\www\dong\code\cert\apiclient_key.pem";
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, $sslCertPath);
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, $sslKeyPath);

    if( count($aHeader) >= 1 ){

      curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);

    }

    curl_setopt($ch,CURLOPT_POST, 1);

    curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);

    $data = curl_exec($ch);

    if($data){

      curl_close($ch);

      return $data;

    }

    else {

      $error = curl_errno($ch);

      echo "call faild, errorCode:$error\n";

      curl_close($ch);

      return "curl出错，错误码:$error";

    }

  }

}
