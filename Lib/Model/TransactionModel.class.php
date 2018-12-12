<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class TransactionModel extends CommonModel {

	protected $tableName='transaction_log';

	//交易记录查询
	public function search($map=array(), $page=1, $pageSize=20) {
		$where = array();
		$where = array_merge($where, $map);

		$page = $page?$page:1;
		$pageSize = $pageSize?$pageSize:20;

		$rs = $this->alias('t')
					->join(' xz_wx_users as u ON t.open_id = u.open_id')
					->join(' xz_affairs as af ON t.affair_id = af.id')
					->field('t.open_id, t.create_time, t.out_trade_no, t.type, t.refund_fee, t.total_fee, u.nickname, u.name, u.avatarurl, u.mobile, af.id, af.active_time, af.close_time, af.address, af.address_Lng, af.address_Lat, af.promise_money, af.quota, af.adr_name, af.title, af.content, af.status, u.uuid')
					->where($where)
					->page($page, $pageSize)
					->order('t.create_time asc')
					->select();

		return $rs;
	}

}
