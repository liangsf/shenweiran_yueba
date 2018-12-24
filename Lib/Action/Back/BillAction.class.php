<?php

/**
 * [BillAction 对账单]
 */
class BillAction extends MyAction
{
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

    /**
     * [bills 获取支付记录及统计]
     * @param  integer $page [页数]
     * @param  integer $size [条数]
     * @return [type]  $start      [开始日期 如：2018-09-12]
     * @return [type]  $end      [结束日期 如：2018-09-12]
     */
    public function bills($page=1, $size=20)
    {
        $billMod = M('TransactionLog');


        if( !empty($_P0ST['start']) && !empty($_P0ST['end']) ) {
            $star_date = $_P0ST['start'].' 00:00:00';
            $end_date = $_POST['end'].' 23:59:59';
        } else {
            $star_date = date('Y-m-d 00:00:00', strtotime("-1 day"));
            $end_date = date('Y-m-d 23:59:59', strtotime("-1 day"));
        }

        $page = intval($page);
        $size = intval($size);
        $page = $page?$page:1;
        $pageSize = $size?$size:20;


        $w['create_time'] = array(array('egt', $star_date), array('elt', $end_date));
        $list = $billMod->where($w)->page($page, $pageSize)->select();

        $count = $billMod->where($w)->count();

        $w['type'] = 1; //收入
        $inMoney = $billMod->where($w)->field('sum(total_fee) as money')->find();
        $w['type'] = 2; //支出
        $refundMoney = $billMod->where($w)->field('sum(refund_fee) as money')->find();

        $w['type'] = 4; //企业转账
        $outMoney = $billMod->where($w)->field('sum(total_fee) as money')->find();

        $res['data']['list'] = $list;
        $res['data']['count'] = $count;
        $res['data']['income'] = $inMoney['money']/100;
        $res['data']['refund'] = $refundMoney['money']/100;
        $res['data']['pay'] = $outMoney['money']/100;
        $res['data']['ˈprɒfɪt'] = ($inMoney['money']-$refundMoney['money']-$outMoney['money'])/100;

        $this->ajaxReturn($res, 'ok', 200);
    }


}
