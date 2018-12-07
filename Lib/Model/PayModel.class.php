<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
include "./WxPay/lib/WxPay.Api.php";
class PayModel extends CommonModel {

	//统一下单
	public function downOrder($title='', $tradeNo='', $money=0, $openId, $attach='0')
	{
		$money = $money * 100;
		// 统一支付下单
        try{
        	// $tools = new JsApiPay();
        	// $openId = $tools->GetOpenid();
        	//②、统一下单
        	$input = new WxPayUnifiedOrder();
        	$input->SetBody("朽著科技-".$title);
        	$input->SetAttach($attach);
        	$input->SetOut_trade_no($tradeNo);
        	$input->SetTotal_fee($money);
        	$input->SetTime_start(date("YmdHis"));
        	$input->SetTime_expire(date("YmdHis", time() + 600));
        	$input->SetGoods_tag('');
        	$input->SetNotify_url(C('SITEURL')."index.php/Notify/payNotify");
        	$input->SetTrade_type("JSAPI");
        	$input->SetOpenid($openId);

			// echo $title;
			// echo $tradeNo;
			// echo  $moneyl;
			// echo $openId;
			// exit;


        	$config = new WxPayConfig();
        	$order = WxPayApi::unifiedOrder($config, $input);
        	//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        	//print_r($order);
        	$jsApiParameters = $this->GetJsApiParameters($order);
            //print_R($jsApiParameters);
			return $jsApiParameters;
        	//获取共享收货地址js函数参数
        	//$editAddress = $tools->GetEditAddressParameters();
            //print_r($editAddress);
        } catch(Exception $e) {
        	return $e->getMessage();
        }
	}

	/**
	 *
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 *
	 * @return json数据，可直接填入js函数作为参数
	 */
	private function GetJsApiParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new WxPayException("参数错误");
		}

		$jsapi = new WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(WxPayApi::getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);

		$config = new WxPayConfig();
		$jsapi->SetPaySign($jsapi->MakeSign($config));
		$parameters = json_encode($jsapi->GetValues());
		return $parameters;
	}



	/**
	 * 退款
	 *$out_trade_no 商户订单号
	 *$affair_info 活动信息及 参会记录信息（订单）
	 */
	public function refund ($out_trade_no, $affair_info)
	{

		if(( isset($out_trade_no) && $out_trade_no!='' && !preg_match("/^[0-9a-zA-Z]{10,64}$/i", $out_trade_no, $matches) )) {
			$array = array();
			$array['status'] = false;
			$array['info'] = '退款失败';
			//根据商户订单退款
			try{
				// $transaction_id = '4200000215201812043375560422';//$_REQUEST["transaction_id"];
				$out_trade_no = $out_trade_no;
				$total_fee = $affair_info['promise_money']*100;//$_REQUEST["total_fee"];
				$refund_fee = $affair_info['promise_money']*100;// $_REQUEST["refund_fee"];
				$input = new WxPayRefund();
				// $input->SetTransaction_id($transaction_id);
				$input->SetOut_trade_no($out_trade_no);
				$input->SetTotal_fee($total_fee);
				$input->SetRefund_fee($refund_fee);

				$config = new WxPayConfig();
				$input->SetOut_refund_no("xzsdkphp".date("YmdHis"));
				$input->SetOp_user_id($config->GetMerchantId());
				$rs = WxPayApi::refund($config, $input);
				if($rs['return_code'] == 'SUCCESS' && $rs['result_code'] == 'SUCCESS') {
					//增加交易记录
		            $tsMod = D('Transaction');
		            $tsData['open_id'] = $affair_info['open_id'];
		            $tsData['affair_id'] = $affair_info['id'];
		            $tsData['type'] = 2;
		            $tsData['cash_fee'] = $rs['cash_fee'];
		            $tsData['total_fee'] = $rs['total_fee'];
		            $tsData['out_trade_no'] = $rs['out_trade_no'];
		            $tsData['trade_type'] = 'JSAPI';
		            $tsData['transaction_id'] = $rs['transaction_id'];
		            $tsData['time_end'] = $affair_info['pay_time'];

		            $ok = $tsMod->add($tsData);
					if($ok) {
						$array['info'] = '退款成功';
						$array['status'] = true;
						return $array;
					} else {
						$array['info'] = '退款操作失败';
						return $array;
					}
				} else {
					$array['info'] = $rs['err_code_des'];
					return $array;
				}
			} catch(Exception $e) {
				//Log::ERROR(json_encode($e));
				//echo $e->getMessage();
				$array['info'] = $e->getMessage();
				return $array;
			}
		} else {
			return $array;
		}

	}

}
