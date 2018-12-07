<?php
/**

 */
import("ORG.Util.Util");

class MyAction extends Action {
    protected $openid;
    public function __construct(){

		header('Access-Control-Allow-Origin:*');


        $log = $_POST?$_POST:$_GET;
        $str = date('Y-m-d H:i:s',time()).'----------'.$_SERVER['REQUEST_URI'].':'.json_encode($log)."\r\n";
        file_put_contents('./log.txt',$str , FILE_APPEND);

        $code = $_GET['code'];
        $this->openid = Util::getHttpHeader(WX_HEADER_OPENID) ? Util::getHttpHeader(WX_HEADER_OPENID) : $_GET['openid'];

        if(isset($_POST['formId'])) {
            $this->addFomrId($_POST['formId']);
        }

        if(empty($this->openid) && empty($code)) {
            $this->ajaxReturn('' ,'没有登陆', 401);
        }


    }

    //获取参与活动的人员
    public function getJoinAffairPersonCount($affairId, $status)
    {
        $where['id'] = intval($affairId);
        $where['status'] = intval($status);
        $list = M('UserAffair')->where($where)->select();
        return $list;
    }

    //收集模板消息的 form_id
    private function addFomrId($form_id)
    {
        $data['form_id'] = $form_id;
        $data['create_time'] = time();
        M('Formids')->add($data);
    }

}
