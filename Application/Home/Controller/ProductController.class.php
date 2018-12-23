<?php
namespace Home\Controller;
use Think\Controller;
class ProductController extends CommonController {
    public function index(){
    	echo "前台的Product控制器index方法"; 
    }
    /*-----------------------商品详情页接口开始---------------------*/
    public function pro_detail(){
    	$data = A('WxApi')->wxPayConfig();
		if(count(array_values($data))!=4){
			redirect("../User/error");
		}else{
			$this->assign($data);
		}
		$proId = I('proId');
		if(!empty($proId)){
			$where['p.id'] = $proId;
			$where['pi.status'] = 1; 
			$res = M('product')->alias('p')->join('tg_product_image pi on pi.pro_id=p.id')->where($where)->field('p.name,pi.img_path')->find();
			if(empty($res)){
				redirect("../User/error");
			}
			$url = "http://stdbuy.wisvalley.com/index.php/Product/pro_detail?proId=".$proId;
			$this->assign('proName',$res['name']);
			$img_path = explode("./",$res['img_path'])[1];
			$this->assign('proImg',C('domain').$img_path);
			$this->assign('url',$url);
		}
		$this->display();
    }
    /**
	* 判断商品是否已经下架
	* 
	* @access public 
	* @param $proId
	* @return 返回json数据
	*/
    public function isOnSale(){
    	$pro_id = I("proId");
    	$where['id'] = $pro_id;
    	$res = M('product')->where($where)->field('status')->find();
    	if(empty($res)){
    		$this->ajaxReturn(array("result"=>0),'json');
    	}else if($res['status']==0){
    		$this->ajaxReturn(array("result"=>3),'json');
    	}
    }
    /**
	* 根据商品ID取出轮播图的图片
	* 
	* @access public 
	* @param $proId
	* @return 返回json数据
	*/
	public function getAllPicture(){
		$proId = I('proId');
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['pro_id'] = $proId;
		$where['status'] = array("EGT",0);
		$result = M('product_image')->where($where)->field('img_path')->order('status desc')->select();
		if(!empty($result)){
			$array = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($array,'json');
		}else{
			$this->ajaxReturn("{'result':'0'}",'json');
		}
	}
	/**
	* 根据商品ID取出商品名称、原价、售价、总销量
	* 
	* @access public 
	* @param $proId
	* @return json 返回信息的json数据
	*/
	public function getProductBaseInfo(){
		$proId = I('proId');
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where1['ps.status'] = 1;
		$where1['p.id'] = $proId;
		$where1['pi.status'] = 1;
		$where1['p.status'] = 1;
		$result1 = M('product')->alias('p')->join(array('tg_pro_standard ps on p.id=ps.pro_id','tg_product_image pi on pi.pro_id=p.id'))->where($where1)->field('p.name,p.merchant_id,ps.sale_price,ps.original_price,pi.img_path')->find();
		$where2['pro_id'] = $proId;
		$result2 = M('pro_standard')->where($where2)->sum('sale_num');
		if(!empty($result1)&&$result2>=0){
			$result1['all'] = $result2;
			$array = array(
					"result"=>1,
					"data"=>$result1
				);
			$this->ajaxReturn($array,'json');
		}else if(empty($result1)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	/**
	* 根据商品ID取出所有规格
	* 
	* @access public 
	* @param $proId
	* @return json 返回信息的json数据
	*/
	public function getAllStandard(){
		$proId = I('proId');
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['pro_id'] = $proId;
		$where['status'] = array("EGT",0);
		$result = M('pro_standard')->where($where)->field('id,name,sale_price,original_price')->select();
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
	* 获取商品的描述图片
	* 
	* @access public 
	* @param $proId
	* @return json 返回商品信息的json数据
	*/
	public function getPictureOfDescription(){
		$proId = I("proId");
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['pro_id'] = $proId;
		$where['status'] = array("EGT",0);
		$result = M('product_image_descript')->where($where)->field("img_path")->select();
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
		/**
	* 根据商品ID，获取属于和这个商品相同三级分类的商品
	*（查看相似、热卖推荐有用到）
	* 
	* @access public 
	* @param $proId
	* @return json 返回商品信息的json数据
	*/
	public function getAllProductHasSameThirdSortId(){
		$proId = I('proId');
		$order = I('order');
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//查找这个商品的三级分类ID
		$where1['id'] = $proId;
		$sortResult = M('Product')->where($where1)->field("sort_id")->find();
		if(!empty($sortResult)){
			$sortId = $sortResult['sort_id'];
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$where['pi.status'] = 1;
		$where['ps.status'] = 1;
		$where['p.id'] = array('NEQ',$proId);//把自己排除掉
		$where['p.status'] = 1;
		$where['p.sort_id'] = $sortId;
		$result = M('product')
				->alias('p')
				->join(array('tg_pro_standard ps on ps.pro_id=p.id','tg_product_image pi on pi.pro_id=p.id'))
				->where($where)
				->limit(8)//这里每页展示的数量规定好
				->field('p.id,p.name,ps.sale_price,pi.img_path')
				->select();
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($result)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
				"result"=>1,
				"data"=>$result
			);
			$this->ajaxReturn($array,'json');
		}
	}

	/**
	* 取出商城新上架商品（pro_detail页面新品推荐用到）
	* 
	* @access public 
	* @param $proId
	* @return json 返回信息的json数据
	*/
	public function getNewProduct(){
		$proId = I('proId');
		if(empty($proId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['pi.status'] = 1;
		$where['ps.status'] = 1;
		$where['p.id'] = array('NEQ',$proId);//把自己排除掉
		$where['p.status'] = 1;
		$result = M('product')
				->alias('p')
				->join(array('tg_pro_standard ps on ps.pro_id=p.id','tg_product_image pi on pi.pro_id=p.id'))
				->where($where)
				->limit(8)//这里每页展示的一定数量
				->field('p.id,p.name,ps.sale_price,pi.img_path')
				->select();
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($result)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($array,'json');
		}
	}
	/*-----------------------商品详情页接口结束---------------------*/
	/**
	* 添加购物车接口
	* 
	* @access public 
	* @param $userId 用户的ID
	* @param $standardId 规格ID
	* @param $num 商品数量
	* @return json 返回是否操作成功
	*/
	public function addCart(){
		/*
			前端传的数据
			{'standardId':2,'num':123}
		*/
		$proId = I('proId');
		$userId = session("userId");
		$standardId = I('standardId');
		$num = I('num');
		if(empty($proId)||empty($userId)||empty($standardId)||empty($num)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//如果在购物表有数据就直接加相应的数量
		$where['standard_id'] = $standardId;
		$cartResult = M('cart')->where($where)->field("id,status")->find();
		if(!empty($cartResult)&&$cartResult['status']==1){
			$where1['id'] = $cartResult['id'];
			$addResult = M("cart")->where($where1)->setInc("num",$num);
			if($addResult>0){
				$this->ajaxReturn(array("result"=>1),'json');
			}else{
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else if(!empty($cartResult)&&$cartResult['status']==0){
			//有原来的记录，但是期间删除了，更新数量和状态
			$data = array(
				"num"=>$num,
				"time"=>date("Y-m-d H:i:s",time()),
				"status"=>1
			);
			$where2['id'] = $cartResult['id'];
			$result = M('cart')->where($where2)->save($data);
			if(!empty($result)){
				$this->ajaxReturn(array("result"=>1),'json');
			}else{
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else if($cartResult!==false&&empty($cartResult)){
			$data = array(
				"pro_id"=>$proId,
				"user_id"=>$userId,
				"standard_id"=>$standardId,
				"num"=>$num,
				"time"=>date("Y-m-d H:i:s",time()),
				"status"=>1
			);
			$result = M('cart')->add($data);
			if($result){
				$this->ajaxReturn(array("result"=>1),'json');
			}else{
				print_r(3);die;
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	/**
	* 购物车列表接口
	* 
	* @access public 
	* @param $userId 用户ID
	* @return json 返回用户购物车json数据
	*/
	public function cartList(){
		$userId =session('userId');
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['c.user_id'] = $userId;
		$where['pi.status'] = 1;
		$where['c.status'] = 1;
		$where['p.status'] = 1;
		$result = M('cart')->alias('c')->join(array('tg_product p on p.id=c.pro_id','tg_pro_standard ps on c.standard_id=ps.id','tg_product_image pi on pi.pro_id=c.pro_id','tg_merchant m on m.id=p.merchant_id'))
		->where($where)
		->field('c.id,ps.id sid,p.id pid,ps.sale_price,ps.original_price,c.num,p.name,ps.name sname,pi.img_path,m.id mid')
		->select();
		if(is_array($result)&&!empty($result)){
			$arr = array(
					"result"=>1,
					"data"=>$result
				);
			$this->ajaxReturn($arr,'json');
		}else if(empty($result)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	/**
	* 删除购物车接口
	* 
	* @access public 
	* @param $proId 商品ID以‘,’连接的字符串组成的json数据
	* @return json 返回是否操作成功
	*/
	public function deleteCart(){
		$cartIdstr = I('proIdStr');
		/*
			前端传过来的数据是这样的,在前台拼接成以‘,’连接的字符串
			{'proIdStr':'1,3,5,7,3'}
		*/
		if(empty($cartIdstr)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$cartIdArray = explode(',',$cartIdstr);
		$where['id'] = array("IN",$cartIdArray);
		$data['status'] = 0;
		$result = M('cart')->where($where)->setField($data);
		if(empty($result)){
			//数据库删除失败，直接返回
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$this->ajaxReturn(array("result"=>1),'json');
	}
	/**
	* 获取所有的一级分类
	* 
	* @access public 
	* @return json 返回一级分类的json数据
	*/
	public function getAllFirstSort(){
		$where['parent_id'] = 0;
		$where['status'] = 1;
		$sort_res = M('sort')->where($where)->field("id,sort_name")->select();
		if($sort_res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($sort_res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$sort_res
				);
			$this->ajaxReturn($array,'json');
		}
	}
	/**
	* 获取一级分类下的二级分类
	* 
	* @access public 
	* @return json 返回二级分类的json数据
	*/
	public function getSecondSortByFirstSortId(){
		$fid = I('fid');
		if(empty($fid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['parent_id'] = $fid;
		$where['status'] = 1;
		$res = M('sort')->where($where)->field('id,sort_name')->select();
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
	* 根据二级分类ID，获取属于这个二级分类的商品
	*（分类页面点击二级分类进入商品列表页有用到）
	* 
	* @access public 
	* @param $sortId
	* @return json 返回商品信息的json数据
	*/
	public function getAllSortBelongTheSecondSort(){
		$sortId = I('sortId');
		$page = I('page');
		$order = I('order');
		if(empty($sortId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($page)){
			$page = 1;
		}
		$up = I('up');
		$where1['s1.id'] = $sortId;
		//取出二级分类下面的所有三级分类
		$oneResult = M('sort')->alias('s1')->join('tg_sort s2 on s2.parent_id=s1.id')->where($where1)->field('s2.id')->select();
		if(is_array($oneResult)&&!empty($oneResult)){
			$ids = array();
			//把二维数组转成一维数组
			for($i=0;$i<count($oneResult);$i++){
				$ids[$i] = $oneResult[$i]['id'];
			}
			$where2['pi.status'] = 1;
			$where2['ps.status'] = 1;
			$where2['p.status'] = 1;//上架的商品
			$where2['p.sort_id'] =array("in",$ids);
			if(!empty($order)){
				$result = M('product')
						->alias('p')
						->join(array('tg_product_image pi on p.id=pi.pro_id','tg_pro_standard ps on p.id=ps.pro_id'))
						->where($where2)
						->order($order.' '.$up)
						->page($page.',8')
						->field('p.id,p.name,ps.sale_price,pi.img_path')
						->select();
			}else{
				$result = M('product')
						->alias('p')
						->join(array('tg_product_image pi on p.id=pi.pro_id','tg_pro_standard ps on p.id=ps.pro_id'))
						->where($where2)
						->page($page.',8')
						->field('p.id,p.name,ps.sale_price,pi.img_path')
						->select();
			}
			if($result===false){
				$this->ajaxReturn(array("result"=>0),'json');
			}else if(empty($result)){
				$this->ajaxReturn(array("result"=>3),'json');
			}else{
				$array = array(
						"result"=>1,
						"data"=>$result
					);
				$this->ajaxReturn($array,'json');
			}
		}else if(empty($oneResult)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	/**
	* 根据一级分类ID，获取属于这个一级分类的商品
	*（首页点击一级分类进入商品列表页有用到）
	* 
	* @access public 
	* @param $sortId
	* @return json 返回商品信息的json数据
	*/
	public function getAllSortBelongTheFirstSort(){
		$sortId = I('sortId');
		$page = I('page');
		$order = I('order');
		if(empty($sortId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($page)){
			$page = 1;
		}
		$up = I('up');
		$where1['s1.id'] = $sortId;
		//取出该一级分类下的所有三级分类
		$oneResult = M('sort')->alias('s1')->join(array('tg_sort s2 on s2.parent_id=s1.id','tg_sort s3 on s3.parent_id=s2.id'))->where($where1)->field('s3.id')->select();
		if(is_array($oneResult)&&!empty($oneResult)){
			$ids = array();
			//把二维数组转成一维数组
			for($i=0;$i<count($oneResult);$i++){
				$ids[$i] = $oneResult[$i]['id'];
			}
			$where2['pi.status'] = 1;
			$where2['ps.status'] = 1;
			$where2['p.status'] = 1;//上架的商品
			$where2['p.sort_id'] =array("in",$ids);
			if(!empty($order)){
				$result = M('product')
						->alias('p')
						->join(array('tg_product_image pi on p.id=pi.pro_id','tg_pro_standard ps on p.id=ps.pro_id'))
						->where($where2)
						->order($order.' '.$up)
						->page($page.',8')
						->field('p.id,p.name,ps.sale_price,pi.img_path')
						->select();
			}else{
				$result = M('product')
						->alias('p')
						->join(array('tg_product_image pi on p.id=pi.pro_id','tg_pro_standard ps on p.id=ps.pro_id'))
						->where($where2)
						->page($page.',8')
						->field('p.id,p.name,ps.sale_price,pi.img_path')
						->select();
			}
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
		}else if(empty($oneResult)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}	
	/**
	* 按关键字查找商品列表
	* 
	* @access public 
	* @param $key 查找关键字
	* @param $page 分页数
	* @param $order 排序方式
	* @return json 返回商品列表的json数据
	*/
	public function searchProductByKey(){
		$key = I('key');
		if(empty($key)||$key=="null"){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//调用百度的文字处理的词法分析接口，获得分析后的词组
		$res = AipNlp($key);
		if(empty($res['items'])){
			//请求接口失败
			$this->ajaxReturn(array("result"=>4),'json');
		}
		//保存where数组
		$whereArray = array();
		for($i=0;$i<count($res['items']);$i++){
			$handelKey = $this->handleSearchKey($res['items'][$i]['item']);
			array_push($whereArray,array('like',$handelKey));
		}
		array_push($whereArray, 'OR');
		$where['p.name']  = $whereArray; 
		//接收页数参数
		$page = I('page');
		if(empty($page)){
			$page = 1;
		}
		$where['pi.status'] = 1;
		$where['ps.status'] = 1;
		$where['p.status'] = 1;//1表示上架的商品
		$proList = M('product')
				   ->alias('p')
				   ->join(array('tg_product_image pi on pi.pro_id=p.id','tg_pro_standard ps on ps.pro_id=p.id'))
				   ->page($page.',8')
				   ->where($where)
				   ->field('p.name,p.id,pi.img_path,ps.sale_price')
				   ->select();
		if($proList===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($proList)){
			//向搜索记录表里记录数据
			$this->saveSearchRecord($key);
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$proList
				);
			//向搜索记录表里记录数据
			$this->saveSearchRecord($key);
			$this->ajaxReturn($array,'json');
		}
	}
	/**  
	* 将搜索的关键字的每个字符之间加入'%'
	* 
	* @access public
	* @param $str 查找关键字
	* @return $key 返回处理后包含'%'的字符串
	*		  null 传入参数为空返回null
	*/
	public function handleSearchKey($str){
		if(empty($str)){
			return null;
		}
		preg_match_all("/./u", $str, $arr); 
		$key = '';
		for($i=0;$i<count($arr[0]);$i++){
			$key .='%'.$arr[0][$i];
		}
		$key = $key.'%';
		return $key;
	}
	/**
	* 
	* @access public
	* @param $userId 用户ID
	* @param $key 搜索的关键字
	* @return  返回操作结果
	*/
	public function saveSearchRecord($key){
		if($key==null){
			return false;
		}
		$userId = session("userId");
		//不用判断参数是否为空，调用时已经会判断
		$where['search_name'] = $key;
		$where['user_id'] = $userId;
		$searchResult = M('search_record')->where($where)->find();
		if(is_array($searchResult)&&!empty($searchResult)){
			$data['time'] = date("Y-m-d H:i:s");
			$updateResult = M('search_record')->where($where)->save($data);
			if($updateResult===false){
				return false;
			}else{
				return true;
			}
		}else if(empty($searchResult)){
			$data = array(
				"search_name"=>$key,
				"user_id"=>$userId,
				"time"=>date("Y-m-d H:i:s",time()),
				"status"=>1
			);
			$result = M('search_record')->add($data);
			if($result){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/*********从这里开始是商家管理的部分，放在手机端**********/

	/**
	* 
	* 商家在手机端上架商品
	* @access public
	* @return  返回操作结果
	*/
	public function putProduct(){
		$open_id = session("openId");
		if(empty($open_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$mid_res = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
		if(empty($mid_res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$merchant_id = $mid_res['id'];
		//判断图片是否为空
		$fi = I("focusImg");
		$di = I("describeImg");
		if(empty($fi)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		if(empty($di)){
			$this->ajaxReturn(array("result"=>6),'json');
		}
		/*1、开启事务*/
		$product = M();
		$product->startTrans();
		/*2、先存商品表*/
		$proName = I('proName');
		//分类是否合法
		$sort = I('sort');
		$sort_array = explode(",",$sort);
		if(count($sort_array)!=3){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$third_sort_id = $sort_array[2];
		if($third_sort_id==0){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		$product_data = array(
				"name"=>$proName,
				"sort_id"=>$third_sort_id,
				"merchant_id"=>$merchant_id,
				"status"=>1,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$product_res = $product->table('tg_product')->add($product_data);
		if($product_data===false){
			$product->rollback();
			$this->ajaxReturn(array("result"=>0),'json');
		}
		/*3、保存规格表*/
		$standard = I('standard');
		for($i=0;$i<count($standard);$i++){
			//第一个为默认规格
			if($i==0){
				$status=1;
			}else{
				$status=0;
			}
			$standard_data = array(
					"pro_id"=>$product_res,
					"name"=>$standard[$i]['sName'],
					"sale_price"=>$standard[$i]['sPrice'],
					"original_price"=>$standard[$i]['oPrice'],
					"inventory"=>$standard[$i]['inventory'],
					"sale_num"=>0,
					"status"=>$status,
					"time"=>date("Y-m-d H:i:s",time())
				);
			$standard_res = $product->table('tg_pro_standard')->add($standard_data);
			if($standard_res===false){
				$product->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			/*4、如果优惠活动不为空，那么继续存活动表*/
			$activity = $standard[$i]['activity'];
			if(!empty($activity)){
				$a_ids = explode(",",$activity);
				for($j=0;$j<count($a_ids);$j++){
					$activities = array(
						"standard_id"=>$standard_res,
						"discount_id"=>$a_ids[$j],
						"merchant_id"=>$merchant_id,
						"status"=>1,
						"time"=>date("Y-m-d H:i:s",time())
					);
					$activity_res = $product->table('tg_standard_discount')->add($activities);
					if($activity_res===false){
						$product->rollback();
						$this->ajaxReturn(array("result"=>0),'json');
					}
				}
			}
		}
		/*5、保存商品图片*/
		//处理轮播图片
		$focus_img = I("focusImg");
		for($m=0;$m<count($focus_img);$m++){
			$downLoadWxFocusImg = $this->getImgFromWx($focus_img[$m],'./Public/admin_upload/');
			if(!$downLoadWxFocusImg){
				$product->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			if($m==0){
				$status=1;
			}else{
				$status=0;
			}
			$focus_data[] = array(
						"pro_id"=>$product_res,
						"img_path"=>$downLoadWxFocusImg,
						"status"=>$status,
						"time"=>date("Y-m-d H:i:s"),
						"merchant_id"=>$merchant_id
					);
		}
		$focus_result = M('product_image')->addAll($focus_data);
		if($focus_result===false){
			$product->rollback();
			$this->ajaxReturn(array("result"=>0),'json');
		}
		//处理描述图片
		$describe_img = I("describeImg");
		for($n=0;$n<count($describe_img);$n++){
			$downLoadWxDescribeImg = $this->getImgFromWx($describe_img[$n],'./Public/admin_uploadInfo/');
			if(!$downLoadWxDescribeImg){
				$product->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			$descript_data[] = array(
						"pro_id"=>$product_res,
						"img_path"=>$downLoadWxDescribeImg,
						"status"=>1,
						"time"=>date("Y-m-d H:i:s")
					);
		}
		$describe_result = M('product_image_descript')->addAll($descript_data);
		if($describe_result===false){
			$product->rollback();
			$this->ajaxReturn(array("result"=>0),'json');
		}
		//都没有出错，提交事务，并返回；
		$product->commit();
		$this->ajaxReturn(array("result"=>1),'json');
		
	}
	
	public function getImgFromWx($serverId,$path){
        //获取access_token
        $access_token = A('WxApi')->getAccessToken();
        if(!$access_token){
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$serverId;
        $fileInfo = A('Merchant')->downloadWeixinFile($url);
        $content = $fileInfo['body'];//把下载下的内容写入到本地服务器文件里
        $filename = time().$serverId.'.jpg';//文件名
        if(!file_exists($path)){
            mkdir($path,0777,true);
			chmod($path,0777);
        }
        $data = file_put_contents($path.$filename,$content);
        if($data){
        	$url = "/Public".explode("./Public", $path.$filename)[1];
            return $url;
        }else{
        	return false;
        }
    }
	/**
	* 
	* 商家商品页面的三列选择分类处理
	* @access public
	* @return  返回操作结果
	*/
	public function sortDispose(){
		$sort_res = M('sort')->field('id,sort_name name,parent_id')->select();
		//为空或者出错都当成出错处理
		if(empty($sort_res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$sort = sort_merge($sort_res);
		$array = array(
				"result"=>1,
				"data"=>$sort
			);
		$this->ajaxReturn($array,'json');
	}
	/**
	* 
	* 获取管理员添加的活动列表
	* @access public
	* @return  返回操作结果
	*/
	public function getAllActivities(){
		$discount_res = M('discount')->where(array("status"=>1))->field('name,id')->select();
		if($discount_res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($discount_res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$discount_res
				);
			$this->ajaxReturn($array,'json');
		}
	}

	/**
	* 
	* 修改商品信息
	* @access public
	* @return  返回操作结果
	*/
	public function modifyProductInfo(){
		//获取商家ID
		$open_id = session("openId");
		if(empty($open_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$mid_res = M('merchant')->where(array("open_id"=>$open_id))->field('id')->find();
		if(empty($mid_res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$merchant_id = $mid_res['id'];
		//获取商品ID
		$pid = I('pid');
		if(empty($pid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		/*1、开启事务*/
		$modify = M();
		$modify->startTrans();
		/*2、修改商品表*/
		$pro_name = I('proName');
		if(empty($pro_name)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$pro = array(
				"name"=>$pro_name
			);
		//判断分类是否修改
		$sort = I('sort');
		if($sort!=0){
			$sort_array = explode(",",$sort);
			if(count($sort_array)!=3){
				$this->ajaxReturn(array("result"=>4),'json');
			}
			$pro['sort_id'] = $sort_array[2];
		}
		$pro_where['id'] = $pid;
		$pro_save_res = $modify->table('tg_product')->where($pro_where)->save($pro);
		if($pro_save_res===false){
			$modify->rollback();
			$this->ajaxReturn(array("result"=>0),'json');
		}
		/*2、修改规格表和活动表（包括规格包含删除和修改两部分）*/
		//（1）如果不为空，就先处理需要删除的规格
		$delete_standard = I('deleteStandard');
		if(!empty($delete_standard)){
			$delete_standard_where['id'] = array("IN",$delete_standard);
			$delete_standard_res = $modify->table('tg_pro_standard')->where($delete_standard_where)->save(array("status"=>-1));
			if($delete_standard_res===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			//删除对应的活动
			$activity_where['standard_id'] = array("IN",$delete_standard);
			$activity_search_res = $modify->table('tg_standard_discount')->where($activity_where)->field('id')->select();
			if($activity_search_res===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			if(!empty(array_filter($activity_search_res))){
				$delete_activity_res = $modify->table('tg_standard_discount')->where($activity_where)->save(array("status"=>0));
				if($delete_activity_res===false){
					$modify->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
			}
		}
		$standard = I('standard');
		for($i=0;$i<count($standard);$i++){
			if(!empty($standard[$i]['sid'])){
				//规格ID不为空，是修改的值
				$standard_data = array(
						"name"=>$standard[$i]['sName'],
						"inventory"=>$standard[$i]['inventory'],
						"sale_price"=>$standard[$i]['sPrice'],
						"original_price"=>$standard[$i]['oPrice'],
						"time"=>date("Y-m-d H:i:s",time())
					);
				$standard_where['id'] = $standard[$i]['sid'];
				$standard_res = $modify->table('tg_pro_standard')->where($standard_where)->save($standard_data);
				if($standard_res===false){
					$modify->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
				//判断该规格是否有参与的活动
				$discount_search_res = $modify->table('tg_standard_discount')->where(array("standard_id"=>$standard[$i]['sid'],"status"=>1))->field('discount_id')->select();
				//接收修改后的活动值
				$activity = $standard[$i]['activity'];
				if(empty(array_filter($discount_search_res))&&empty($activity)){
					print_r("continue");
					continue;
				}else if(!empty(array_filter($discount_search_res))&&empty($activity)){
					//之前不为空，传过来为空，直接删除
					$discount_res = $modify->table('tg_standard_discount')->where(array("standard_id"=>$standard[$i]['sid']))->save(array("status"=>0));
					if($discount_res===false){
						$modify->rollback();
						$this->ajaxReturn(array("result"=>0),'json');
					}
				}else if(empty(array_filter($discount_search_res))&&!empty($activity)){
					//原始活动表为空，传过来不为空直接存
					$a_ids = explode(",",$activity);
					for($j=0;$j<count($a_ids);$j++){
						$activities = array(
							"standard_id"=>$standard[$i]['sid'],
							"discount_id"=>$a_ids[$j],
							"merchant_id"=>$merchant_id,
							"status"=>1,
							"time"=>date("Y-m-d H:i:s",time())
						);
						$activity_res = $modify->table('tg_standard_discount')->add($activities);
						if($activity_res===false){
							$modify->rollback();
							$this->ajaxReturn(array("result"=>0),'json');
						}
					}
				}else{
					//如果没发生修改，直接跳入下一层循环
					$a_ids = explode(",",$activity);
					$one = array_column($discount_search_res,'discount_id');
					sort($one);
					sort($a_ids);
					if(!($one==$a_ids)){
						//发生了部分修改先删后存（有待优化）
						$discount_res = $modify->table('tg_standard_discount')->where(array("standard_id"=>$standard[$i]['sid']))->save(array("status"=>0));
						if($discount_res===false){
							$modify->rollback();
							$this->ajaxReturn(array("result"=>0),'json');
						}
						//存
						for($j=0;$j<count($a_ids);$j++){
							$activities = array(
								"standard_id"=>$standard[$i]['sid'],
								"discount_id"=>$a_ids[$j],
								"merchant_id"=>$merchant_id,
								"status"=>1,
								"time"=>date("Y-m-d H:i:s",time())
							);
							$activity_res = $modify->table('tg_standard_discount')->add($activities);
							if($activity_res===false){
								$modify->rollback();
								$this->ajaxReturn(array("result"=>0),'json');
							}
						}
					}
				}
			}else{
				//规格ID为空，说明是新存进来的数据
				$standard_data = array(
					"pro_id"=>$pid,
					"name"=>$standard[$i]['sName'],
					"sale_price"=>$standard[$i]['sPrice'],
					"original_price"=>$standard[$i]['oPrice'],
					"inventory"=>$standard[$i]['inventory'],
					"sale_num"=>0,
					"status"=>0,
					"time"=>date("Y-m-d H:i:s",time())
				);
				$standard_res = $modify->table('tg_pro_standard')->add($standard_data);
				if($standard_res===false){
					$modify->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
				//如果优惠活动不为空，那么存活动表
				$activity = $standard[$i]['activity'];
				if(!empty($activity)){
					$ids = explode(",",$activity);
					for($k=0;$k<count($ids);$k++){
						$activities = array(
							"standard_id"=>$standard_res,
							"discount_id"=>$ids[$k],
							"merchant_id"=>$merchant_id,
							"status"=>1,
							"time"=>date("Y-m-d H:i:s",time())
						);
						$activity_res = $modify->table('tg_standard_discount')->add($activities);
						if($activity_res===false){
							$modify->rollback();
							$this->ajaxReturn(array("result"=>0),'json');
						}
					}
				}
			}
		}
		//有可能把默认的规格删掉了，需要额外处理
		$default_where = array(
				"pro_id"=>$pid,
				"status"=>1
			);
		$default_res = $modify->table('tg_pro_standard')->where($default_where)->field('id')->find();
		if($default_res===false){
			$modify->rollback();
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(empty($default_res)){
			$result = $modify->table('tg_pro_standard')->where(array("pro_id"=>$pid))->field("id,time")->order('time')->select();
			if(empty(array_filter($result))||$result===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			$save_result = $modify->table('tg_pro_standard')->where(array("id"=>$result[0]['id']))->setField(array("status"=>1));
			if($save_result===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		/*3、处理轮播图片*/
		//处理删除原来的图片
		$focus_delete = I('focusDelete');
		if(!empty($focus_delete)){
			$focus_delete_where['id'] = array("IN",$focus_delete);
			$focus_delete_res = $modify->table('tg_product_image')->where($focus_delete_where)->save(array("status"=>-1));
			if($focus_delete_res===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		//添加新增图片
		$focus_img = I('focus');
		if(!empty($focus_img)){
			for($m=0;$m<count($focus_img);$m++){
				$downLoadWxFocusImg = $this->getImgFromWx($focus_img[$m],'./Public/admin_upload/');
				if(!$downLoadWxFocusImg){
					$modify->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
				$focus_data[] = array(
						"pro_id"=>$pid,
						"img_path"=>$downLoadWxFocusImg,
						"status"=>0,
						"time"=>date("Y-m-d H:i:s"),
						"merchant_id"=>$merchant_id
					);
			}
			$focus_res = $modify->table('tg_product_image')->addAll($focus_data);
			if($focus_res===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		//可能默认的轮播图片被删除，需要额外处理
		$default_focus_where = array(
				"pro_id"=>$pid,
				"status"=>1
			);
		$default_focus_res = $modify->table('tg_product_image')->where($default_focus_where)->field('id')->find();
		if($default_focus_res===false){
			$modify->rollback();
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(empty(array_filter($default_focus_res))){
			$result = $modify->table('tg_product_image')->where(array("pro_id"=>$pid))->field("id,time")->order('time')->select();
			if(empty(array_filter($result))||$result===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
			$save_result = $modify->table('tg_product_image')->where(array("id"=>$result[0]['id']))->setField(array("status"=>1));
			if($save_result===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		/*4、处理描述图片*/
		//处理删除原来的描述图片
		$describe_delete = I('describeDelte');
		if(!empty($describe_delete)){
			$describe_delete_where['id'] = array("IN",$describe_delete);
			$describe_delete_res = $modify->table('tg_product_image_descript')->where($describe_delete_where)->save(array("status"=>-1));
			if($describe_delete_res===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		//添加新增图片
		$describe_img = I('describe');
		if(!empty($describe_img)){
			for($n=0;$n<count($describe_img);$n++){
				$downLoadWxDescribeImg = $this->getImgFromWx($describe_img[$n],'./Public/admin_uploadInfo/');
				if(!$downLoadWxDescribeImg){
					$modify->rollback();
					$this->ajaxReturn(array("result"=>0),'json');
				}
				$descript_data[] = array(
						"pro_id"=>$pid,
						"img_path"=>$downLoadWxDescribeImg,
						"status"=>1,
						"time"=>date("Y-m-d H:i:s")
					);
			}
			$describe_res = $modify->table('tg_product_image_descript')->addAll($descript_data);
			if($describe_res===false){
				$modify->rollback();
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}
		//都没错误，提交事务，返回结果
		$modify->commit();
		$this->ajaxReturn(array("result"=>1),'json');
	}

	/**
	* 
	* 下架商品
	* @access public
	* @return  返回操作结果
	*/
	public function putProductAway(){
		$pid = I("id");
		if(empty($pid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$res = M('product')->where(array("id"=>$pid))->save(array("status"=>0));
		if(empty($res)){
			$this->ajaxReturn(array("result"=>0),'json');	
		}
		$this->ajaxReturn(array("result"=>1),'json');
	}
}