<?php

/**
 * author lsf880101@foxmail.com
 * 申请取消活动接口
 */
class ApplyAction extends MyAction
{

    /**
     * [lists 申请活动关闭列表]
     * @param  integer $type [0 待审核 1已审核]
     * @param  integer $page [description]
     * @param  integer $size [description]
     * @return [type]        [description]
     */
    public function lists($type=0, $page=1, $size=20)
    {
        $type = intval($type);

        $page = intval($page);
        $size = intval($size);
        $page = $page?$page:1;
        $pageSize = $size?$size:20;

        if($type == 0) {
            $w['a.status'] = 0;
        }
        if($type == 1) {
            $w['a.status'] = 1;
        }

        if($type == 2) {
            $w['a.status'] = 2;
        }

        $fields = "a.id,
                  af.title,
                  a.`open_id`,
                  u.nickname,
                  af.`promise_money`,
                  af.`active_time`,
                  af.`quota`,
                  af.`create_time`,
                  u.name,
                  (SELECT COUNT(id)  FROM xz_user_affair AS  ua WHERE ua.affair_id=a.affair_id AND (ua.status <> 0) AND (ua.pay_type <> 0) ) AS persons";

        $list = M('AffairCancelApply')->alias('a')
                                ->join(' xz_wx_users as u ON a.open_id = u.open_id')
                                ->join(' xz_affairs as af ON af.id = a.affair_id')
                                ->field($fields)
                                ->where($w)
                                ->page($page, $pageSize)
                                ->select();


        //echo M()->getLastSql();
        $count = M('AffairCancelApply')->alias('a')->where($w)->count();
        $res['data']['list'] = $list;
        $res['data']['count'] = $count; //记录总数
        $res['data']['page'] = $page;   //页数
        $res['data']['size'] = $size;   //条数

        return $res;
    }


    /**
     * [approval 审批]
     * @param  integer $id   [活动id]
     * @param  integer $type [审批结果]
     * @return [type]        [description]
     */
    public function approval($id=0, $type=0)
    {
        $id = intval($id);
        $type = intval($type);

        if($id == 0 || $type == 0) {
            $this->ajaxReturn('', '审批失败', 402);
        }

        $w['id'] = $id;
        if($type == 1) {
            $data['status'] = 1;
        }
        if($type == 2) {
            $data['status'] = 2;
        }
        $data['approval_time'] = date('Y-m-d H:i:s', time());
        $ok = M('AffairCancelApply')->where($w)->save($data);
        if( $ok > 0) {
            if(type=1) {
                $this->refundJoinMoney($id);
            }
            $this->ajaxReturn('', '审批成功', 200);
        } else {
            $this->ajaxReturn('', '审批失败', 402);
        }
    }

    private function refundJoinMoney($id)
    {
        $baseInfo = M('BaseConf')->where('id=1')->find();
        $afInfo = M('Affairs')->where('id='.$id)->find();

        $UF = D('UF');

        $memberList = $UF->where( array('affair_id' => $id, 'status' => 1) )->select();

        $pay = D('Pay');

        //获取签到退款扣除的费率
        $join_fl = $baseInfo['join_fl'];
        $cutMoney = $afInfo['promise_money']*$join_fl/100;
        $info['refund_fee'] = $afInfo['promise_money']-$cutMoney;
        $info['refund_fee'] = sprintf("%.2f",$info['refund_fee']);
        //获取签到退款扣除的费率

        if(count($memberList) > 0) {
            foreach ($memberList as $key => $value) {
                // code...
                $info = array();
                $info['open_id'] = $value['open_id'];
                $info['promise_money'] = $afInfo['promise_money'];
                $info['refund_fee'] =
                $info['id'] = $afInfo['id'];
                $pay_resault = $pay->refund($value['out_trade_no'], $info);
                if($pay_resault['status']) {
                    $ufwhere['affair_id'] = $afInfo['id'];
                    $ufWhere['pay_type'] = 1;
                    $ufwhere['open_id'] = $value['open_id'];
                    $updata['refund_money'] = $info['refund_fee'];  //退款金额 后续退款在设置
                    $updata['pay_type'] = 2;
                    $upok = $ufMod->where($ufwhere)->save($updata);
                }

            }
        }
    }

}
