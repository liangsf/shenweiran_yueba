<?php

class AffairAction extends MyAction {

    public function __construct() {
        parent::__construct();
    }

    //添加聚会活动
    public function add () {
        $data = $_POST;

        $data['quota'] = intval($data['quota']);

        $data['open_id'] = strval($this->openid);

        try {
            $tranMod = new Model();


            $affairMod = D('Affair');
            $id = $affairMod->addData($data);
            if($id) {
                if($data['notice']) {
                    $Notice = D('Notice');
                    $notice_arr = array();
                    $notice_arr['open_id'] = $this->openid;
                    $notice_arr['affair_id'] = $id;
                    $notice_arr['notice'] = $data['notice'];
                    $notice_ok = $Notice->setNotice($notice_arr);
                }


                if(!empty($data['i_join'])) {
                    $user_arrair_data['open_id'] = strval($this->openid);
                    $user_arrair_data['affair_id'] = $id;
                    $userAffairId = M('UserAffair')->add($user_arrair_data);
                    if($userAffairId) {
                        $tranMod->commit();
                        $this->ajaxReturn(['id'=>$id], '添加成功', 200);
                    } else {
                        $this->ajaxReturn('', '添加失败', 400);
                    }
                }
                $this->ajaxReturn(['id'=>$id], '添加成功', 200);
            } else {
                $tranMod->rollback();
                $this->ajaxReturn('', '添加失败', 400);
            }

        } catch (Exception $e) {
            $this->ajaxReturn([
                'loginState' => E_AUTH,
                'error' => $e->getMessage()
            ], '异常', 444);
        }



    }

    //查询一个活动
    public function find () {
        $id = intval($_GET['id']);
        $affairMod = D('Affair');

        $where['id'] = $id;

        $info = $affairMod->where($where)->find();
        //$personList = $this->getJoinAffairPersonCount($id, 1);    //获取所有报名成功的人
        //$info['all_promise_money'] = $info['promise_money']*count($personList);



        //获取提醒时间
        $notice_where['open_id'] = strval($this->openid);
        $notice_where['affair_id'] = $info['id'];
        $noticeRs = D('Notice')->where($notice_where)->find();

        $info['notice'] = $noticeRs['notice'];

        $info = $this->getWaitAllot($info, false);

        $this->ajaxReturn($info, 'ok', 200);
    }

    //修改聚会信息
    public function upInfo()
    {
        $data = $_POST;

        $where['open_id'] = strval($this->openid);
        $where['id'] = intval($data['id']);
        $where['status'] = 0;
        $data['update_time'] = date('Y-m-d H:i:s', time());



        $affairMod = D('Affair');

        //设置提醒
        if(isset($data['notice']) && $data['notice']>0) {
            $count = $affairMod->where($where)->count();
            $Notice = D('Notice');
            $notice_arr = array();
            $notice_arr['open_id'] = $this->openid;
            $notice_arr['affair_id'] = intval($data['id']);
            $notice_arr['notice'] = $data['notice'];
            if($count<1) {
                $notice_ok = $Notice->setNotice($notice_arr);
                if($notice_ok) {
                    $this->ajaxReturn('', '设置提醒成功', 200);
                } else {
                    $this->ajaxReturn('', '设置提醒失败', 402);
                }
            } else {
                $notice_ok = $Notice->setNotice($notice_arr);
            }
        }

        $ok = $affairMod->where($where)->save($data);



        if($ok) {
            $this->ajaxReturn(['id'=>$data['id']], '修改成功', 200);
        } else {
            $this->ajaxReturn('fail', '活动已开始不可以修改', 402);
        }

    }

    //获取自己 参与的与发起的所有活动
    public function lists()
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
                $where['af.active_time'] = array('lt', date('Y-m-d H:i:s', time()));
            }
            $where['af.status'] = $data['status'];
        }

        //判断是我参与的还是我创建的
        if(isset($data['isme'])) {
            if($data['isme'] == 'true') {
                //$where['_string'] =  ' af.open_id = a.open_id ';

                $awhere['a.open_id'] = strval($this->openid);
                $list = D('Affair')->search($awhere, $page, $size);
                $list = $this->getWaitAllot($list);
                $this->ajaxReturn($list, '', 200);
            } else {
                $where['_string'] =  ' af.open_id != a.open_id ';
            }

        }

        $list = $ufModel->search($where, $page, $size);

        $list = $this->getWaitAllot($list);

        $this->ajaxReturn($list, '', 200);
    }

    private function getWaitAllot($list, $isList=true)
    {
        $ufMod = D('UF');

        if($isList) {
            foreach ($list as $key => $value) {
                // code...
                $money = $ufMod->getWaitAllotMoney($value['id']);
                $value['wait_allot_money'] = $money['money'];
                $list[$key] = $value;
            }
            return $list;
        } else{

            $money = $ufMod->getWaitAllotMoney($list['id']);
            $list['wait_allot_money'] = $money['money'];

            return $list;

        }

    }

    //即将开始的聚会   状态等于进行中 且 开始时间大于当前时间的活动
    public function hangAffairs()
    {
        $data = $_POST;

        $page = intval($_GET['page']);
        $size = intval($_GET['size']);

        $ufModel = D('UF');

        $where['a.open_id'] = strval($this->openid);
        $where['af.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
        $where['af.status'] = 0;
        $where['a.status'] = array('eq', 1);

        $list = $ufModel->search($where, $page, $size);
        $list = $this->getWaitAllot($list);

        $this->ajaxReturn($list, '', 200);
    }


    //取消活动
    public function cancelAffair()
    {

        //$tranMod = new Model(); //事物
        //$tranMod->startTrans();

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

        $data['status'] = 3;    //活动状态3代表申请取消活动
        $isOk = $affairMod->where($affWhere)->save($data);
        //$str = date('Y-m-d H:i:s',time()).'----------SQL:'.M()->getLastSql().$ok."\r\n";
        //file_put_contents('./log.txt',$str , FILE_APPEND);


        if($isOk) {

            //增加申请取消记录
            $applyData['open_id'] = $this->openid;
            $applyData['affair_id'] = $affairId;
            M('AffairCancelApply')->add($applyData);

            $this->ajaxReturn('', '取消成功', 200);
            /*$ufMod = D('UF');
            $ufWhere['affair_id'] = $affairId;
            $ufWhere['_string'] = " status = 1 or status = 2";
            $ufList = $ufMod->where($ufWhere)->select();
            if(count($ufList<1)) {
                $tranMod->commit();
                $this->ajaxReturn('', '取消成功', 200);
            } else {
                //执行退款
                //$refundStatus = $this->refunds($affairId);
                //提交退款申请
                //执行退款
            }

            if($refundStatus) { //退款成功
                $tranMod->commit();
                $this->ajaxReturn('', '取消成功', 200);
            } else {
                $tranMod->rollback();
                $this->ajaxReturn('', '取消异常', 402);
            }*/
        } else {
            $this->ajaxReturn('', '取消失败', 402);
        }

    }

    /**
     * [refunds 活动取消给所有参与人退款]
     * @param  [int] $id [活动id]
     */
    private function refunds($id)
    {
        // $baseInfo = M('BaseConf')->where('id=1')->find();   //获取退款费率
        //
        // $ufMod = D('UF');
        // $ufWhere['affair_id'] = $affairId;
        // $ufWhere['_string'] = " status = 1 or status = 2";
        // $ufList = $ufMod->where($ufWhere)->select();
        //
        //
        //
        // $payMod = D('Pay');
        // $pay_resault = $payMod->refund($info['out_trade_no'], $info);
    }


    /**
     * [closeAffair 关闭活动，设置人员签到签到情况、退还保证金、分发红包]
     * @param  [type] $id [活动id]
     * @return [type]     [description]
     */
    public function orgerCloseAffair()
    {
        $id = intval($_POST['id']);
        $persons = json_decode($_POST['users'], true); //迟到人列表

        // file_put_contents('./log.txt',json_encode($data) , FILE_APPEND);
        // exit;

        $id = intval($id);
        $affMod = D('Affair');
        $afWhere['id'] = $id;
        $afWhere['open_id'] = $this->openid;
        $afInfo = $affMod->where($afWhere)->find();

        //确认是活动创建者
        //活动已开始且活动进行中的才可以处理
        if(!$afInfo) {
            $this->ajaxReturn('', '您不是组织者', 402);
        }

        if( $afInfo['status'] != 0 ) {
            $this->ajaxReturn('', '活动已结束', 402);
        }

        $current_date = date('Y-m-d H:i:s', time());
        if( $afInfo['active_time'] > $current_date) {
            $this->ajaxReturn('', '活动还没有开始', 402);
        }

        $signList = array();    //签到的人
        $lateList = array();    //迟到的人
        if( is_array($persons) && count($persons) > 0 ) {
            foreach($persons as $k=>$v) {
                if(intval($v['type']) == 1) {
                    $signList[] = $v;
                } else {
                    $lateList[] = $v;
                }
            }
        } else {
            $this->ajaxReturn('', '请勾选参会人', 402);
        }

        $ufMod = D('UF');


        //$openidArr = explode(',', $openids);
        //设置用户签到状态
        if( count($persons) >0 && is_array($persons) ) {

            $tranMod = new Model();
            $tranMod->startTrans();

            $upstatus = true;
            foreach( $persons as $k=>$v) {
                if(intval($v['type']) == 1) {
                    //设置签到
                    $w['open_id'] = strval($v['open_id']);
                    $w['affair_id'] = $id;
                    $ufData['status'] = 2;
                    $ufData['sign_time'] = date('Y-m-d H:i:s', time());
                    $ok = $ufMod->where($w)->save($ufData);
                    if($ok<1) {
                        $upstatus = false;
                    }
                } else{
                    //设置迟到
                    $w['open_id'] = strval($v['open_id']);
                    $w['affair_id'] = $id;
                    $ufData['status'] = 3;
                    $ufData['sign_time'] = date('Y-m-d H:i:s', time());
                    $ok = $ufMod->where($w)->save($ufData);
                    if($ok<1) {
                        $upstatus = false;
                    }
                }

            }

            if( $upstatus == true ) {
                $tranMod->commit();
            } else {
                $tranMod->rollback();
                $this->ajaxReturn('', '操作失败', 402);
            }
        }




        $payMod = D('Pay');
        $base_info = M('BaseConf')->where('id=1')->find();

        $signCount = count($signList);
        $lateCount = count($lateList);

        if($signCount == 0) {
            //都迟到退款的扣除比例
            $backRate = $base_info['all_late_rate'];
        }
        if($lateCount == 0) {
            //都签到退款的扣除比例
            $backRate = $base_info['join_fl'];
        }

        if($signCount !=0 && $lateCount != 0) {
            //分钱的扣除比例
            $packRate = $base_info['out_fl'];
        }



        $cutMoney = $afInfo['promise_money']*$backRate/100;
        $afInfo['refund_fee'] = $afInfo['promise_money']-$cutMoney;
        $back_money = sprintf("%.2f",$afInfo['refund_fee']);

        //退款 start
        $reback_status = true;
        $back_res = $ufMod->refundPromise($id, $back_money);
        if(!$back_res) {
            //退款有失败
            $reback_status = false;
        }
        //退款 end

        //分发红包 start
        $red_pack_status = true;
        if($signCount !=0 && $lateCount != 0) {

            $cutPackMoney = $afInfo['promise_money']*$packRate/100;
            $red_money = $afInfo['promise_money']*$lateCount-$cutPackMoney;
            $send_red_money = $red_money/$signCount;
            $send_red_money = sprintf("%.2f",$send_red_money);


            $tsMod = D('Transaction');
            $wtMod = D('WxTrans');
            foreach($signList as $k=>$v) {

                $data['hb_type'] = 1;
                $data['red_money'] = $send_red_money;
                $data['hb_time'] = date('Y-m-d H:i:s', time());
                $where['affair_id'] = $id;
                $where['open_id'] = $v['open_id'];
                $where['status'] = 2;
                $where['hb_type'] = 0;
                $isOk = $ufMod->where($where)->save($data);

                if($isOk) {
                    $redpackstatus = $wtMod->WxTransfers($v['open_id'], $send_red_money, $afInfo['title']);
                    if($redpackstatus['status'] == true) {
                        //增加支付记录
                        $tsData['open_id'] = $v['open_id'];
                        $tsData['affair_id'] = $id;
                        $tsData['type'] = 4;
                        $tsData['cash_fee'] = $send_red_money*100;
                        $tsData['total_fee'] = $send_red_money*100;
                        $tsData['out_trade_no'] = $redpackstatus['data']['partner_trade_no'];
                        $tsData['transaction_id'] = $redpackstatus['data']['payment_no'];
                        $tsData['wx_response'] = $redpackstatus['data']['wx_response'];
                        $tsMod->add($tsData);
                    } else {
                        $red_pack_status = false;   //企业转账失败
                    }
                } else {
                    $red_pack_status = false;
                }

            }


        }

        //分发红包 end


        //关闭活动
        if($red_pack_status && $reback_status) {
            $js_data['status'] = 1;
            $affMod->where($afWhere)->save($js_data);
            $this->ajaxReturn('', '分配成功', 200);
        } else {
            $this->ajaxReturn('', '红包发放中', 200);
        }



    }




    //操作按钮
    public function getOptBtn()
    {
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

          if($current_time<$active_time && $afInfo['status'] == 0) {
              $btns['extend'] = true;
          }


          //获取参会与人信息
          $ufMod = D('UF');
          $ufWhere['affair_id'] = $affairId;
          $ufWhere['open_id'] = $openid;
          //$ufWhere['status'] = 1;
          $ufInfo = $ufMod->where($ufWhere)->find();


          if($afInfo['open_id'] == $openid && $current_time<$active_time && $afInfo['status'] == 0) {
              $btns['update'] = true;
              $chae = $active_time - $current_time;
              if($chae<=7200) {
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

              $chae = $active_time-$current_time;
              if($chae<=7200) {
                  $btns['update'] = false;
              }
          }

          if(!empty($ufInfo)) {
              if($ufInfo['status']==1 && $ufInfo['pay_type']==1 && $afInfo['status'] == 0 && $current_time<$active_time) {
                  //当距离活动开始还要两小时的时候显示签到
                  $chae = $active_time - $current_time;
                    if($chae<=7200) {
                        $btns['sign'] = true;
                    }
              }

              // if($ufInfo['status']==2 && $ufInfo['hb_type'] == 0 && $current_time>$active_time && $afInfo['status'] == 0) {
              //     $btns['getMoney'] = true;
              // }

              if($current_time>$active_time) {
                  $btns['view'] = true;
              }
          }



          $this->ajaxReturn($btns, 'ok', 200);


    }


}
