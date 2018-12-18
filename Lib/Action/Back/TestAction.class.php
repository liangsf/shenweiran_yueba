<?php

/**
 * author lsf880101@foxmail.com
 */
//import("ORG.WxPay.lib.WxPay#Api", "", ".php");    先不用这个   对应目录ThinkPHP\Extend\Library\ORG\WxPay

include "./WxPay/lib/WxPay.Api.php";
include './WxPay/lib/WxPay.Notify.php';
class TestAction extends Action
{

    //获取消息列表
    public function index()
    {
        //echo $this->user;

        import("yueba.Action.JssdkAction");
        $jssdk = new JssdkAction(C('WX_AppID'), C('WX_AppSecret'));
        //$rs = D('Message')->where($where)->find();
        $xx = $jssdk->sendReplyMsg('ovABH47yJ3yBjub1hBz2WSxKG7VI', '消息');

        print_r($xx);
    }

    //支付调试
    public function pay()
    {




        // code...
        try{

        	// $tools = new JsApiPay();
        	// $openId = $tools->GetOpenid();

        	//②、统一下单
        	$input = new WxPayUnifiedOrder();
        	$input->SetBody("test");
        	$input->SetAttach("test");
        	$input->SetOut_trade_no("sdkphp".date("YmdHis"));
        	$input->SetTotal_fee("1");
        	$input->SetTime_start(date("YmdHis"));
        	$input->SetTime_expire(date("YmdHis", time() + 600));
        	$input->SetGoods_tag("test");
        	$input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
        	$input->SetTrade_type("JSAPI");
        	$input->SetOpenid('ovABH47yJ3yBjub1hBz2WSxKG7VI');

        	$config = new WxPayConfig();
        	$order = WxPayApi::unifiedOrder($config, $input);
        	//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        	//print_r($order);
        	$jsApiParameters = $this->GetJsApiParameters($order);
            print_R($jsApiParameters);
        	//获取共享收货地址js函数参数
        	//$editAddress = $tools->GetEditAddressParameters();
            //print_r($editAddress);
        } catch(Exception $e) {
        	print_r($e);
        }
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

    //支付成功的回调
    public function payNotify()
    {
        $xml = '<xml><appid><![CDATA[wx486d78adc2a70186]]></appid><attach><![CDATA[test]]></attach><bank_type><![CDATA[CFT]]></bank_type><cash_fee><![CDATA[1]]></cash_fee><fee_type><![CDATA[CNY]]></fee_type><is_subscribe><![CDATA[N]]></is_subscribe><mch_id><![CDATA[1487094692]]></mch_id><nonce_str><![CDATA[a6ij4cyjgl67butf8k4d0xc6jwwiv5jv]]></nonce_str><openid><![CDATA[ovABH4_ex4yiyUNLHEc086r5wQ1U]]></openid><out_trade_no><![CDATA[xzpay20181203111609]]></out_trade_no><result_code><![CDATA[SUCCESS]]></result_code><return_code><![CDATA[SUCCESS]]></return_code><sign><![CDATA[319959D1DDFF8C9D50CAFA5A67EA8993186F5A033AB41087C46C45800BB34B93]]></sign><time_end><![CDATA[20181203111654]]></time_end><total_fee>1</total_fee><trade_type><![CDATA[JSAPI]]></trade_type><transaction_id><![CDATA[4200000234201812033874964111]]></transaction_id></xml>';

        $config = new WxPayConfig();

        $data = WxPayResults::Init($config, $xml);
        $objData = WxPayNotifyResults::Init($config, $xml);

        //TODO 1、进行参数校验
        if(!array_key_exists("return_code", $data)
            ||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            $this->ajaxReturn('', $msg, 403);
            return false;
        }
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }

        //TODO 2、进行签名验证
		try {
			$checkResult = $objData->CheckSign($config);
			if($checkResult == false){
				//签名错误
				//Log::ERROR("签名错误...");
                $this->ajaxReturn('', '签名错误', 403);
				return false;
			}
		} catch(Exception $e) {
			$this->ajaxReturn('', $e->getMessage(), 403);
		}

        //查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
            $this->ajaxReturn('', $msg, 403);
			return false;
		}

        print_R($data);
        $this->ajaxReturn('支付成功', '支付成功', 200);
    }

    /**
	 *
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 *
	 * @return json数据，可直接填入js函数作为参数
	 */
	public function GetJsApiParameters($UnifiedOrderResult)
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


    //获取自己 参与的与发起的所有活动
    public function listsx()
    {
        $data = $_POST;

        $page = intval($_GET['page']);
        $size = intval($_GET['size']);

        $ufModel = D('UF');

        $where['a.open_id'] = strval($this->openid);
        // if(isset($data['id'])) {
        //     $where['a.affair_id'] = $data['affair_id'];
        // }
        if(isset($data['status'])) {
            if($data['status'] == 0) {
                $where['af.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
            }
            $where['af.status'] = $data['status'];
        }

        //判断是我参与的还是我创建的
        if(isset($data['isme'])) {
            if($data['isme'] == 'true') {
                $where['_string'] =  ' af.open_id = a.open_id ';
            } else {
                $where['_string'] =  ' af.open_id != a.open_id ';
            }

        }

        $list = $ufModel->search($where, $page, $size);

        // $this->ajaxReturn($list, '', 200);
        echo M()->getLastSql();
    }

    //即将开始的聚会
    public function hangAffairs()
    {
        $data = $_POST;


        $page = intval($_GET['page']);
        $size = intval($_GET['size']);

        $ufModel = D('UF');

        if(isset($data['openid'])) {
            $where['a.open_id'] = strval($data['openid']);
        }
        $where['af.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
        $where['af.status'] = 0;

        $list = $ufModel->search($where, $page, $size);
        echo M()->getLastSql();
    }

    public function test($value='')
    {

        $ufMod = D('UF');
        $ufWhere['affair_id'] = 52;
        $ufWhere['_string'] = " status = 1 or status = 2";
        $ufMod->where($ufWhere)->select();
        echo M()->getLastSql();
        exit;
        $where['affair_id'] = 52;
        $affairMod = D('Affair');
        $affairInfo = $affairMod->where("id=".$where['affair_id'])->find();
        // print_r($affairInfo);
        //echo $affairInfo['close_time'];
        echo strtotime($affairInfo['close_time']);
        if($affairInfo['close_time'] == '0000-00-00 00:00:00') {
            echo 'xxxx';
        } else {
            echo 'aaaa';
        }
        //echo $affairMod->getLastSql();
    }

    //测试签到获取数据
    public function sign()
    {

        $affairId = intval($_POST['id']);
        $curentLng = $_POST['lng']; //当前位置
        $curentLat = $_POST['lat'];

        $where['a.affair_id'] = $affairId;
        $where['a.open_id'] = 'ovABH47yJ3yBjub1hBz2WSxKG7VI';//$this->openid;
        $info = D('UF')->search($where);

        $activeLng = $info[0]['address_Lng'];
        $activeLat = $info[0]['address_Lat'];

        $juli = getdistance_mi($activeLng, $activeLat,  $curentLng, $curentLat);

        echo $juli;
    }



    public function cancelAffair()
    {
        $this->openid = 'ovABH47yJ3yBjub1hBz2WSxKG7VI';
        $tranMod = new Model(); //事物
        $tranMod->startTrans();

        $affairId = intval($_POST['id']);
        $affairMod = D('Affair');


        $affWhere['id'] = $affairId;
        $affWhere['open_id'] = $this->openid;

        //活动开始后不可以取消
        $affInfo = $affairMod->where($affWhere)->find();
        $active_time = strtotime($affInfo['active_time']);
        $current_time = time();
        if($current_time>$active_time) {
            $this->ajaxReturn('', '活动已开始不可以取消', 402);
        }
        //活动开始后不可以取消

        $data['status'] = 1;
        $isOk = $affairMod->where($affWhere)->save($data);
        $str = date('Y-m-d H:i:s',time()).'----------SQL:'.M()->getLastSql().$ok."\r\n";
        file_put_contents('./log.txt',$str , FILE_APPEND);


        if($isOk) {

            $baseInfo = M('BaseConf')->where('id=1')->find();   //获取退款费率
            $ufMod = D('UF');
            $ufWhere['affair_id'] = $affairId;
            $ufWhere['status'] = 1;
            $ufList = $ufMod->where($ufWhere)->select();
            if(count($ufList)) {
                $tranMod->commit();
                $this->ajaxReturn('', '取消成功', 200);
            } else {
                //执行退款
                //code...
                $ufWhere['affair_id'] = $affairId;
                $ufWhere['status'] = 1;
                $ufData['status'] = 4;
                $ufOk = D('UF')->where($ufWhere)->save($ufData);
                //echo M()->getLastSql();exit;
                //执行退款
            }

            if(1 && $ufOk) { //退款成功
                $tranMod->commit();
                $this->ajaxReturn('', '取消成功', 200);
            } else {
                $tranMod->rollback();
                $this->ajaxReturn('', '取消异常', 402);
            }
        } else {
            $this->ajaxReturn('', '取消失败', 402);
        }

    }


    //操作按钮
    public function getOptBtn()
    {
        $this->openid = 'ovABH47yJ3yBjub1hBz2WSxKG7VI';
        // code...
        $affairId = intval($_POST['id']);
        $btns = array(
            'extend' => false,  //邀请
            'update' => false,  //修改
            'sign' => false,    //签到
            'getMoney' => false,    //领取红包
            'join' => false,    //参与红包
            'view' => false,    //查看
          );

          $openid = $this->openid;

          $afWhere['id'] = $affairId;
          $afInfo = D('Affair')->where($afWhere)->find();

          $active_time = strtotime($afInfo['active_time']);
          $current_time = time();

          $current_date = date('Y-m-d', $current_time);
          $active_date = date('Y-m-d', $active_time);

          if($current_time<$active_time) {
              $btns['extend'] = true;
          }


          //获取参会与人信息
          $ufMod = D('UF');
          $ufWhere['affair_id'] = $id;
          $ufWhere['open_id'] = $openid;
          //$ufWhere['status'] = 1;
          $ufInfo = $ufMod->where($ufWhere)->find();



          if($afInfo['open_id'] == $openid && $current_time<$active_time && $afInfo['status'] == 0) {
              $btns['update'] = true;
              if($current_date == $active_date) {
                  $btns['update'] = false;
              }
          }

          if(($ufInfo['status']==0 || empty($ufInfo)) && $afInfo['status'] == 0 && $current_time<$active_time) {
              $btns['join'] = true;
          }

          if($afInfo['open_id'] != $openid && $current_time<$active_time && $afInfo['status'] == 0) {

              if($ufInfo['status']==1) {
                  $btns['update'] = true;
              }

              if($current_date == $active_date) {
                  $btns['update'] = false;
              }
          }

          if(!empty($ufInfo)) {
              if($ufInfo['status']==1 && $afInfo['status'] == 0 && $current_time<$active_time) {
                    if($current_date == $active_date) {
                        $btns['sign'] = true;
                    }
              }

              if($ufInfo['status']==2) {
                  $btns['getMoney'] = true;
              }

              if($ufInfo['status']==5) {
                  $btns['view'] = true;
              }
          }



          $this->ajaxReturn($btns, 'ok', 200);


    }

    public function xjs($value='')
    {

        $a=1;
        $b=2;
        echo  $a,$b;
        // code...
        echo "xzpay".date("YmdHis").'-'.mt_rand(1000,9999);
    }


    //退款
    public function refund()
    {
        //查询订单
        // try{
    	// 	$transaction_id = '4200000215201812043375560422';//$_REQUEST["transaction_id"];
    	// 	$input = new WxPayRefundQuery();
    	// 	$input->SetTransaction_id($transaction_id);
    	// 	$config = new WxPayConfig();
    	// 	print_R(WxPayApi::refundQuery($config, $input));
    	// } catch(Exception $e) {
    	// 	Log::ERROR(json_encode($e));
    	// }
    	// 申请退款
        try{
    		// $transaction_id = '4200000215201812043375560422';//$_REQUEST["transaction_id"];
            $out_trade_no = 'xzpay20181201121322';
    		$total_fee = 1;//$_REQUEST["total_fee"];
    		$refund_fee =1;// $_REQUEST["refund_fee"];
    		$input = new WxPayRefund();
    		// $input->SetTransaction_id($transaction_id);
    		$input->SetOut_trade_no($out_trade_no);
    		$input->SetTotal_fee($total_fee);
    		$input->SetRefund_fee($refund_fee);

    		$config = new WxPayConfig();
    	    $input->SetOut_refund_no("sdkphp".date("YmdHis"));
    	    $input->SetOp_user_id($config->GetMerchantId());
    		print_R(WxPayApi::refund($config, $input));
    	} catch(Exception $e) {
    		//Log::ERROR(json_encode($e));
            echo $e->getMessage();
    	}
    	exit();
    }

    public function checkIsSet($value='')
    {
        // code...
        $this->newIsSet(1);
    }

    public function newIsSet()
    {
        // code...
        if(isset($value)) {
            echo 'ok';
        } else {
            echo 'fail';
        }
    }

    public function xiugai($value='')
    {
        // code...
        // asdlf
        $data['uuid'] = 1090;
        $ok = M('WxUsers')->where('open_id="ovABH47yJ3yBjub1hBz2WSxKG7VI"')->save($data);
        var_dump($ok);
    }

    /**
     * 下载对账单
     * @return [type] [description]
     */
    public function downloadBills()
    {
        $_REQUEST["bill_date"] = '20181215';
        $_REQUEST["bill_type"] = 'ALL';
        if(isset($_REQUEST["bill_date"]) && $_REQUEST["bill_date"] != ""){

        	$bill_date = $_REQUEST["bill_date"];
            $bill_type = $_REQUEST["bill_type"];
        	$input = new WxPayDownloadBill();
        	$input->SetBill_date($bill_date);
        	$input->SetBill_type($bill_type);
        	$config = new WxPayConfig();
        	$file = WxPayApi::downloadBill($config, $input);

        	//echo  htmlspecialchars($file, ENT_QUOTES);
        	//TODO 对账单文件处理
            $format_res = deal_WeChat_response($file);
            print_r($format_res);
            exit(0);
        }
    }

}
