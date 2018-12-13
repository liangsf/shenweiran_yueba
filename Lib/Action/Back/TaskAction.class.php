<?php
/**
 * 任务 发送模板消息 轮询
 */
class TaskAction extends Action {


    //提醒 用户设置的提醒  -  计划任务控制在2分钟扫描一次
    public function passiveNotice()
    {
        import("yueba.Action.JssdkAction");
        $jssdk = new JssdkAction(C('WX_AppID'), C('WX_AppSecret'));

        $noticeMod = D('Notice');
        $map['n.status'] = 0;
        $map['a.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
        $list = $noticeMod->getNotice($map);

        $formMod = M('Formids');

        $current_time = time();
        foreach ($list as $key => $value) {
            // code...
            $active_time = strtotime($value['active_time']);
            $cha = ($active_time - $current_time)/60;

            $notice = $value['notice'];
            if($cha>($notice-1) && $cha<($notice+1)) {
                //加一分钟 减一分钟
                //发送消息

                //获取form_id
                $formWhere['open_id'] = $value['open_id'];
                $form_rs = $formMod->where($formWhere)->order('create_time asc')->find();

                $cont['id'] = $value['affair_id'];
                $cont['title'] = $value['title'];
                $cont['time'] = $value['active_time'];
                $cont['addr'] = $value['adr_name'];
                $rs = $jssdk->sendAffairMsg($value['open_id'], $form_rs['form_id'], $cont);
                $rs = (array)json_decode($rs);


                if($rs['errcode'] == '') {
                    //发送成功
                    $formMod->where('id='.$form_rs['id'])->delete();    //删除form_id
                    $save['status'] = 1;
                    $noticeMod->where('id='.$value['id'])->save($save); //设置已发送
                    echo '发送成功';
                } else {
                    //发送失败  41029 form_id 已经被使用  考虑是否继续发送？
                    echo '发送失败'.$rs['errcode'];
                }
            }

        }


    }

    //发送数据并清理form_id
    private function sendMsgAndDelFormId($openid,$cont)
    {
        $formMod = M('Formids');
        $formWhere['open_id'] = $openid;
        $form_rs = $formMod->where($formWhere)->order('create_time asc')->find();
        $rs = $jssdk->sendAffairMsg($openid, $form_rs['form_id'], $cont);
        $rs = (array)json_decode($rs);
        if($rs['errcode'] == '') {
            //发送成功
            $formMod->where('id='.$form_rs['id'])->delete();    //删除form_id
            echo '发送成功';
        } else {
            //发送失败  41029 form_id 已经被使用  考虑是否继续发送？
            echo '发送失败'.$rs['errcode'];
        }
    }

    //活动开启前5分钟发送提醒消息    2分钟扫描一次
    public function sendNoticeMsg()
    {
        $ufMod = D('UF');
        //活动还差5分钟就开始的 活动大于当前时间 且 活动时间
        //2018-09-17 17:23:10    2018-09-17 17:18:10
        //2018-09-17 17:24:10 +6 2018-09-17 17:22:10 +4
        $start_time = time()+240;
        $end_time = time()+360;
        $start_date = date('Y-m-d H:i:s', $start_time);
        $end_date = date('Y-m-d H:i:s', $end_time);

        $w['af.active_time'] = array(array('gt', $start_date), array('lt', $end_date));
        $w['af.status'] = 0;

        $w['a.status'] = 1;

        $list = $ufMod->search($w);
        //echo M()->getLastSql();

        foreach($list as $k=>$v) {
            $cont['id'] = $v['id'];
            $cont['title'] = $v['title'];
            $cont['time'] = $v['active_time'];
            $cont['addr'] = $v['adr_name'];
            $this->sendMsgAndDelFormId($v['open_id'], $cont);
        }

    }

    //扫描活动 进行关闭
    public function checkAndCloseAffair()
    {
        //检测活动 符合条件 关闭活动
        $affMod = D('Affair');
        // $canClose = $affMod->checkAffair($affairId);
        // if($canClose) {
        //     $affMod->closeAffair($affairId);
        // }
        //检测活动 符合条件 关闭活动

        //都签到的情况（参与人=签到人 && 活动开始后） || 部分签到的情况（领取红包的人=签到的人 && 签到人>0） || 都迟到的情况（参与人=迟到人）

        //获取已经过时一天的活动
        $pre_day_time date('Y-m-d H:i:s', strtotime("-1 day"));

        $where['active_time'] = array('lt', $pre_day_time);
        $where['status'] = 0;
        $list = $affMod->where($where)->select();

        if(count($list)>0) {
            $ufMod = D('UF');

            foreach ($list as $key => $value) {
                // code...
                $countArr = $ufMod->getAllStatus($value['id']);
                if( $countArr['signCount'] == $countArr['joinCount'] ) {
                    //都签到了 直接关闭会议设置会议状态
                    $affMod->closeAffair($value['id']);
                }

                if( $countArr['signCount'] == $countArr['redpackCount'] &&  $countArr['signCount']>0 )  {
                    //部分签到 且 都领取了迟到红包 直接关闭会议
                    $affMod->closeAffair($value['id']);
                }

                if( $countArr['signCount'] == 0 && ( $countArr['joinCount'] == $countArr['lateCount'] ) ) {
                    //没有人签到 都迟到了（没有点击签到的也算迟到） 扣除一定比例的费用返还给用户 并 关闭会议

                    if( count($countArr['joinList']) == $countArr['lateCount']) {

                        foreach($countArr['joinList'] as $lk=>$lv) {
                            $this->refuncAllLateMoney( $lv );
                        }

                        $affMod->closeAffair($value['id']);
                    }


                }

            }
        }


    }

    private function refuncAllLateMoney($affair)
    {

        $info['promise_money'] = $affair['order_money'];
        $info['refund_fee'] = 0;
        $info['open_id'] = $affair['open_id'];
        $info['id'] = $affair['affair_id'];
        $info['out_trade_no'] = $affair['out_trade_no'];

        //执行退款
        $payMod = D('Pay');
        $base_info = M('BaseConf')->where('id=1')->find();

        //获取所有人都迟到扣除的费率
        $all_late_fl = $base_info['all_late_rate'];
        $cutMoney = $info['promise_money']*$all_late_fl/100;
        $info['refund_fee'] = $info['promise_money']-$cutMoney;
        $info['refund_fee'] = sprintf("%.2f",$info['refund_fee']);
        //获取签到退款扣除的费率

        $pay_resault = $payMod->refund($info['out_trade_no'], $info);
        //执行退款

        if($pay_resault['status']) {
            $updata['pay_type'] = 2;
            $updata['refund_money'] = $info['refund_fee'];
            $ufwhere['affair_id'] = $affair['affair_id'];
            $ufwhere['open_id'] = $affair['open_id'];
            $upok = $ufMod->where($ufwhere)->save($updata);


            return true;
        } else {
            return false;
        }


    }



}
