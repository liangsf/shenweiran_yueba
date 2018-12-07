<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class UFModel extends CommonModel {

	protected $tableName='user_affair';


	//查询信息
	public function search($map=array(), $page=1, $pageSize=20) {
		$where = array();
		$where = array_merge($where, $map);

		$page = $page?$page:1;
		$pageSize = $pageSize?$pageSize:20;

		$rs = $this->alias('a')
					->join(' xz_wx_users as u ON a.open_id = u.open_id')
					->join(' xz_affairs as af ON a.affair_id = af.id')
					->field('a.join_time, a.open_id, a.sign_time, a.out_trade_no, a.pay_time, a.status as join_status, u.nickname, u.name, u.avatarurl, u.mobile, af.id, af.active_time, af.close_time, af.address, af.address_Lng, af.address_Lat, af.promise_money, af.quota, af.adr_name, af.title, af.content, af.status')
					->where($where)
					->page($page, $pageSize)
					->order('af.active_time desc')
					->select();

	    return $rs;
	}


}
