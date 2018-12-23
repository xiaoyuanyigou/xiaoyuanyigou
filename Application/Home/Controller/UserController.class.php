<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends CommonController {
    public function index(){
    	echo "前台的User控制器index方法"; 
    }
    /**
	* 查看用户的所有收货地址
	* 
	* @access public
	* @param $userId 用户ID
	* @return json 返回地址列表的json数据
	*/
	public function addressList(){
		$userId = session('userId');
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['status'] = array('EGT',0);
		$where['user_id'] = $userId;
		$addressResult = M('address')->where($where)->field("id,name,tel,roughly_address,detail_address,status")->select();
		if(is_array($addressResult)&&!empty($addressResult)){
			$array = array(
				"result"=>1,
				"data"=>$addressResult
			);
			$this->ajaxReturn($array,'json');
		}else if(is_array($addressResult)&&empty($addressResult)){
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

	/**
	* 添加收货地址
	* 
	* @access public
	* @param $userId 用户ID
	* @param $name 姓名
	* @param $tel 电话
	* @param $roughlyAddress 省市县
	* @param $detailAddress 详细地址
	* @return json 返回是否操作成功
	*/
	public function addAddress(){
		$userId = session('userId');
		$name = I('name');
		$tel = I('tel');
		$roughlyAddress = I('roughlyAddress');
		$detailAddress = I('detailAddress');

		$isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $tel);
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		/*放后台判断这些参数是否为空更安全，前台容易被改*/
		if(empty($name)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		if(empty($tel)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(empty($roughlyAddress)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		if(empty($detailAddress)){
			$this->ajaxReturn(array("result"=>6),'json');
		}
		//手机号是否合格
		if($isMatched==0){
			$this->ajaxReturn(array("result"=>7),'json');
		}
		$address = array(
				"user_id"=>$userId,
				"name"=>$name,
				"tel"=>$tel,
				"roughly_address"=>$roughlyAddress,
				"detail_address"=>$detailAddress,
				"status"=>0,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$addressResult = M('address')->add($address);
		if(!$addressResult){
			$this->ajaxReturn(array("result"=>0),'json');
		}else{
			$this->ajaxReturn(array("result"=>1),'json');
		}
	}

	/**
	* 更新收货地址
	* 
	* @access public
	* @param $userId 用户ID
	* @param $name 姓名
	* @param $tel 电话
	* @param $roughlyAddress 省市县
	* @param $detailAddress 详细地址
	* @return json 返回是否操作成功
	*/
	public function updateAddress(){
		/*
			前端传的数据
			{'name':'江军','tel':212124134233,'roughlyAddress':'粗略地址','detailAddress':详细地址'}
		*/
		$userId = session("userId");
		$aid = I("aid");
		$name = I('name');
		$tel = I('tel');
		$roughlyAddress = I('roughlyAddress');
		$detailAddress = I('detailAddress');
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		/*放后台判断这些参数是否为空更安全，前台容易被改*/
		if(empty($name)){
			$this->ajaxReturn(array("result"=>3),'json');
		}
		if(empty($tel)){
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(empty($roughlyAddress)){
			$this->ajaxReturn(array("result"=>5),'json');
		}
		if(empty($detailAddress)){
			$this->ajaxReturn(array("result"=>6),'json');
		}
		//手机号是否合格
		$isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $tel);
		if($isMatched==0){
			$this->ajaxReturn(array("result"=>7),'json');
		}
		$where['id'] = $aid;
		$update = array(
				"name"=>$name,
				"tel"=>$tel,
				"roughly_address"=>$roughlyAddress,
				"detail_address"=>$detailAddress,
				"time"=>date("Y-m-d H:i:s",time())
			);
		$updateAddressResult = M('address')->where($where)->save($update);
		if($updateAddressResult>0||$updateAddressResult===0){
			//更新成功
			$this->ajaxReturn(array("result"=>1),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

	/**
	* 删除地址
	* 
	* @access public
	* @param $addressId 地址表ID
	* @return json 返回是否操作成功
	*/
	public function deleteAddress(){
		$addressId = I('addressId');
		$userId = session("userId");
		if(empty($addressId)||empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$data['status'] = -1;
		$where['id'] = $addressId;
		$result = M('address')->where($where)->setField($data);
		if($result==1){
			$this->ajaxReturn(array("result"=>1),'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}

	/**
	* 根据地址表ID获得地址信息
	* 
	* @access public
	* @param $addressId 地址表ID
	* @return json 返回地址信息的json数据
	*/
	public function oneAddressInfo(){
		$aid = I("addressId");
		$userId = session("userId");
		if(empty($aid)||empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['id'] = $aid;
		$result = M('address')->where($where)->field('id,name,tel,roughly_address,detail_address')->find();
		if(is_array($result)&&!empty($result)){
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
	* 设置默认地址
	* 
	* @access public
	* @param $addressId 地址表ID
	* @return json 返回是否操作成功
	*/
	public function defaultAddress(){
		/*
			前台传数据
			{'addressId':123}
		*/
		$addressId = I('addressId');
		$userId = session('userId');
		if(empty($addressId)||empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$address = M('address');
		$flag = true; //先设定一个值为 true;
		$address->startTrans(); //开启事务

		$where['id'] = $addressId;
		$addressInfo = $address->where($where)->field('status')->find();
		//$addressInfo结果不可能为空
		if($addressInfo['status']==1){
			//已经是默认地址了
			$this->ajaxReturn(array("result"=>3),'json');
		}else{
			//如果之前有默认地址，则设置为不默认
			$condition = array(
					"user_id"=>$userId,
					"status"=>1
				);
			$idInfo = $address->where($condition)->field('id')->find();
			if(!empty($idInfo)){
				//取消之前的默认地址
				$setStatus = $address->where('id='.$idInfo['id'])->setField('status',0);
				if(!$setStatus){
					$address->rollback();//出错就回滚
					$flag = false; //并且把标志位设置为 false
					$this->ajaxReturn(array("result"=>0),'json');
				}
			}
			//把本条地址设置成默认地址
			$id['id'] = $addressId;
			$setResult = $address->where($id)->setField('status',1);
			if(!$setResult){
				$address->rollback();//出错就回滚
				$flag = false; //并且把标志位设置为 false
				$this->ajaxReturn(array("result"=>0),'json');
			}
			//提交事务
			if($flag){
				$address->commit();
				$this->ajaxReturn(array("result"=>1),'json');
			}
		}
	}

	/**
	* 提交订单的页面地址请求
	* 
	* @access public
	* @param $userId 用户ID
	* @return json 返回是地址信息的json数据
	*/
	public function getUserDefaultAddress(){
		$userId = session("userId");
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['user_id'] = $userId;
		$where['status'] = 1;
		$result = M('address')->where($where)->field('id,name,tel,roughly_address,detail_address')->find();
		if(is_array($result)){
			$array = array(
					"result"=>1,
					"data"=>$array
				);
			$this->ajaxReturn($array,'json');
		}else{
			$this->ajaxReturn(array("result"=>0),'json');
		}
	}


	/**
	* 查看个人信息
	* 
	* @access public
	* @param $userId 用户ID
	* @return json 返回个人信息的json数据
	*/
	public function checkUserInfo(){
		$userId = session("userId");
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['id'] = $userId;
		$result = M('user')->where($where)->field('username,img_path,tel,email')->find();
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
	* 修改个人手机号信息
	* 
	* @access public
	* @param $userId 用户ID
	* @param $tel 用户手机号
	* @param $email 用户邮箱
	* @return json 返回修改结果
	*/
	public function alterUserInfo(){
		/*在前端，如果没有变，就传空值过来，如果有变化，就传改变后的值*/
		$userId = session("userId");
		$tel = I('tel');
		$email= I('email');
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		if(!empty($tel)){
			//手机号是否合格
			$isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $tel);
			if($isMatched==0){
				$this->ajaxReturn(array("result"=>6),'json');
			}
			$telResult = $this->alterUserTel($userId,$tel);
			if(!$telResult){
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else{
			$this->ajaxReturn(array("result"=>4),'json');
		}
		if(!empty($email)){
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
				$this->ajaxReturn(array("result"=>7),'json');
			}
			$emailResult = $this->alterUserEmail($userId,$email);
			if(!$emailResult){
				$this->ajaxReturn(array("result"=>0),'json');
			}
		}else{
			$this->ajaxReturn(array("result"=>5),'json');
		}
		$this->ajaxReturn(array("result"=>1),'json');	
	}

	/**
	* 修改个人手机号信息
	* 
	* @access public
	* @param $userId 用户ID
	* @param $tel 用户手机号
	* @return json 返回修改结果
	*/
	public function alterUserTel($userId,$tel){
		$where['id'] = $userId;
		$result = M('user')->where($where)->setField('tel',$tel);
		if($result===false){
			return false;
		}else{
			return true;
		}
	}

	/**
	* 修改个人邮箱信息
	* 
	* @access public
	* @param $userId 用户ID
	* @param $email 用户手机号
	* @return json 返回修改结果
	*/
	public function alterUserEmail($userId,$email){
		$where['id'] = $userId;
		$result = M('user')->where($where)->setField('email',$email);
		if($result===false){
			return false;
		}else{
			return true;
		}
	}

	/**
	* 修改个人头像
	* 
	* @access public
	* @param $userId 用户ID
	* @param $imgPath 用户头像路径
	* @return json 返回修改结果
	*/
	public function alterUserImg($userId,$imgPath){
		/*是否为空在调用处判断*/
		// if(empty($userId)||empty($imgPath)){
		// 	return null;
		// }
		$where['id'] = $userId;
		$result = M('user')->where($where)->setField('img_path',$imgPath);
		if(!$result){
			return false;
		}else{
			return true;
		}
	}

	/**
	* 获取历史搜索
	* 
	* @access public
	* @param $userId 用户ID
	* @return json 返回搜索记录的json数据
	*/
	public function getSearchRecord(){
		$userId = session("userId");
		if(empty($userId)){
			$this->ajaxReturn(array("result"=>2),'json');
		}
		$where['user_id'] = $userId;
		$result = M('search_record')->where($where)->field('search_name')->order('time desc')->limit(8)->select();
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
	* 商家注册
	* 
	* @access public
	* @param $userId 用户ID
	* @return json 返回搜索记录的json数据
	*/
	public function register(){
        $data = A('WxApi')->wxPayConfig();
        if(count(array_values($data))!=4||$data===false){
            redirect("../User/error");
        }
        $this->assign($data);
        $this->display();
    }
    /**
    * 请求商家的信息（如果不为空，就是我的信息，如果是空，就是成为商家）
    * 
    * @access public
    * @param
    * @return json 返回结果
    */
    public function getMerchantInfo(){
        $open_id = session("openId");
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        $where['open_id'] = $open_id;
        $where['status'] = 1;//通过审核的店铺
        $result = M('merchant')->where($where)->field('id,shop_name,real_name,logo_path,tel,address,status,pay,area')->find();
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
    * 处理商家信息，添加或者修改
    * 
    * @access public
    * @param $merchant 商家信息数组
    * @return json 返回结果
    */
    public function disposeMerchantInfo(){
        $open_id = session("openId");
        $shop_name = I("shopName");
        $logo_path = I("wxServerId");
        $real_name = I("realName");
        $tel = I("tel");
        $area = I("area");
        $address = I("address");
        $pay = I("pay");
        $mid = I("mid");
        $latitude = I("latitude");
        $longitude = I("longitude");
        /*1、判断参数是否正确*/
        if(empty($logo_path)){
            $this->ajaxReturn(array("result"=>3),'json');
        }
        if(empty($shop_name)){
            $this->ajaxReturn(array("result"=>4),'json');
        }
        if(empty($real_name)){
            $this->ajaxReturn(array("result"=>5),'json');
        }
        if(empty($tel)){
            $this->ajaxReturn(array("result"=>6),'json');
        }
        if(empty($area)){
            $this->ajaxReturn(array("result"=>7),'json');
        }
        if(empty($address)){
            $this->ajaxReturn(array("result"=>8),'json');
        }
        if(empty($pay)){
            $this->ajaxReturn(array("result"=>9),'json');
        }
        $isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $tel);
        //手机号是否合格
        if($isMatched==0){
            $this->ajaxReturn(array("result"=>A),'json');
        }
        if(empty($longitude)||empty($latitude)){
            $this->ajaxReturn(array("result"=>E),'json');
        }
        if(empty($open_id)){
            $this->ajaxReturn(array("result"=>2),'json');
        }
        //如果serverId不空，通过serverId下载头像
        if($logo_path != "unChange"){
            $path = $this->getImgFromWx($logo_path);
            if(!$path){
                $this->ajaxReturn(array("result"=>0),'json');
            }
        }
        
        /*2、判断是添加信息还是修改信息*/
        if($mid==""){
            /*1、判断这个人是否有还在审核中的店铺，如果有，就不让他注册*/
            $where1['open_id'] = $open_id;
            $where['status'] = 0;
            $ret = M('merchant')->where($where)->field('status')->find();
            if($ret===false){
                $this->ajaxReturn(array("result"=>0),'json');
            }else if(!empty($ret)){
                $this->ajaxReturn(array("result"=>"D"),'json');
            }

            $add = array(
                    "shop_name"=>$shop_name,
                    "real_name"=>$real_name,
                    "logo_path"=>$path,
                    "tel"=>$tel,
                    "area"=>$area,
                    "address"=>$address,
                    "pay"=>$pay,
                    "status"=>0,//刚申请，店铺状态为0，通过审核后状态为1
                    "open_id"=>$open_id,
                    "time"=>date("Y-m-d H:i:s",time()),
                    "balance"=>0
                );
            $add_res = M('merchant')->add($add);
            if($add_res===false){
                $this->ajaxReturn(array("result"=>0),'json');
            }else{
                //添加成功
                $this->ajaxReturn(array("result"=>B),'json');
            }
        }else{
            $save = array(
                    "shop_name"=>$shop_name,
                    "real_name"=>$real_name,
                    "tel"=>$tel,
                    "area"=>$area,
                    "address"=>$address,
                    "pay"=>$pay
                );
            if($logo_path!="unChange"){
                $save['logo_path'] = $path;
            }
            $where['open_id'] = $open_id;
            $save_res = M('merchant')->where($where)->save($save);
            if($save_res==0){
                //没有修改信息
                $this->ajaxReturn(array("result"=>C),'json');
            }else if($save_res==1){
                $this->ajaxReturn(array("result"=>1),'json');
            }else{
                $this->ajaxReturn(array("result"=>0),'json');
            }
        }
    }
}