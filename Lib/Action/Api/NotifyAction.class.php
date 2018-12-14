<?php

include "./WxPay/lib/WxPay.Api.php";
include './WxPay/lib/WxPay.Notify.php';
class NotifyAction extends Action {


    //支付成功的回调
    public function payNotify()
    {
        // $xml = '<xml><appid><![CDATA[wx486d78adc2a70186]]></appid><attach><![CDATA[test]]></attach><bank_type><![CDATA[CFT]]></bank_type><cash_fee><![CDATA[1]]></cash_fee><fee_type><![CDATA[CNY]]></fee_type><is_subscribe><![CDATA[N]]></is_subscribe><mch_id><![CDATA[1487094692]]></mch_id><nonce_str><![CDATA[a6ij4cyjgl67butf8k4d0xc6jwwiv5jv]]></nonce_str><openid><![CDATA[ovABH4_ex4yiyUNLHEc086r5wQ1U]]></openid><out_trade_no><![CDATA[xzpay20181203111609]]></out_trade_no><result_code><![CDATA[SUCCESS]]></result_code><return_code><![CDATA[SUCCESS]]></return_code><sign><![CDATA[319959D1DDFF8C9D50CAFA5A67EA8993186F5A033AB41087C46C45800BB34B93]]></sign><time_end><![CDATA[20181203111654]]></time_end><total_fee>1</total_fee><trade_type><![CDATA[JSAPI]]></trade_type><transaction_id><![CDATA[4200000234201812033874964111]]></transaction_id></xml>';

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];

        $config = new WxPayConfig();
        $data = WxPayResults::Init($config, $xml);
        $objData = WxPayNotifyResults::Init($config, $xml);

        //file_put_contents('./log.txt',json_encode($data) , FILE_APPEND);


        //TODO 1、进行参数校验
        if(!array_key_exists("return_code", $data)
            ||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            echo $msg;
            return false;
        }
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            echo $msg;
            return false;
        }

        //TODO 2、进行签名验证
		try {
			$checkResult = $objData->CheckSign($config);
			if($checkResult == false){
				//签名错误
                $msg = "签名错误";
                echo $msg;
				return false;
			}
		} catch(Exception $e) {
            echo $e->getMessage();
            return false;
		}

        //查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
            echo $msg;
			return false;
		}

        //设置签到成功
        /*$ufData['status'] = 1;  //设置活动状态
        $ufData['pay_type'] = 1;    //设置支付状态
        $ufData['pay_time'] = date('Y-m-d H:i:s', time());
        $ufData['order_money'] = $data['total_fee']/100;
        $ufWhere['out_trade_no'] = $data['out_trade_no'];
        $ufWhere['open_id'] = $data['openid'];
        $ok = D('UF')->where($ufWhere)->save($ufData);*/
        $ufData['pay_type'] = 1;    //设置支付状态
        $ufData['pay_time'] = date('Y-m-d H:i:s', time());
        $ufWhere['out_trade_no'] = $data['out_trade_no'];
        $ufWhere['open_id'] = $data['openid'];
        $ufWhere['status'] = 1;
        $ok = D('UF')->where($ufWhere)->save($ufData);

        if($ok) {
            //增加交易记录
            $tsMod = D('Transaction');
            $tsData['open_id'] = $data['openid'];
            $tsData['affair_id'] = $data['attach'];
            $tsData['type'] = 1;
            $tsData['cash_fee'] = $data['cash_fee'];
            $tsData['total_fee'] = $data['total_fee'];
            $tsData['out_trade_no'] = $data['out_trade_no'];
            $tsData['trade_type'] = $data['trade_type'];
            $tsData['transaction_id'] = $data['transaction_id'];
            $tsData['time_end'] = $data['time_end'];
            $tsData['wx_response'] = json_encode($data);

            $tsMod->add($tsData);

            exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
        }


        //print_r($data);


    }

    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);

        $config = new WxPayConfig();
        $result = WxPayApi::orderQuery($config, $input);
        //print_R($result);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

}
