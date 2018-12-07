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

    //领取红包  -- 领取红包退回保证金
    public function receiveCash($value='')
    {
        $affairId = intval($_POST['id']);
        $openid = $this->openid;
        // code...
        try {
            // 执行退款操作
            // code...
            // 执行退款操作
            if(1) {
                $ufMod = D('UF');
                $data['hb_type'] = 1;
                $data['hb_time'] = date('Y-m-d H:i:s', time());
                $where['affair_id'] = $affairId;
                $where['open_id'] = $openid;
                $where['status'] = 2;
                $isOk = $ufMod->where($where)->save($data);
                if($isOk) {
                    $this->ajaxReturn('', '领取成功', 200);
                } else {
                    $this->ajaxReturn('', '领取失败', 403);
                }
            } else {
                $this->ajaxReturn('', '退款异常', 403);
            }


        } catch (Exception $e) {
            $this->ajaxReturn('', '退款异常'.$e->getMessage(), 403);
        }

    }

}
