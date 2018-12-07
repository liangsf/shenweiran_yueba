<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class UserModel extends CommonModel {

	protected $tableName='wx_users';

	public function addUser($res) {
		$res = (array)$res;

		$data = $res;

		$where['open_id'] = $data['open_id'];
		$rs = $this->where($where)->find();
		if($rs){
			return $rs;
		}
		$uid = $this->add($data);
		return $uid;
	}

	//获取一条用户数据
	public function findUserByOpenId($openId) {
		$where['open_id'] = $openId;
		$rs = $this->where($where)->find();
		if($rs) {
			return $rs;
		}
		return null;
	}

}
