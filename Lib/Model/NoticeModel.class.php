<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class NoticeModel extends CommonModel {

	protected $tableName='affair_notice';

	//设置提醒
	public function setNotice($data)
    {
        if(!is_array($data) ){
            return false;
        }


        $where['open_id'] = $data['open_id'];
        $where['affair_id'] = $data['affair_id'];
        $rs = $this->where($where)->find();
        if($rs) {
            $this->where('id='.$rs['id'])->save($data);
        } else {
            $this->add($data);
        }
        return true;
    }

	//获取提醒的数据
	public function getNotice($map)
	{
		$where = array();
		$where = array_merge($where, $map);

		$list = $this->alias('n')
					->join(' xz_affairs as a ON n.affair_id = a.id')
					->field('n.*, a.title, a.`active_time`, a.`adr_name`')
					->where($where)
					->select();

	    return $list;
	}

}
