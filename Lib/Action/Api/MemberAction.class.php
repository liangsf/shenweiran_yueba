<?php

/**
 * 参会成员管理
 */

class MemberAction extends MyAction {

    public function __construct() {
        parent::__construct();
    }

    //获取参与活动的所有人
    public function lists() {
        $ufModel = D('UF');
        $page = intval($_GET['page']);
        $size = intval($_GET['size']);
        $where['a.affair_id'] = intval($_GET['id']);
        $where['a.status'] = array('gt', 0);
        $list = $ufModel->search($where, $page, $size);
        // echo M()->getLastSql();

        if(strpos($rs['avatarurl'], 'http') === FALSE ) {
            $rs['avatarurl'] = C('SITEURL').$rs['avatarurl'];
        }

        foreach ($list as $key => $value) {
            if(strpos($value['avatarurl'], 'http') === FALSE ) {
                $list[$key]['avatarurl'] = C('SITEURL').$value['avatarurl'];
            }
        }

        $this->ajaxReturn($list, '', 200);

    }

    //获取迟到的人
    public function latePerson()
    {
        $ufModel = D('UF');
        $page = intval($_GET['page']);
        $size = intval($_GET['size']);
        $where['a.affair_id'] = intval($_GET['id']);
        $where['_string'] = ' a.status=1 || a.status=3';
        $list = $ufModel->search($where, $page, $size);

        if(strpos($rs['avatarurl'], 'http') === FALSE ) {
            $rs['avatarurl'] = C('SITEURL').$rs['avatarurl'];
        }

        foreach ($list as $key => $value) {
            if(strpos($value['avatarurl'], 'http') === FALSE ) {
                $list[$key]['avatarurl'] = C('SITEURL').$value['avatarurl'];
            }
        }
        $this->ajaxReturn($list, '', 200);
    }

    //获取成功签到的人
    public function signPerson()
    {
        $ufModel = D('UF');
        $page = intval($_GET['page']);
        $size = intval($_GET['size']);
        $where['a.affair_id'] = intval($_GET['id']);
        $where['a.status'] = 2;
        $list = $ufModel->search($where, $page, $size);

        if(strpos($rs['avatarurl'], 'http') === FALSE ) {
            $rs['avatarurl'] = C('SITEURL').$rs['avatarurl'];
        }

        foreach ($list as $key => $value) {
            if(strpos($value['avatarurl'], 'http') === FALSE ) {
                $list[$key]['avatarurl'] = C('SITEURL').$value['avatarurl'];
            }
        }
        $this->ajaxReturn($list, '', 200);
    }

    //参加聚会
    public function add()
    {

        $data['open_id'] = strval($this->openid);
        $data['join_time'] = date('Y-m-d H:i:s', time());
        $data['status'] = 0;
        $data['affair_id'] = intval($_POST['id']);

        $where['open_id'] = strval($this->openid);
        $where['affair_id'] = intval($_POST['id']);

        //获取活动详情
        $affairMod = D('Affair');
        $affairInfo = $affairMod->where("id=".$where['affair_id'])->find();

        $ufModel = D('UF');

        //生成订单号
        $tradeNo = "xzpay".date("YmdHis").mt_rand(1000,9999);
        $data['out_trade_no'] = $tradeNo;
        $rs = $ufModel->where($where)->find();



        if($rs) {
            $payMod = D('Pay');
            $jsApiParameters = $payMod->downOrder($affairInfo['title'], $rs['out_trade_no'], $affairInfo['promise_money'], $this->openid, $affairInfo['id']);
            $this->ajaxReturn($jsApiParameters, '已提交报名，待支付保证金', 200);
        } else {
            //检查参加条件
            $checkRs = checkJoin($affairInfo);
            if(!$checkRs['status']) {
                $this->ajaxReturn($rs, $checkRs['info'], 402);
            }
            //检查参加条件
            $id = $ufModel->add($data);
            if($id) {
                $payMod = D('Pay');
                $jsApiParameters = $payMod->downOrder($affairInfo['title'], $tradeNo, $affairInfo['promise_money'], $this->openid, $affairInfo['id']);
                $this->ajaxReturn($jsApiParameters, '提交报名，待支付保证金', 200);
            } else {
                $this->ajaxReturn($id, '参加失败', 402);
            }

        }

    }


    //签到
    public function sign()
    {
        $affairId = intval($_POST['id']);
        $curentLng = $_POST['lng']; //当前位置
        $curentLat = $_POST['lat'];

        $where['a.affair_id'] = $affairId;
        $where['a.open_id'] = $this->openid;
        $ufMod = D('UF');
        $info = $ufMod->search($where);
        $info = $info[0];

        $activeLng = $info['address_Lng'];
        $activeLat = $info['address_Lat'];

        $juli = getdistance_mi($activeLng, $activeLat,  $curentLng, $curentLat);


        $active_time = strtotime($info['active_time']);
        $current_time = time();

        $base_info = M('BaseConf')->where('id=1')->find();

        if($juli<$base_info['distance']) {
            if($info['join_status'] == 1 && $info['status'] == 0) {
                $updata['sign_time'] = date('Y-m-d H:i:s', time());
                $ufwhere['affair_id'] = $affairId;
                $ufwhere['open_id'] = $this->openid;
                if($current_time<$active_time) {
                    $updata['status'] = 2;
                    $updata['pay_type'] = 2;
                    //执行退款
                    $payMod = D('Pay');
                    $pay_resault = $payMod->refund($info['out_trade_no'], $info);
                    //执行退款
                    if($pay_resault['status']) {
                        $upok = $ufMod->where($ufwhere)->save($updata);
                        $this->ajaxReturn($upok, '签到成功', 200);
                    } else {
                        $this->ajaxReturn($upok, $pay_resault['info'], 403);
                    }

                } else {
                    $updata['status'] = 3;
                    $upok = $ufMod->where($ufwhere)->save($updata);
                    $this->ajaxReturn($upok, '您迟到啦！', 200);
                }

            } else {
                $this->ajaxReturn('', '已签到', 402);
            }
        } else {
            $this->ajaxReturn('', '请在活动地点'.$base_info['distance'].'米范围内签到', 402);
        }



    }


}
