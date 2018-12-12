<?php

class UserAction extends MyAction {

    public function __construct() {
        parent::__construct();
    }

    //获取用户基本信息
    public function findUserInfo() {
        $rs = D('User')->findUserByOpenId($this->openid);

        if(strpos($rs['avatarurl'], 'http') === FALSE ) {
            $rs['avatarurl'] = C('SITEURL').$rs['avatarurl'];
        }

        $this->ajaxReturn($rs, '', 200);
    }

    //修改用户信息
    public function upUser() {
        $data = $_POST;



        $data['open_id'] = strval($this->openid);

        D('User')->save($data);
        $this->ajaxReturn('ok', 'ok', 200);
    }

    //修改头像
    public function savePhoto() {
        $file = $_FILES['file'];
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '10M');

        // 限制文件格式，支持图片上传
        if ($file['type'] !== 'image/jpeg' && $file['type'] !== 'image/png' && $file['type'] !== 'image/jpg') {
            $this->ajaxReturn('', '不支持的上传图片类型', 402);
            return;
        }

        // 限制文件大小：5M 以内
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->ajaxReturn('', '上传图片过大，仅支持 10M 以内的图片上传', 402);
            return;
        }

        try {
            $up_rs = imgUpload('Phones','jpg,png,jpeg');

            if(!is_array($up_rs)) {
                $this->ajaxReturn('', '上传失败', 402);
            }

            $data['open_id'] = strval($this->openid);
            $data['avatarurl'] = $up_rs[0]['savepath'].$up_rs[0]['savename'];

            $ok = D('User')->save($data);
            $url = C('SITEURL').$data['avatarurl'];
            if($ok) {
                $this->ajaxReturn($url, '修改成功', 200);
            } else {
                $this->ajaxReturn('', '修改失败', 402);
            }


        } catch (Exception $e) {
            $this->ajaxReturn('', '图片上传异常', 405);
        }

    }

    //领取红包  -- 领取红包（平分迟到的保证金）
    public function receiveCash($value='')
    {
        $affairId = intval($_POST['id']);
        $openid = $this->openid;

        $ufMod = D('UF');

        $oneMoneyRes = $ufMod->getOneAllotMoney($affairId);
        if($oneMoneyRes['status'] == 0) {
            $this->ajaxReturn('', '活动还没开始不可以领取红包', 403);
        }
        if($oneMoneyRes['status'] == 2) {
            $this->ajaxReturn('', '大家都迟到了，保证金在活动结束后退回', 403);
        }
        if($oneMoneyRes['status'] == 3) {
            $this->ajaxReturn('', '大家准时到达 可喜可贺！', 403);
        }
        if($oneMoneyRes['money']<=0) {
            $this->ajaxReturn('', '没有可分配的红包', 403);
        }

        $param['open_id'] = $openid;
        $param['affair_id'] = $affairId;
        $param['hb_type'] = 0;
        $param['status'] = 2;
        $order = $ufMod->where($where)->find();
        if($order) {

            // code...
            try {
                // 企业转账
                $oneMoneyRes['money'] = sprintf("%.2f",$oneMoneyRes['money']);
                $redpackstatus = D('WxTrans')->WxTransfers($openid, $oneMoneyRes['money']);


                // 企业转账
                if($redpackstatus['status'] == true) {
                    //增加支付记录
                    $tsMod = D('Transaction');
                    $tsData['open_id'] = $openid;
                    $tsData['affair_id'] = $affairId;
                    $tsData['type'] = 4;
                    $tsData['cash_fee'] = $oneMoneyRes['money']*100;
                    $tsData['total_fee'] = $oneMoneyRes['money']*100;
                    $tsData['out_trade_no'] = $redpackstatus['data']['partner_trade_no'];
                    $tsData['transaction_id'] = $redpackstatus['data']['payment_no'];
                    $tsData['wx_response'] = $redpackstatus['data']['wx_response'];
                    $tsMod->add($tsData);


                    $data['hb_type'] = 1;
                    $data['red_money'] = $oneMoneyRes['money'];
                    $data['hb_time'] = date('Y-m-d H:i:s', time());
                    $where['affair_id'] = $affairId;
                    $where['open_id'] = $openid;
                    $where['status'] = 2;
                    $isOk = $ufMod->where($where)->save($data);
                    if($isOk) {
                        //检测活动 符合条件 关闭活动
                        $affMod = D('Affair');
                        $canClose = $affMod->checkAffair($affairId);
                        if($canClose) {
                            $affMod->closeAffair($affairId);
                        }
                        //检测活动 符合条件 关闭活动

                        $this->ajaxReturn('', '领取成功', 200);
                    } else {
                        $this->ajaxReturn('', '领取失败', 403);
                    }
                } else {
                    $this->ajaxReturn('', $redpackstatus['info'], 403);
                }


            } catch (Exception $e) {
                $this->ajaxReturn('', '退款异常'.$e->getMessage(), 403);
            }

        } else {
            $this->ajaxReturn('', '不符合领取红包的条件', 403);
        }



    }

    public function transLog()
    {

        $page = intval($_GET['page']);
        $size = intval($_GET['size']);

        $openid = $this->openid;

        $w['a.open_id'] = $openid;
        $w['a.status'] = 1;
        $w['af.status'] = 0;
        $w['af.active_time'] = array('gt', date('Y-m-d H:i:s', time()));
        $money = D('UF')->getPromiseMoney($w);   //已支付且未签到 的 未开始并未结束的活动的保证金总和

        $where['t.open_id'] = $openid;
        $transList = D('Transaction')->search($where, $page, $size);

        $res['money'] = $money;
        $res['list'] = $transList;

        $this->ajaxReturn($res, 'ok', 200);

    }


}
