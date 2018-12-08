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

	//获取成功签到的人
    public function signPerson($id)
    {
        $ufModel = D('UF');
        $where['affair_id'] = intval($id);
        $where['status'] = 2;
        $count = $ufModel->where($where)->count();
        return $count;
	}

	//获取迟到的人
    public function latePerson($id)
    {
        $ufModel = D('UF');
        $where['affair_id'] = intval($id);
        $where['_string'] = ' status=1 || status=3';
        $count = $ufModel->where($where)->count();
        return $count;
	}

	//获取每个人可以分到的钱
	public function getOneAllotMoney($id)
	{
		//获取后台配置扣除的费率
        $base_info = M('BaseConf')->where('id=1')->find();
        $cutRate = $base_info['out_fl'];

        //获取活动进信息
        $affWhere['id'] = $id;
        $affInfo = M('Affair')->where($affWhere)->find();

        //获取所有迟到的人（没有签到的也算作迟到）
        $lateCount = $this->latePerson($id);

        //获取所有正常签到的人
        $signCount = $this->signPerson($id);

        $allLateMoney = $affInfo['promise_money']*$lateCount;
        $cutMoney = $allLateMoney*$cutRate/100;
        $useMoney = $allLateMoney-$cutMoney;

        //每个人可以分配的钱
        $oneMoney = $useMoney/$signCount;

		$oneMoney = sprintf("%.2f",$oneMoney);
		return $oneMoney;
	}

}
