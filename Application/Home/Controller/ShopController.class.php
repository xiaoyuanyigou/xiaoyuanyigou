<?php
namespace Home\Controller;
use Think\Controller;
class ShopController extends CommonController {
	/**
	* 根据商品ID获取店铺信息
	* 
	* @access public
	* @param $proId 商品ID
	* @return json 返回订单的json数据
	*/
	public function getShopInfoByProId(){
		$proId = I("proId");
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['p.id'] = $proId;
		$result = M('merchant')->alias('m')->join('tg_product p on p.merchant_id=m.id')->where($where)->field('m.id,m.logo_path,m.shop_name,m.tel,m.address,m.latitude,m.longitude')->find();
		if(!empty($result)){
			$array = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($array,'json');
		}else{
			//为空都属于出错
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

	/**
	* 根据订单ID获取该订单支持的支付方式(多家店有不同的支持付款方式)
	* （规定微信支付必须要，货到付款可选）
	* @access public
	* @param $orderId 订单ID
	* @return json 返回订单的json数据
	*/
	public function getOrderPayment(){
		$ids = I("oids");
		if(empty($ids)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$ids_string = explode(",", $ids);
		$where['op.order_id'] = array("IN",$ids_string);;
		$result = M('order_pro')->alias('op')->join("tg_merchant m on op.merchant_id=m.id")->where($where)->field("m.pay")->distinct(true)->select();
		if($result===false||empty($result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$pay = explode(",", $result[0]['pay']);
		for($i=0;$i<count($result);$i++){
			if($i<count($result)-1){
				$result1 = explode(",", $result[$i+1]['pay']);
				$pay = array_intersect($pay,$result1);
			}else{
				break;
			}
		}
		//如果以后要加其他支付方式，这里要修改
		if(in_array(1,$pay)&&in_array(2, $pay)){
			//两种付款方式都支持
			$this->ajaxReturn(array("result"=>1),'json');
		}else if(in_array(1,$pay)&&!in_array(2, $pay)){
			//只支持微信支付
			$this->ajaxReturn(array("result"=>3),'json');
		}else if(!in_array(1,$pay)&&in_array(2, $pay)){
			//只支持货到付款
			$this->ajaxReturn(array("result"=>4),'json');
		}else{
			//算做出错
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

}