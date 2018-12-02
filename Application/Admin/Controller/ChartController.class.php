<?php
namespace Admin\Controller;
use Think\Controller;
class ChartController extends Controller {
    /**
	* 近七天每天的订单数，如果带了商家ID，就选店铺的近七天订单数
	* 包含退款、退货、送货中的订单
	* @access public 
	* @param $time 当前时间
	* @return json 返回七天订单数的字符串
	*/
	public function getThisWeekOrderNumOfPerDay($merchant_id=""){
		$timArr = array(
				date("Y-m-d H:i:s",strtotime("-7 day")),
				date("Y-m-d H:i:s",strtotime("-6 day")),
				date("Y-m-d H:i:s",strtotime("-5 day")),
				date("Y-m-d H:i:s",strtotime("-4 day")),
				date("Y-m-d H:i:s",strtotime("-3 day")),
				date("Y-m-d H:i:s",strtotime("-2 day")),
				date("Y-m-d H:i:s",strtotime("-1 day")),
				date("Y-m-d H:i:s",time())
			);
		$res = '';
		for($i=0;$i<7;$i++){
			if(!empty($merchant_id)){
				$where['merchant_id'] = $merchant_id;
			}
			$where['status'] = array("GT",0);//只要是付过款的订单
			$where['buy_time'] = array('between',array($timArr[$i],$timArr[$i+1]));
			$num = M('order')->where($where)->count();
			if($num<0||$num===false){
				return false;
			}
			if($i==6){
				$res .=$num;
			}else{
				$res .=$num.',';
			}
		}
		return $res;
	}
	/**
	* 近七天的订单总数，如果带了商家ID，就选店铺的近七天订单总数
	* 
	* @access public 
	* @param $time 当前时间
	* @return json 返回七天订单总数
	*/
	public function getThisWeekOrderNum($merchant_id=""){
		$before = date("Y-m-d H:i:s",strtotime("-7 day"));//七天前的时间
		$now = date("Y-m-d H:i:s",time());
		if(!empty($merchant_id)){
			$where['merchant_id'] = $merchant_id;
		}
		$where['status'] = array("GT",0);//付过款的订单，包括退款、退货
		$where['buy_time'] = array('between',array($before,$now));
		$num = M('order')->where($where)->count();
		if($num===false||$num<0){
			return false;
		}
		return $num;
	}
	/**
	* 获取近七天销售总额，如果带了商家ID，就获取店铺七天销售总额
	* 统计已完成的订单
	* @access public 
	* @param $time 当前时间
	* @return json 返回销售额
	*/
	public function getAllSalePriceInThisWeek($merchant_id=""){
		$before = date("Y-m-d H:i:s",strtotime("-7 day"));//七天前的时间
		$now = date("Y-m-d H:i:s",time());
		if(!empty($merchant_id)){
			$where['merchant_id'] = $merchant_id;
		}
		$where['status'] = 3;//统计已完成的订单
		$where['full_time'] = array('between',array($before,$now));
		$sum = M('order')->where($where)->sum('price');
		if($sum===false||$sum<0){
			return false;
		}
		if($sum==NULL){
			return 0;
		}
		return $sum;
	}

	public function test(){
		$res = $this->getAllSalePriceInThisWeek(1);
		print_r($res);
	}
	/**
	* 近七天每天的销售额，带了商家ID，就查询该店铺的每天销售额
	* 统计已经完成的订单
	* @access public
	* @param $time 当前时间
	* @return json 返回七天销售额的字符串
	*/
	function getThisWeekSalePriceSum($merchant_id=""){
		$timArr = array(
				date("Y-m-d H:i:s",strtotime("-7 day")),
				date("Y-m-d H:i:s",strtotime("-6 day")),
				date("Y-m-d H:i:s",strtotime("-5 day")),
				date("Y-m-d H:i:s",strtotime("-4 day")),
				date("Y-m-d H:i:s",strtotime("-3 day")),
				date("Y-m-d H:i:s",strtotime("-2 day")),
				date("Y-m-d H:i:s",strtotime("-1 day")),
				date("Y-m-d H:i:s",time())
			);
		$result = '';
		for($i=0;$i<7;$i++){
			if(!empty($merchant_id)){
				$where['merchant_id'] = $merchant_id;
			}
			$where['status'] = 3;//统计完成订单
			$where['full_time'] = array('between',array($timArr[$i],$timArr[$i+1]));
			$res = M('order')->where($where)->sum('price');
			if($res===false||$res<0){
				return false;
			}
			if($i==6){
				$result .=$res;
			}else{
				$result .=$res.',';
			}
		}
		return $result;
	}
	/**
	* 系统商品总数，带了商家ID，就查询该店铺的商品总数
	* 下架的不算
	* @access public
	* @param $time 当前时间
	* @return json 返回对应的商品总数
	*/
	public function getAllProductNum($merchant_id=""){
		if(!empty($merchant_id)){
			$where['merchant_id'] = $merchant_id;
		}
		$where['status'] = 1;//已上架的
		$num = M('product')->where($where)->count();
		if($num === false||$num<0){
			return false;
		}
		return $num;
	}
	/**
	* 系统用户总数
	* 
	* @access public 
	* @param $time 当前时间
	* @return json 返回人数
	*/
	public function getUserNum(){
		$where['status'] = 1;
		$result = M('user')->where($where)->count();
		if($result===false||$result<0){
			return false;
		}
		return $result;
	}
}