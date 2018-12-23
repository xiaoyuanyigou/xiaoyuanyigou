<?php
namespace Home\Controller;

use Think\Controller;

/**
 * @Author: Marte
 * @Date:   2017-08-03 10:25:21
 * @Last Modified by:   Marte
 * @Last Modified time: 2017-08-03 14:20:17
 */

class TestController extends Controller{

    public function index(){
      $merchantId = "123";
      $where['open_id'] = $merchantId;

      $shop_name = "幸福蛋糕坊";
      $model = M("merchant");
      $a = $model->where("shop_name='".$shop_name."' and open_id !='".$merchantId."'")->find();
         if(!empty($a)){
           //该名字已被申请
           echo 9;
         }else{
            echo "yes";
         }
    dump($a);

    }


}
