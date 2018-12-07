<?php
/**
 * @author lsf <lsf880101@foxmail.com>
 */
class NoticeModel extends CommonModel {

	protected $tableName='affair_notice';

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

}
