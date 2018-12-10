<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends CommonController {
    public function index(){
    	$data = A('WxApi')->wxPayConfig();
		if(count(array_values($data))!=4||$data===false){
			redirect("../User/error");
		}else{
			$this->assign($data);
		}
    	$this->display();
    }
    //动态获取首页的轮播图
    public function getFocusOfIndex(){
    	$res = M('focus')->field('pro_id,img_path,index,time')->order('`index`,time desc')->limit(8)->select();
    	if(empty($res)){
    		//为空或者出错都当成出错
    		$this->ajaxReturn(array("result"=>0),'json');
    	}
    	$array = array(
    			"result"=>1,
    			"data"=>$res
    		);
    	$this->ajaxReturn($array,'json');
    }
    //动态获取分类模块
    public function sortMdouleOfIndex(){
        $res = M('sort_module')->field('name,sort_id,img_path,index,time')->order('`index`,time desc')->limit(4)->select();
        if(empty($res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$res
            );
        $this->ajaxReturn($array,'json');

    }
    //动态获取商品模块
    public function productModuleOfIndex(){
        $res = M('product_module')->field('pro_id,index,time,img_path')->order('`index`,time desc')->limit(4)->select();
        if(empty($res)){
             $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$res
            );
        $this->ajaxReturn($array,'json');
    }
    //动态获取标题模块商品
    public function titleModuleOfIndex(){
        $index = I('index');
        if($index==1){
            $num = 5;
        }else if($index==2){
            $num = 3;
        }
        $res = M('title_module')->alias('tm')->join('tg_title_module_product tmp on tmp.title_module_id=tm.id')->where(array("tm.index"=>$index))->field('tm.title,tm.first_sort_id,tmp.pro_id,tmp.img_path')->order('tmp.index,time desc')->limit($num)->select();
        if(empty($res)){
            $this->ajaxReturn(array("result"=>0),'json');
        }
        $array = array(
                "result"=>1,
                "data"=>$res
            );
        $this->ajaxReturn($array,'json');
    }
}