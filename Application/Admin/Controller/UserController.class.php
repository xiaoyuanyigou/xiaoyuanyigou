<?php
namespace Admin\Controller;
use Think\Controller;
class UserController extends Controller {
    public function index(){
      $this->display();
    }
    //验证码生成
    public function verify(){
        $config =    array(
            'fontSize'    =>    14,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify = new \Think\Verify($config);
        $Verify->entry(1);
    }

    // 登录页面
    public function login(){
     session("id",null);
      $this->display();
    }

    //登录判断方法
    public function loginCheck(){
       $username=I("username");
       $password=I("password");
       $code=I("code");
        if (empty($username)) {     //用户名为空返回3
            $this->ajaxReturn("{'result':'3'}",'json');
        }
        if (empty($password)) { //密码为空返回4
            $this->ajaxReturn("{'result':'4'}",'json');
        }
        if(empty($code)){               //验证码为空返回6
            $this->ajaxReturn("{'result':'6'}",'json');
        }
        //与验证码进行比较，不相等时返回7，默认的id=1
        if(!check_verify($code)){
            $this->ajaxReturn("{'result':'7'}",'json');
        }
        $where = array(
                "username"=>$username,
                "password"=>$password,
            );
       $res=M('admin_user')->where($where)->field('username,id')->find();
        $cesf=M('admin_user');
        $str=createNonceStr($length=8);

       if(is_array($res)&&count($res)>0){
            session("id",$res['id']);//表示用户名和密码正确
           $data = array(
              'string' =>$str , );
           $id=session("id");
               $cesf->where(array("id"=>$id,))->save($data);
            session("str",$cesf->where(array("id"=>$id))->getField('string'));
            //返回数组数据
            $this->ajaxReturn("{'result':'1'}",'json');
       }else{
            $this->ajaxReturn("{'result':'5'}",'json');
       }

    }

    //添加用户方法
    public function registercheck(){
        $id=session("id");
        if(!isset($id)){
            redirect(U('User/index'));
        }
        $username=I("username");
        $password=I("password");
        $repassword=I("repassword");
        $nowtime=date("Y-m-d H:i:s");
        $path=__ROOT__."/Public/images/a1.jpg";
        //两次输入的密码不相同
        if ($password!==$repassword) {
                $this->ajaxReturn("{'result':1}",'json');
        }
        $where=array(
                'username'=>$username,
            );
        $ret=M('admin_user')->where($where)->field('username')->find();
        if (is_array($ret)&&count($ret)>0) {
            session("id",$ret['id']);//表示输入的用户名数据库中含有该数据
            $this->ajaxReturn("{'result':'2'}",'json');//报错！！！
        }else{

        $regdata=M('admin_user');
        $data=array(
                'username'=>$username,
                'status'=>1,
                'password'=>$password,
                'register_time'=>$nowtime,
                'tel'=>'',
                'image_path'=>$path,

            );
        $regdata->add($data);
        }
            $this->ajaxReturn("{'result':'3'}",'json');//可以进行提交
    }


       //用户信息
    public function userinfo(){
      $id=I('id');
      if(!isset($id)){
            redirect(U('User/index'));
        }
      $model=M('admin_user');
      $where['id']=session("id");
      $data=$model->where($where)->find();//选取用户的相关信息
      //dump($data);
      if($data===false){
           $this->ajaxReturn("{'result':'0'}",'json');
       }else{
           $array = array(
               "result"=>1,
               "data"=>$data//在前端判断是否为空
           );
           $this->ajaxReturn(json_encode($array),'json');

       }

    }

    //修改用户信息
    /*public function editUser(){
      $id=I('id');
      if(!isset($id)){
            redirect(U('User/index'));
        }
      $where['id']=$id;
      $model=M('admin_user');
      $info['usrername']=I('username');
      $info['email']=I('email');
      $info['tel']=I('tel');
      $info['password']=I('password');
      if(empty($info['password'])){//只修改基本信息
        $res=$model->where($where)->field('username,email,tel')->filter('strip_tags')->save($info);
       if($res==false){

        $data['msg']='你没有做任何修改！';

      }else{
        $data['msg']='修改成功！';
        $data['usrername']=I('username');
        $data['email']=I('email');
        $data['tel']=I('tel');
      }

      }else{//修改密码
        $pwd=M('admin_user')->where($where)->getField('password');
        //echo $pwd;
        $res=$model->where($where)->setField('password',$info['password']);
        if($res==false){

            $data['msg']='新密码与旧密码相同！';

       }else{

           $data['msg']='修改成功！';
        }

        }



      if($data==false){
           $this->ajaxReturn("{'result':'0'}",'json');
       }else{
           $array = array(
               "result"=>1,
               "data"=>$data//在前端判断是否为空
           );
           $this->ajaxReturn(json_encode($array),'json');

       }
    }*/
     //修改用户信息
    public function editUser(){
      $id=I('id');
      if(!isset($id)){
            redirect(U('User/index'));
        }
      $where['id']=$id;
      $model = M('admin_user');
      $username = I('username');
      $email = I('email');
      $tel = I('tel');
      $password = I('password');
      $confirm_password = I('confirm_password');

      /**********修改基本信息开始*********/

        if(!empty($username)){
           $res=$model->where($where)->setField('username',$username);
           $nameRes=true;
        }else{
        $this->ajaxReturn("{'result':'2'}",'json');//姓名为空
        }

        if(!empty($tel)){
        //手机号是否合格
         $isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $tel);
         if($isMatched==0){
         $this->ajaxReturn("{'result':'3'}",'json');//号码不合格
         }else{
         $telRes=true;
         }
      }else{
         $this->ajaxReturn("{'result':'4'}",'json');//号码为空
      }
       if(!empty($email)){
         if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
           $this->ajaxReturn("{'result':'5'}",'json');//email不合格
        }else{
          $emailRes=true;
        }
      }else{
        $this->ajaxReturn("{'result':'6'}",'json');//email为空
     }
       if($nameRes&&$telRes&&$emailRes){
            $info=array(
                'username' => $username,
                'tel' => $tel,
                'email' => $email

              );
            $res=$model->where($where)->field('username,tel,email')->filter('strip_tags')->save($info);
            if($res===false){
             $this->ajaxReturn("{'result':'0'}",'json');
            }
             $this->ajaxReturn("{'result':'1'}",'json');

       }
  }

     /***********修改基本信息结束**********/



    /*******修改密码*****/
    public function editPwd(){
      $id=I('id');
      if(!isset($id)){
            redirect(U('User/index'));
        }
      $where['id']=$id;
      $model = M('admin_user');
      $password = I('password');
      $confirm_password = I('confirm_password');
       /***********修改密码开始**********/
      $pwd=M('admin_user')->where($where)->getField('password');
      $flag = true;
        if(empty($password)){

              $this->ajaxReturn("{'result':'8'}",'json');
        }
        if(empty($confirm_password)){
              $this->ajaxReturn("{'result':'9'}",'json');
        }
        if($pwd==$password){
            $this->ajaxReturn("{'result':'11'}",'json');
              $flag = false;
        }
        if($password!=$confirm_password){
              $this->ajaxReturn("{'result':'10'}",'json');
              $flag = false;
        }


        if($flag){
             $res = $model->where($where)->setField("password",$confirm_password);
             if(!$res){
                $this->ajaxReturn("{'result':'0'}",'json');
             }
                $this->ajaxReturn("{'result':'1'}",'json');
        }

    }
    /*
      修改头像
     */
   public function edit_img(){
        $id=session("id");
        if(!isset($id)){
            redirect(U('User/index'));
        }
        if( !IS_AJAX ) {
            E('页面不存在！');
        }
        $model=M('admin_user');
        $where['id']=session("id");
        $info['image_path']=I('image_path');
        // print_r($info['image_path']);
        // die;
        $res=$model-> where($where)->setField('image_path',$info['image_path']);
         if($res!=false){
             $data['msg']='保存成功！';
             $data['image_path']=I('image_path');
         }else{
             $img_path=$model->where($where)->getField('image_path');
             $data['msg']='操作失败！';
             $data['image_path']=$img_path;
          }
          if($data==false){
           $this->ajaxReturn("{'result':'0'}",'json');
       }else{
           $array = array(
               "result"=>1,
               "data"=>$data//在前端判断是否为空
           );
           $this->ajaxReturn(json_encode($array),'json');

       }
   }


   /**
     * 上传头像
     */
    public function uploadImg(){
       $model=M('admin_user');
       $where['id']=session("id");
      if (IS_POST) {

            header('Content-Type: text/html; charset=utf-8');
            $result = array();
            $username=I('username');
            $msg = '';
            //上传目录
            $path='Uploads'.'/avatar/';
            // 取服务器时间+8位随机码作为部分文件名，确保文件名无重复。
            $filename = date("YmdHis").'_'.floor(microtime() * 1000).'_'.createRandomCode(8);

//如果文件存在，就删除原文件

           /* if (file_exists($path)) {
                delDir($path);
            }
            $avatars = array("__avatar1");
            $avatar = $_FILES[$avatars['0']];
            if ($avatar['error'] > 0 ){
                $msg .= $avatar['error'];
            }
//如果文件不存在，创建文件
            if(!file_exists($path)){

            }*/
           $avatars = array("__avatar1");
            $avatar = $_FILES[$avatars['0']];
            if ($avatar['error'] > 0 ){
                $msg .= $avatar['error'];
            }
              makeDir($path);
            $savePath = $path . $filename . ".jpg";
            if(move_uploaded_file($avatar["tmp_name"], $savePath)){
              //将图片存到数据库

                $res=$model-> where($where)->setField('image_path',$savePath);
                if($res===false){
                $result['success'] = false;
               }else{

                $result['msg'] = $msg;
                $result['success'] = true;
                $result['img']=$savePath;


             }
                echo json_encode($result);

            }else{
                $result['success'] = false;
            }



        }
}
     /**
     * 用户列表
     */
        public function userList(){

        $id=session("id");
        if(!isset($id)){
            redirect(U('User/index'));
        }

          $userData = M('admin_user')->select();
          if (is_array($userData)&&!empty($userData)) {
           $array = array(
            'result' =>1 ,
            'data' => $userData,
             );
           $this->ajaxReturn(json_encode($array),'json');
          }else if (empty($userData)) {
            $this->ajaxReturn("{'result':'3'}",'json');//没有返回数据
          }else{
            $this->ajaxReturn("{'result':'3'}",'json');
          }



        }
}