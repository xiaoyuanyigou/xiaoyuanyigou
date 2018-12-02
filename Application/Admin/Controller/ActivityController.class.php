<?php
namespace Admin\Controller;
use Think\Controller;
class ActivityController extends Controller {
	public function activityListHandel(){
		$a_res = M('discount')->where(array("status"=>1))->field('id,name,condition,condition_explain,content_explain,content')->select();
		if($a_res===false){
			$this->ajaxReturn(array("result"=>0),'json');
		}else if(empty($a_res)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$a_res
				);
			$this->ajaxReturn($array,'json');
		}
	}
	public function activityDetail(){
		$id = I('aid');
		if(empty($id)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$res = M('discount')->where(array("id"=>$id))->field('name,condition,condition_explain,content_explain,content')->find();
		if(empty($res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$array = array(
					"result"=>1,
					"data"=>$res
				);
			$this->ajaxReturn($array,'json');
		}
	}
	public function activityModifyHandel(){
		$aid = I('aid');
		$name  = I('name');
		$content = I('content');
		$condition = I('condition');
		$condition_exp = I('conditionExp');
		$content_exp = I('contentExp');
		if(empty($name)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else if(empty($content)){
			$this->ajaxReturn(array("result"=>4),'json');
		}else if(empty($condition)){
			$this->ajaxReturn(array("result"=>5),'json');
		}else if(empty($condition_exp)){
			$this->ajaxReturn(array("result"=>6),'json');
		}else if(empty($content_exp)){
			$this->ajaxReturn(array("result"=>7),'json');
		}
		//新增活动
		if(empty($aid)){
			$data = array(
					"name"=>$name,
					"condition"=>$condition,
					"content"=>$content,
					"status"=>1,
					"time"=>date("Y-m-d H:i:s",time()),
					"condition_explain"=>$condition_exp,
					"content_explain"=>$content_exp
				);
			$res = M('discount')->add($data);
			if(empty($res)){
				$this->ajaxReturn(array("result"=>0),'json');
			}else{
				$this->ajaxReturn(array("result"=>1),'json');
			}
		//修改活动
		}else{
			$save = array(
					"name"=>$name,
					"condition"=>$condition,
					"content"=>$content,
					"time"=>date("Y-m-d H:i:s",time()),
					"condition_explain"=>$condition_exp,
					"content_explain"=>$content_exp
				);
			$where['id'] = $aid;
			$res = M('discount')->where($where)->save($save);
			if($res===false){
				$this->ajaxReturn(array("result"=>0),'json');
			}else if($res==0){
				$this->ajaxReturn(array("result"=>8),'json');
			}else{
				$this->ajaxReturn(array("result"=>1),'json');
			}
		}
	}
	public function deleteActivity(){
		$aid = I("aid");
		if(empty($aid)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$res = M('discount')->where(array('id'=>$aid))->save(array("status"=>-1));
		if(empty($res)){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}
}