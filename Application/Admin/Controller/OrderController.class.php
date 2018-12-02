<?php
namespace Admin\Controller;
use Think\Controller;
class OrderController extends CommonController {
	/**
	* 根据条件获取商城的所有订单
	* 
	* @access public
	* @param $status 订单状态
	* @return json 返回订单列表的json数据
	*/
	public function orderListHandel(){
		$status = I('status');
		if($status==""){
			$where['o.status'] = array('EGT',-1);
		}else{
			$where['o.status'] = $status;
		}
		$order_res = M('order')->alias('o')->join(array('tg_merchant m on m.id=o.merchant_id','tg_address a on a.id=o.address_id'))->where($where)->field('o.id,o.order_number,o.pay,o.price,o.status,m.shop_name,a.name')->select();
		if($order_res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($order_res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$order_res
				);
			$this->ajaxReturn($array,'json');
		}
	}

	/**
	* 根据订单ID，获得地址信息
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单的json数据
	*/
	public function getAddressInfoByOrderId(){
		$orderId = I('oid');
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
	* 根据订单ID，获得订单的所有信息（查看订单用到）
	* 
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单的json数据
	*/
	public function checkOrderInfo(){
		$order_id = I('oid');
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
		$result = M('order')->alias('o')->join('tg_user u on o.user_id=u.id')->where($where)->field('o.id,o.order_number,o.original_price,o.price,o.buy_time,o.status,u.username,o.pay,o.remark')->find();
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
}