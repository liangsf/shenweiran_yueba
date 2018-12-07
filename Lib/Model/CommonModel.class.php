<?php
/**
 * 数据库操作基类
 *@author lsf <lsf880101@foxmail.com>
 */
class CommonModel extends Model {
	protected $tableName;
		
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 获取单条数据 
	 * @author lsf <lsf880101@foxmail.com>
	 */
	public function get_one_data($where,$fields='*'){
		return $this->where($where)->field($fields)->find();
	}
	
	/**
	 * 获取多条数据
	 * @author lsf <lsf880101@foxmail.com>
	 */
	public function get_more_data($where,$fields='*'){
		$result = array();
		if ($limit == 1){
			$result = $this->get_one_data($where,$fields);
		}else{
				return $this->where($where)->field($fields)->select();
		}
		return $result;
	}
	/**
	 * 获取分页数据
	 * @author lsf <lsf880101@foxmail.com>
	 * Enter description here ...
	 * @param $where
	 * @param $fields
	 * @param $order
	 * @param $ispage
	 * @param $page
	 */
	public function get_page_data($where,$fields='*',$order,$page=20){
		$result = array();
		import("ORG.Util.Page");
		$count = $this->where($where)->count();	// 查诟满趍要求癿总记录数
		$Page = new Page($count,$page);	// 实例化分页类 传入总记录数和每页显示癿记录数
		$show = $Page->show();	// 分页显示输出
		$list = $this->where($where)->order($order)->field($fields)->limit($Page->firstRow.','.$Page->listRows)->select();
		$result['data'] = $list;
		$result['page'] = $show;
		return $result;
	}
}