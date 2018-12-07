<?php
include "./WxPay/lib/WxPay.Api.php";
include './WxPay/lib/WxPay.Notify.php';
class NoticeAction extends MyAction {

    public function __construct() {
        parent::__construct();
    }

    //设置提醒
    public function setNotice($data)
    {
        if(!is_array($data) ){
            return false;
        }
        $anMod = M('AffairNotice');

        $where['open_id'] = $data['open_id'];
        $where['affair_id'] = $data['affair_id'];
        $rs = $anMod->where($where)->find();
        if($rs) {
            $anMod->where('id='.$rs['id'])->save($data);
        } else {
            $anMod->add($data);
        }
        return true;
    }



}
