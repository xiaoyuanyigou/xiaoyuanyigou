<?php
	namespace Home\Controller;
	use Think\Controller;
	header("content-type:text/html;charset=utf-8");
	class WxApiController extends Controller{
		/**
         * ===============微信支付的接口===================
         * @param string $body             商品描述
         * @param int $total_fee           订单总额
         * @param string $nonceStr         权限验证配置产生的随机字符串
         * @param int $timeStamp           权限验证配置产生的时间戳
         * ====================================================
         * 
         *=====================================================
         */
		public function wxPayApi($body,$total_fee,$openid,$out_trade_no,$noncestr,$timestamp){
			/*1、设置统一下单参数*/
			$values=array(
                    "appid"             =>C('appid'),
					"attach"			=>"校园易购",
					"body"				=>$body,
                    "mch_id"            =>C('mch_id'),
                    "nonce_str"         =>$this->createNonceStr(),
                    "notify_url"        =>C('notify_url'),
                    "openid"            =>$openid,
					"out_trade_no"		=>$out_trade_no,
					"spbill_create_ip"  =>$_SERVER['REMOTE_ADDR'],
                    "total_fee"         =>$total_fee,
					"trade_type"		=>C('trade_type')
				);
			/*第一次设置签名*/
			$sign = $this->SetSign($values);
			/*生成xml格式*/
            $xml  = '<xml>
                       <appid><![CDATA['.$values['appid'].']]></appid>
                       <attach><![CDATA['.$values['attach'].']]></attach> 
                       <body><![CDATA['.$values['body'].']]></body> 
                       <mch_id><![CDATA['.$values['mch_id'].']]></mch_id> 
                       <nonce_str><![CDATA['.$values['nonce_str'].']]></nonce_str>  
                       <notify_url><![CDATA['.$values['notify_url'].']]></notify_url>  
                       <openid><![CDATA['.$values['openid'].']]></openid>  
                       <out_trade_no><![CDATA['.$values['out_trade_no'].']]></out_trade_no> 
                       <spbill_create_ip><![CDATA['.$values['spbill_create_ip'].']]></spbill_create_ip> 
                       <total_fee><![CDATA['.$values['total_fee'].']]></total_fee>  
                       <trade_type><![CDATA['.$values['trade_type'].']]></trade_type>  
                       <sign><![CDATA['.$sign.']]></sign> 
                    </xml>';
            /*2、调用统一下单接口*/
            $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
            $response=$this->postXmlCurl($xml, $url, $useCert = false, $second = 10);
            /*将结果转化为数组*/
            $responseRet=$this->Init($response);
            /*得到预下单ID*/
            $prepay_id=$responseRet['prepay_id'];
            /*3、第二大步发起微信支付*/
            $SignPrePay="prepay_id=".$prepay_id;
            $pay=array(
                "package"=>$SignPrePay,
                "appId"=>C('appid'),
                "nonceStr"=>$noncestr,//这个随机字符串必须是注入权限配置产生的字符串
                "signType"=>'MD5',
                "timeStamp"=>$timestamp//这个随机字符串必须是注入权限配置产生的时间戳
            );
            //第二次签名
            $paySign=$this->SetSign($pay);
            $ret=array(
                "prepay_id"=>$prepay_id,
                "paySign"=>$paySign
                );
            return $ret;
		}
		/**
         * ===============微信退款的接口===================
         * @param string $out_trade_no    需要退款的商户订单号
         * @param int $total_fee          订单总额
         * @param int $refund_fee         退款金额
         */
        public function WxRefund($out_trade_no,$total_fee,$refund_fee){
        	//设置退款接口参数
        	$refund=array(
        			"out_trade_no"		=>$out_trade_no,//商户侧传给微信的订单号、商户自己生成的订单号(微信支付时产生的)
        			"appid"				=>C('appid'),//公众号的appID
        			"mch_id"			=>C('mch_id'),//商户平台的商户号
        			"nonce_str"			=>$this->createNonceStr(),//随机字符串
        			"op_user_id"		=>C('mch_id'),//操作员帐号, 默认为商户号
        			"out_refund_no"		=>'0'.C('mch_id').date("YmdHis"),//商户系统内部的退款单号，商户系统内部唯一
        			"refund_fee"		=>$refund_fee,//退款金额
        			"total_fee"			=>$total_fee,//订单总额
        			"refund_account"	=>"REFUND_SOURCE_RECHARGE_FUNDS"//退款资金来源、这里使用可用余额
        		);
        	//二、生成签名
            $sign=$this->SetSign($refund);
            //生成xml数据
            $xml='<xml>
                   <appid><![CDATA['.$refund['appid'].']]></appid>
                   <mch_id><![CDATA['.$refund['mch_id'].']]></mch_id>
                   <nonce_str><![CDATA['.$refund['nonce_str'].']]></nonce_str>
                   <op_user_id><![CDATA['.$refund['op_user_id'].']]></op_user_id>
                   <out_refund_no><![CDATA['.$refund['out_refund_no'].']]></out_refund_no>
                   <out_trade_no><![CDATA['.$refund['out_trade_no'].']]></out_trade_no>
                   <refund_account><![CDATA['.$refund['refund_account'].']]></refund_account>
                   <refund_fee><![CDATA['.$refund['refund_fee'].']]></refund_fee>
                   <total_fee><![CDATA['.$refund['total_fee'].']]></total_fee>
                   <sign><![CDATA['.$sign.']]></sign>
                  </xml>';
            //发送xml数据,调用退款接口
            $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
            $result=$this->postXmlCurl($xml, $url, true, $second = 6);
            $respondRet=$this->Init($result);
            //将微信传回来的退款结果直接返回
            return $respondRet;
        }
        /**
         * 微信企业付款的接口（提现）
         * @param string $openid            微信用户的openID
         * @param int $amount               提现总额（单位为分）
         */
        public function withDrawals($openid,$amount){
            //设置提现接口参数
            $values=array(
            		"mch_appid"			=>C('appid'),
            		"mchid"				=>C('mch_id'),
            		"nonce_str"			=>$this->createNonceStr(),
            		"partner_trade_no"	=>C('mch_id').date("YmdHis"),
            		"openid"			=>$openid,
            		"check_name"		=>"NO_CHECK",//不检验是否实名
            		"amount"			=>$amount,
            		"desc"				=>"校园易购网商家提现",
            		"spbill_create_ip"	=>$_SERVER['REMOTE_ADDR']
            	);
            //签名
            $sign=$this->SetSign($values);
            //拼接成xml
            $xml = '<xml>
                        <amount><![CDATA['.$values['amount'].']]></amount> 
                        <check_name><![CDATA['.$values['check_name'].']]></check_name> 
                        <desc><![CDATA['.$values['desc'].']]></desc> 
                        <mch_appid><![CDATA['.$values['mch_appid'].']]></mch_appid>
                        <mchid><![CDATA['.$values['mchid'].']]></mchid>
                        <nonce_str><![CDATA['.$values['nonce_str'].']]></nonce_str>  
                        <openid><![CDATA['.$values['openid'].']]></openid>  
                        <partner_trade_no><![CDATA['.$values['partner_trade_no'].']]></partner_trade_no>
                        <spbill_create_ip><![CDATA['.$values['spbill_create_ip'].']]></spbill_create_ip>  
                        <sign><![CDATA['.$sign.']]></sign> 
                    </xml>';
            $url="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
            $result=$this->postXmlCurl($xml, $url, true, $second = 6);
            $returnArray=$this->Init($result);
            //直接赶回微信返回的数据
            return $returnArray;
        }
        /**
         * ===============微信扫码支付的接口(模式二)===================
         * @param string $body             商品描述
         * @param int $total_fee           订单总额
         */
        public function NativePay_2($total_fee,$body){
            $values['body'] = $body;
            $values['attach'] = "江军";
            $values['out_trade_no'] = C('mch_id').date("YmdHis");
            $values['total_fee'] = $total_fee;
            $values['time_start'] = date("YmdHis");
            $values['time_expire'] = date("YmdHis", time() + 600);
            $values['goods_tag'] = "jay";
            $values['notify_url'] = C('native_notify_url');//扫码的回调地址
            $values['trade_type'] = "NATIVE";
            $values['product_id'] = "2016";
            $values['appid'] = C("appid");
            $values['mch_id'] = C("MCHID");
            $values['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
            $values['nonce_str'] = $this->createNonceStr();
            //调用统一下单接口
            //检测必填参数
            if(!isset($values['out_trade_no'])) {
                echo "缺少统一支付接口必填参数out_trade_no！";
            }else if(!isset($values['body'])){
                echo "缺少统一支付接口必填参数body！";
            }else if(!isset($values['total_fee'])) {
                echo "缺少统一支付接口必填参数total_fee！";
            }else if(!isset($values['trade_type'])) {
                echo "缺少统一支付接口必填参数trade_type！";
            }else if(!isset($values['product_id'])){
                echo "统一支付接口中，缺少必填参数product_id！";
            }
            //签名
            $sign = $this->SetSign($values);
            //组成xml数据
            $xml =   '<xml>
                       <appid><![CDATA['.$values['appid'].']]></appid>
                       <attach><![CDATA['.$values['attach'].']]></attach> 
                       <body><![CDATA['.$values['body'].']]></body> 
                       <goods_tag><![CDATA['.$values['goods_tag'].']]></goods_tag>
                       <mch_id><![CDATA['.$values['mch_id'].']]></mch_id> 
                       <nonce_str><![CDATA['.$values['nonce_str'].']]></nonce_str>  
                       <notify_url><![CDATA['.$values['notify_url'].']]></notify_url> 
                       <out_trade_no><![CDATA['.$values['out_trade_no'].']]></out_trade_no> 
                       <product_id><![CDATA['.$values['product_id'].']]></product_id>
                       <spbill_create_ip><![CDATA['.$values['spbill_create_ip'].']]></spbill_create_ip> 
                       <time_expire><![CDATA['.$values['time_expire'].']]></time_expire>
                       <time_start><![CDATA['.$values['time_start'].']]></time_start>
                       <total_fee><![CDATA['.$values['total_fee'].']]></total_fee>  
                       <trade_type><![CDATA['.$values['trade_type'].']]></trade_type>  
                       <sign><![CDATA['.$sign.']]></sign> 
                    </xml>';
            $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
            $ret = $this->postXmlCurl($xml,$url,false,$second = 6);  
            $result = $this->Init($ret);      
            $url = urlencode($result["code_url"]);
            return $url;
        }

         /**
         * ===============微信刷卡支付的接口===================
         * @param string $body             商品描述
         * @param string $auth_code        微信上的授权序号
         * @param int $total_fee           订单总额
         */
        public function Micropay($auth_code,$body,$total_fee){
            //设置一些参数
            $micropay['auth_code']=$auth_code;
            $micropay['body']=$body;
            $micropay['total_fee']=$total_fee;
            $micropay['out_trade_no']=C('mch_id').date("YmdHis");
            $micropay['spbill_create_ip']=$_SERVER['REMOTE_ADDR'];//终端ip
            $micropay['appid']=C('appid');
            $micropay['mch_id']=C('mch_id');
            $micropay['nonce_str']=$this->createNonceStr();
            //提交被扫支付
            if(!isset($micropay['body'])) {
                echo "提交被扫支付API接口中，缺少必填参数body！";
            }else if(!isset($micropay['out_trade_no'])) {
                echo "提交被扫支付API接口中，缺少必填参数out_trade_no！";
            }else if(!isset($micropay['total_fee'])) {
                echo "提交被扫支付API接口中，缺少必填参数total_fee！";
            }else if(!isset($micropay['auth_code'])) {
                echo "提交被扫支付API接口中，缺少必填参数auth_code！";
            }
            //签名
            $sign=$this->SetSign($micropay);
            $xml='<xml>
                   <appid><![CDATA['.$micropay['appid'].']]></appid>
                   <auth_code><![CDATA['.$micropay['auth_code'].']]></auth_code>
                   <body><![CDATA['. $micropay['body'].']]></body>
                   <mch_id><![CDATA['.$micropay['mch_id'].']]></mch_id>
                   <nonce_str><![CDATA['.$micropay['nonce_str'].']]></nonce_str>
                   <out_trade_no><![CDATA['.$micropay['out_trade_no'].']]></out_trade_no>
                   <sign><![CDATA['.$sign.']]></sign>
                   <spbill_create_ip><![CDATA['.$micropay['spbill_create_ip'].']]></spbill_create_ip>
                   <total_fee><![CDATA['.$micropay['total_fee'].']]></total_fee>
                  </xml>';
            //调用刷卡支付接口
            $url = "https://api.mch.weixin.qq.com/pay/micropay";
            $result=$this->postXmlCurl($xml, $url, false, $second = 10);
            $respondRet=$this->Init($result);
            //如果判断返回的数据
            if(!array_key_exists("return_code", $respondRet)||!array_key_exists("out_trade_no", $respondRet)||!array_key_exists("result_code", $respondRet)){
                echo "接口调用失败,请确认是否输入是否有误！";
            }
            //签名验证
            $out_trade_no=$micropay['out_trade_no'];
            //接口调用成功，明确返回调用失败
            if($result["return_code"] == "SUCCESS" && 
               $result["result_code"] == "FAIL" && 
               $result["err_code"] != "USERPAYING" && 
               $result["err_code"] != "SYSTEMERROR"){
                return false;
            }
            //③、确认支付是否成功
            $queryTimes = 10;
            while($queryTimes > 0){
                $succResult = 0;
                $queryResult = $this->query($out_trade_no, $succResult);
                //如果需要等待1s后继续
                if($succResult == 2){
                    sleep(2);
                    continue;
                } else if($succResult == 1){//查询成功
                    echo $queryResult;
                } else {//订单交易失败
                    return false;
                }
            }
        }
         
        /**
         * ===============下面都是接口用到的公共函数，都放在一个文件里面了
         * ===============下面都是接口用到的公共函数，都放在一个文件里面了
         */
      



		/**
         * =================微信支付通过config接口注入权限验证配置===================
         * @param string $appId            在配置文件里定义
         * 
         */
        public function wxPayConfig(){
            ini_set('date.timezone','Asia/Shanghai');
            //生成时间戳
            $timeStamp=time();
            $appId=C('appid');
            //生成16位随机字符串
            $nonceStr=$this->createNonceStr();
            //获得jsapi_ticket
            $jsapi_ticket=$this->getJsapiTicket();
            if(!$jsapi_ticket){
                return false;
            }
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            //对所有待签名参数按照字段名的ASCII 码从小到大排序（字典序）
            $string='jsapi_ticket='.$jsapi_ticket.'&noncestr='.$nonceStr.'&timestamp='.$timeStamp.'&url='.$url;
            $signature=sha1($string);
            $data=array(
                "noncestr"=>$nonceStr,
                "signature"=>$signature,
                "timestamp"=>$timeStamp,
                "appid"=>$appId
                );
            return $data;
        }

        //生成随机字符串（默认16位）
        public function createNonceStr($length=16){
            $chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $str="";
            for ($i=0;$i<$length;$i++) {
              $str.=substr($chars,mt_rand(0, strlen($chars)-1),1);
            }
            return $str;
        }
        /**
         *=============获取jsapi_ticket================
         *从数据库里获取jsapi_ticket，也可以使用其他方法
         */
	    public function getJsapiTicket(){
	        $res=M("weixin")->field("jsapi_ticket,jsapi_ticket_upd_time")->find();
            if($res===false){
                return false;
            }
	        $ticket_upime=strtotime($res['jsapi_ticket_upd_time']);
	        if((time()-$ticket_upime)<7200){
	            return $res['jsapi_ticket'];
	        }else{
	            $access_token=$this->getAccessToken();
                if(!$access_token){
                    return false;
                }
	            $result = file_get_contents('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi');
	            $result = json_decode($result, true);
	            //把最新的jsapi_ticket和更新时间存进数据库
	            $update['jsapi_ticket']=$result['ticket'];
	            $update['jsapi_ticket_upd_time']=date('Y-m-d H:i:s',time());
                //这里出错可以不用处理，下次就是重新获取
	            M("weixin")->where("id=1")->save($update);
	            return $result['ticket'];
	        }
	    }
	    /**
         *=============获取access_token================
         *从数据库里获取access_token，也可以使用其他方法
         */
	    public function getAccessToken(){
	        $res=M("weixin")->field("access_token_upd_time,access_token,app_id,app_secret")->find(1);
            if(empty($res)){
                return false;
            }
	        $access_uptime=strtotime($res['access_token_upd_time']);
	        if((time()-$access_uptime)<7200){
	            //access_token没有过期，直接从数据库里获取
	            return $res['access_token'];
	        }else{
	            //access_token过期，重新获取
	            $result = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$res['app_id'].'&secret='.$res['app_secret']);
	            $result = json_decode($result, true);
                if(empty($result['access_token'])){
                    return false;
                }
	            //将获取的access_token和更新时间存到数据库
	            $update['access_token_upd_time']=date('Y-m-d H:i:s',time());
	            $update['access_token']=$result['access_token'];
                //这里出错可以不用处理，下次就是重新获取
	            M("weixin")->where("id=1")->save($update);
	            return $result['access_token'];
	        }
	    }
	    //签名方法
        public function SetSign($array){
            $sign = $this->MakeSign($array);
            return $sign;
        }
        /**
         * 生成签名
         * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
         */
        public function MakeSign($array){
            //签名步骤一：按字典序排序参数
            ksort($array);
            $string = $this->ToUrlParams($array);
            //签名步骤二：在string后加入KEY
            $string = $string . "&key=".C('key');
            //签名步骤三：MD5加密
            $string = md5($string);
            //签名步骤四：所有字符转为大写
            $result = strtoupper($string);
            return $result;
        }
         /**
         * 格式化参数格式化成url参数
         */
        public function ToUrlParams($array){
            $buff = "";
            foreach ($array as $k => $v)
            {
                if($k != "sign" && $v != "" && !is_array($v)){
                    $buff .= $k . "=" . $v . "&";
                }
            }
            $buff = trim($buff, "&");
            return $buff;
        }
        /**
         * 以post方式提交xml到对应的接口url
         * 
         * @param string $xml  需要post的xml数据
         * @param string $url  url
         * @param bool $useCert 是否需要证书，默认不需要
         * @param int $second   url执行超时时间，默认30s
         * @throws WxPayException
         */
        public static function postXmlCurl($xml, $url, $useCert = false, $second = 30){       
            $ch = curl_init();
            //设置超时
            curl_setopt($ch, CURLOPT_TIMEOUT, $second);
            // //如果有配置代理这里就设置代理
            // if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0" 
            //     && WxPayConfig::CURL_PROXY_PORT != 0){
            //     curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
            //     curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
            // }
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
            //设置header
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            if($useCert == true){
                //设置证书
                //使用证书：cert 与 key 分别属于两个.pem文件
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLCERT,APP_PATH.'cert/apiclient_cert.pem');
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLKEY,APP_PATH.'cert/apiclient_key.pem');
            }
            //post提交方式
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            //运行curl
            $data = curl_exec($ch);
            //返回结果
            if($data){
                curl_close($ch);
                return $data;
            } else { 
                $error = curl_errno($ch);
                curl_close($ch);
                echo "curl出错，错误码:$error";
            }
        }
        /**
         * 将xml转为array
         * @param string $xml
         * @throws WxPayException
         */
        public function Init($xml){
            if(!$xml){
                echo "xml数据异常！";
            }
            //将XML转为array
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $resultArray=json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            return $resultArray;
        }
        /**
         * 
         * 查询订单情况
         * @param string $out_trade_no  商户订单号
         * @param int $succCode         查询订单结果
         * @return 0 订单不成功，1表示订单成功，2表示继续等待
         */
        public function query($the_out_trade_no, &$succCode){
            $out_trade_no=$the_out_trade_no;
            $result = $this->orderQuery($out_trade_no);
            
            if($result["return_code"] == "SUCCESS" 
                && $result["result_code"] == "SUCCESS")
            {
                //支付成功
                if($result["trade_state"] == "SUCCESS"){
                    $succCode = 1;
                    return $result;
                }
                //用户支付中
                else if($result["trade_state"] == "USERPAYING"){
                    $succCode = 2;
                    return false;
                }
            }
            
            //如果返回错误码为“此交易订单号不存在”则直接认定失败
            if($result["err_code"] == "ORDERNOTEXIST")
            {
                $succCode = 0;
            } else{
                //如果是系统错误，则后续继续
                $succCode = 2;
            }
            return false;
        }
        /**
         * 
         * 查询订单，WxPayOrderQuery中out_trade_no、transaction_id至少填一个
         * appid、mchid、spbill_create_ip、nonce_str不需要填入
         * @param int $timeOut
         * @throws WxPayException
         * @return 成功时返回，其他抛异常
         */
        public function orderQuery($the_out_trade_no,$timeOut = 6)
        {
            $url = "https://api.mch.weixin.qq.com/pay/orderquery";
            //检测必填参数
            $orderquery['out_trade_no']=$the_out_trade_no;
            if(!isset($orderquery['out_trade_no'])&& !isset($transaction_id)){
                echo "订单查询接口中，out_trade_no、transaction_id至少填一个！";
            }
            $orderquery['appid']=C('appid');//公众账号ID
            $orderquery['mch_id']=C('mch_id');//商户号
            $orderquery['nonce_str']=$this->createNonceStr();//随机字符串
            $sign=$this->SetSign($orderquery);//签名
            $xml = '<xml>
                       <appid><![CDATA['.$orderquery['appid'].']]></appid>
                       <mch_id><![CDATA['.$orderquery['mch_id'].']]></mch_id>
                       <nonce_str><![CDATA['.$orderquery['nonce_str'].']]></nonce_str>
                       <out_trade_no><![CDATA['.$orderquery['out_trade_no'].']]></out_trade_no>
                       <sign><![CDATA['.$sign.']]></sign>
                    </xml>';
            $response = $this->postXmlCurl($xml, $url, false, $timeOut);
            $result = $this->Init($response);
            return $result;
        }
        //将URL转化为微信的URL
        public function ReplaceUrl($par){
            $app=M("weixin")->find(1);
            $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app['app_id'].'&redirect_uri=';
            $url.=urlencode('http://mxt.yxttx.cn/home.php'.$par);
            $url.='&response_type=code&scope=snsapi_base&state=STATE&connect_redirect=1#wechat_redirect';
            return $url;
        }
	}
?>