<?php
namespace Admin\Controller;
use Think\Controller;
class CommonController extends Controller {
	public function _initialize(){
  		$id=session("id");
      $cesf=M('admin_user');
          $str=session("str");
     	if(!isset($id)){
            redirect(U('User/login'));

        }else if($cesf->where(array("id"=>$id))->getField('string')!=$str){
          // $this->ajaxReturn("{'result':'1'}",'json');
          redirect(U('User/login'));
        }
	}
    public function index(){
    	echo "后台Common控制器index方法";
    	$this->display();
    }
}