<?php
/**
 * 任务 发送模板消息 轮询
 */
class TaskAction extends Action {


    //提醒 用户设置的提醒  -  计划任务控制在2分钟扫描一次
    public function passiveNotice()
    {
        import("yueba.Action.JssdkAction");
        $jssdk = new JssdkAction(C('WX_AppID'), C('WX_AppSecret'));

        $noticeMod = D('Notice');
        $map['n.status'] = 0;
        $map['a.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
        $list = $noticeMod->getNotice($map);

        $formMod = M('Formids');

        $current_time = time();
        foreach ($list as $key => $value) {
            // code...
            $active_time = strtotime($value['active_time']);
            $cha = ($active_time - $current_time)/60;

            $notice = $value['notice'];
            if($cha>($notice-1) && $cha<($notice+1)) {
                //加一分钟 减一分钟
                //发送消息

                //获取form_id
                $formWhere['open_id'] = $value['open_id'];
                $form_rs = $formMod->where($formWhere)->order('create_time asc')->find();

                $cont['title'] = $value['title'];
                $cont['time'] = $value['active_time'];
                $cont['addr'] = $value['adr_name'];
                $rs = $jssdk->sendAffairMsg($value['open_id'], $form_rs['form_id'], $cont);
                $rs = (array)json_decode($rs);


                if($rs['errcode'] != '') {
                    //发送成功
                    $formMod->where('id='.$form_rs['id'])->delete();    //删除form_id
                    $save['status'] = 1;
                    $noticeMod->where('id='.$value['id'])->save($save);
                } else {
                    //发送失败
                }


                $notDate['status'] = 1;
                $noticeMod->where('id='.$value['id'])->save($notDate);  //设置已发送
            }

        }


    }

    //发送数据并清理form_id
    private sendMsgAndDelFormId($openid,$cont)
    {
        $formMod = M('Formids');
        $formWhere['open_id'] = $openid;
        $form_rs = $formMod->where($formWhere)->order('create_time asc')->find();
        $rs = $jssdk->sendAffairMsg($openid, $form_rs['form_id'], $cont);
        $rs = (array)json_decode($rs);
        if($rs['errcode'] != '') {
            //发送成功
            $formMod->where('id='.$form_rs['id'])->delete();    //删除form_id
        } else {
            //发送失败
        }
    }

    //活动开启前5分钟发送提醒消息    2分钟扫描一次
    public function sendNoticeMsg()
    {
        $ufMod = D('UF');
        //活动还差5分钟就开始的 活动大于当前时间 且 活动时间
        //2018-09-17 17:23:10    2018-09-17 17:18:10
        //2018-09-17 17:24:10 +6 2018-09-17 17:22:10 +4
        $start_time = time()+240;
        $end_time = time()+360;
        $start_date = date('Y-m-d H:i:s', $start_time);
        $end_date = date('Y-m-d H:i:s', $end_time);

        $w['af.active_time'] = array(array('gt', $start_date), array('lt', $end_date));
        $w['af.status'] = 0;

        $w['a.status'] = 1;

        $list = $ufMod->search($w);
        //echo M()->getLastSql();

        foreach($list as $k=>$v) {
            $cont['title'] = $v['title'];
            $cont['time'] = $v['active_time'];
            $cont['addr'] = $v['adr_name'];
            $this->sendMsgAndDelFormId($v['open_id'], $cont);
        }

    }



}
