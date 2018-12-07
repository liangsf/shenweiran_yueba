<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class AffairModel extends CommonModel {

	protected $tableName='affairs';

	//添加留言信息
	public function addData($data) {
		if(empty($data['open_id'])) {
			return false;
		}

		return $this->add($data);
	}

	//查询信息
	public function search($map=array(), $page=1, $pageSize=20) {
		$where = array();
		$where = array_merge($where, $map);

		$page = $page?$page:1;
		$pageSize = $pageSize?$pageSize:20;

		$rs = $this->alias('a')
					->join(' xz_wx_users as u ON a.open_id = u.open_id')
					->field('u.nickname, u.name, u.avatarurl, u.mobile, a.id, a.active_time, a.close_time, a.address, a.address_Lng, a.address_Lat, a.promise_money, a.quota, a.adr_name, a.title, a.content, a.status')
					->where($where)
					->page($page, $pageSize)
					->order('a.active_time desc')
					->select();

	    return $rs;
	}

}
