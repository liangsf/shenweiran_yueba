<?php


// 本类由系统自动生成，仅供测试用途
class IndexAction extends MyAction {
    public function index(){
        //echo Util::getHttpHeader(Constants::WX_HEADER_CODE);
        echo WX_HEADER_CODE;
    	$this->show('thanks');
    }


    public function test() {
        $tranMod = new Model();
        $msgMod = D('Message');
        $UserAffairMod = M('UserAffair');
        $tranMod->startTrans();
        $data['title'] = 'xxxx';
        $data['content'] = 'fffff';
        $msgMod->add($data);

        $dd['open_id'] = '123123';
        $UserAffairMod->add($dd);
        if(1) {

            $tranMod->commit();
        } else {
            echo '回滚';
            $tranMod->rollback();
        }



    }
}
