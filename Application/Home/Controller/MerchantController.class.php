<?php
namespace Home\Controller;
use Think\Controller;
class MerchantController extends CommonController{
    public function _initialize(){
        parent::_initialize();
        $open_id = session("openId");
        if(empty($open_id)){
            print_r("openID为空");
            // redirect("../User/error");
        }
        $where['open_id'] = $open_id;
        $res = M('merchant')->where($where)->field('id,open_id')->find();
        if($res===false){
            print_r($res);
            // redirect("../User/error");
        }else if(empty($res)){
            print_r($res);die;
            redirect("../User/unMerchant");
        }
    }
    
    
    public function put_product(){
        $data = A('WxApi')->wxPayConfig();
        if(count(array_values($data))!=4||$data===false){
            redirect("../User/error");
        }
        $this->assign($data);
        $this->display();
    }

    public function modify_product(){
        $data = A('WxApi')->wxPayConfig();
        if(count(array_values($data))!=4||$data===false){
            redirect("../User/error");
        }
        $this->assign($data);
        $this->display();
    }

    /**
    * 根据商家的openId，获得该商家的所有订单信息（商家控制器订单列表用到）
    * 
    * @access public
    * @param $status 订单状态
    * @param $page 请求的页数
    * @param $open_id 商家在session中的openID
    * @return json 返回订单列表的json数据
    */
    public function orderList(){
        /*
            前端传的数据
            {'status':12,'page':2}
        */
        $open_id = session("openId");
        $status = I('status');
        $page = I('page');
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        //拼接where条件
        $where['m.open_id'] = $open_id;
        //处理分页和订单状态
        if($status==''){
            $where['o.status'] = array('EGT',0);//没传状态，就默认取所有订单
        }else{
            $where['o.status'] = $status;
        }
        if(empty($page)){
            $page = 1;
        }
        //取出所有订单ID
        $id_result = M('order')->alias('o')->join('tg_merchant m on m.id=o.merchant_id')->where($where)->page($page.' ,8')->order("buy_time desc")->field("o.id")->select();
        if($id_result===false){
             $this->ajaxReturn(array("result"=>0),'json');
        }else if(empty($id_result)){
            $this->ajaxReturn(array("result"=>3),'json');
        }else{
            $order_array = array();
            for($i=0;$i<count($id_result);$i++){
                $order_result = A('Order')->getOrderListInfoByOrderId($id_result[$i]['id']);
                if($order_result===false){
                    $this->ajaxReturn(array("result"=>0),'json');
                }
                $order_array[$i] = $order_result;
            }
        }
        $array = array(
                "result"=>1,
                "data"=>$order_array
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 根据商家openID，获得该商家的所有退款订单信息（商家控制器订单列表退款用到）
    * 
    * @access public
    * @param $page 请求的页数
    * @param $open_id 用户在第三方平台的openID
    * @return json 返回订单列表的json数据
    */
    public function refundOrderList(){
        $page = I('page');
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $where['m.open_id'] = $open_id;
        $where['o.status'] = array("EGT",0);
        if(empty($page)){
            $page = 1;
        }
        $oid_result = M('order')->alias('o')->join(array('tg_merchant m on m.id=o.merchant_id','tg_refund r on r.order_id=o.id'))->where($where)->field("o.id,r.status")->page($page.' ,8')->select();
        if($oid_result===false){
            //查询出错
            $this->ajaxReturn(array("result"=>0),'json');
        }else if(empty($oid_result)){
            //结果为空
            $this->ajaxReturn(array("result"=>3),'json');
        }else{
            //正常情况
            $refundArray = array();
            for($i=0;$i<count($oid_result);$i++){
                $result = A('Order')->getOrderListInfoByOrderId($oid_result[$i]['id']);
                if(!empty($result)){
                    $refundArray[$i]['rstatus'] = $oid_result[$i]['status'];
                    $refundArray[$i]['list'] = $result;
                }else{
                    $this->ajaxReturn(array("result"=>0),'json');
                }
            }
            $array = array(
                    "result"=>1,
                    "data"=>$refundArray
                );
            $this->ajaxReturn($array,'json');
        }
    }
    /**
    * 退货订单列表（商家控制器的订单列表用到）
    * 
    * @access public
    * @param $open_id 商家openID
    * @return json 返回申请的结果
    */
    public function returnOrderList(){
        $page = I('page');
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        //拼接where条件
        $where['m.open_id'] = $open_id;
        $where['o.status'] = array("EGT",0);
        if(empty($page)){
            $page = 1;
        }
        $oid_result = M('order')->alias('o')->join(array('tg_merchant m on m.id=o.merchant_id','tg_return r on r.order_id=o.id'))->where($where)->field('o.id,r.status')->page($page.' ,8')->select();
        if($oid_result===false){
            //查询出错
            $this->ajaxReturn(array("result"=>0),'json');
        }else if(empty($oid_result)){
            //结果为空
            $this->ajaxReturn(array("result"=>3),'json');
        }else{
            //正常情况
            $refundArray = array();
            for($i=0;$i<count($oid_result);$i++){
                $result = A('Order')->getOrderListInfoByOrderId($oid_result[$i]['id']);
                if(!empty($result)){
                    $refundArray[$i]['rstatus'] = $oid_result[$i]['status'];
                    $refundArray[$i]['list'] = $result;
                }else{
                    $this->ajaxReturn(array("result"=>0),'json');
                }
            }
            $array = array(
                    "result"=>1,
                    "data"=>$refundArray
                );
            $this->ajaxReturn($array,'json');
        }
    }
    

    /**
    * 从微信服务器下载刚刚上传的文件
    * 
    * @access public
    * @param $merchant数组
    * @param $mid 商家ID
    * @return json 返回结果
    */
    public function getImgFromWx($serverId){
        //获取access_token
        $access_token = A('WxApi')->getAccessToken();
        if(!$access_token){
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$serverId;
        $fileInfo =$this->downloadWeixinFile($url);
        $content = $fileInfo['body'];
        //把下载下的内容写入到本地服务器文件里
        $filename = time().$serverId.'.jpg';//文件名
        $dirname = APP_PATH.'shopLogo/';
        if(!file_exists($dirname)){
            mkdir($dirname,777,true);
            chmod($dirname,0777);
        }
        $data = file_put_contents($dirname.$filename,$content);
        if($data){
            $url = "/Application".explode("./Application", $dirname.$filename)[1];
            return $url;
        }else{
            return false;
        }
    }
    //从微信服务器下载文件到本地服务器
    public function downloadWeixinFile($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600 );//建立连接超时设置
        curl_setopt($ch, CURLOPT_TIMEOUT, 600 );//返回数据超时设置
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('header' => $httpinfo), array('body' => $package));
        return $imageAll;
    }
    /**
    * 立即接单的处理办法
    * 
    * @access public
    * @param $number 快递编号
    * @param $company 快递公司
    * @return json 返回结果
    */
    public function receiveOrder(){
        $company = I("company");
        $number = I("number");
        $id = I("orderId");
        if(empty($company)){
            $this->ajaxReturn(array("result"=>3),'json');
        }
        if(empty($number)){
            $this->ajaxReturn(array("result"=>4),'json');
        }
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $data = array(
                "status"=>2,
                "tracking_num"=>$number,
                "tracking_company"=>$company,
                "deliver_time"=>date("Y-m-d H:i:s",time())
            );
        $where['id'] = $id;
        $res = M('order')->where($where)->save($data);
        if(!empty($res)){
            $this->ajaxReturn(array("result"=>1),'json');
        }else{
            $this->ajaxReturn(array("result"=>0),'json');
        }
    }
    /**
    * 蛋糕店的立即接单方法（修改订单状态，还需要通知用户何时何地送达）
    * 
    * @access public
    * @param $time 送达时间
    * @param $location 送达地点
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function remind(){
        $location = I("location");
        $time = I("time");
        $id = I("orderId");
        if(empty($location)){
            $this->ajaxReturn(array("result"=>3),'json');
        }
        if(empty($time)){
            $this->ajaxReturn(array("result"=>4),'json');
        }
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        //先修改订单表的状态
        $data = array(
                "status"=>2,
                "deliver_time"=>date("Y-m-d H:i:s",time())
            );
        $where['id'] = $id;
        $res = M('order')->where($where)->setField($data);
        if(empty($res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }else{
            //不出错，就给购买者发送消息
            $where['o.id'] = $id;
            $res = M('order')->alias('o')->join('tg_user u on u.id=o.user_id')->where($where)->field("u.open_id,o.order_number,o.status,o.id,o.open_id,o.student_id,o.p_id")->find();
            if($res===false){
                $this->ajaxReturn(array("result"=>5),'json');
            }
            $res['url'] = "stdbuy.wisvalley.com/index.php/Order/check_order?oid=".$res['id'];
            $res['time'] = $time;
            $res['location'] = $location;
            $msg_res = A('Interface')->deliverTimeAndAddress($res);
            if($msg_res){
                $this->ajaxReturn(array("result"=>1),'json');
            }else{
                $this->ajaxReturn(array("result"=>5),'json');
            }
        }
    }
    /**
    * 蛋糕店商家点击完成，改变货到付款的订单状态为已完成
    * 
    * @access public
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function completeOrderByMerchant(){
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        //判断支付方式是否是货到付款
        $pay_res = M('order')->where('id='.$id)->field('pay,deliver_time')->find();
        if(empty($pay_res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        if($pay_res['pay']!=2){
            $this->ajaxReturn(array("result"=>3),'json');
        }
        $deliver_time = strtotime($pay_res['deliver_time']);
        $original_time = strtotime("-6 day");//至少要5天
        if($deliver_time<$original_time){
            /*符合要求，改变订单状态*/
            $data = array(
                    "full_time"=>date("Y-m-d H:i:s",time()),
                    "status"=>3,
                    "receive_type"=>3
                );
            $where['id'] = $id;
            $complete_res = M('order')->where($where)->save($data);
            if(!empty($complete_res)){
                $this->ajaxReturn(array("result"=>1),'json');
            }else{
                $this->ajaxReturn(array("result"=>0),'json');
            }
        }else{
            $this->ajaxReturn(array("result"=>4),'json');
        }
    }
    /**
    * 商家查看退款信息
    * 
    * @access public
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function refundInfo(){
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $refund = M('refund')->where(array("order_id"=>$id))->field("count,apply_time,reason")->find();
        if(empty($refund)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$refund
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 商家同意退款
    * 
    * @access public
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function refundAgree(){
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $refund = M();
        $refund->startTrans();
        //修改订单表状态为退款成功
        $where['id'] = $id;
        $data['status'] = 5;
        $status_res = $refund->table('tg_order')->where($where)->setField($data);
        if(empty($status_res)){
            $refund->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //修改退款表
        $where1['order_id'] = $id;
        $save = array(
                "reply_time"=>date("Y-m-d H:i:s",time()),
                "status"=>1
            );
        $refund_res = $refund->table('tg_refund')->where($where1)->save($save);
        if(empty($refund_res)){
            $refund->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //退款成功向该小孩的家长发送退款成功消息
        $p_res = M('order')->where(array("id"=>$id))->field("p_id")->find();
        if(!empty($p_res)&&$p_res['p_id']==1){
            A('Interface')->yxtRefundNotifyToParent($id);
        }else if(!empty($p_res)&&$p_res['p_id']==2){
            A('Interface')->hebayRefundNotifyToParent($id);
        }
        //调用微信退款接口，将金额退回原支付账户
        $order = M('order')->where(array('id'=>$id))->field('out_trade_no,price')->find();
        if(empty($order)){
            $refund->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //根据out_trade_no获取订单的总价（可能合并付款的情况）
        $all = M('order')->where(array('out_trade_no'=>$order['out_trade_no']))->sum('price');
        if(empty($all)){
            $refund->rollback();
            $this->ajaxReturn(array("result"=>10),'json');
        }
        $res = A('WxApi')->WxRefund($order['out_trade_no'],$all*100,$order['price']*100);
        if($res['return_code']=='SUCCESS'&&$res['result_code']=='SUCCESS'){
            $refund->commit();
            $this->ajaxReturn(array("result"=>1),'json');
        }else{
            $refund->rollback();
            $this->ajaxReturn(array("result"=>20),'json');
        }
    }
    /**
    * 商家拒绝退款
    * 
    * @access public
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function refundRefuse(){
        $reason = I('reason');
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        if(empty($reason)){
            $this->ajaxReturn(array("result"=>3),'json');
        }
        $refuse = M();
        $refuse->startTrans();
        //修改订单表状态为待发货
        $data['status'] = 1;
        $where['id'] = $id;
        $status_res = M('order')->where($where)->save($data);
        if(empty($status_res)){
            $refuse->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //修改退款表
        $where1['order_id'] = $id;
        $save = array(
                "reply_time"=>date("Y-m-d H:i:s",time()),
                "status"=>2,
                "reason"=>$reason
            );
        $refuse_res = $refuse->table('tg_refund')->where($where1)->save($save);
        if(empty($refuse_res)){
            $refuse->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $refuse->commit();
        $this->ajaxReturn(array("result"=>1),'json');
    }
    /**
    * 商家查看退货信息
    * 
    * @access public
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function returnInfo(){
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $where['order_id'] = $id;
        $res = M('return')->where($where)->field('tel,tracking_number,reason,apply_time')->find();
        if(empty($res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$res
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 商家点击“货已收到”
    * 
    * @access public
    * @param $id 订单ID
    * @return json 返回结果
    */
    public function goodsHasReceived(){
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        //开启事务
        $receive = M();
        $receive->startTrans();
        /*1、将订单状态改成退货成功*/
        $status_res = $receive->table('tg_order')->where(array("id"=>$id))->setField(array("status"=>8));
        if($status_res===false){
            $receive->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        /*2、修改退货表*/
        $save = array(
                "status"=>1,
                "reply_time"=>date("Y-m-d H:i:s",time())
            );
        $return_res = $receive->table('tg_return')->where(array("order_id"=>$id))->save($save);
        if($return_res===false){
            $receive->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        /*3、给家长发退货消息*/
        A('Interface')->returnSuccessMsgToParent($id);
        /*4、给用户打钱*/
        $order = M('order')->where(array('id'=>$id))->field('out_trade_no,price')->find();
        if(empty($order)){
            $receive->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //根据out_trade_no获取订单的总价（可能合并付款的情况）
        $all = M('order')->where(array('out_trade_no'=>$order['out_trade_no']))->sum('price');
        if(empty($all)){
            $receive->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $res = A('WxApi')->WxRefund($order['out_trade_no'],$all*100,$order['price']*100);
        if($res['return_code']=='SUCCESS'&&$res['result_code']=='SUCCESS'){
            $receive->commit();
            $this->ajaxReturn(array("result"=>1),'json');
        }else{
            $receive->rollback();
            $this->ajaxReturn(array("result"=>0),'json');
        }
    }
    /**
    * 获取商家近七天销售总额
    * 统计已完成的订单
    * @access public 
    * @return json 返回销售额
    */
    public function getMerchantSalePriceThisWeek(){
        $open_id = session('openId');
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $m_id = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
        if(empty($m_id)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $price = A("Admin/Chart")->getAllSalePriceInThisWeek($m_id['id']);
        if($price===false){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$price
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 获取商家近七天订单总数
    * 包括退款、退货、未完成、已完成订单
    * @access public 
    * @return json 返回订单数
    */
    public function getOrderNumThisWeek(){
        $open_id = session('openId');
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $m_id = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
        if(empty($m_id)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $num = A("Admin/Chart")->getThisWeekOrderNum($m_id['id']);
        if($num===false){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$num
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 获取商家店铺商品总数
    * 下架的商品不算
    * @access public 
    * @return json 返回商品总数
    */
    public function getProductNum(){
        $open_id = session('openId');
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $m_id = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
        if(empty($m_id)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $num = A("Admin/Chart")->getAllProductNum($m_id['id']);
        if(!$num){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$num
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 获取近七天每天的订单数
    * 
    * @access public 
    * @return json 返回订单数的字符串
    */
    public function getThisWeekOrderNumPerDay(){
        $open_id = session('openId');
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $m_id = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
        if(empty($m_id)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $result = A('Admin/Chart')->getThisWeekOrderNumOfPerDay($m_id['id']);
        if($result===false){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$result
            );
        $this->ajaxReturn($array,'json');
    }
    /**
    * 获取近七天每天的销售额字符串
    * 
    * @access public 
    * @return json 返回销售额的字符串
    */
    public function getThisWeekSalePriceSumPerDay(){
        $open_id = session('openId');
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $m_id = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
        if(empty($m_id)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $result = A('Admin/Chart')->getThisWeekSalePriceSum($m_id['id']);
        if($result===false){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$result
            );
        $this->ajaxReturn($array,'json');
    }

    /**
    * 根据商品ID，获取商品的所有信息
    * 
    * @access public 
    * @return json 返回销售额的字符串
    */
    public function getProductDetailInfoById(){
        $pro_id = I('pid');
        if(empty($pro_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $where['p.id'] = $pro_id;
        //取出商品名称和分类名称
        $pro_res = M('product')->alias('p')->join('tg_sort s on s.id=p.sort_id')->where($where)->field('p.name,s.sort_name')->find();
        if(empty($pro_res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //取出商品轮播图片
        $focus_where['pro_id'] = $pro_id;
        $focus_where['status'] = array("EGT",0);
        $f_res = M('product_image')->where($focus_where)->field('id,img_path,status')->order('status desc')->select();
        if(empty($f_res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //取出商品描述图片
        $d_res = M('product_image_descript')->where(array("pro_id"=>$pro_id,"status"=>1))->field('id,img_path,time')->order('time')->select();
        if(empty($d_res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        //取出规格的信息
        $standard_where['pro_id'] = $pro_id;
        $standard_where['status'] = array("EGT",0);
        $s_res = M('pro_standard')->where($standard_where)->field('id,name,sale_price,original_price,inventory')->select();
        for($i=0;$i<count($s_res);$i++) {
            $sd_res = M('standard_discount')->alias('sd')->join('tg_discount d on d.id=sd.discount_id')->where(array("sd.standard_id"=>$s_res[$i]['id'],"sd.status"=>1))->field('d.name,d.id')->select();
            if($sd_res===false){
                $this->ajaxReturn(array("result"=>0),'json');
            }else if(empty($sd_res)){
                $s_res[$i]['activities'] = array();
            }else{
                $s_res[$i]['activities'] = $sd_res;
            }
        }
        $result = array(
                "base" => $pro_res,
                "focus" =>$f_res,
                "describe"=>$d_res,
                "standard"=>$s_res
            );
        //取出所有活动
        $discount_res = M('discount')->where(array("status"=>1))->field('name,id')->select();
        if($discount_res===false){
            $this->ajaxReturn(array("result"=>0),'json');
        }else if(empty($discount_res)){
            $result['activity'] = array();
        }else{
            $result['activity'] = $discount_res;
        }
        $array = array(
                "result"=>1,
                "data"=>$result
            );
        $this->ajaxReturn($array,'json');
    }

    /**
    * 取出所有商品
    * 
    * @access public 
    * @return json 返回商品列表json数据
    */
    public function allProductOfShop(){
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $mid_res = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
        if(empty($mid_res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $merchant_id = $mid_res['id'];
        $page = I('page');
        if(empty($page)){
            $page = 1;
        }
        $where['p.status'] = 1;
        $where['p.merchant_id'] = $merchant_id;
        $where['ps.status'] = 1;
        $where['pi.status'] = 1;
        $res = M('product')->alias('p')->join(array('tg_pro_standard ps on ps.pro_id=p.id','tg_product_image pi on pi.pro_id=p.id'))->where($where)->field('p.id,p.name,p.time,pi.img_path,ps.sale_price')->page($page.",10")->order('p.time desc')->select();
        if($res===false){
            $this->ajaxReturn(array("result"=>0),'json');
         }else if(empty($res)){
            $this->ajaxReturn(array("result"=>3),'json');
         }else{
            $array = array(
                    "result"=>1,
                    "data"=>$res
                );
            $this->ajaxReturn($array,'json');
         }
    }

    /**
    * 商家中心取出商家信息
    * 
    * @access public 
    * @return json 返回商品列表json数据
    */
    public function merchantShopInfo(){
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2));
        }
        $res = M('merchant')->where(array('open_id'=>$open_id))->field('shop_name,tel,logo_path,balance,id')->find();
        if($res===false){
            $this->ajaxReturn(array("result"=>0));
        }else if(empty($res)){
            $this->ajaxReturn(array("result"=>3));
        }else{
            //取出七天成交额
            $price = A("Admin/Chart")->getAllSalePriceInThisWeek($res['id']);
            if($price===false){
                $this->ajaxReturn(array("result"=>0));
            }
            $res['price'] = $price;
            //取出七天订单数
            $num = A("Admin/Chart")->getThisWeekOrderNum($res['id']);
            if($num===false){
                $this->ajaxReturn(array("result"=>0));
            }
            $res['num'] = $num;
            $this->ajaxReturn(array("result"=>1,"data"=>$res));
        }
    }

    /**
    * 取出余额
    * 
    * @access public 
    * @return json 返回商品列表json数据
    */
    public function merchantBalance(){
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2));
        }
        $res = M('merchant')->where(array('open_id'=>$open_id))->field('balance')->find();
        if($res===false){
            $this->ajaxReturn(array("result"=>0));
        }else if(empty($res)){
            $this->ajaxReturn(array("result"=>3));
        }else{
            $this->ajaxReturn(array("result"=>1,"data"=>$res['balance']));
        }
    }

    /**
    * 插入提现记录
    * 
    * @access public 
    * @return json 返回商品列表json数据
    */
    public function insertWithdrawRecord(){
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2));
        }
        $count = I('count');
        $withdraw = M();
        $withdraw->startTrans();
        $res = M('merchant')->where(array("open_id"=>$open_id))->field('id,balance')->find();
        if(empty($res)){
           $this->ajaxReturn(array("result"=>0)); 
        }
        if($count>$res['balance']){
            $this->ajaxReturn(array("result"=>3));
        }
        //插入提现记录表
        $data = array(
                "merchant_id"=>$res['id'],
                "count"=>$count,
                "apply_time"=>date("Y-m-d H:i:s",time()),
                "status"=>0,
                "after_balance"=>$res['balance']-$count
            );
        $add = $withdraw->table('tg_withdraw')->add($data);
        if(empty($add)){
            $withdraw->rollback();
            $this->ajaxReturn(array("result"=>0)); 
        }
        //修改商家余额
        $sub = $withdraw->table('tg_merchant')->where(array("open_id"=>$open_id))->setDec("balance",$count);
        if(empty($sub)){
            $withdraw->rollback();
            $this->ajaxReturn(array("result"=>0));
        }
        //向资产变更表里插数据
        $data1 = array(
                "merchant_id"=>$res['id'],
                "count"=>$count,
                "change_id"=>$add."_w",
                "reason"=>"商家提现",
                "before_balance"=>$res['balance'],
                "after_balance"=>$res['balance']-$count,
                "time"=>date("Y-m-d H:i:s",time())
            );
        $result = $withdraw->table('tg_asset')->add($data1);
        if(empty($result)){
            $withdraw->rollback();
            $this->ajaxReturn(array("result"=>0));
        }
        $withdraw->commit();
        $this->ajaxReturn(array("result"=>1));
    }

    /**
    * 获取资产变更记录
    * 
    * @access public 
    * @return json 返回商品列表json数据
    */
    public function assetsChangeList(){
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2));
        }
        $where['m.open_id'] = $open_id;
        $res = M('asset')->alias('a')->join('tg_merchant m on m.id=a.merchant_id')->where($where)->field('a.id,a.count,a.before_balance,a.after_balance,a.time,a.change_id')->order('a.time desc')->select();
        if($res===false){
            $this->ajaxReturn(array("result"=>0));
        }else if(empty($res)){
            $this->ajaxReturn(array("result"=>3));
        }else{
            //还要根据提现ID计算出提现状态，在前端展示
            for($i=0;$i<count($res);$i++){
                $arr = explode("_", $res[$i]['change_id']);
                if($arr[1]=="w"){
                    $result = M('withdraw')->where(array("id"=>$arr[0]))->field('status')->find();
                    if(empty($result)){
                        $this->ajaxReturn(array("result"=>0));
                    }
                    $res[$i]['w_status'] = $result['status'];
                }
            }
            $this->ajaxReturn(array("result"=>1,"data"=>$res));
        }
    }

    /**
    * 根据资产表的ID查询
    * 
    * @access public 
    * @return json 返回商品列表json数据
    */
    public function assetDetail(){
        $id = I('id');
        if(empty($id)){
            $this->ajaxReturn(array('result'=>2));
        }
        $res = M('asset')->where(array('id'=>$id))->field('count,change_id,before_balance,after_balance,time')->find();
        if(empty($res)){
            $this->ajaxReturn(array('result'=>0));
        }
        $arr = explode("_", $res['change_id']);
        if($arr[1]=="w"){
            //提现记录
            $w_res = M('withdraw')->where(array("id"=>$arr[0]))->field('status,reason')->find();
            if(empty($w_res)){
                $this->ajaxReturn(array('result'=>0));
            }
            $result = array_merge($res,$w_res,array("flag"=>"w"));
            $this->ajaxReturn(array("result"=>1,"data"=>$result));
        }else{
            $o_res = M('order')->where(array("id"=>$arr[0]))->field('order_number')->find();
            if(empty($o_res)){
                $this->ajaxReturn(array('result'=>0));
            }
            $result = array_merge($res,$o_res,array("flag"=>"o"));
            $this->ajaxReturn(array("result"=>1,"data"=>$result));
        }
    }
}