<?php

/**
 * author lsf880101@foxmail.com
 * 基础信息配置
 */
class BaseAction extends MyAction
{

    //获取配置信息
    public function find() {
        $rs = M('BaseConf')->where('id=1')->find();
        $this->ajaxReturn($rs, 'ok', 200);
    }

    //修改信息
    public function upInfo() {
        $body = getPostPayload();

        if($body['sxf']) {
            $data['join_fl'] = $body['sxf'];
        }

        if($body['tic']) {
            $data['out_fl'] = $body['tic'];
        }

        if($body['distance']) {
            $data['distance'] = $body['distance'];
        }

        if($body['all_late_rate']) {
            $data['all_late_rate'] = $body['all_late_rate'];
        }

        M('BaseConf')->where('id=1')->save($data);
        $this->ajaxReturn('ok', 'ok', 200);
    }
}
