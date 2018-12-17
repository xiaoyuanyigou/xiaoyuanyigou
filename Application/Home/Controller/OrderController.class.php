<?php
namespace Home\Controller;
use Think\Controller;
class OrderController extends CommonController {
    public function index(){
    	echo "前台的Order控制器index方法"; 
    }



    /**
	* 提交订单接口(用户选择完规格以后、或者购物车结算时)
	* 
	* @access public
	* @param $userId 用户ID
	* @param $proInfoArray 二维数组，第二维是商品ID和对应商品的数量
	* @return json 返回是否操作成功
	*/
	public function submitOrder(){
		/*
			前端传的数据
			{'proInfoArray':[{'proId':324,'num':3,'standardId':23,'mid':1,'price':12,'oprice':12},{'proId':324,'num':3,'standardId':23,'mid':2,'price':12,'oprice':12},{'proId':34,'num':4,'standardId':3,'mid':1,'price':12,'oprice':12}]}
			不同的店铺属于不同的订单，所以将数据转化为下面格式
			array(
				[0]=>array(
					[0]=>array(
						"proId"=>324,
						"num"=>3,
						"standardId"=>23,
						"mid"=>1,
						"price"=>12,
						"oprice"=>2
					),
					[1]=>array(
						"proId"=>324,
						"num"=>3,
						"standardId"=>23,
						"mid"=>1,
						"price"=>12,
						"oprice"=>2
					),
				),
				[1]=>array(
					[0]=>array(
						"proId"=>324,
						"num"=>3,
						"standardId"=>23,
						"mid"=>2,
						"price"=>12,
						"oprice"=>2
					)
				)
			);
		*/
		$proInfoArray = I("proInfoArray");
		$userId = session("userId");
		/*缺少参数，直接返回*/
		if(empty($userId)||empty($proInfoArray)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//将属于同一店铺的商品放在一起
		$orderArray = array();
		foreach($proInfoArray as $k=>$v){
			//判断商品库存是否满足购买
			$s_res = M('pro_standard')->where(array("id"=>$v['standardId']))->field('inventory,name')->find();
			if(empty($s_res)){
				$this->ajaxReturn(array("result"=>0),'json');
			}
			if($s_res['inventory']<$v['num']){
				$this->ajaxReturn(array("result"=>3,"standard"=>$s_res['name']),'json');
			}
		    $orderArray[$v['mid']][] = $v;
		}
		/*1、设置事务，开启事务*/
		$submitOrder = M();
		$submitOrder->startTrans();
		//不同的订单，只要一起提交订单编号是一样的
		$orderNumber = createOrderNum();
		//遍历关联数组，不同的店铺属于不同的订单，存进数据库
		foreach ($orderArray as $key => $value) {
			/*1、先存订单表*/
			$orderInfo = array(
					"user_id"=>$userId,
					"order_number"=>$orderNumber,
					"merchant_id"=>$value[0]['mid'],//一个订单只属于一个店铺
					"status"=>-1,//确认了信息，但未提交订单，状态为-1
					"save_time"=>date("Y-m-d H:i:s",time())
				);
			//返回订单表ID
			$orderResult = $submitOrder->table(C('DB_PREFIX').'order')->add($orderInfo);
			//数据库存数据出错
			if(empty($orderResult)){
				//回滚事务
				$submitOrder->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			/*2、接着存order_pro表,有多个商品*/
			$all = array();
			for($i=0;$i<count($value);$i++){
				//$all的维数和$proInfoArray的维数是一样的
				$all[$i] = array(
					"order_id"=>$orderResult,
					"pro_id"=>$value[$i]['proId'],
					"num"=>$value[$i]['num'],
					"standard_id"=>$value[$i]['standardId'],
					"merchant_id"=>$value[$i]['mid'],
					"original_per_price"=>$value[$i]['oprice'],
					"sale_per_price"=>$value[$i]['price']
				);
			}
			$orderProResult = $submitOrder->table(C('DB_PREFIX').'order_pro')->addAll($all);
			if(empty($orderProResult)){
				//出错就回滚事务
				$submitOrder->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		//没出错，就提交事务
		$submitOrder->commit();
		/*提交订单成功以后，把订单编号存到session中*/
		session("orderNumber",$orderNumber);
		$this->ajaxReturn(array("result"=>1),'json');
	}

	/**
	* 根据地址ID获取地址的详细信息
	*（有两种可能，提交订单或者，选择地址某个地址时候）
	* 
	* @access public
	* @param $addressId 地址表ID
	* @return json 返回地址信息json数据
	*/
	public function getAddressInfo(){
		$addressId = I('addressId');
		if($addressId==null){
			//如果参数传过来是空，就取默认地址，如果没有默认地址，就显示添加地址
			$where['status'] = 1;
			$where['user_id'] = session("userId");
			$default = M('address')->where($where)->field('id,name,tel,roughly_address,detail_address')->find();
			if(empty($default)){
				//默认地址为空
				$this->ajaxReturn(array("result"=>3),'json');
			}else if(!empty($default)){
				$array = array(
						"result"=>1,
						"data"=>$default
					);
				$this->ajaxReturn($array,'json');
			}else{
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else{
			//如果有值传过来
			$addressId = I('addressId');
			if(empty($addressId)){
				$this->ajaxReturn(array("result"=>2),'json');
			}
			$where['id'] = $addressId;
			$result = M('address')->where($where)->field('id,name,tel,roughly_address,detail_address')->find();
			if(is_array($result)&&!empty($result)){
				$array = array(
						"result"=>1,
						"data"=>$result
					);
				$this->ajaxReturn($array,'json');
			}else if(empty($result)){
				$this->ajaxReturn(array("result"=>3),'json');
			}else{
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
	}
	/**
	* 根据session中的订单编号，获取该订单编号下的所有订单信息
	* 
	* @access public
	* @param $order_number 订单编号
	* @return json 返回所有订单json数据
	*/
	public function getOrdersInfoByOrderNumber(){
		$order_number = session("orderNumber");
		if(empty($order_number)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		/*1、取出所有订单ID*/
		$where['order_number'] = $order_number;
		$id_result = M('order')->where($where)->field("id")->select();
		if(empty($id_result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$info = array();
		for($i=0;$i<count($id_result);$i++){
			//商品信息
			$pro = $this->getOrderInfoByOrderId($id_result[$i]['id']);
			$oprice = $this->getPrePriceByOrderId($id_result[$i]['id']);
			if(empty($pro)||empty($oprice)){
				$this->ajaxReturn(array("result"=>0),'json');
			}
			$info[$i]['pro']['list'] = $pro;
			$info[$i]['pro']['oprice'] = $oprice;
			//优惠信息
			$discount_result = $this->getDiscountInfoByOrderId($id_result[$i]['id']);
			if($discount_result===false){
				$this->ajaxReturn(array("result"=>0),'json');
			}else if($discount_result==null){
				$info[$i]['discount'] = array(
						"list"=>array(),
						"save_price"=>0
					);
			}else{
				$info[$i]['discount'] = $discount_result;
			}
		}
		$array = array(
				"result"=>1,
				"data"=>$info
			);
		$this->ajaxReturn($array,'json');
	}
	/**
	* 完善订单信息（提交订单准备支付时）
	* 
	*提交订单的处理步骤
	*1、根据商品ID，向优惠表里查询是否有记录
	*2、判断是否满足优惠条件（条件是自己制定的）
	*3、根据条件计算出优惠的价格
	*4、根据不同的店铺，生成不同的订单，插入到订单表，但是不同的订单拥有相同的订单编号（同淘宝）
	*5、向订单明细表插入数据，特别是优惠ID，（这可以为空，取数据时候判断即可）
	*支付回调的处理步骤
	*1、多个订单一起支付（支付金额的计算：购物车、单个商品、订单列表再次支付<只有单个情况>）
	*2、
	*
	*
	* @access public
	* @param $orderNumber 订单编号
	* @return json 返回操作结果
	*/
	public function completeOrder(){
		/*************第三方平台相关的代码*************/
		$studentId = $_COOKIE['value'];//学生ID
		$plantform = $_COOKIE['plantform'];//平台ID
		if(!empty($studentId)){
			//如果不空，根据不同平台，调用对应平台的接口获取孩子家长的openid
			$parent = A('Interface')->getStudentInfoById($studentId,$plantform);
			if($plantform==2){
				$p_openid = $parent['parent_openid'];
			}else if($plantform==1){
				$p_openid = $parent['openid'];
			}
			// $p_openid = "oKqSawc3BNlX3CUXYuFbsoWgYiVw";//江军在呵贝的openID
		}
		/*******************************************/
		//前端传过来的数据
		/*
		[{'oid':1,'oprice':45.88,'price':23.00,'remark':"备注",'addressId':12,'send':1}]
		*/
		$order = I("order");
		if(empty($order)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		/*1、设置事务，开启事务*/
		$completeOrder = M();
		$completeOrder->startTrans();
		$oids = '';//保存订单ID的数组，传给支付页面
		//遍历传过来的订单，存数据库
		for($i=0;$i<count($order);$i++){
			$where['id'] = $order[$i]['oid'];
			$order_info = array(
					"original_price"=>$order[$i]['oprice'],
					"remark"=>$order[$i]['remark'],
					"address_id"=>$order[$i]['addressId'],
					"buy_time"=>date("Y-m-d H:i:s",time()),
					"student_id"=>$studentId,
					"open_id"=>$p_openid,
					"p_id"=>$plantform,
					"status"=>0,
					"send"=>$order[$i]['send'],
					"price"=>$order[$i]['price']
				);
			/*先存订单表*/
			$where['id'] = $order[$i]['oid'];
			$order_result = $completeOrder->table(C('DB_PREFIX').'order')->where($where)->save($order_info);
			if(empty($order_result)){
				//出错就回滚事务
				$completeOrder->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			/*再存订单明细表*/
			//根据订单ID获取活动列表
			$discount_result = $this->getDiscountInfoByOrderId($order[$i]['oid']);
			if($discount_result===false){
				//出错就回滚事务
				$completeOrder->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}else if($discount_result!==null){
				for($j=0;$j<count($discount_result['list']);$j++){
					$where = array(
							"order_id"=>$order[$i]['oid'],
							"standard_id"=>$discount_result['list'][$j]['standard_id']
						);
					$order_pro_result = $completeOrder->table(C('DB_PREFIX').'order_pro')->where($where)->field('discount_id')->find();
					if(empty($order_pro_result)){
						//查询出错就回滚事务
						$completeOrder->rollback();
						$this->ajaxReturn(array("result"=>0),'json');
					}else if($order_pro_result['discount_id']==0){
						//如果还没存优惠表ID（格式是：1,2,4）直接将结果存进去
						$data['discount_id'] = $discount_result['list'][$j]['id'];
						$save_result = $completeOrder->table(C('DB_PREFIX').'order_pro')->where($where)->save($data);
						if(empty($save_result)){
							//存数据库出错就回滚事务
							$completeOrder->rollback();
							$this->ajaxReturn(array("result"=>0),'json');
						}
					}else{
						//这个规格下面有多个优惠活动，已经存了部分进去了，把优惠活动ID追加到后面
						$data['discount_id'] = $order_pro_result['discount_id'].','.$discount_result['list'][$j]['id'];
						$save_result = $completeOrder->table(C('DB_PREFIX').'order_pro')->where($where)->save($data);
						if(empty($save_result)){
							//存数据库出错就回滚事务
							$completeOrder->rollback();
							$this->ajaxReturn(array("result"=>0),'json');
						}
					}
				}
			}
			//将订单ID拼接成字符串传给支付页面
			if($i==count($order)-1){
				$oids .= $order[$i]['oid'];
			}else{
				$oids .= $order[$i]['oid'].',';
			}
		}	
		//没出错，就提交事务
		$completeOrder->commit();
		$array = array(
				"result"=>1,
				"data"=>$oids
			);
		$this->ajaxReturn($array,'json');
	}
	/**
	* 根据订单ID，获得地址信息（查看订单用到）
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单的json数据
	*/
	public function getAddressInfoByOrderId(){
		$orderId = I('orderId');
		if(empty($orderId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['o.id'] = $orderId;
		$result = M('order')->alias('o')->join('tg_address a on a.id=o.address_id')->where($where)->field("a.name,a.tel,a.roughly_address,a.detail_address")->find();
		if(!empty($result)){
			$array = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($array,'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

	/**
	* 根据用户ID，获得该用户的所有订单信息（订单列表用到）
	* 
	* @access public
	* @param $status 订单状态
	* @param $page 请求的页数
	* @param $open_id 用户在第三方平台的openID
	* @return json 返回订单列表的json数据
	*/
	public function orderList(){
		/*
			前端传的数据
			{'status':12,'page':2,'openId':'23'}
		*/
		$open_id = I('openId');
		$status = I('status');
		$page = I('page');
			// $open_id = null;
			// $status = '';
		$user_id = session("userId");
		if(empty($user_id)&&empty($open_id)){
			//用户ID和openID都为空就返回，只要有一个不空，就可能会有订单
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//拼接where条件
		if(empty($open_id)){
			$where['user_id'] = $user_id;
		}else{
			$where['_string'] = 'user_id='.$user_id.' OR open_id="'.$open_id.'"';
		}
		//处理分页和订单状态
		if($status==''){
			$where['status'] = array('EGT',0);//没传状态，就默认取所有订单
		}else{
			$where['status'] = $status;
		}
		if(empty($page)){
			$page = 1;
		}
		//取出所有订单ID
		$id_result = M('order')->where($where)->page($page.',8')->order("buy_time desc")->field('id')->select();
		if($id_result===false){
             $this->ajaxReturn(array("result"=>0),'json');
        }else if(empty($id_result)){
            $this->ajaxReturn(array("result"=>3),'json');
        }else{
            $order_array = array();
            for($i=0;$i<count($id_result);$i++){
                $order_result = $this->getOrderListInfoByOrderId($id_result[$i]['id']);
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
	* 根据用户ID，获得该用户的所有退款订单信息（订单列表退款用到）
	* 
	* @access public
	* @param $page 请求的页数
	* @param $open_id 用户在第三方平台的openID
	* @return json 返回订单列表的json数据
	*/
	public function refundOrderList(){
		$page = I('page');
		$open_id = I("openId");
		$user_id = session("userId");
		if(empty($user_id)&&$open_id==null){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//拼接where条件
		if($open_id==null){
			$where1['o.user_id'] = $user_id;
		}else{
			$where1['_string'] = 'o.user_id='.$user_id.' OR o.open_id="'.$open_id.'"';
		}
		$where1['o.status'] = array("EGT",0);
		if(empty($page)){
			$page = 1;
		}
		$oid_result = M('order')->alias('o')->join('tg_refund r on r.order_id=o.id')->where($where1)->field('o.id,r.status')->page($page.',8')->select();
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
				$result = $this->getOrderListInfoByOrderId($oid_result[$i]['id']);
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
	* 申请退款
	* 
	* @access public
	* @param $orderId 订单ID
	* @param $reason 退款理由
	* @return json 返回退款是否成功的结果
	*/
	public function applyRefund(){
		$order_id = I('orderId');
		$reason = I('reason');
		$count = I('count');
		$user_id = session("userId");
		if(empty($order_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//判断这个订单是否是本人下的单
		$result = M('order')->where('id='.$order_id)->field('user_id')->find();
		if(empty($result)){
			//结果为空、查询出错都提示出错
			$this->ajaxReturn(array("result"=>0),'json');
		}else if($result['user_id']==session("userId")){
			//是本人下的单，执行以下操作
			//判断是否已经在退款表里有记录,并检查状态
			$preWhere['order_id'] = $order_id;
			$res = M('refund')->where($preWhere)->field('status')->find();
			if(!empty($res)&&$res['status']==0){
				//该订单正在退款中，商家还没回复
				$this->ajaxReturn(array("result"=>3),'json');
			}
			if(!empty($res)&&$res['status']==1){
				//该订单已经退款成功，不能重复操作
				$this->ajaxReturn(array("result"=>4),'json');
			}
			if(!empty($res)&&$res['status']==2){
				//该订单退款被拒绝过
				$this->ajaxReturn(array("result"=>5),'json');
			}
			if($res===false){
				$this->ajaxReturn(array("result"=>0),'json');
			}
			//开启事务
			$model = M();
	   		$model->startTrans();
			//修改订单状态为4
			$orderWhere['id'] = $order_id;
			$orderData['status'] = 4;
			$orderResult = $model->table("tg_order")->where($orderWhere)->setField($orderData);
			if($orderResult===false){
				$model->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			//想退款表里插入记录
			$data = array(
					"order_id"=>$order_id,
					"user_id"=>$user_id,
					"reason"=>$reason,
					"count"=>$count,
					"apply_time"=>date("Y-m-d H:i:s",time()),
					"status"=>0//刚申请时状态为0
				);
			$result = $model->table("tg_refund")->add($data);
			if(!empty($result)){
				$model->commit();
				//向商家发消息
				A('Interface')->sendRefundMsgToMerchant($order_id);
				$this->ajaxReturn(array("result"=>1),'json');
			}else{
				$model->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else{
			//不是本人的下单不能退款
			$this->ajaxReturn(array("result"=>6),'json');
		}
	}

	/**
	* 查看退款进度
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回退款状态
	*/
	public function checkRefundProgress(){
		$orderId = I('orderId');
		if(empty($orderId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['order_id'] = $orderId;
		$result = M('refund')->where($where)->field('status,apply_time,reply_time,reply')->find();
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(empty($result)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$array = array(
				"result"=>1,
				"data"=>$result
			);
		$this->ajaxReturn($array,'json');
	}
	/**
	* 退货订单列表
	* 
	* @access public
	* @param $user_id 用户ID
	* @param $open_id 用户openID
	* @return json 返回申请的结果
	*/
	public function returnOrderList(){
		$page = I('page');
		$open_id = I("openId");
		$user_id = session("userId");
		if(empty($user_id)&&$open_id==null){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//拼接where条件
		if($open_id==null){
			$where1['o.user_id'] = $user_id;
		}else{
			$where1['_string'] = 'o.user_id='.$user_id.' OR o.open_id="'.$open_id.'"';
		}
		$where1['o.status'] = array("EGT",0);
		if(empty($page)){
			$page = 1;
		}
		$oid_result = M('order')->alias('o')->join('tg_return r on r.order_id=o.id')->where($where1)->field('o.id,r.status')->page($page.',8')->select();
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
				$result = $this->getOrderListInfoByOrderId($oid_result[$i]['id']);
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
	* 申请退货
	* 
	* @access public
	* @param $order_id 订单ID
	* @param $user_id 用户ID
	* @param $name 用户姓名
	* @param $tel 用户电话
	* @param $tracking_number 退货的快递单号
	* @return json 返回申请的结果
	*/
	public function applyReturn(){
		$name = I("name");
		$tel = I("tel");
		$tracking_number = I("track");
		$reason = I("reason");
		$order_id = I("orderId");
		$user_id = session("userId");
		if(empty($name)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		if(empty($tel)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(empty($tracking_number)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		if(empty($reason)){
			$this->ajaxReturn(array("result"=>6),'json');
		}
		$isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $tel);
		if($isMatched==0){
			$this->ajaxReturn(array("result"=>10),'json');
		}
		if(empty($order_id)||empty($user_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//查询订单表里的用户ID，便于后面判断是否是本人下的单
		$result = M('order')->where('id='.$order_id)->field('user_id')->find();
		if(empty($result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if($result['user_id']==$user_id){
			//是本人下的单
			//再去退货表的查询是否已经有退货记录
			$where1['order_id'] = $order_id;
			$res = M('return')->where($where1)->field('status')->find();
			if($res===false){
				$this->ajaxReturn(array("result"=>0),'json');
			}else if(empty($res)){
				//第一次申请退货
				//开启事务
				$model = M();
		   		$model->startTrans();
				//修改订单状态为4
				$where2['id'] = $order_id;
				$orderData['status'] = 7;
				$orderResult = $model->table("tg_order")->where($where2)->setField($orderData);
				if($orderResult===false){
					$model->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
				//向退货表里插入记录
				$data = array(
						"order_id"=>$order_id,
						"user_id"=>$user_id,
						"reason"=>$reason,
						"name"=>$name,
						"tel"=>$tel,
						"tracking_number"=>$tracking_number,
						"apply_time"=>date("Y-m-d H:i:s",time()),
						"status"=>0//刚申请时状态为0
					);
				$return_result = $model->table("tg_return")->add($data);
				if(!empty($return_result)){
					$model->commit();
					//向商家发退货消息，然后再返回
					A('Interface')->sendReturnMsgToMerchant($order_id);
					$this->ajaxReturn(array("result"=>1),'json');
				}else{
					$model->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
			}else if($res['status']==0){
				//正在退货中
				$this->ajaxReturn(array("result"=>7),'json');
			}else if($res['status']==1){
				//已经退货成功
				$this->ajaxReturn(array("result"=>8),'json');
			}else if($res['status']==2){
				//已经退货失败
				$this->ajaxReturn(array("result"=>9),'json');
			}
		}else{
			$this->ajaxReturn(array("result"=>11),'json');
		}
	}
	/**
	* 根据订单ID获取商家的地址信息，用来给用户邮寄退货商品（退货页面用到）
	* 
	* @access public
	* @param $order_id 订单ID
	* @return json 返回申请的结果
	*/
	public function getMerchantInfoByOrderId(){
		$order_id = I('orderId');
		if(empty($order_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$result = M('merchant')->alias('m')->join('tg_order o on o.merchant_id=m.id')->field('m.real_name,m.tel,m.address')->find();
		if(!empty($result)){
			$array = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($array,'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	/**
	* 根据订单ID，获得订单的所有信息（查看订单用到）
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单的json数据
	*/
	public function checkOrderInfo(){
		$order_id = I("orderId");
		if(empty($order_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$result = array();//保存最终所有信息
		//商品信息
		$pro = $this->getOrderInfoByOrderId($order_id);
		//订单信息
		$order = $this->getOrderDetailInfoById($order_id);
		if(empty($pro)||empty($order)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$result['pro'] = $pro;
		$result['order'] = $order;
		//优惠信息
		$discount_result = $this->getDiscountInfoByOrderId($order_id);
		if($discount_result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if($discount_result==null){
			$result['discount'] = array(
					"list"=>array(),
					"save_price"=>0
				);
		}else{
			$result['discount'] = $discount_result;
		}
		$array = array(
				"result"=>1,
				"data"=>$result
			);
		$this->ajaxReturn($array,'json');
	}

	/*支付页面配置*/
	public function pay(){
		$oids = I('oids');
		$open_id = session('openId');
		if(empty($open_id)){
			redirect("../User/error");
		}
		/*1、接口配置*/
		$data = A('WxApi')->wxPayConfig();
		if(count(array_values($data))!=4||$data===false){
			redirect("../User/error");
		}
		$this->assign($data);
		/*2、检查条件，准备发起支付*/
		$ids_array = explode(",", $oids);
		$total_fee = 0;//保存支付总价格
		for($i=0;$i<count($ids_array);$i++){
			$where['o.id'] = $ids_array[$i];
			$result = M('order')->alias('o')->join(array('tg_order_pro op on op.order_id=o.id','tg_product p on p.id=op.pro_id'))->where($where)->field('o.status,o.price,p.name')->select();
			//查询出错
			if($result===false){
				redirect("../User/error");
				// print_r("查询出错！");
			}
			//判断订单状态是否是待支付状态
			if($result[0]['status']!=0){
				redirect("../User/error");
				// print_r("该订单不符合支付条件!");
			}
			//body的值就使用第一个商品的名称，而且截取40个字节
			if($i==0){
				$body = changeStr($result[0]['name'],40);
			}
			//计算支付的总价
			$total_fee += $result[0]['price'];
		}
		/*3、生成out_trade_no，每次发起支付都更新订单表里的out_trade_no*/
		$out_trade_no = C('mch_id').date('YmdHis');
		$where1['id'] = array("IN",$ids_array);
		$data['out_trade_no'] = $out_trade_no;
		$update_result = M('order')->where($where1)->setField($data);
		if($update_result===false){
			redirect("../User/error");
			// print_r("更新out_trade_no出错！");
		}
		/*4、把必须的参数赋值给模板，选择微信支付时，插入支付记录表*/
		$this->assign("out_trade_no",$out_trade_no)->assign("total_fee",$total_fee)->assign("body",$body);
		/*4、调用微信支付接口，向模板里赋值参数*/
		$w = A('WxApi')->wxPayApi($body,$total_fee*100,$open_id,$out_trade_no,$data['noncestr'],$data['timestamp']);
		if(count(array_values($w))!=2){
			redirect("../User/error");
			// print_r("调起微信支付参数少了");
		}
		$this->assign($w);
		$this->display();
	}
	/**
	* 用户选择微信支付向支付记录表里插支付请求记录
	* 
	* @access public
	* @param $oids 订单ID组成的字符串
	* @param $appid 商户的appid
	* @param $timestamp 支付配置产生的时间戳
	* @param $noncestr 支付配置产生的随机字符串
	* @param $out_trade_no 商户生成的唯一标识号
	* @param $open_id 用户的openID
	* @param $total_fee 支付的金额，单位为分
	* @param $body 支付的备注
	* @return json 返回操作结果
	*/
	public function insertPayRecord(){
		$oids = I('oids');
		$appid = I('appid');
		$timestamp = I('timestamp');
		$noncestr = I('noncestr');
		$out_trade_no = I('out_trade_no');
		$open_id = session("openId");
		$total_fee = I('total_fee');
		$body = I('body');
		if(empty($oids)||empty($appid)||empty($timestamp)||empty($noncestr)||empty($out_trade_no)||empty($open_id)||empty($total_fee)||empty($body)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$spbill_create_ip = $_SERVER['REMOTE_ADDR'];
		$mch_id = C('mch_id');
		$notify_url = C('notify_url');
		$trade_type = C('trade_type');
		$user_id = session('userId');
		$data = array(
				"appid"=>$appid,
				"nonce_str"=>$noncestr,
				"mch_id"=>$mch_id,
				"body"=>$body,
				"timestamp"=>$timestamp,
				"notify_url"=>$notify_url,
				"open_id"=>$open_id,
				"out_trade_no"=>$out_trade_no,
				"spbill_create_ip"=>$spbill_create_ip,
				"total_fee"=>$total_fee,
				"trade_type"=>$trade_type,
				"order_ids"=>$oids,
				"user_id"=>$user_id,
				"request_time"=>date("Y-m-d H:i:s",time()),
				"status"=>0//开始请求，状态为0
			);
		$result = M('pay_record')->add($data);
		if(empty($result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}
	/**
	* 货到付款的处理方法
	* 
	* @access public
	* @param $oids 订单ID组成的字符串
	* @return json 返回操作结果
	*/
	public function deliveryPayhandel(){
		$oids = I('oids');
		$price = I('price');
		if(empty($oids)||empty($price)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		/*1、判断支付的条件，订单状态为0才能支付*/
		$id_array = explode(",",$oids);
		$where['id'] = array("IN",$id_array);
		$status_result = M('order')->where($where)->field('order_number,status')->select();
		if(empty($status_result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		for($i=0;$i<count($status_result);$i++){
			if($status_result[$i]['status']!=0){
				//不满足支付条件
				$this->ajaxReturn(array("result"=>3),'json');
			}
		}
		/*2、更新订单表的支付方式和订单状态*/
		$data['pay'] = 2;//货到付款
		$data['status'] = 1;//改为已经支付的状态
		$result = M('order')->where($where)->save($data);
		if(empty($result)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$ids_array = explode(",",$oids);
		for($i=0;$i<count($ids_array);$i++){
			// 5、用户下单成功，向商家发下单的消息
			A("Interface")->sendNewOrderToMerchant($ids_array[$i]);
			// 6、用户下单成功，向监护人发送订单消息
			if(!empty(cookie('plantform'))&&!empty(cookie('value'))){
				//先确定是哪个平台的
				if(cookie("plantform")==1){
					//育讯通
					A('Interface')->yxtSendMsgToParent($ids_array[$i]);
				}else if(cookie("plantform")==2){
					//呵贝
					A("Interface")->sendMsgToParent($ids_array[$i]);
				}
			}
			//对销量和库存做相应的修改
			A('Order')->saleStatistic($result[$i]['oid']);
		}
		$this->ajaxReturn(array("result"=>1),'json');
	}

	/**
	* 根据订单ID字符串来获取订单的原价和优惠后的价格(pay页面用到)
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单数据
	*/
	public function getPrice(){
		/*传过来的数据：{"oids","1,2,3"}*/
		$oids = I("oids");
		if($oids==null){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$ids = explode(",", $oids);
		$oprice = 0;//保存原价
		$price = 0;//保存优惠后的价格
		for($i=0;$i<count($ids);$i++){
			$where['id'] = $ids[$i];
			$result = M('order')->where($where)->field("original_price,price")->find();
			//不能用empty()如果是没有优惠，empty(0)也是返回true
			if($result===false){
				$this->ajaxReturn(array("result"=>0),'json');
			}
			$oprice += $result['original_price'];
			$price += $result['price'];
		}
		$array = array(
				"result"=>1,
				"oprice"=>$oprice,
				"price"=>$price
			);
		$this->ajaxReturn($array,'json');
	}
	/**
	* 根据订单ID来获取订单信息(提交订单用到，没有购买者微信的)
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单数据
	*/
	public function getOrderInfoByOrderId($orderId){
		if(empty($orderId)){
			return false;
		}
		$where['o.id'] = $orderId;
		$where['pi.status'] = 1;
		$result = M('order_pro')->alias('op')->join(array('tg_order o on o.id=op.order_id','tg_product p on p.id=op.pro_id','tg_product_image pi on pi.pro_id=op.pro_id','tg_pro_standard ps on ps.id=op.standard_id','tg_merchant m on m.id=op.merchant_id'))->where($where)->field('m.id mid,m.shop_name,m.logo_path,m.tel,pi.img_path,p.name,op.num,ps.name sname,ps.sale_price,o.id oid')->select();
		if(!empty($result)){
			return $result;
		}else{
			return false;
		}
	}

	/**
	* 根据订单ID来获取订单信息(订单列表用到，有购买者微信的)
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单数据
	*/
	public function getOrderListInfoByOrderId($orderId){
		if(empty($orderId)){
			return false;
		}
		$where['o.id'] = $orderId;
		$where['pi.status'] = 1;
		$result = M('order_pro')->alias('op')->join(array('tg_order o on o.id=op.order_id','tg_product p on p.id=op.pro_id','tg_product_image pi on pi.pro_id=op.pro_id','tg_pro_standard ps on ps.id=op.standard_id','tg_merchant m on m.id=op.merchant_id','tg_user u on u.id=o.user_id'))->where($where)->field('m.id mid,m.shop_name,m.logo_path,m.tel,pi.img_path,p.name,op.num,ps.name sname,ps.sale_price,o.id oid,o.status,o.price,o.pay,u.username,u.img_path u_img_path')->select();
		if(!empty($result)){
			return $result;
		}else{
			return false;
		}
	}
	/**
	* 根据订单ID来获取更具体的订单信息(查看订单用到)
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单数据
	*/
	public function getOrderDetailInfoById($order_id){
		if(empty($order_id)){
			return false;
		}
		$where['o.id'] = $order_id;
		$result = M('order')->alias('o')->join('tg_user u on o.user_id=u.id')->where($where)->field('o.id,o.order_number,o.original_price,o.price,o.buy_time,o.status,u.username,o.pay,o.remark,o.tracking_num')->find();
		if(empty($result)){
			return false;
		}
		return $result;
	}
	/**
	* 根据订单ID计算这个订单优惠信息(需要计算出订单的总价,判断条件时候用到)
	*
	* @access public
	* @param $order_id 订单ID
	* @return json 返回订单优惠信息
	*/
	public function getDiscountInfoByOrderId($order_id){
		//取订单的优惠前总价，判断是否符合条件
		$order_sum_price=$this->getPrePriceByOrderId($order_id);
		if(empty($order_id)||empty($order_sum_price)){
			return false;
		}
		$where['o.id'] = $order_id;
		//取出该订单下的所有优惠活动
		$result = M('discount')->alias('d')->join(array('tg_standard_discount sd on sd.discount_id=d.id','tg_order_pro op on op.standard_id=sd.standard_id','tg_order o on o.id=op.order_id'))->field("d.id,d.name,d.condition,d.content,op.standard_id")->distinct(true)->where($where)->select();
		if($result===false){
			return false;
		}
		if($result!==false&&empty($result)){
			return null;
		}
		//遍历每个活动，得到该订单的优惠价格
		$discount = 0;//保存优惠的总金额
		$activity_array = array();//保存商品支持的活动
		foreach ($result as $key => $value){
			$temp = $this->getDiscountMoneyByCondition($value['condition'],$value['content'],$order_sum_price);
			if($temp!=0){
				array_push($activity_array,$value);
			}
			$discount += $temp;
		}
		$array = array(
			"list"=>$activity_array,
			"save_price"=>$discount//该订单要优惠的价格
			);
		return $array;
	}
	/**
	* 根据订单ID计算该订单优惠前的价格
	*
	* @access public
	* @param $orderID 订单ID
	* @return json 返回优惠前订单价格
	*/
	public function getPrePriceByOrderId($orderId){
		if(empty($orderId)){
			return false;
		}
		$where['order_id'] = $orderId;
		$result = M('order_pro')->alias('op')->where($where)->field("sale_per_price,num")->select();
		if(empty($result)){
			return false;
		}
		$sum = 0;
		foreach ($result as $key => $value) {
			$sum += $value['sale_per_price']*$value['num'];
		}
		return $sum;
	}
	/**
	* 根据优惠条件,优惠内容获得优惠金额
	* ！！！！后期根据活动的增加修改代码！！！！！！！！！
	* @access public
	* @param $condition 优惠条件
	* @return json 返回优惠的价格
	*/
	public function getDiscountMoneyByCondition($condition,$content,$order_price){
		//判断是否是满减或者满折（是否包含over）
		$condition_array = explode("_", $condition);
		if($condition_array[0]=="over"){//是满减或者满折
			if($condition_array[1]<$order_price){//满足条件
				//根据优惠内容判断
				$content_array = explode("_", $content);
				if($content_array[0]=="s"){//是满减
					return (float)$content_array[1];
				}else if($content_array[0]=="d"){//是满折
					return (1-(float)$content_array[1])*(float)$order_price;
				}
			}else{
				return 0;
			}
		}else if($condition_array[0]=="birthday"){//第三方平台生日当天
			$plantform = cookie("plantform");
			$birthday = cookie("birthday");
			$time = explode(" ", date("Y-m-d H:i:s",time()));
			$now_date = $time[0];
			if(!empty($plantform)&&($birthday==$now_time)){//满足条件
				$content_array = explode("_", $content);
				if($content_array[0]=="s"){//是减一定金额
					return (float)$content_array[1];
				}else if($content_array[0]=="d"){//是打折
					return (1-(float)$content_array[1])*(float)$order_price;
				}
			}else{
				return 0;
			}
		}
	}

    /**
	* 根据订单状态获取属于这个用户的此类订单的数量（用户中心那里显示用到）
	* 
	* @access public
	* @param $status 订单状态
	* @return json 返回订单数量的json数据
	*/
	public function orderNumByStatus(){
		$userId = session("userId");
		$status = I("status");
		if(empty($userId)||$status==""){
			$this->ajaxReturn(array("result"=>2),'json');	
		}
		$where['user_id'] = $userId;
		$where['status'] = $status;
		$result = M('order')->where($where)->count();
		if($result>=0){
			$array = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($array,'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');	
		}
	}

	/**
	* 根据订单ID，把订单相关的商品的销量和库存修改相应的数量(回调地址里调用)
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回操作结果
	*/
	public function saleStatistic($orderId){
		if(empty($orderId)){
			return false;
		}
		//取出该订单的所有商品ID
		$where['order_id'] = $orderId;
		$saleResult = M('order_pro')->where($where)->field('pro_id,num,standard_id')->select();
		if(empty($saleResult)){
			return false;
		}
		for($i=0;$i<count($saleResult);$i++){
			//开启事务
			$sale = M('pro_standard');
			$sale->startTrans();
			$condition['id'] = $saleResult[$i]['standard_id'];
			$saleRes = $sale->where($condition)->setInc('sale_num',$saleResult[$i]['num']);
			$inventoryRes = $sale->where($condition)->setDec('inventory',$saleResult[$i]['num']);
			if($saleRes===false||$inventoryRes===false){
				$sale->rollback();
				return false;
			}
			$sale->commit();
		}
		return true;
	}
	/**
	* 确认收货，将订单状态修改成已完成，向资产表插入数据，修改商家余额；
	* 
	* @access public
	* @return json 返回操作结果
	*/
	public function confirmReceive(){
		$id = I('id');
		if(empty($id)){
			$this->ajaxReturn(array("result"=>2));
		}
		//根据订单取出基本信息
		$info = M('order')->alias('o')->join('tg_merchant m on o.merchant_id=m.id')->where(array("o.id"=>$id))->field('o.price,o.status,o.merchant_id,m.balance')->find();
		if(empty($info)){
			$this->ajaxReturn(array("result"=>0));
		}
		$confirm = M();
		$confirm->startTrans();
		/*1、修改订单信息*/
		$data1 = array(
				"full_time"=>date("Y-m-d H:i:s",time()),
				"status"=>3,
				"receive_type"=>1
			);
		$res = $confirm->table('tg_order')->where(array('id'=>$id))->save($data1);
		if($res===false){
			$confirm->rollback();
			$this->ajaxReturn(array("result"=>0));
		}
		/*2、向资产表里插入数据*/
		$data2 = array(
				"merchant_id"=>$info['merchant_id'],
				"before_balance"=>$info['balance'],
				"count"=>$info['price'],
				"change_id"=>$id."_o",
				"reason"=>"订单收入",
				"after_balance"=>$info['balance']+$info['price'],
				"time"=>date("Y-m-d H:i:s",time())
			);
		$asset = $confirm->table('tg_asset')->add($data2);
		if($asset===false){
			$confirm->rollback();
			$this->ajaxReturn(array("result"=>0));
		}
		/*3、修改商家余额*/
		$balance = $confirm->table('tg_merchant')->where(array("id"=>$info['merchant_id']))->setInc('balance',$info['price']);
		if($balance===false){
			$confirm->rollback();
			$this->ajaxReturn(array("result"=>0));
		}
		$confirm->commit();
		$this->ajaxReturn(array("result"=>1));
	}

	/**
	* 自动收货，将订单表里订单状态为2而且距离发货时间15天的订单改成已完成(定时任务)
	* 
	* @access public
	* @return json 返回操作结果
	*/
	public function autoReceiving(){
		$where['status'] = 2;
		$time = date("Y-m-d H:i:s",strtotime("-15 day"));
		$where['deliver_time'] = array("ELT",$time);
		$order_res = M('order')->where($where)->field('id,price,merchant_id')->select();
		if(empty($order_res)){
			return false;
		}else{
			//遍历结果
			for($i=0;$i<count($order_res);$i++){
				$receive = M();
				$receive->startTrans();
				/*1、修改订单状态*/
				$data = array(
						"full_time"=>date("Y-m-d H:i:s",time()),
						"status"=>3,
						"receive_type"=>2
					);
				$res = $receive->table('tg_order')->where(array("id"=>$order_res[$i]['id']))->save($data);
				if($res===false){
					$receive->rollback();
					continue;
				}
				/*2、向资产表里插入数据*/
				//取出商家余额
				$merchant = M('merchant')->where(array("id"=>$order_res[$i]['merchant_id']))->field('balance')->find();
				if($merchant===false){
					$receive->rollback();
					continue;
				}
				//插入数据
				$asset = array(
						"merchant_id"=>$order_res[$i]['merchant_id'],
						"before_balance"=>$merchant['balance'],
						"count"=>$order_res[$i]['price'],
						"change_id"=>$order_res[$i]['id']."_o",
						"reason"=>"订单收入",
						"after_balance"=>$merchant['balance']+$order_res[$i]['price'],
						"time"=>date("Y-m-d H:i:s",time())
					);
				$asset = $receive->table('tg_asset')->add($asset);
				if($asset===false){
					$receive->rollback();
					continue;
				}
				/*3、修改商家余额*/
				$balance = $receive->table('tg_merchant')->where(array("id"=>$order_res[$i]['merchant_id']))->setInc('balance',$order_res[$i]['price']);
				if($balance===false){
					$receive->rollback();
					continue;
				}
				$receive->commit();
			}
			return true;
		}
	}

	/*
		1、公众号菜单的设置，对应三类客户，分别给个入口
		（1）购买者的商城入口：商城入口
		（2）商家的入口：商家中心
		（3）潜在的商家，通过渠道关注到我们公众号：招商政策（需要我们自己想里面的内容，如何吸引到商家在我们这里开店）
		2、组建团队，寻找比赛能力强的人（有补贴）
		（1）准备学校里的各种比赛
		（2）通过比赛包装我们的项目
		3、系统里增加退货的流程
		商城里防近视的笔，厂家免费提供了60套，放在商城里提供给用户试用，用户只要缴纳押金就可以发货，用户试用完成就可以直接购买或者退货，如果选择退货，我们收到货以后把押金返还个用户
		4、育讯通公众号里的校园头条会改版，里面有各种模块，部分模块会由我们自己来管理，比如育儿、益智、好书推荐等由我们自己定也由我们自己管理，就是发文章，底部加个相关的商品链接；
		5、有些价值需要我们自己挖掘，有好的想法就可以直接实践
	*/
   
}