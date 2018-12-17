<?php

/**
 * author lsf880101@foxmail.com
 * 活动相关
 */
class AffairAction extends MyAction
{

    /**
     * [find 活动详情及参与用户]
     * @param  integer $id [活动id]
     * @return [json]      {data:{list:[参与人列表],info:活动详情, info:, status:200}
     */
    public function find($id=0) {
        $id = intval($id);
        $info = D('Affair')->find($id);

        //参会人员
        $w['ua.affair_id'] = $id;
        $list = M('UserAffair')->alias('ua')
                        ->join(' xz_wx_users as u ON ua.open_id = u.open_id')
                        ->field("ua.*, u.name, u.nickname, u.mobile")
                        ->where($w)
                        ->select();

        $res['data']['list'] = $list;
        $res['data']['info'] = $info;
        $this->ajaxReturn($res, 'ok', 200);
    }

    /**
     * [lists 活动列表]
     * @param  integer $type [1即将开始 2待关闭 3已关闭]
     * @param  string  $id   [活动id]
     * @param  string  $name [活动title]
     * @param  integer $page [当前页]
     * @param  integer $size [每页条数]
     * @return [type]        [description]
     */
    public function lists($type=1, $id='', $name="", $page=1, $size=20)
    {


        $page = intval($page);
        $size = intval($size);
		$page = $page?$page:1;
		$pageSize = $size?$size:20;

        $where = array();
        if($type == 1) {
            //即将开始
            $where['a.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
            $where['a.status'] = 0;
        }
        if($type == 2) {
            //待关闭
            $where['a.status'] = 0;
            $where['a.active_time'] = array('lt', date('Y-m-d H:i:s', time()));
        }
        if($type == 3) {
            $where['_string'] = " a.status=1 or a.status=3";
        }
        $id = intval($id);
        if($id != 0) {
            $where['a.id'] = $id;
        }

        $name = strval($name);
        if($name !== '') {
            $where['a.title'] = array('like', '%'.$name.'%');
        }

        //$where['ua.status'] = array('neq', 0);  //确保都是支付的
        //$where['ua.pay_type'] = array('neq', 0);  //确保都是支付的

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
        $order = "a.id";

		$list = D('Affair')->alias('a')
					->join(' xz_wx_users as u ON a.open_id = u.open_id')
					->field($fields)
					->where($where)
					->page($page, $pageSize)
					->order($order)
					->select();

        $count = D('Affair')->alias('a')
					->where($where)
					->count('id');

        //echo M()->getLastSql();
        $res['data']['list'] = $list;   //记录列表
        $res['data']['count'] = $count; //记录总数
        $res['data']['page'] = $page;   //页数
        $res['data']['size'] = $size;   //条数

        $this->ajaxReturn($res, 'ok', 200);

    }


}
