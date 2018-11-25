<?php

    /**
     * 
     * 上传图片代码部分
     * @param $targetDir 临时文件目录 uploadDir 上传文件目录
     * @return $uploadPath 访问图片的url
     */
	function upload($targetDir,$uploadDir){
		// $targetDir = 'public/upload_tmp';
		// $uploadDir = 'public/admin_upload';

		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// Create target dir
		if (!file_exists($targetDir)) {
		    @mkdir($targetDir,0777,true);
		}
		// Create target dir
		if (!file_exists($uploadDir)) {
		    @mkdir($uploadDir,0777,true);
		}
		// Get a file name
		if (isset($_REQUEST["name"])) {
			//正常进入这里执行
		    $fileName = $_REQUEST["name"];
		} elseif (!empty($_FILES)) {
			
		    $fileName = $_FILES["file"]["name"];
		} else {
		    $fileName = uniqid("file_");	
		}	
		//-------------------------------------------------------
		//无论是什么文件名称，先unicode转utf8
		//unicode转utf8
		//注意$str='"..."'，内部双引号，外部单引号
		//对于变量$str='..'，我们需要处理'"'.$str.'"',处理后成一个新变量
		function unicode2utf8($str){
			if(!$str)
				return $str;
			$decode = json_decode($str);
			if($decode) 
				return $decode;
			$str = '["' . $str . '"]';
			$decode = json_decode($str);
			if(count($decode) == 1){
				return $decode[0];
			}
			return $str;
		}
		$randomStr = createNonceStr(8);
		$fileName = $randomStr.$fileName;
		
		$fileName0 =unicode2utf8('"'.$fileName.'"');
		$fileName1 = iconv("UTF-8", "GBK", $fileName0);//防止fopen语句失效
		
		//$fileName1=unicode2utf8('"'.$fileName.'"');
		//----------------------------------------------------------------------
		// php的内置常量DIRECTORY_SEPARATOR是一个显示系统分隔符的命令，DIRECTORY_SEPARATOR是php的内部常量，不需要任何定义与包含即可直接使用。linux下相当于 '/'，Windows相当于'/'或者'\'
		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName1;
		$uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName0;
		$uploadPath0 = $uploadDir . DIRECTORY_SEPARATOR . $fileName0;

		// Chunking might be enabled
		// 分片操作，从js中获取request值，查看是否设置了
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;

		// Remove old temp files
		if ($cleanupTargetDir) {
		    if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
		    //临时文件目录无法打开。
		        die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
		    }

		    while (($file = readdir($dir)) !== false) {
		    	//总是会执行两次？？？？？？？？？？？、
		        $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;	//临时文件名
		        // If temp file is current file proceed to the next
		        if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
		            continue;
		        }
		       
		        // Remove temp file if it is older than the max age and is not the current file
		        if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
		            @unlink($tmpfilePath);
		        }
		    }
		    closedir($dir);
		}

		// Open temp file
		if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
			//打开临时文件失败
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}

		if (!empty($_FILES)) {
			//
			if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			}

			// Read binary input stream and append it to temp file
			if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			}
		}
		else {
			if (!$in = @fopen("php://input", "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			}
		}

		//将文件从临时目录转移到upload
		while ($buff = fread($in, 4096)) {
			fwrite($out, $buff);
		}

		@fclose($out);
		@fclose($in);

		rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");

		$index = 0;
		$done = true;
		for( $index = 0; $index < $chunks; $index++ ) {
			if ( !file_exists("{$filePath}_{$index}.part") ) {
				$done = false;
				break;
			}
		}

		//echo $uploadPath;		//这个路径就可以直接存储到数据库中。
		if ( $done ) {
			if (!$out = @fopen($uploadPath, "wb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			}

			if ( flock($out, LOCK_EX) ) {
				for( $index = 0; $index < $chunks; $index++ ) {
					if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) 
						break;
					while ($buff = fread($in, 4096)) {
						fwrite($out, $buff);
					}

					@fclose($in);
					@unlink("{$filePath}_{$index}.part");
				}

				flock($out, LOCK_UN);
			}
			@fclose($out);
		}

		return DIRECTORY_SEPARATOR.$uploadPath0;
		//我们可以在这里进行入库操作，以json_encode(数组)返回给客户端，由uploadSuccess事件处理
		// Return Success JSON-RPC response
		//die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
	}//end upload

	// 检验验证码
	function check_verify($code, $id = '1'){   //标示id=1，则验证对应的验证码1
		 $verify = new \Think\Verify();    
		 return $verify->check($code, $id);
	}