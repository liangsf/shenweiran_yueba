<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class MessageModel extends CommonModel {

	protected $tableName='message';

	//添加留言信息
	public function addMsg($data) {
		if(empty($data['open_id'])) {
			return false;
		}

		return $this->add($data);
	}

}
