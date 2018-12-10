<?php
namespace Home\Controller;
use Think\Controller;
class InterfaceController extends Controller {
	/**
	* 通过学生ID获取学生姓名(商品详情页用到，某某专享)
	* 
	* @access public 
	* @param $studentId
	* @return json 返回信息的json数据
	*/
	public function getStudentNameById(){
		$studentId = I("student");
		$type = I("type");
		if(empty($studentId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}

		$result = $this->getStudentInfoById($studentId,$type);
		if($result){
			$array = array(
					"result"=>1,
					"data"=>$result['student_name']
				);
			$this->ajaxReturn($array,'json');
		}
		//都没执行就报错
		$this->ajaxReturn(array("result"=>0),'json');
	}
	/**
	* 通过学生ID获取学生信息(商品详情页用到，某某专享)
	* 呵贝的家长openid命名是“parent_openid”
	* 育讯通家长的openid命名是“openid”
	* 在调用处判断
	* @access public 
	* @param $studentId
	* @return json 返回信息的json数据
	*/
	public function getStudentInfoById($studentId,$type){
		/**调用接口对应平台的接口**/
		if($type==1){
			//育讯通
			$url = C('yxt_core_server').'InterfaceCore.php/Fun/GetStudentInfoFromStudentId';
		}else if($type==2){
			//呵贝
			$url = C('hebay_core_server').'wxinterface.php/InterfaceMall/GetStudentInfoFromStudentId';
		}
		$data = array(
				"student_id"=>$studentId
			);
		$result = $this->curl_request($url,$data);
		$result = json_decode($result,true);
		if($result['success']){
			return $result['msg'];
		}else{
			return false;
		}
	}
	/**
	* 通过订单ID向家长发信息
	* 
	* @access public
	* @param $order_id 订单ID
	* @param $type 那个平台
	* @return json 返回信息的json数据
	*/
	public function yxtSendMsgToParent($orderId){
		if(empty($orderId)){
			return false;
		}
		$where['o.id'] = $orderId;
		$result = M('order_pro')->alias('op')->join(array('tg_order o on o.id=op.order_id','tg_user u on u.id=o.user_id','tg_product p on p.id=op.pro_id','tg_pro_standard ps on ps.id=op.standard_id'))->where($where)->field("o.id,o.order_number,o.buy_time,o.student_id,o.send,u.username,ps.sale_price,op.num,p.name")->select();
		
		if(empty($result)||$result[0]['send']==0){
			//出错，为空，订单表里是否发送为否，直接return
			return false;
		}
		//最终调用模板消息的数组
		$data = array(
				"template_title"=>"下单成功通知",
				"type"=>1,//1、及时发送；2:、扫表发送
				"wx_id"=>1,
				"template_id"=>2,
				"student_id"=>$result[0]['student_id'],
				"url"=>C('domain').'index.php/Order/check_order?oid='.$result[0]['id'],
				"title"=>$result[0]['username'].",为你孩子购买了礼物",
				"key1"=>$result[0]['order_number'],
				"content"=>$result[0]['buy_time']
			);
		$sum = 0;
		$proName = '';
		for($i=0;$i<count($result);$i++){
			$sum += $result[$i]['sale_price']*$result[$i]['num'];
			if($i==(count($result)-1)){
				$proName .=$result[$i]['name'];
			}else{
				$proName .=$result[$i]['name']."、";
			}
		}
		$data['key2'] = "￥".$sum;
		$data['key3'] = $proName;
		$url = C('yxt_msg_server')."WeixinSend.php/Index/SendTemplateByStudentIdParent";
		$this->curl_request($url,$data);
	}

	/**
	* 通过订单ID向家长发订单信息(呵贝推送接口)
	* 
	* @access public
	* @param $order_id 订单ID
	* @param $type 那个平台
	* @return json 返回信息的json数据
	*/
	public function sendMsgToParent($orderId){
		//根据数据库order表里的pay字段来判断是否发，不发直接return
		$where['o.id'] = $orderId;
		$result = M('order_pro')->alias('op')->join(array('tg_order o on o.id=op.order_id','tg_user u on u.id=o.user_id','tg_product p on p.id=op.pro_id','tg_pro_standard ps on ps.id=op.standard_id'))->where($where)->field("o.id,o.order_number,o.buy_time,o.open_id,o.send,u.username,ps.sale_price,op.num,p.name")->select();
		if(empty($result)||$result[0]['send']==0){
			//出错，为空，订单表里是否发送为否，直接return
			return false;
		}
		//最终调用模板消息的数组
		$data = array(
				"template_id"=>2,
				"openid"=>$result[0]['open_id'],
				"url"=>C('domain').'index.php/Order/check_order?oid='.$result[0]['id'],
				"first"=>$result[0]['username'].",给您的小孩送了礼物",
				"key1"=>$result[0]['order_number'],
				"remark"=>$result[0]['buy_time']
			);
		$sum = 0;
		$proName = '';
		for($i=0;$i<count($result);$i++){
			$sum += $result[$i]['sale_price']*$result[$i]['num'];
			if($i==(count($result)-1)){
				$proName .=$result[$i]['name'];
			}else{
				$proName .=$result[$i]['name']."、";
			}
		}
		$data['key2'] = "￥".$sum;
		$data['key3'] = $proName;
		//呵贝
		$url = C('hebay_core_server').'wxinterface.php/InterfaceMall/SenTemplate';
		return $this->curl_request($url,$data);
	}
	/**
	* 通过订单ID的字符串向所有商家发消息，校园易购的接口（可以有多个商家）
	* 
	* @access public
	* @param $oids 订单ID字符串
	* @return json 返回操作结果
	*/
	public function sendNewOrderToMerchant($oid){
		if(empty($oid)){
			return false;
		}
		$where['o.id'] = $oid;
		$result = M('merchant')->alias('m')->join(array('tg_order o on o.merchant_id=m.id'))->field("m.open_id,m.id mid,o.id,o.remark,o.buy_time,o.order_number,o.pay")->where($where)->find();
		if(empty($result)){
			return false;
		}
		if($result['pay']==1){
			$result['pay'] = "微信支付";
		}else if($result['pay']==2){
			$result['pay'] = "货到付款";
		}
		$result['title'] = "你收到了一个订单";
		//这里是商户后台查看订单的链接（不是用户）
		$result['url'] = C('domain').'index.php/Merchant/check_order?oid='.$result['id'];
		//调用发消息接口,后面需要判断是否发送成功，每发一条就存进数据库，不成功的再继续发
		return A('WxMsg')->sendTplMsg($result);
	}

	/**
	 * 用户申请退款，向商家发送模板消息
	 * @param  $orderId 
	 * @return true/false
	 */
	public function sendRefundMsgToMerchant($orderId){
		$where['o.id'] = $orderId;
		$where['r.status'] = 0;
		$result = M('order')
		->alias('o')
		->join (array('tg_merchant m on m.id = o.merchant_id','tg_refund r on r.order_id = o.id'))
		->where($where)
		->field('o.order_number,r.apply_time,r.reason,m.id as mid,m.open_id')
		->find();
		if(empty($result)){
			return false;
		}
		$result['title'] = "收到一个退款申请";
		$result['url'] = C('domain').'index.php/Merchant/check_order?oid='.$orderId;
		//调用发消息接口
		return A('WxMsg')->sendRefundMsg($result);
	}

	/**
	 * 用户申请退货，向商家发送模板消息
	 * @param  $orderId 
	 * @return true/false
	 */
	public function sendReturnMsgToMerchant($orderId){
		$where['o.id'] = $orderId;
		$where['r.status'] = 0;
		$result = M('order_pro')
		->alias('op')
		->join (array('tg_order o on o.id=op.order_id','tg_merchant m on m.id = o.merchant_id','tg_return r on r.order_id = o.id','tg_product p on p.id=op.pro_id'))
		->where($where)
		->field('p.name,o.order_number,r.reason,m.open_id,o.price')
		->find();
		if(empty($result)){
			return false;
		}
		$result['title'] = "收到一个退货申请";
		$result['url'] = C('domain').'index.php/Merchant/check_order?oid='.$orderId;
		//调用发消息接口
		return A('WxMsg')->sendReturnTplMsg($result);
	}
	/**
	* 给家长发关于他孩子的订单的退款信息(退款成功才发)（呵贝的接口）
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回信息的json数据
	*/
	public function hebayRefundNotifyToParent($orderId){
		$where['o.id'] = $orderId;
		$result = M('order')->alias('o')->join(array('tg_user u on u.id=o.user_id','tg_refund r on r.order_id=o.id'))->where($where)->field("u.username,r.reason,r.count,o.open_id")->find();
		if(empty($result)||empty($result['open_id'])){
			return false;
		}
		$data = array(
				"template_id"=>3,
				"openid"=>$result['open_id'],
				"url"=>C('domain').'index.php/Order/check_order?oid='.$orderId,
				"first"=>$result['username']."为你孩子购买的订单退款成功",
				"remark"=>"退款结果：退款成功",
				"key1"=>$result['reason'],
				"key2"=>"￥".$result['count']
			);
		$url = C('hebay_core_server').'wxinterface.php/InterfaceMall/SenTemplate';
		return $this->curl_request($url,$data);
	}
	/**
	* 给家长发关于他孩子的订单的退款信息(退款成功才发)（育讯通的接口）
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回信息的json数据
	*/
	public function yxtRefundNotifyToParent($orderId){
		$where['o.id'] = $orderId;
		$result = M('order')->alias('o')->join(array('tg_user u on u.id=o.user_id','tg_refund r on r.order_id=o.id'))->where($where)->field("u.username,r.reason,r.count,o.student_id")->find();
		if(empty($result)||empty($result['student_id'])){
			return false;
		}
		$data = array(
				"type"=>1,
				"wx_id"=>1,
				"template_id"=>3,
				"template_title"=>"退款成功",
				"student_id"=>$result['student_id'],
				"url"=>C('domain').'index.php/Order/check_order?oid='.$orderId,
				"title"=>$result['username']."为你孩子购买的订单退款成功",
				"content"=>"退款结果：退款成功",
				"key1"=>$result['reason'],
				"key2"=>"￥".$result['count']
			);
		$url = C('yxt_msg_server')."WeixinSend.php/Index/SendTemplateByStudentIdParent";
		$this->curl_request($url,$data);
	}

	/**
	* 给家长发关于他孩子的订单的退货信息
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回信息的json数据
	*/
	public function returnSuccessMsgToParent($oid){
		$res = M('order')->alias('o')->join(array('tg_order_pro op on op.order_id=o.id','tg_product p on p.id=op.pro_id'))->where(array("o.id"=>$oid))->field('p.name,o.order_number,o.p_id,o.open_id,o.student_id,o.price,o.buy_time')->find();
		if(empty($res)){
			return false;
		}
		if(empty($res['p_id'])){
			return fasle;
		}
		//是育讯通的订单
		if(!empty($res['student_id'])&&$res['p_id']==1){
			//最终调用模板消息的数组
			$data = array(
					"template_title"=>"订单退货成功通知",
					"type"=>1,//1、及时发送；2:、扫表发送
					"wx_id"=>1,
					"template_id"=>5,
					"student_id"=>$res['student_id'],
					"url"=>C('domain').'index.php/Order/check_order?oid='.$oid,
					"title"=>"您有一个订单退货成功",
					"key1"=>$res['order_number'],
					"key2"=>$res['name'],
					"key3"=>"退货成功",
					"key4"=>"订单金额退回原支付账户，退货金额".$res['price'],
					"content"=>"下单时间：".$res['buy_time']
				);
			$url = C('yxt_msg_server')."WeixinSend.php/Index/SendTemplateByStudentIdParent";
			return $this->curl_request($url,$data);
		}else if(!empty($res['open_id'])&&$res['p_id']==2){
			//呵贝
			$data = array(
				"template_id"=>1,
				"openid"=>$res['open_id'],
				"url"=>C('domain').'index.php/Order/check_order?oid='.$oid,
				"first"=>"您有一个订单退货成功",
				"remark"=>"下单时间：".$res['buy_time'],
				"key1"=>"退货成功",
				"key2"=>date("Y-m-d H:i:s",time()),
				"key3"=>"订单金额退回原支付账户，退货金额：".$res['price'],
			);
			$url = C('hebay_core_server').'wxinterface.php/InterfaceMall/SenTemplate';
			return $this->curl_request($url,$data);
		}
	}
	/**
	* 商家确定送达时间和地点
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回信息的json数据
	*/
	public function deliverTimeAndAddress(){
		if(empty($res['p_id'])){
			return fasle;
		}
		//是育讯通的订单
		if(!empty($res['student_id'])&&$res['p_id']==1){
			//最终调用模板消息的数组
			$data = array(
					"template_title"=>"蛋糕送货信息通知",
					"type"=>1,//1、及时发送；2:、扫表发送
					"wx_id"=>1,
					"template_id"=>4,
					"student_id"=>$res['student_id'],
					"url"=>C('domain').'index.php/Order/check_order?oid='.$res['id'],
					"title"=>"蛋糕送货信息通知",
					"key1"=>$res['order_number'],
					"key2"=>"商家已接单",
					"content"=>"送货时间：".$res['time']." \\n送货地点：".$res['location']
				);
			$url = C('yxt_msg_server')."WeixinSend.php/Index/SendTemplateByStudentIdParent";
			return $this->curl_request($url,$data);
		}else if(!empty($res['open_id'])&&$res['p_id']==2){
			//呵贝
			$data = array(
				"template_id"=>1,
				"openid"=>$res['open_id'],
				"url"=>C('domain').'index.php/Order/check_order?oid='.$res['id'],
				"first"=>"蛋糕送货信息通知",
				"key1"=>"蛋糕送达时间和地点",
				"key2"=>date("Y-m-d H:i:s",time()),
				"key3"=>"订单编号为".$res['order_number']."\\n送货时间：".$res['time']."\\n送货地点：".$res['location'],
			);
			$url = C('hebay_core_server').'wxinterface.php/InterfaceMall/SenTemplate';
			return $this->curl_request($url,$data);
		}
	}
	//微信支付回调地址
	public function notify(){
		//获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        try {
            $result = A("WxApi")->Init($xml);
        }catch(Exception $e){
            echo $e->getMessage();
        }
        /*1、判断通信、交易是否成功,不成功直接返回*/
        if($result['return_code']!=='SUCCESS'||$result['result_code']!=='SUCCESS'){
        	return;
        }
        /*2、判断请求是否来源于微信，验证签名*/
        // $where['o.out_trade_no'] = $result['out_trade_no'];
        // $sign = M('pay_record')->alias('pr')->join('order o on o.id=pr.order_id')->where($where)->field("pr.sign")->find();
        // //查询出错或者签名验证失败，直接返回
        // if(empty($sign)||$sign['sign']!=$result['sign']){
        // 	return;
        // }
        /*3、处理自己的业务逻辑*/
        $res=$this->NotifyProcess($result);
        /*4、判断自己业务逻辑是否处理成功，给微信提示*/
        if($res == false){
            //处理失败直接return，因为微信只识别成功和延迟的信息
            return;
        }
        $values['return_code']="SUCCESS";
        $values['return_msg']="OK";
        $xml = "<xml>";
        foreach ($values as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        echo $xml;//直接输出xml
	}
	/**
	* 回调的逻辑做四件事
	* 1、把支付表记录状态改为支付成功
	* 2、给小孩家长发送消息（查看订单的页面）
	* 3、修改对应的商品的销量和库存
	* 4、给该订单中商品对应的所有商家发消息（收到一个订单）
     * @param array $data 微信返回的所有数据
     * @return 成功返回true，失败返回false
     */
    public function NotifyProcess($data){
        $where['o.out_trade_no'] = $data['out_trade_no'];
        //只有支付记录表支付状态为0时才可以修改状态
        $result = M('order')->alias('o')->join('tg_pay_record pr on pr.out_trade_no=o.out_trade_no')->where($where)->field('pr.status,pr.id,o.id oid,o.student_id,o.p_id')->select();
        //判断是否已经处理过，处理过就直接返回，微信会访问回调8次，以此来提高通知成功的概率
        if(empty($result)||$result[0]['status']!=0){
        	return false;
        }
        //修改支付记录表状态
        $where1['out_trade_no'] = $data['out_trade_no'];
        $update['status'] = 1;
        $update['complete_time'] = date("Y-m-d H:i:s",time());
        $res = M('pay_record')->where($where1)->save($update);
        if(empty($res)){
        	return false;
        }
        //向订单表存支付方式
        $pay['pay'] = 1;
        $where2['out_trade_no'] = $data['out_trade_no'];
        $payRes = M('order')->where($where2)->setField($pay);
        if(empty($payRes)){
        	return false;
        }
        for($i=0;$i<count($result);$i++){
			//用户下单成功，向商家发下单的消息
			$this->sendNewOrderToMerchant($result[$i]['oid']);
			//6、用户下单成功，向监护人发送订单消息
			if(!empty($result[$i]['p_id'])&&$result[$i]['p_id']==1){
				//育讯通
				$this->yxtSendMsgToParent($result[$i]['oid']);
			}else if(!empty($result[$i]['p_id']&&$result[$i]['p_id']==2)){
				//呵贝
				$this->sendMsgToParent($result[$i]['oid']);
			}
			//对商品销量和库存做相应的修改
			A('Order')->saleStatistic($result[$i]['oid']);
        }
        return true;
    }
    	/**
	* curl请求接口获取数据
	* 
	* @access public
	* @param $url 访问的URL
	* @param $post post数据(不填则为GET)
	* @param $cookie 提交的$cookies
	* @param $returnCookie 是否返回$cookies
	* @return json 返回信息的json数据
	*/
  	public function curl_request($url,$post='',$cookie='', $returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if(curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            return $data;
        }
 	}

 	public function test(){
 		$res = A('WxApi')->withDrawals("ofqod0p0aoimRJXPQ5v1Xh3pRQ64",1);
 		print_r($res);
 	}
}