<?php
namespace Admin\Controller;
use Think\Controller;
class SortController extends CommonController {
    public function index(){
    	echo "后台Sort控制器index方法";

    }
    public function sort(){
    	$sortResult = M('sort')->select();
    	$array = sort_merge($sortResult);
    	$this->assign("list",$array);
    	$this->display();
	}

	/**
	* 修改分类名称
	* 
	* @access public 
	* @param $sortId 分类ID
	* @param $name 需要修改成的名称
	* @return json 返回修改结果
	*/
	public function alterSort(){
		$sortId = trim(I('sortId'));
		$name = trim(I('alterName'));
		if(empty($sortId)||empty($name)){
			$this->ajaxReturn("{'result':'2'}",'json');
		}
		$where['id'] = $sortId;
		$update = array(
				"sort_name"=>$name,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$fixResult = M('sort')->where($where)->save($update);
		if(!$fixResult){
			$this->ajaxReturn("{'result':'0'}",'json');
		}else{
			$this->ajaxReturn("{'result':'1'}",'json');
		}
	}

	/**
	* 获取所有一级分类
	* 
	* @access public 
	* @return json 返回所有分类的json数据
	*/
	public function getFirstSort(){
		$where['parent_id'] = 0;
		$sortResult = M('sort')->where($where)->field('id,sort_name')->select();
		if(empty($sortResult)){
			$this->ajaxReturn("{'result':'3'}",'json');
		}else if(is_array($sortResult)&&!empty($sortResult)){
			$array = array(
					"result"=>1,
					"data"=>$sortResult
				);
			$this->ajaxReturn(json_encode($array),'json');
		}else{
			$this->ajaxReturn("{'result':'0'}",'json');
		}
	}

	/**
	* 获取所有子分类
	* 
	* @access public 
	* @param $parentId 分类ID
	* @return json 返回所有分类的json数据
	*/
	public function getChildrenSort(){
		$pid = I('parentId');
		if(empty($pid)&&$pid!=0){
			$this->ajaxReturn("{'result':'2'}",'json');
		}
		if($pid==-1){
			$this->ajaxReturn("{'result':'1','data':'null'}",'json');
		}
		$where['parent_id'] = $pid;
		$sortResult = M('sort')->where($where)->field('id,sort_name')->select();
		if(is_array($sortResult)){
			$array = array(
				"result"=>1,
				"data"=>$sortResult
			);
			$this->ajaxReturn(json_encode($array),'json');
		}else{
			$this->ajaxReturn("{'result':'0'}",'json');
		}
	}

	/**
	* 添加分类
	* 
	* @access public 
	* @param $sortName 分类名称
	* @param $parentId 分类ID
	* @return json 返回所有分类的json数据
	*/
	public function addSort(){
		$parentId = I('parentId');
		$sortName = I('sortName');
		$recommend = I("recommend");
		if($parentId==""||$sortName==""){
			$this->ajaxReturn("{'result':'2'}",'json');
		}
		if($parentId==-1){
			$this->ajaxReturn("{'result':'3'}",'json');
		}
		$data = array(
				"sort_name"=>$sortName,
				"parent_id"=>$parentId,
				"recommend"=>$recommend,
				"status"=>1,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$result = M('sort')->add($data);
		if(!$result){
			$this->ajaxReturn("{'result':'0'}",'json');
		}
		$this->ajaxReturn("{'result':'1'}",'json');
	}
}