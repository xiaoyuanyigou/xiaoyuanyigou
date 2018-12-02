<?php
namespace Admin\Controller;
use Think\Controller;
class IndexPageController extends CommonController {
	public function pictrue(){
		$fileInfo = $_FILES['img'];
		//上传出错
		if($fileInfo['error']>0){
			$this->ajaxReturn(array("result"=>0,"msg"=>$fileInfo['error']),'json');
		}
		//支持的图片上传格式数组
		$allowExt=array('jepg','jpg','png');
		//取得文件的拓展名
		$ext=pathinfo($fileInfo['name'],PATHINFO_EXTENSION);
		if(!in_array($ext,$allowExt)){
			//非法图片类型
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//设置最大的文件大小（PHP的配置文件里还有需要配置最大上传大小）
		$maxsize=1024*1024*10;//10M
		if($fileInfo['size']>$maxsize){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		//判断文件是不是通过http POST上传的
		if(!is_uploaded_file($fileInfo['tmp_name'])){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		//检测是否为真实的图片类型
		if(!getimagesize($fileInfo['tmp_name'])){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		//图片存放的路径
		$uploadPath = './Public/index_images/';
		if(!file_exists($uploadPath)){
			mkdir($uploadPath,0777,true);
			chmod($uploadPath,0777);
		}
		$uniName=md5(uniqid(microtime(true),true)).'.'.$ext;//获得唯一文件名
		$destination=$uploadPath.$uniName;
		if(@!move_uploaded_file($fileInfo['tmp_name'],$destination)){
			$this->ajaxReturn(array("result"=>0,"msg"=>"移动临时文件出错！"),'json');
		}else{
			$return_url = "/Public".explode("./Public", $destination)[1];
			$this->ajaxReturn(array("result"=>1,"url"=>$return_url),'json');
		}
	}
	//设置轮播图的商品列表
	public function productList(){
		$where['pi.status'] = 1;
		$where['p.status'] = 1;
		$res = M('product')->alias('p')->join(array('tg_product_image pi on pi.pro_id=p.id','tg_sort s on s.id=p.sort_id','tg_merchant m on m.id=p.merchant_id'))->where($where)->field('p.id pid,p.name,m.shop_name,s.sort_name,pi.img_path')->select();
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

	//向轮播图表插入信息
	public function setFocusHandle(){
		$p_id = I("pid");
		$index = I("index");
		$path = I("path");
		if(empty($p_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($index)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(empty($path)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		$res = M('focus')->where(array("pro_id"=>$p_id))->find();
		if($res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$data = array(
				"index"=>$index,
				"pro_id"=>$p_id,
				"img_path"=>$path,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$result = M("focus")->add($data);
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}

	//轮播图列表
	public function focusListHandel(){
		$where['p.status'] = 1;
		$where['m.status'] = 1;
		$res = M('focus')->alias('f')->join(array('tg_product p on p.id=f.pro_id','tg_merchant m on m.id=p.merchant_id','tg_sort s on s.id=p.sort_id'))->where($where)->field('f.id,f.img_path,f.index,f.time,p.name,m.shop_name,s.sort_name')->order('f.index,f.time desc')->select();
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
	//删除某个轮播图
	public function deleteFocus(){
		$f_id = I('fid');
		if(empty($f_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$res = M('focus')->where(array("id=".$f_id))->delete();
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>1),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

	//设置分类模块，列出所有二级分类
	public function secondSortList(){
		$where['s1.status'] = 1;
		$where['s2.status'] = 1;
		$where['s2.parent_id'] = 0;
		$res = M('sort')->alias('s1')->join('tg_sort s2 on s1.parent_id=s2.id')->field("s1.id,s1.sort_name,s2.sort_name p_sort_name")->where($where)->select();
		if($res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(empty($res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$array = array(
				"result"=>1,
				"data"=>$res
			);
		$this->ajaxReturn($array,'json');
	}
	//向分类模块表插入信息
	public function setSortModuleHandle(){
		$s_id = I("sid");
		$index = I("index");
		$path = I("path");
		$name = I("name");
		if(empty($s_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($index)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(empty($name)){
			$this->ajaxReturn(array("result"=>6),'json');
		}
		if(empty($path)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		//判断是否是四个汉字
		$length = mb_strlen($name, 'UTF-8');
		if($length>4){
			$this->ajaxReturn(array("result"=>7),'json');
		}
		$res = M('sort_module')->where(array("sort_id"=>$s_id))->find();
		if($res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$data = array(
				"index"=>$index,
				"sort_id"=>$s_id,
				"img_path"=>$path,
				"name"=>$name,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$result = M("sort_module")->add($data);
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}
	//已设置的分类模块列表
	public function sortModuleListHandel(){
		$res = M('sort_module')->alias('sm')->join('tg_sort s on s.id=sm.sort_id')->field('sm.id,sm.index,sm.name module_name,sm.img_path,s.sort_name')->order('sm.index,sm.time desc')->select();
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
	//删除某个分类模块
	public function deleteSortModule(){
		$sid = I('sid');
		if(empty($sid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//查看数据库表里是否有4个，如果刚好四个，不让删除
		$result = M('sort_module')->count();
		if(empty($result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if($result<5){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		$res = M('sort_module')->where(array("id"=>$sid))->delete();
		if(empty($res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}

	//向商品模块表插入记录
	public function productModuleHandel(){
		$p_id = I("pid");
		$index = I("index");
		$path = I("path");
		if(empty($p_id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($index)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(empty($path)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		$res = M('product_module')->where(array("pro_id"=>$p_id))->find();
		if($res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$data = array(
				"index"=>$index,
				"pro_id"=>$p_id,
				"img_path"=>$path,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$result = M("product_module")->add($data);
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}
	//删除商品模块
	public function prductModuleDelete(){
		$pid = I('pid');
		if(empty($pid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//查看数据库表里是否有4个，如果刚好四个，不让删除
		$result = M('product_module')->count();
		if(empty($result)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if($result<6){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		$res = M('product_module')->where(array("id"=>$pid))->delete();
		if(empty($res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}
	//首页商品模块的商品列表
	public function productModuleListHandle(){
		$where['p.status'] = 1;
		$where['m.status'] = 1;
		$res = M('product_module')->alias('pm')->join(array('tg_product p on p.id=pm.pro_id','tg_merchant m on m.id=p.merchant_id','tg_sort s on s.id=p.sort_id'))->where($where)->field('pm.id,pm.img_path,pm.index,pm.time,p.name,m.shop_name,s.sort_name')->order('pm.index,pm.time desc')->select();
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
	//首页标题模块的列表
	public function titleModuleListHandel(){
		$res = M('title_module')->alias('tm')->join('tg_sort s on tm.first_sort_id=s.id')->field('tm.id,tm.title,s.sort_name,tm.index,tm.first_sort_id')->order('tm.`index`')->select();
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
	//根据标题模块的ID获取对应信息
	public function getInfoByTitleMdouleId(){
		$tmid = I('tmid');
		if(empty($tmid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$res = M('title_module')->where(array("id"=>$tmid))->find();
		if(empty($res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		$array = array(
					"result"=>1,
					"data"=>$res
				);
		$this->ajaxReturn($array,'json');
	}
	//获取所有一级分类
	public function getFirstSort(){
		$where['parent_id'] = 0;
		$sortResult = M('sort')->where($where)->field('id,sort_name')->select();
		if($sortResult===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(!empty($sortResult)){
			$array = array(
					"result"=>1,
					"data"=>$sortResult
				);
			$this->ajaxReturn($array,'json');
		}else{
			$this->ajaxReturn(array("result"=>3),'json');
		}
	}
	//修改标题模块的信息
	public function modifyTitleModuleHandle(){
		$fid = I('fid');
		$tmid = I('tmid');
		$title = I('title');
		if(empty($tmid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($title)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		$where['id'] = $tmid;
		$data = array(
				"title"=>$title,
				"first_sort_id"=>$fid
			);
		$res = M('title_module')->where($where)->save($data);
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>1),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	//取出一级分类下的所有商品
	public function getAllProductByFirstSortId(){
		$sid = I('sid');
		if(empty($sid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		//取出该一级分类下的所有三级分类
		$oneResult = M('sort')->alias('s1')->join(array('tg_sort s2 on s2.parent_id=s1.id','tg_sort s3 on s3.parent_id=s2.id'))->where(array("s1.id"=>$sid))->field('s3.id')->select();
		if($oneResult===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($oneResult)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$ids = array();
			//把二维数组转成一维数组
			for($i=0;$i<count($oneResult);$i++){
				$ids[$i] = $oneResult[$i]['id'];
			}
			$where['pi.status'] = 1;
			$where['p.status'] = 1;//上架的商品
			$where['p.sort_id'] =array("in",$ids);
			$res = M('product')->alias('p')->join(array('tg_product_image pi on pi.pro_id=p.id','tg_sort s on s.id=p.sort_id','tg_merchant m on m.id=p.merchant_id'))->where($where)->field('p.id pid,p.name,m.shop_name,s.sort_name,pi.img_path')->select();
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
	}
	public function setTitleModuleHandle(){
		$pid = I('pid');
		$index = I('index');
		$path = I('path');
		$tmid = I('tmid');
		if(empty($pid)||empty($tmid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(empty($index)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		if(empty($path)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		//判断商品是否已经在商品模块中
		$result = M('title_module_product')->where(array("pro_id"=>$pid))->find();
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if(!empty($result)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		$data = array(
				"title_module_id"=>$tmid,
				"index"=>$index,
				"img_path"=>$path,
				"pro_id"=>$pid,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$res = M('title_module_product')->add($data);
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>1),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
	//已经在首页的标题模块商品列表
	public function titleModuleListHandle(){
		$tmid = I('tmid');
		if(empty($tmid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['tmp.title_module_id'] = $tmid;
		$where['p.status'] = 1;
		$where['m.status'] = 1;
		$res = M('title_module_product')->alias('tmp')->join(array('tg_product p on p.id=tmp.pro_id','tg_merchant m on m.id=p.merchant_id','tg_sort s on s.id=p.sort_id'))->where($where)->field('tmp.id,tmp.img_path,tmp.index,tmp.time,p.name,m.shop_name,s.sort_name')->order('tmp.index,tmp.time desc')->select();
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
	//删除标题模块的商品
	public function titleModuleDelete(){
		$tmpid = I('tmpid');
		$tmid = I('tmid');
		if(empty($tmpid)||empty($tmid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$result = M('title_module_product')->where(array("title_module_id"=>$tmid))->count();
		if($result===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}
		if($tmid==1){
			$num = 6;
		}else if($tmid==2){
			$num = 4;
		}
		if($result<$num){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		$where['id'] = $tmpid;
		$res = M('title_module_product')->where($where)->delete();
		if(!empty($res)){
			$this->ajaxReturn(array("result"=>1),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}
}