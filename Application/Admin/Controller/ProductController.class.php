<?php
namespace Admin\Controller;
use Think\Controller;

class ProductController extends CommonController {
    public function index(){	
    	$this->display('commercialList');
    }


/*-------------------------------------------------------------------------------------------*/	 

    /**
     * 查看商品列表
     * @access public 
	 * @param 不需要参数
	 * @return json 返回所有商品的json数据
	 *   product表：名称
	 *   standard表：规格名，现价，销量，库存	（默认的）
	 *   sort表:	分类名
	 *   image表：图片路径
     */
    public function productList(){
    	$merchantId = session('merId');	//从session中获取商家ID
    	//$merchantId = 1;

    	$where0['status'] = 1;
    	$where0['merchant_id'] = $merchantId;
    	$count = M('product')
    	->where($where0)
    	->field('count(*) as total')
    	->find();
    	
    	if($count['total']<1)
    	{//查询商品结果为空，total小于1
    		$arr = array(
    			'result' => '1',
    			'count' => '0'
    			);
    		$this->ajaxReturn(json_encode($arr),'json');die;
    	}
    	else{
    		$where['product.merchant_id'] = $merchantId; //选出对应商家的商品
    		$where['product.status'] =1;	//筛选出有效的商品
    		$where['image.status'] = 1;		//选出默认图片
	    	$where['standard.status'] = 1;	//选出默认规格
	    	$product= M('product')
	    	->alias('product')
	    	->join('tg_pro_standard standard On standard.pro_id = product.id')
	    	->join('tg_product_image image On image.pro_id = product.id')
	    	->join('tg_sort sort On sort.id = product.sort_id')
	    	->field('
	    		product.id as pro_id,
	    		product.name as pro_name,
				sort.sort_name as sort_name,
				image.img_path as image_url,

				standard.name as standard_name,
				standard.sale_price as standard_price,
				standard.sale_num as standard_sales,
				standard.inventory as standard_inventory
	    		')
	    	->where($where)
	    	->select();

	    	if(!is_array($product)){
	    	//查询出错，使用is_array来判断
	    		$this->ajaxReturn('{"result":"0"}','json');die;
	    	
	    	}else{
	    		$arr = array(
	    			'result' => '1',
	    			'count'=>$count['total'],
	    			'data' => $product
	    			);
	    		$this->ajaxReturn(json_encode($arr),'json');die;
	    	}
    	}//end else
    }

/*-------------------------------------------------------------------------------------------*/	 

     /**
     * 查看单个商品信息
	 * @access public 
	 * @param $proId 商品的ID
	 * @return json 返回单个商品的json数据
	 *   product表：名称 ，推荐到首页
	 *   standard表：规格名，现价，销量，库存	（默认的）
	 *   sort表:	各级 分类名
	 *   image表：图片路径
	 *   Idescript 表：图片路径     
	 */
	public function checkProduct(){
		$proId = I('proId');	
		if(!isset($proId)){		
			$this->ajaxReturn('{"result":"2"}','json');die;		//没有传值过来
		}

		//查询id为proId，并且状态为1 的商品是否存在
		$where0['id'] = $proId;
		$where0['status'] = 1;
		$product = M('product')->where($where0)->find();		

		if($product===false){
			$this->ajaxReturn('{"result":"0"}','json');die;		//查询错误
		}else if($product===null){
	 		$this->ajaxReturn('{"result":"3"}','json');die;		//查询结果为空
		}else{
			/*
				查询商品基本信息（联表sort）
			 */
			$where1['product.id'] = $proId;
	 		$proInfo = M('Product')
	 		->where($where1)
	 		->alias('product')
	 		->join('tg_sort s1 On s1.id=product.sort_id')		
	 		->join('tg_sort s2 On s2.id=s1.parent_id')
	 		->join('tg_sort s3 On s3.id=s2.parent_id')		
	 		->field('
	 				product.id as pro_id,
		 			name as pro_name,	
		 			put_focus as focus,	
		 			s1.sort_name as sort_name1,
		 			s2.sort_name as sort_name2,
		 			s3.sort_name as sort_name3')
	 		->find();
			/*
			  获取商品各个规格的信息
			 
			 */
			$where2['status'] = array('gt',-1);
			$where2['pro_id'] = $proId;
	 		$standards = M ('pro_standard')
	 		->where($where2)
	 		->field('
	 				id as standard_id,
					name as standard_name,
					status as standard_status,
					original_price,
					sale_price,
					inventory,
					sale_num
	 				')
	 		->order('standard_status desc')
	 		->select();
	 		/*
	 		   获取商品的全部图片
	 		 
	 		 */
	 		$where3['status'] = array('gt',-1);
	 		$where3['pro_id'] = $proId;
	 		$images = M ('product_image')
	 		->where($where3)
	 		->field('
	 				id as img_id,
					img_path,
					status
	 				')
	 		->order('status desc')
	 		->select();
	 		/*
	 		 	获取商品的描述图片
	 		   
	 		 */
	 		$where4['pro_id'] = $proId;
	 		$where4['status'] = 1;
	 		$descript = M('product_image_descript')
	 		->where($where4)
	 		->field('
					img_path
	 			')
	 		->order('id asc')
	 		->select();

		 	if($proInfo===false||!is_array($standards)||!is_array($images)||!is_array($descript)){
		 		$this->ajaxReturn('{"result":"0"}','json');die;		//查询出错
		 	}else{
		 		/*
		 		  将各部分信息拼装成数组data

		 		 */
		 		$data = array(
			    		$proInfo,
			    		$standards,
			    		$images,
			    		$descript
			    		);
				$arr = array(
						"result"=>1,
						"data"=>$data
					);
				$this->ajaxReturn(json_encode($arr),'json');
		 	}//end else
		}//end else	
	}


/*-------------------------------------------------------------------------------------------*/	 
 	
 	/**
 	 * 修改商品信息
 	 * @access public 
	 * @param $product 商品基本信息	 $updateStandard 更新规格	
	 *        $deleteStandard 删除规格	$addStandard 新增规格
	 * @return json 返回修改是否成功
 	 */ 	
 	public function alertProduct(){
 		$product = I('product');		//0 :name, 1:id , 2:sort_id , 3:put_focus

 		$updateStandard = I('updateStandard');
 		$addStandard = I ('proStandard');
 		$deleteStandard = I('deleteStandard');

 		$deleteImage = I('deleteImage');	
 		$defaultImage = I('defaultImage');
 		
 		if(empty($product)||empty($defaultImage)||$product[2]<0){
 		//商品信息不完整
 			$this->ajaxReturn('{"result":"2"}','json');die;
 		}

 		/**
    	 *	add操作：
 		 * 	  standard表：和添加商品信息的代码基本一致
    	 *  	image表：把id一致的记录，status置为0.(非默认图片)
    	 */
    	if(!empty($addStandard)){
    		$countStandard = sizeof($addStandard[0]);	
	    	$standard = array();	
	    	for($i=0;$i<$countStandard;$i++){
	    		$standard[$i]=array(
	    			"pro_id" => $product[1],		//上面添加好的proID，填在此处
	    			"name" => $addStandard[0][$i],
	    			"original_price" => $addStandard[1][$i],
	    			"sale_price" => $addStandard[2][$i],
	    			"inventory" => $addStandard[3][$i],
	    			"time" =>  date('Y-m_d H:i:s'),
	    			"status" => 0
	    			);
	    	}
	    	$resultS = M('pro_standard')->addAll($standard);
    	}

    	//新增图片。
    	$whereImage['pro_id'] = $product[1];
    	$whereImage['status'] = -3;
    	$updateImage['status'] = 0;

    	$resultI = M('product_image')
    	->where($whereImage)
    	->save($updateImage);
 		/**
 		 *	delete操作：
 		 * 		standard，image表：将ID的那条记录status设置为-1
 		 */
 		if(!empty($deleteStandard)){
 			for($i=0;$i<count($deleteStandard);$i++){
 				$where_1['id'] = $deleteStandard[$i];
 				$update_1['status'] = -1;

 				M('pro_standard')
 				->where($where_1)
 				->save($update_1);
 			}//end for
 		}//end if

 		if(!empty($deleteImage)){
 			for($i=0;$i<count($deleteImage);$i++){
 				$where_2['id'] = $deleteImage[$i];
 				$update_2['status'] = -1;

 				M('product_image')
 				->where($where_2)
 				->save($update_2);
 			}//end for
 		}//end if

 		/**
 		 * update操作：
 		 * 	  product 表：proName，put_focus，sort_id
 		 *    standard表：（循环更新）：name，inventory，original_price，sale_price
 		 *    image 表 ：status 设置为1. （修改默认图片）	
 		 */
 		$where0['id'] = $product[1];
 		$update0['name'] = $product[0];
 		if($product[2]!=0)
			$update0['sort_id'] = $product[2];	//sort_id 有变化
		$update0['put_focus'] =  $product[3]-1;

		$standardUpdate = M('product')
		->where($where0)
		->save($update0);

		if(!empty($updateStandard)){
			//只有更新的规格数组不是空的时候执行
			for($i=0;$i<count($updateStandard);$i++){
			//更新的规格，每一个循环一次，并update数据库
				$update1['name'] = $updateStandard[1][$i];
				$update1['inventory'] = $updateStandard[2][$i];
				$update1['original_price'] = $updateStandard[3][$i];
				$update1['sale_price'] = $updateStandard[4][$i];

				$where1['id'] = $updateStandard[0][$i];

				M('pro_standard')
				->where($where1)
				->save($update1);
				}//end for
		}//end if

		$update2['status'] = 0;
		$where2['status'] = 1;
		$where2['pro_id'] =	$product[1]; 		//proId 在页面中是在product数组中

		M('product_image')
		->where($where2)
		->save($update2);			//将原来的status为1 的改为0（pro_id)

		$update3['status'] = 1;
		$where3['id'] = $defaultImage;
		M('product_image')
		->where($where3)
		->save($update3);			//将页面传过来的默认图片id，所在记录status设为1

		if(false){
    	//更新数据的时候，返回值是受影响的记录数。（如果没有变化返回值也是0）
    		$this->ajaxReturn('{"result":"0"}','json');die;
    	}
    	else{
    		$this->ajaxReturn('{"result":"1"}','json');
    	}
 	}


/*-------------------------------------------------------------------------------------------*/	 

    /**
   	 *
     * 添加商品
     * @access public 
	 * @param $proName 商品名称	$proStandard 商品规格信息数组	$proSort 商品分类
	 * @return json 返回单个商品的json数据
	 *        
     */
    public function addProduct(){
    	$merchantId = session('merId');	//从session中获取商家ID
    	$adminId =  session ('id');
    	
    	//$merchantId = 1;
    	//$adminId = 1;


    	$proName = I('proName');
    	$sortId = I('sortId');	
    	$focus = I('focus');	//避免empty的误判，前端的传值加了1		
    	$proStandard = I('proStandard');

    	

 		//empty函数会认为0，也是一个空	,,负值也会认为是empty
    	if(empty($proName)||empty($proStandard)||empty($sortId)||empty($focus)||$sortId<0){	
    	//proStandard是一个数组，如果没有传过来，认为是一个空的字符串
    		$this->ajaxReturn('{"result":"2"}','json');die;
    	}

    	$whereImage['status'] = -2;
    	$whereImage['admin_user_id'] =$adminId;
    	$whereImage['merchant_id'] = $merchantId;
    	$imagesNum = M('product_image')
    	->where($whereImage)					
    	->find();

    	if($imagesNum<1){
    		$this->ajaxReturn('{"result":"3"}','json');die;		//图片没有上传
    	}

    	/*
    	  添加product表:proName , sortId
    	  
    	 */
    	$product = array(
    			"sort_id" => $sortId,
    			"name" => $proName,
    			"focus" => $focus-1,	
    			"merchant_id" => $merchantId,
    			"time" => date('Y-m_d H:i:s'),
    			"status" => 1	//添加商品，状态就设为1
    		);
    	$resultPro = M('product')->add($product);

    	/*
    	  添加image表：

    	 */
    	

    	$whereImage0['merchant_id'] =$merchantId;
    	$whereImage0['admin_user_id'] = $adminId;
    	$whereImage0['status'] = -2;

    	$updateImage0['status'] = 0;
    	$updateImage0['pro_id'] = $resultPro;

    	$updateImage1['status'] = 1;

	
		$imageDefault = M('product_image')
    	->where($whereImage0)
    	->min('id');			//获取status为-2 的ID最小的记录
  
		$resultI = M('product_image')
    	->where($whereImage0)
    	->save($updateImage0);	//将所有status为-2 的变为0。

    	
    	$whereImage1['id'] =$imageDefault;
    	$resultI = M('product_image')
    	->where($whereImage1)
    	->save($updateImage1);	//将刚才找到的id最小的status 的变为1。



    	/*
    	  添加standard表
    	  			
    	 */
    	if(!empty($proStandard)){
	    	$countStandard = sizeof($proStandard[0]);	//此处写二维数组的第二维。才是规格的数目
	    	$standard = array();	//用来存储批量添加的standard数组。
	    	for($i=0;$i<$countStandard;$i++){
	    	//循环赋值添加数据的数组，第一维是各个规格，第二维规格信息
	    		if(0==$i)
	    			$default = 1;
	    		else
	    			$default = 0;
	    		$standard[$i]=array(
	    			"pro_id" => $resultPro,		//上面添加好的proID，填在此处
	    			"name" => $proStandard[0][$i],
	    			"inventory" => $proStandard[1][$i],
	    			"original_price" => $proStandard[2][$i],
	    			"sale_price" => $proStandard[3][$i],
	    			"time" =>  date('Y-m_d H:i:s'),
	    			"status" => $default
	    			);
	    	}
	    	M('pro_standard')->addAll($standard);
    	}
    	if(!$resultPro){
    	//插入数据失败
    		$this->ajaxReturn('{"result":"0"}','json');die;
    	}
    	else{
    		$arr = array(
    			"result" => '1',
    			"data" => $resultPro		
    		); 		
	    	$this->ajaxReturn(json_encode($arr),'json');	//返回值有新增商品id
    	}//end else
    }


/*-------------------------------------------------------------------------------------------*/	 

    /**
     * 下架商品		将ID对应的记录status设置为0 
     * @access public 
	 * @param $proId 商品id	
	 * @return json 返回成功/失败  
     */
    public function deleteProduct(){
    	$proId = I('proId');

    	if(empty($proId)){
    		$this->ajaxReturn('{"result":"2"}','json');die;
    	}//end if
    	else{	
    		$where['id'] = $proId;
    		$update['status'] = 0;

    		$result = M('product')
    	    ->where($where)
    	    ->save($update);
    	}//end else

    	if(!$result){
    		$this->ajaxReturn('{"result":"0"}','json');die;
    	}//end if
    	else{
    		$this->ajaxReturn('{"result":"1"}','json');die;
    	}//end else
    }// end deleteProduct
 

/*-------------------------------------------------------------------------------------------*/	
 	/**
 	 *
 	 * 删除无效图片记录
 	 * 
 	 *         在有上传图片操作的页面加载时 删除image表中status为-2 ,-3的记录
 	 */
 	public function deleteImages(){
 		//从session中获取管理员的id,商家id，加入到where条件。
 		$adminId = session('id');
 		$merchantId = session('merId');
 		//
 		$where['status'] = -2;
 		$where['admin_user_id'] = $adminId;
 		$where['merchant_id'] = $merchantId ;
 		//$where['merchant_id'] = merchantId;
 		//$where['admin_user_id'] = $adminId;
 		M('product_image')->where($where)->delete();
 	}
 	
 	public function deleteImagesInfo(){
 		$adminId = session('id');
 		$merchantId = session('merId');
 		//
 		$where['status'] = -3;
 		$where['admin_user_id'] = $adminId;
 		$where['merchant_id'] = $merchantId;

 		M('product_image')->where($where)->delete();
 	}
 	/**
 	 * 更新（删除）描述图片
 	 */
 	public function alertImagesInfo(){
 		$deleteImage = I('deleteImage');
 
 		if(!empty($deleteImage)){
 			for($i=0;$i<count($deleteImage);$i++){
 				$where['id'] = $deleteImage[$i];
 				$update['status'] = -1;

		 		M('product_image_descript')
		 		->where($where)
		 		->save($update);
 			}//end for	
 			$this->ajaxReturn('{"result":"1"}','json');die;
 		}//end if 
 	}
/*-------------------------------------------------------------------------------------------*/	 
 	/**
 	 *
 	 * 上传图片到服务器文件夹，
 	 * @param  $uid: 商品的Id
 	 * @return  无
 	 * 	如果uid有值，就把字段pro_id填好
 	 * 		没有值，就查询product表，找到下一个商品应该出现的id，填入pro_id
 	 *  
 	 */
 	public function uploadImages(){
   		$uploadUrl = upload('./Public/upload_tmp/','./Public/admin_upload/');
 		$uid = I('uid');

 		//从session中获取管理员的id,商家id，加入到where条件。
 		$adminId = session('id');
 		$merchantId = session('merId');
 		//
 		//
 		//$merchantId = 1;
 		//$adminId = 1;
 		
 		if($uid=='null'){				//zhelizhuyiyixia **************
 		//处理添加商品时，添加图片
 			$proId = M('product')->max('id');	//获取当前最大的id	，写入字段pro_id ++
 			$image = array(
 				"img_path" => $uploadUrl,
 				"status" => -2,
 				"pro_id" => $proId+1,
 				"time" =>  date('Y-m_d H:i:s'),
 				"merchant_id" => $merchantId,
 				"admin_user_id" => $adminId
 			);
 		}//end $image
 		else {
 		//处理修改商品时，添加图片
 			$image = array(
 				"img_path" => $uploadUrl,
 				"status" => -3,
 				"pro_id" => $uid,
 				"time" =>  date('Y-m_d H:i:s'),
 				"merchant_id" => $merchantId,
 				"admin_user_id" => $adminId
 			);
 		}//end else
 		$resullt = M('product_image')->add($image);
 	}//end uploadImages`

 	/**
 	 * 上传商品描述图片
 	 * @param  $uid: 商品的Id
 	 * @return  无
 	 */
 	public function uploadImagesInfo(){
   		$uploadUrl = upload('./Public/upload_tmp/','./Public/admin_uploadInfo/');
 		$uid = I('uid');

 		if($uid=='null'){
 				//这里处理什么？？？？？
 		}
 		else {
 		//处理修改商品时，添加图片
 			$image = array(
 				"img_path" => $uploadUrl,
 				"status" => 1,
 				"pro_id" => $uid,
 				"time" =>  date('Y-m_d H:i:s')
 			);
			$resullt = M('product_image_descript')->add($image);
 		}//end else
 	
 	}//end uploadImages`


/*-------------------------------------------------------------------------------------------*/	 

    /**
     *	查看商品的描述图片
     * 	@access public 
	 *  @param $proId 商品的ID
	 *  @return json 返回单个商品的json数据
     */
    public function checkImages(){
    	$proId = I('proId');	//从前端获取的ID值
		if(!isset($proId)){		
			$this->ajaxReturn("{'result':'2'}",'json');		//没有传值过来
		}else{
			$where['pro_id'] = $proId;
			$where['status'] = array('gt',-1);
			$images = M('product_image_descript')
			->where($where)
			->field('
					id as img_id,
					img_path
				')
			->select();
		}//end else
		if(!is_array($images)){
			$this->ajaxReturn('{"result":"0"}','json');die;
	    	
	    }
	    else{
	    		$arr = array(
	    			'result' => '1',
	    			'data' => $images
	    			);
	    		$this->ajaxReturn(json_encode($arr),'json');die;
	    }//end else			
    }

/*-------------------------------------------------------------------------------------------*/	 
}//end controller


