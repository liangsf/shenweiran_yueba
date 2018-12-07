<?php


class WxPayAction extends MyAction {

    public function __construct() {
        parent::__construct();
    }

    //支付成功的回调
    public function payNotify()
    {
        // $xml = '<xml><appid><![CDATA[wx486d78adc2a70186]]></appid><attach><![CDATA[test]]></attach><bank_type><![CDATA[CFT]]></bank_type><cash_fee><![CDATA[1]]></cash_fee><fee_type><![CDATA[CNY]]></fee_type><is_subscribe><![CDATA[N]]></is_subscribe><mch_id><![CDATA[1487094692]]></mch_id><nonce_str><![CDATA[a6ij4cyjgl67butf8k4d0xc6jwwiv5jv]]></nonce_str><openid><![CDATA[ovABH4_ex4yiyUNLHEc086r5wQ1U]]></openid><out_trade_no><![CDATA[xzpay20181203111609]]></out_trade_no><result_code><![CDATA[SUCCESS]]></result_code><return_code><![CDATA[SUCCESS]]></return_code><sign><![CDATA[319959D1DDFF8C9D50CAFA5A67EA8993186F5A033AB41087C46C45800BB34B93]]></sign><time_end><![CDATA[20181203111654]]></time_end><total_fee>1</total_fee><trade_type><![CDATA[JSAPI]]></trade_type><transaction_id><![CDATA[4200000234201812033874964111]]></transaction_id></xml>';
        //
        // $val = WxPayResults::Init($xml);
        // print_R($val);
        // $this->ajaxReturn('支付成功', '支付成功', 200);
    }

    //调用统一下单
    public function downOrder()
    {
        //$this->openid = 'ovABH47yJ3yBjub1hBz2WSxKG7VI';
        if(!isset($_POST['affair_id'])) {
            $this->ajaxReturn('', '活动id不能为空', 403);
        }
        $affairId = intval($_POST['affair_id']);
        $affairMod = D('Affair');
        $affWhere['id'] = $affairId;
        $affairInfo = $affairMod->where($affWhere)->find();

        $ufMod = D('UF');
        $ufWhere['affair_id'] = $affairId;
        $ufWhere['open_id'] = $this->openid;
        $ufInfo = $ufMod->where($ufWhere)->find();


        $payMod = D('Pay');
        $jsApiParameters = $payMod->downOrder($affairInfo['title'], $ufInfo['out_trade_no'], $affairInfo['promise_money'], $this->openid);
        $this->ajaxReturn($jsApiParameters, '获取成功', 200);
    }



}
