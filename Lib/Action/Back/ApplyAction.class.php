<?php

/**
 * author lsf880101@foxmail.com
 * 申请取消活动接口
 */
class ApplyAction extends MyAction
{


    public function lists($type=0)
    {
        $type = intval($type);
        if($type == 0) {
            $w['status'] = 0;
        }
        if($type == 1) {
            $w['status'] = 1;
        }

        if($type == 2) {
            $w['status'] = 2;
        }

        $fields = "a.id,
                  a.title,
                  a.`open_id`,
                  a.`promise_money`,
                  a.`active_time`,
                  a.`quota`,
                  a.`create_time`,
                  u.name,
                  a.`active_time`,
                  (SELECT COUNT(id)  FROM xz_user_affair AS  ua WHERE ua.affair_id=a.id AND (ua.status <> 0) AND (ua.pay_type <> 0) ) AS persons";
        M('AffairCancelApply')->alias('a')
                                ->join(' xz_wx_users as u ON a.open_id = u.open_id')
                                ->field($fields)
                                ->where($w)
                                ->select();

    }

}
