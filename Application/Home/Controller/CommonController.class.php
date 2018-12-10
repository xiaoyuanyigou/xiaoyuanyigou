<?php
namespace Home\Controller;
use Think\Controller;
class CommonController extends Controller{
    public function _initialize(){
        if(empty(session('userId'))){
            //需要每个页面写成获取用户信息的地址接口，不然不会有code
            $app=M("weixin")->find(1);
            $code=$_REQUEST['code'];
            if(empty($code)){
                //取当前的页面的url，记录原先要进的页面，在回调地址上填写该地址
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app['app_id'].'&redirect_uri='.urlencode($url).'&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect');
            }else{
                $result=file_get_contents('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$app['app_id'].'&secret='.$app['app_secret'].'&code='.$code.'&grant_type=authorization_code');
            }
            $result=json_decode($result, true);
            //通过access_token拉取用户信息
            if(!empty($result['access_token'])){
                $user_info=file_get_contents('https://api.weixin.qq.com/sns/userinfo?access_token='.$result['access_token'].'&openid='.$result['openid'].'&lang=zh_CN');
                $user_info=json_decode($user_info,true);
                //将openID存到session中用于微信支付
                session("openId",$user_info['openid']);
            }else{
                print_r("获取access_token失败");
                return;
            }
            $userlist=M('user')->where(array("open_id"=>$user_info['openid']))->field("id")->find();
            //数据库里没有该用户
            if(empty($userlist)){
                $user=array(
                        "open_id"=>$user_info['openid'],
                        "sex"=>$user_info['sex'],
                        "username"=>$user_info['nickname'],
                        "img_path"=>$user_info['headimgurl'],
                        "country"=>$user_info['country'],
                        "province"=>$user_info['province'],
                        "city"=>$user_info['city'],
                        "status"=>1,
                        "time"=>date("Y-m-d H:i:s",time())
                    );
                $ret = M('user')->add($user);
                if(!empty($ret)){
                    session("userId",$ret);
                }else{
                    print_r("出错了！");
                }
            }else if(is_array($userlist)&&!empty($userlist)){
                session("userId",$userlist['id']);
            }else if($userlist==false){
                print_r("出错了！");
            }
        }

        /*第一次进来向数据库存传过来的参数*/
        $plantform = I('type');//平台ID（在平台表里面的）
        $student_id = I('student_id');
        if(!empty($plantform)){
            //向cookie中存其他平台传过来的参数，不设置期限，浏览器关闭就清除（微信内置浏览器的关闭是在微信关闭后才算关闭）
            cookie("plantform",$plantform);
            cookie("value",$student_id);
        }else{
            $pre_page = $_SERVER['HTTP_REFERER'];
            // 不是自己网站页面进来，就清空cookie
            if(strpos($pre_page, "stdbuy.wisvalley.com") === false){
                cookie("plantform",null);
                cookie("value",null);
            }
        }
	}
}