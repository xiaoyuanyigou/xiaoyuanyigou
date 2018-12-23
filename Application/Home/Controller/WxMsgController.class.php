<?php
namespace Home\Controller;
use Think\Controller;
/**
 * 微信接口类
 */
class WxMsgController extends Controller{
    /**
     * 主函数
     */
    function __construct(){
        parent::__construct();
    }
    /*
     * 发送采集请求的方法
     */
    private function http_curl($url,$type='get',$res='json',$data=''){
        //1.创建连接资源
        $ch = curl_init();
        //2.设置参数
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if($type=='post'){
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if($res='json'){
            if(curl_errno($ch)){
                return curl_errno($ch);
            }
            return json_decode($output,true);
        }
    }
    /**
     * 订单生成回复模板消息
     * @param   array类型
     *      openId, oderUrl, orderTitle, orderTime, proName, orderNum, remark
     * @return  
     *     true/false
     */
    public function sendTplMsg($info){
        //1、获取access_token
        $access_token = A('WxApi')->getAccessToken();
        if(empty($access_token)){
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        //2、组装array
        $array = array(
            'touser' => $info['open_id'] ,
            'template_id' => '7xJm0QyykxOvOQYdwf80pn6xETt4ZOS330aa7uPOBeQ',
            'url' => $info['url'] ,
            'data' => array(
                    'first' => array('value' => $info['title'],'color'=> '#173177'),
                    'keyword1' => array('value' => $info['buy_time']),
                    'keyword2' =>  array('value' => $info['pay']),
                    'keyword3' =>  array('value' => $info['order_number']),
                    'remark' => array('value' => "订单备注：".$info['remark'])
                )
        );
        //3、array转变为json格式
        $postJson = json_encode($array);
        //4、调用curl函数
        $res = $this->vpost($url,$postJson);
        $res = json_decode($res,true);
        if($res['errcode']=='0'&&$res['errmsg']=='ok'){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 退货订单回复模板消息
     * @param array类型
     * @return  布尔值
     */
    public function sendReturnTplMsg($info){
        //1、获取access_token
        $access_token = A('WxApi')->getAccessToken();
        if(empty($access_token)){
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        //2、组装array
        $array = array(
            'touser' => $info['open_id'],
            'template_id' => 'WdIBIm9Ho9OSVzSc6HS8fHaOSqCujWQlwEotuP-Teec',
            'url' => $info['url'] ,
            'data' => array(
                    'first' => array('value' => $info['title'],'color'=> '#173177'),
                    'keyword1' => array('value' => $info['order_number']),
                    'keyword2' =>  array('value' => $info['name']),
                    'keyword3' =>  array('value' => "￥".$info['price']),
                    'remark' => array('value' => "退货原因：".$info['reason'])
                )
        );
        //3、array转变为json格式
        $postJson = json_encode($array);
        //4、调用curl函数
        $res = $this->vpost($url,$postJson);
        $res = json_decode($res,true);
        if($res['errcode']=='0'&&$res['errmsg']=='ok'){
            return true;
        }else{
            return false;
        }
    }
    //用户申请退款给商家发送消息
    public function sendRefundMsg($info){
        //1、获取access_token
        $access_token = A('WxApi')->getAccessToken();
        if(empty($access_token)){
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        //2、组装array
        $array = array(
            'touser' => $info['open_id'] ,
            'template_id' => '5Q-hPBCyVqVg8s7RlHlkyHrpXgUE-qoG13Tb3O7c7dY',
            'url' => $info['url'] ,
            'data' => array(
                    'first' => array('value' => $info['title'],'color'=> '#173177'),
                    'keyword1' => array('value' => $info['order_number']),
                    'keyword2' =>  array('value' => $info['apply_time']),
                    'keyword3' =>  array('value' => '申请退款'),
                    'remark' => array('value' => "退款原因：".$info['reason'])
                )
            );
        //3、array转变为json格式
        $postJson = json_encode($array);
        //4、调用curl函数
        $res = $this->vpost($url,$postJson);
        $res = json_decode($res,true);
        if($res['errcode']=='0'&&$res['errmsg']=='ok'){
            return true;
        }else{
            return false;
        }
    }

    // //蛋糕店给用户提示什么时间什么地点送到
    // public function sendArrriveAddressAndTime($arr){
    //     $access_token = A('WxApi')->getAccessToken();
    //     if(!$access_token){
    //         return false;
    //     }
    //     $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
    //     $array = array(
    //         'touser' => $arr['open_id'],
    //         'template_id' => 'Yv7uP25vhhqHVrPNS25KR0A2pi9ryxng2BWBz_d7fX8',
    //         'url' => $arr['url'],
    //         'data' => array(
    //                 'first' => array('value' => "商家提供了送货时间和地点",'color'=> '#173177'),
    //                 'OrderSn' => array('value' => $arr['order_number']),
    //                 'OrderStatus' =>  array('value' => $arr['status']),
    //                 'remark' => array('value' => $arr['time'].$arr['location'])
    //             )
    //     );
    //     //3、array转变为json格式
    //     $postJson = json_encode($array);
    //     //4、调用curl函数
    //     $res = $this->vpost($url,$postJson);
    //     $res = json_decode($res,true);
    //     if($res['errcode']==0&&$res['errmsg']=='ok'){
    //         return true;
    //     }else{
    //         return false;
    //     }
    // }

    public function setMenu(){
        /*$con['id']=1;
        $app_id = M("weixin")->where($con)->getField("app_id");
        $app_secret = M("weixin")->where($con)->getField("app_secret");
        $result = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$app_id.'&secret='.$app_secret);
        $result = json_decode($result, true);
        if ($result['access_token']) {
            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$result['access_token'];
            $menu = '{"button":'.
                '[
                {"type":"view","name":"进入呵贝","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app_id.'&redirect_uri=http%3a%2f%2ftest.720hebei.com%2fweixin.php%2fIndex%2findex&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},
                {"type":"view","name":"身份绑定","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app_id.'&redirect_uri=http%3a%2f%2ftest.720hebei.com%2fweixin.php%2fBind%2fBind&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},
                {"type":"click","name":"帮助","key":"含子菜单的一级菜单无触发关键字或链接地址","sub_button":
                    [{"type":"view","name":"建议","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app_id.'&redirect_uri=http%3a%2f%2f720hebei.com%2fAdmin%2fWeixinSite%2fadvise&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},
                    {"type":"view","name":"绑定教程","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app_id.'&redirect_uri=http%3a%2f%2f720hebei.com%2fAdmin%2fWeixinSite%2fopeator_course&response_type=code&scope=snsapi_base&state=123#wechat_redirect"}
                    ]
                }]}';
            $menu = htmlspecialchars_decode($menu);
            $this->vpost($url, $menu);
            echo $menu;
        } else {
            echo 'error';
        }*/
        $app=M("weixin")->find(1);
        $result = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$app['app_id'].'&secret='.$app['app_secret']);
        $result = json_decode($result, true);
        if ($result['access_token']) {
            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$result['access_token'];
            $menu = '{"button":'.
                '[
                {"type":"view","name":"商城入口","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app['app_id'].'&redirect_uri=http%3a%2f%2fstdbuy.wisvalley.com%2findex.php%2fIndex%2findex&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect"},
                {"type":"view","name":"商家中心","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app['app_id'].'&redirect_uri=http%3a%2f%2fstdbuy.wisvalley.com%2findex.php%2fMerchant%2fpersonal_center&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect"},
                {"type":"view","name":"招商政策","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app['app_id'].'&redirect_uri=http%3a%2f%2fstdbuy.wisvalley.com%2findex.php%2fMerchant%2fattract&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect"},
                ]}';
                

            $menu = htmlspecialchars_decode($menu);
            $this->vpost($url, $menu);
            echo $menu;
        } else {
            echo 'error';
        }

//https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx6ddf68ecc658aff7&redirect_uri=http%3a%2f%2fmxt.yxttx.cn%2fhome.php%2fIndex%2findex%2fproId%2f2&response_type=code&scope=snsapi_base&state=123#wechat_redirect
        /*$result = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx28c049b5d5ce3819&secret=ad56c8d4cbf8a72558f57e82845003ef');
        $result = json_decode($result, true);
        if ($result['access_token']) {
            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$result['access_token'];
            $menu = '{"button":'.
                    '[{"type":"view","name":"进入呵贝","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWxInterface%2flogin_check&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},'.
                    '{"type":"click","name":"家长绑定","key":"含子菜单的一级菜单无触发关键字或链接地址","sub_button":'.
                        '[{"type":"view","name":"绑定学生","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2frole_bind&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},{"type":"view","name":"邀请绑定","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2finvite&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},{"type":"view","name":"家长教程","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2fopeator_course_parent&response_type=code&scope=snsapi_base&state=123#wechat_redirect"}]},'.'{"type":"click","name":"管理绑定","key":"含子菜单的一级菜单无触发关键字或链接地址","sub_button":'.
                        '[{"type":"view","name":"绑定班级","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2fclass_bind&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},{"type":"view","name":"绑定学校","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2fschool_bind&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},{"type":"view","name":"班级教程","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2fopeator_course_class&response_type=code&scope=snsapi_base&state=123#wechat_redirect"},{"type":"view","name":"学校教程","url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2fzhxy.wisvalley.com%2fAdmin%2fWeixinSite%2fopeator_course_school&response_type=code&scope=snsapi_base&state=123#wechat_redirect"}]}]}';
            $menu = htmlspecialchars_decode($menu);
            $this->vpost($url, $menu);
            echo $menu;
        } else {
            echo 'error';
        }*/
    }
    private function vpost($url,$data){
            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
            // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            // curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
            $tmpInfo = curl_exec($curl); // 执行操作
            if (curl_errno($curl)) {
               echo 'Errno'.curl_error($curl);//捕抓异常
            }
            curl_close($curl); // 关闭CURL会话
            //echo $tmpInfo;
            return $tmpInfo; // 返回数据
        }

    public function test(){

        $new = strip_tags("<a href='test'>Test</a>");
        $postObj=json_decode('{"ToUserName":"gh_32040934cd13","FromUserName":"oWRspuNHAMJU_v8K_lZ9rzAG_gXw","CreateTime":"1466651888","MsgType":"shortvideo","MediaId":"oLEFY-k1jx1KjoW3Egf8fivnWDG74w3FZOm-WCjI_jecfKgU_C6DZjtq-wNTLEoW","ThumbMediaId":"2JcN0xPQjuowlptQzC1lj5ELOTNv-BERXdRMTpHQo17BqhhRvlk01Zo8UMU6JnYB","MsgId":"6299221893986162405"}');
        $postObj='{"ToUserName":"gh_32040934cd13","FromUserName":"oWRspuNHAMJU_v8K_lZ9rzAG_gXw","CreateTime":"1466651888","MsgType":"shortvideo","MediaId":"oLEFY-k1jx1KjoW3Egf8fivnWDG74w3FZOm-WCjI_jecfKgU_C6DZjtq-wNTLEoW","ThumbMediaId":"2JcN0xPQjuowlptQzC1lj5ELOTNv-BERXdRMTpHQo17BqhhRvlk01Zo8UMU6JnYB","MsgId":"6299221893986162405"}';
        echo $postObj;
        $token=A("Base")->update_token(1);
        $this->vpost("https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$token."&type=video",$postObj);
        $this->DoPostMsg($postObj);
        echo $new;die;
        $td['content']="调试1：路径访问正确";M("test")->add($td);die;
        $td['content']="调试2：access_token调试正确";M("test")->add($td);die;
        $td['content']="调试3：access_token调试sql:".M("Base")->getlastsql();M("test")->add($td);die;
    }
    public function index() {
        if($this->checkSignature()){
            // echo $_GET["echostr"];
            // $this -> replyWhensubscribe();
            $this -> responseMsg();
            //$this -> replyNotCatchContent();
        }
    }
    public function responseMsg(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //接收微信发来的XML数据
        if(!empty($postStr)){
            //解析post来的XML为一个对象$postObj
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName; //请求消息的用户
            $toUsername = $postObj->ToUserName; //"我"的公众号id
            $time = time(); //时间戳
            if($postObj->MsgType == 'event'){ //如果XML信息里消息类型为event
                if($postObj->Event == 'subscribe'){ //如果是订阅事件
                    $contentStr = 
                                "<xml>
                                    <ToUserName><![CDATA[$fromUsername]]></ToUserName>
                                    <FromUserName><![CDATA[$toUsername]]></FromUserName>
                                    <CreateTime>$time</CreateTime>
                                    <MsgType><![CDATA[news]]></MsgType>
                                    <ArticleCount>1</ArticleCount>
                                    <Articles>
                                    <item>
                                        <Title><![CDATA[感谢您关注校园易购网]]></Title>
                                        <Description><![CDATA[关于我们]]></Description>
                                        <PicUrl><![CDATA[http://stdbuy.wisvalley.com//Public/images/banner.jpg]]></PicUrl>
                                            <Url><![CDATA[http://stdbuy.wisvalley.com/index.php/Merchant/attract]]></Url>
                                    </item>
                                    </Articles>
                                </xml>";
                    echo $contentStr;
                    exit();
                }
            }else{
                $keyword = trim($postObj->Content); //消息内容
                $msgtype = 'text';//消息类型：文本
                $textTpl = 
                    "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";
                if($keyword == '呵呵'){
                    $contentStr = '<a href="http://stdbuy.wisvalley.com/">jianggui</a>';     //可以是一个a标签
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $contentStr);
                    echo $resultStr;
                    exit();            
                }else{
                    $contentStr = '请点击菜单查看详情';
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $contentStr);
                    echo $resultStr;
                    exit();
                }
            }
            
           
        }else{
            echo "";
            exit();
        }
    }
    // //接收文本消息
    // private function receiveText($object){
    //     $keyword = trim($object->Content);
    //     $url = "http://api100.duapp.com/movie/?appkey=DIY_miaomiao&name=".$keyword;
    //     $output = file_get_contents($url,$keyword);
    //     $contentStr = json_decode($output, true);
    //     if (is_array($contentStr)){
    //         $resultStr = $this->transmitNews($object, $contentStr);
    //     }else{
    //         $resultStr = $this->transmitText($object, $contentStr);
    //     }
    //     return $resultStr;
    // }
    // //回复图文
    // private function transmitNews($object, $arr_item)
    // {
    //     if(!is_array($arr_item))
    //         return;
    //     $itemTpl = 
    //             "<item>
    //                 <Title><![CDATA[%s]]></Title>
    //                 <Description><![CDATA[%s]]></Description>
    //                 <PicUrl><![CDATA[%s]]></PicUrl>
    //                 <Url><![CDATA[%s]]></Url>
    //             </item>";
    //     $item_str = "";
    //     foreach ($arr_item as $item)
    //         $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
    //     $newsTpl = 
                // "<xml>
                //     <ToUserName><![CDATA[%s]]></ToUserName>
                //     <FromUserName><![CDATA[%s]]></FromUserName>
                //     <CreateTime>%s</CreateTime>
                //     <MsgType><![CDATA[news]]></MsgType>
                //     <Content><![CDATA[]]></Content>
                //     <ArticleCount>%s</ArticleCount>
                //     <Articles>
                //         $item_str
                //     </Articles>
                // </xml>";
    //     $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item));
    //     return $resultStr;
    // }
    private function checkSignature(){
        $app=M("weixin")->find(1);
        $token = $app['token'];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    private function replyWhensubscribe(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //dump($postStr);
        if(!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $event = $postObj->Event;
            $keyword = $postObj -> EventKey;
            $Content = $postObj->Content;
            $this->DoPostMsg(json_encode($postObj));
            $time = time();
            $sign="uid=".$fromUsername."&&time=".$time."&&sign=".md5(md5($fromUsername."jsdp2p").$time."wisvalley");
            if($event == 'subscribe'){
                $resultStr = "<xml>
                                    <ToUserName><![CDATA[$fromUsername]]></ToUserName>
                                    <FromUserName><![CDATA[$toUsername]]></FromUserName>
                                    <CreateTime>$time</CreateTime>
                                    <MsgType><![CDATA[news]]></MsgType>
                                    <ArticleCount>1</ArticleCount>
                                    <Articles>
                                    <item>
                                        <Title><![CDATA[感谢您关注铭学堂]]></Title>
                                        <Description><![CDATA[查看帮助]]></Description>
                                        <PicUrl><![CDATA[http://test.720hebei.com/Public/Images/autorepay.jpg]]></PicUrl>
                                            <Url><![CDATA[http://mp.weixin.qq.com/s?__biz=MzI0ODEzNzI1OA==&mid=403691667&idx=1&sn=b73b73ddc9db1e64b11a3842a7707894&scene=23&srcid=05233l8JWOAeO55MtPP62nk4#rd]]></Url>
                                    </item>
                                    </Articles>
                                </xml>";
                echo $resultStr;
            }elseif($event == 'CLICK'){
                /*$time=time();
                $sign="uid=".$fromUsername."&&time=".$time."&&sign=".md5(md5($fromUsername."jsdp2p").$time."wisvalley");
                if ($keyword=="Ucenter") {
                    $resultStr =  '<xml>
                                <ToUserName><![CDATA[' . $fromUsername . ']]></ToUserName>
                                <FromUserName><![CDATA[' . $toUsername . ']]></FromUserName>
                                <CreateTime>' . $time . '</CreateTime>
                                <MsgType><![CDATA[news]]></MsgType>
                                <ArticleCount>1</ArticleCount>
                                <Articles>
                                <item>
                                    <Title><![CDATA[用户中心]]></Title>
                                    <Description><![CDATA[点击上方图片进入呵贝]]></Description>
                                    <PicUrl><![CDATA[http://720hebei.com/Public/Images/20160328231724.png]]></PicUrl>
                                    <Url><![CDATA[http://720hebei.com?'.$sign.']]></Url>
                                </item>
                                </Articles>
                            </xml> ';
                    echo $resultStr;
                }*/
            }else{
                if(strpos($Content,"tel")!==false){
                    /*$tel=substr($Content,3,11);
                    $resultStr =  '<xml>
                                <ToUserName><![CDATA[' . $fromUsername . ']]></ToUserName>
                                <FromUserName><![CDATA[' . $toUsername . ']]></FromUserName>
                                <CreateTime>' . $time . '</CreateTime>
                                <MsgType><![CDATA[news]]></MsgType>
                                <ArticleCount>1</ArticleCount>
                                <Articles>
                                <item>
                                    <Title><![CDATA[呵贝测试环境]]></Title>
                                    <Description><![CDATA[点击进入呵贝测试环境，非工作人员请勿点击!]]></Description>
                                    <Url><![CDATA[http://test.720hebei.com:1215/weixin.php/Base/setSession?tel='.$tel.']]></Url>
                                </item>
                                </Articles>
                            </xml> ';
                    echo $resultStr;*/
                }else{
                    if(!empty($Content)){
                        $a=M("auto_repay")->where("status=1 and (type=1 and  content like '%".$Content."%') or (type=2 and  content='".$Content."')")->find();
                        if(count($a)>0){
                            $resultStr =  '<xml>
                                    <ToUserName><![CDATA[' . $fromUsername . ']]></ToUserName>
                                    <FromUserName><![CDATA[' . $toUsername . ']]></FromUserName>
                                    <CreateTime>' . $time . '</CreateTime>
                                    <MsgType><![CDATA[news]]></MsgType>
                                    <ArticleCount>1</ArticleCount>
                                    <Articles>
                                    <item>
                                        <Title><![CDATA['.$a['title'].']]></Title>
                                        <Description><![CDATA['.$a['description'].']]></Description>
                                        <PicUrl><![CDATA['.$a['picurl'].']]></PicUrl>
                                            <Url><![CDATA['.$a['url'].']]></Url>
                                    </item>
                                    </Articles>
                                </xml> ';
                            echo $resultStr;
                        }else{
                            $resultStr =  "<xml>
                                    <ToUserName><![CDATA[$fromUsername]]></ToUserName>
                                    <FromUserName><![CDATA[$toUsername]]></FromUserName>
                                    <CreateTime>$time</CreateTime>
                                    <MsgType><![CDATA[news]]></MsgType>
                                    <ArticleCount>1</ArticleCount>
                                    <Articles>
                                    <item>
                                        <Title><![CDATA[感谢您关注呵贝]]></Title>
                                        <Description><![CDATA[查看帮助]]></Description>
                                        <PicUrl><![CDATA[http://test.720hebei.com/Public/Images/autorepay.jpg]]></PicUrl>
                                            <Url><![CDATA[http://mp.weixin.qq.com/s?__biz=MzI0ODEzNzI1OA==&mid=403691667&idx=1&sn=b73b73ddc9db1e64b11a3842a7707894&scene=23&srcid=05233l8JWOAeO55MtPP62nk4#rd]]></Url>
                                    </item>
                                    </Articles>
                                </xml>";
                            echo $resultStr;
                        }
                    }
                }
            }
        }
    }
    public function DoPostMsg($postObj){
        $d['Data']=$postObj;
        $postObj=json_decode($postObj,'true');
        $d['ToUserName']=$postObj['ToUserName'];
        $d['FromUserName']=$postObj['FromUserName'];
        $d['CreateTime']=$postObj['CreateTime'];
        $d['MsgType']=$postObj['MsgType'];
        $d['Content']=$postObj['Content'];
        $d['MediaId']=$postObj['MediaId'];
        $d['Recognition']=$postObj['Recognition'];
        $d['PicUrl']=$postObj['PicUrl'];
        $d['ThumbMediaId']=$postObj['ThumbMediaId'];
        $d['Format']=$postObj['Format'];
        $d['MsgId']=$postObj['MsgId'];
        $d['time']=date("Y-m-d H:i:s");
        $ret=M("msg_content")->add($d);
        if($ret){
            if($postObj['MsgType']=="shortvideo"||1==1){
                $resultStr = "<xml>
                                    <ToUserName><![CDATA[".$postObj['FromUserName']."]]></ToUserName>
                                    <FromUserName><![CDATA[".$postObj['ToUserName']."]]></FromUserName>
                                    <CreateTime>".time()."</CreateTime>
                                    <MsgType><![CDATA[news]]></MsgType>
                                    <ArticleCount>1</ArticleCount>
                                    <Articles>
                                    <item>
                                        <Title><![CDATA[感谢您关注呵贝]]></Title>
                                        <Description><![CDATA[查看帮助]]></Description>
                                        <PicUrl><![CDATA[http://test.720hebei.com/Public/Images/autorepay.jpg]]></PicUrl>
                                            <Url><![CDATA[https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx28c049b5d5ce3819&redirect_uri=http%3a%2f%2ftest.720hebei.com%2fAdmin%2fVideoReply%2findex%2fmedia_id%2f".$ret."&response_type=code&scope=snsapi_base&state=123#wechat_redirect]]></Url>
                                    </item>
                                    </Articles>
                                </xml>";
                echo $resultStr;
                die;
            }

        }
    }
}
?>