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


	//检查活动是否符合结束条件
    public function checkAffair($id="")
    {

        // code...
        $where['id'] = intval($id);
        $where['status'] = 0;
        //$where['active_time'] = array('gt', date('Y-m-d H:i:s', time()) );
        $affairInfo = $this->where($where)->find();

        $ufMod = D('UF');

        if(empty($affair_info)) {

			$signCount = $ufMod->signPerson($id);	//签到人数

			//检查所有的签到的人是否等于 所有参与的人
			$joinCount = $ufMod->joinPerson($id);	//参与人数

            //检查所有签到的人是否都领取了红包
            $getRedCount = $ufMod->getRedPack($id); //所有领取红包的人
            if( ($signCount == $joinCount) || ($signCount == $getRedCount) ) {
                return true;
            } else {
                return false;
            }
            //如果已经领取 就可以关闭活动
            //否则不可以关闭
        } else {
            return false;
        }

    }

	//结束活动
	public function closeAffair($id='')
	{
		$where['id'] = intval($id);
		$data['status'] = 1;
		$data['end_time'] = date('Y-m-d H:i:s', time());
		$ok = $this->where($where)->save($data);
		if($ok>0) {
			return true;
		} else {
			return false;
		}
	}



}
