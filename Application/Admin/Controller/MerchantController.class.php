<?php
namespace Admin\Controller;
use Think\Controller;
class MerchantController extends CommonController {
    public function index(){
    	echo "后台Merchant控制器index方法";
    	$this->display();
    }
    public function withdrawListHandel(){
        $status = I('status');
        if($status==""){
            $where['w.status'] = array("EGT",0);
        }else{
            $where['w.status'] = $status;
        }
        $res = M('withdraw')->alias('w')->join('tg_merchant m on m.id=w.merchant_id')->where($where)->field('w.id,w.apply_time,w.status,w.count,m.shop_name,m.real_name')->order('w.apply_time desc')->select();
        if($res===false){
            $this->ajaxReturn(array('result'=>0));
        }else if(empty($res)){
            $this->ajaxReturn(array('result'=>3));
        }else{
            $array = array(
                    "result"=>1,
                    "data"=>$res
                );
            $this->ajaxReturn($array);
        }
    }
    //同意提现，修改提现表状态
    public function withdrawAgree(){
        $wid = I('id');
        $withdraw_res = M('withdraw')->where(array('id'=>$wid))->field('merchant_id,count,status,after_balance')->find();
        if(empty($withdraw_res)){
            $this->ajaxReturn(array('result'=>0));
        }
        if($withdraw_res['status']!=0){
            //不符合条件
            $this->ajaxReturn(array('result'=>3));
        }
        $agree = M();
        $agree->startTrans();
        $res = $agree->table('tg_withdraw')->where(array("id"=>$wid))->setField("status",1);
        if(empty($res)){
            $agree->rollback();
            $this->ajaxReturn(array('result'=>0));
        }
        //这里要调用微信的企业付款接口

        //提交事务
        $agree->commit();
        $this->ajaxReturn(array('result'=>1));
    }

    //拒绝提现，修改提现表状态，插入拒绝理由，修改资产表的变更后余额，修改商家余额
    public function disagreeWithdraw(){
        $wid = I('id');
        $reason = I('reason');
        if(empty($wid)){
            $this->ajaxReturn(array('result'=>2));
        }
        if(empty($reason)){
            $this->ajaxReturn(array('result'=>4));
        }
        $disagree = M();
        //先查看是否是待处理状态的提现
        $where['id'] = $wid;
        $result = M('withdraw')->where($where)->field('status,count,merchant_id')->find();
        if(empty($result)){
            $this->ajaxReturn(array('result'=>0));
        }
        if($result['status']==1||$result['status']==2){
            //不符合条件
            $this->ajaxReturn(array('result'=>3));
        }
        //更新提现表
        $save = array(
                "status"=>2,
                "reason"=>$reason
            );
        $res = $disagree->table('tg_withdraw')->where($where)->save($save);
        if(empty($res)){
            $disagree->rollback();
            $this->ajaxReturn(array('result'=>0));
        }
        //修改资产表的变更后余额
        $where1['change_id'] = $wid."_w";
        $asset_res = $disagree->table('tg_asset')->where($where1)->setInc('after_balance',$result['count']);
        if(empty($asset_res)){
            $disagree->rollback();
            $this->ajaxReturn(array('result'=>0));
        }
        //将商家余额修改回来
        $where2['id'] = $result['merchant_id'];
        $merchant_res = $disagree->table('tg_merchant')->where($where2)->setInc('balance',$result['count']);
        if(empty($merchant_res)){
            $disagree->rollback();
            $this->ajaxReturn(array('result'=>0));
        }
        $disagree->commit();
        $this->ajaxReturn(array('result'=>1));
    }

}