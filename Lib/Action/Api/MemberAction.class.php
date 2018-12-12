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



    //获取成功签到的人
    public function hongbao($id)
    {

        $id = intval($id);

        $res = array();
        $res['join_sign'] = []; //签到并领取准时红包的人员列表
        $res['join'] = []; //所有参与人列表
        $res['get_red_pack'] = []; //已领取红包的人员列表
        $res['not_get_red_pack'] = []; //未领取红包的人员列表
        $res['join_count'] = 0; //准时红包总个数
        $res['join_sign_count'] = 0;     //已领准时退款红包个数
        $res['sign_money'] = 0; //签到准时红包总金额
        $res['late_money'] = 0; //总迟到红包金额
        $res['red_pack_count'] = 0;  //总红包个数
        $res['get_red_pack_count'] = 0;  //领取红包个数

        $where['t.affair_id'] = intval($id);
        $transList = D('Transaction')->search($where);
        //echo M()->getLastSql();

        $ufMod = D('UF');
        $latePersonCount = $ufMod->latePerson($id);
        //活动基本信息
        $afInfo = D('Affair')->find($id);

        $promise_money = 0;
        $res = array();
        foreach($transList as $k=>$v) {

            if(strpos($v['avatarurl'], 'http') === FALSE ) {
                $v['avatarurl'] = C('SITEURL').$v['avatarurl'];
            }

            if($v['type'] == 2) {
                $res['join_sign'][] = $v;   //签到并领取准时红包的人员列表
            }

            if($v['type'] == 1) {
                $res['join'][] = $v;    //所有参与人列表
                $promise_money = $v['total_fee'];
            }

            if($v['type'] == 4) {
                $res['get_red_pack'][] = $v;    //已领取红包的人员列表
            }

        }

        if($latePersonCount!=0) {
            //没有人迟到 就没有红包可领
            //获取已领取红的人员openid集合
            $openids = array();
            foreach($res['get_red_pack'] as $k=>$v){
                $openids[] = $v['open_id'];
            }

            foreach($res['join_sign'] as $k=>$v) {
                if( !in_array($v['open_id'], $openids) ) {
                    $res['not_get_red_pack'][] = $v;    //未领取红包的人员列表
                }
            }
        }


        $res['join_count'] = count($res['join']);   //准时红包总个数
        $res['join_sign_count'] = count($res['join_sign']);     //已领准时退款红包个数
        $res['sign_money'] = $res['join_count']*$promise_money/100; //签到准时红包总金额
        $res['late_money'] = $latePersonCount*$afInfo['promise_money']; //总迟到红包金额
        if($latePersonCount!=0) {
            $res['red_pack_count'] = 0;
        } else {
            $res['red_pack_count'] = count($res['join_sign']);  //总红包个数
        }
        $res['get_red_pack_count'] = count($res['red_pack']);  //领取红包个数



        $this->ajaxReturn($res, 'ok', 200);

        /*
        //准时签到列表-status=2 && pay_type=2 (需要扣除手续费的钱)  已领红包hb_type=1 && status=2 待领取的hb_type=0 && status=2
        if(isset($_GET['late'])) {
            $where['a.hb_type'] = 1;
        }
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

        $arr['signCount'] = $ufModel->signPerson(intval($_GET['id']));
        $arr['lateCount'] = $ufModel->latePerson(intval($_GET['id']));
        $arr['list'] = $list;
        $this->ajaxReturn($arr, '', 200);*/
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

                    //获取签到退款扣除的费率
                    $join_fl = $base_info['join_fl'];
                    $cutMoney = $info['promise_money']*$join_fl/100;
                    $info['refund_fee'] = $info['promise_money']-$cutMoney;
                    $info['refund_fee'] = sprintf("%.2f",$info['refund_fee']);
                    //获取签到退款扣除的费率

                    $pay_resault = $payMod->refund($info['out_trade_no'], $info);
                    //执行退款
                    if($pay_resault['status']) {
                        $updata['refund_money'] = $info['refund_fee'];
                        $upok = $ufMod->where($ufwhere)->save($updata);


                        //检测活动 符合条件 关闭活动
                        $affMod = D('Affair');
                        $canClose = $affMod->checkAffair($affairId);
                        if($canClose) {
                            $affMod->closeAffair($affairId);
                        }
                        //检测活动 符合条件 关闭活动

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
