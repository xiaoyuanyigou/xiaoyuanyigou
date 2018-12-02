<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends CommonController {
     public function index(){
    	 $deliver_count=M('Order')->where('status=1')->count();
         $deal_count=M('Order')->where('status=4')->count();
         $count=$deliver_count+$deal_count;
         $this->assign('deliver_count',$deliver_count);
         $this->assign('deal_count',$deal_count);
         $this->assign('count',$count);
         //echo  session("username");
        $this->show();
    }
}