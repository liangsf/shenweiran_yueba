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

}
