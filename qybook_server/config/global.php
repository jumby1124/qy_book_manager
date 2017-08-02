<?php

	function getPostValue()
	{
		if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])){
		    $GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
		}
		if (is_null($GLOBALS['HTTP_RAW_POST_DATA']) || $GLOBALS['HTTP_RAW_POST_DATA'] == ""){
			if (isset($_POST['json'])){
				$GLOBALS['HTTP_RAW_POST_DATA'] = $_POST['json'] ;
			}
		}
		
		$GLOBALS['PARAM_CONTENT_DATA'] = $GLOBALS['HTTP_RAW_POST_DATA'] ;		
		$arr = json_decode($GLOBALS['HTTP_RAW_POST_DATA']) ;				

		if (is_null($arr))		$cmd = "" ;
		else					$cmd = get_jsonValue($arr, "cmd") ;
		if (is_null($arr) || isset($arr->content) == false)  $value = null ;
		else 					$value = get_jsonValue($arr, "content") ;		
		return array($cmd , $value , getRealIp()) ;
	}
	function getRealIp()
	{
		$ip=false;
  		if(!empty($_SERVER["HTTP_CLIENT_IP"])){
    		$ip = $_SERVER["HTTP_CLIENT_IP"];
  		}
  		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    		$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
	    	if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
	    	for ($i = 0; $i < count($ips); $i++) {
	      		if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
	        		$ip = $ips[$i];
	        		break;
	      		}
	    	}
	  	}
  		return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
	}
	// 返回upload接收的文件 ,返回分别为: 文件内容 , 文件名 , 参数
	function getFileValue()
	{
		$param = "" ;
		$fileName = "" ;
		$file = "" ;
		foreach($_FILES as $k=>$v)
		{
			if (is_array($v)){
				$param = $k ;
				$fileName = $v["name"];
				$file = $v["tmp_name"];
				break ;
			}
		}
		$rnt = array() ;
		if ($param != "" && $fileName != ""){
			$rnt = explode(",",$param);
		}
		return array($file , $fileName , $rnt) ;
	}
	function show_json($json)
	{
		$rn = "" ;
		if (gettype($json) == "object"){
			foreach($json as $k=>$v){
				if (gettype($v) == "object"){
					$rn .= $k . " = " . "{" . show_json($v) . "} , ";
				}else{
					$rn .= $k . " = " . $v . " , ";
				}
			}
		}
		return $rn ;
	}

	function get_jsonValue($json,$key)
	{
		if (isset($json->$key))
			return $json->$key ;
		return "" ;
	}
	function json_encode_utf8($arr) {		
		arrayRecursive($arr, 'urlencode', true);
 		$json = json_encode($arr);
 		return urldecode($json);
	}
	function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
	{
	    foreach ($array as $key => $value)
	    {
	        if (is_array($value)) {
	            arrayRecursive($array[$key], $function, $apply_to_keys_also);
	        } else {
	            $array[$key] = $function($value);
	        }
	 
	        if ($apply_to_keys_also && is_string($key)) {
	            $new_key = $function($key);
	            if ($new_key != $key) {
	                $array[$new_key] = $array[$key];
	                unset($array[$key]);
	            }
	        }
	    }
	}

	// 获取异常信息的详细信息.
	function exceptionToString(Exception $e, $verbose = FALSE)
    {
        if ($e instanceof PHPUnit_Framework_SelfDescribing) {
            if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
                $comparisonFailure = $e->getComparisonFailure();
                $description       = $e->getDescription();
                $message           = $e->getCustomMessage();

                if ($message == '') {
                    $buffer = '';
                } else {
                    $buffer = $message . "\n";
                }

                if ($comparisonFailure !== NULL) {
                    if ($comparisonFailure->identical()) {
                        if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_Object) {
                            $buffer .= "Failed asserting that two variables reference the same object.\n";
                        } else {
                            $buffer .= $comparisonFailure->toString() . "\n";
                        }
                    } else {
                        if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_Scalar) {
                            $buffer .= sprintf(
                              "Failed asserting that %s matches expected value %s.\n",

                              PHPUnit_Util_Type::toString($comparisonFailure->getActual()),
                              PHPUnit_Util_Type::toString($comparisonFailure->getExpected())
                            );
                        }

                        else if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_Array ||
                                 $comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_Object ||
                                 $comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_String) {
                            $buffer .= sprintf(
                              "Failed asserting that two %ss are equal.\n%s\n",

                              strtolower(substr(get_class($comparisonFailure), 36)),
                              $comparisonFailure->toString()
                            );
                        }

                        if ($verbose &&
                           !$comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_Array &&
                           !$comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_Object &&
                           !$comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure_String) {
                            $buffer .= $comparisonFailure->toString() . "\n";
                        }
                    }
                } else {
                    $buffer .= $e->toString();
                    $equal   = $buffer == $description;

                    if (!empty($buffer)) {
                        $buffer .= "\n";
                    }

                    if (!$equal) {
                        $buffer .= $description . "\n";
                    }
                }
            }

            else {
                $buffer = $e->toString();

                if (!empty($buffer)) {
                    $buffer .= "\n";
                }
            }
        }

        else if ($e instanceof PHPUnit_Framework_Error) {
            $buffer = $e->getMessage() . "\n";
        }

        else {
            $buffer = get_class($e) . ': ' . $e->getMessage() . "\n";
        }

        return $buffer;
    }
    // 检测文件目录是否存在,如果不存在,创建
	function make_dir($folder)
	{
		$reval = false;

    	if (!file_exists($folder))
		{
			/* 如果目录不存在则尝试创建该目录 */
			@umask(0);
			$atmp = array();
        	/* 将目录路径拆分成数组 */
			preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

        	/* 如果第一个字符为/则当作物理路径处理 */
			$base = ($atmp[0][0] == '/') ? '/' : '';
	        /* 遍历包含路径信息的数组 */
			foreach ($atmp[1] AS $val)
			{
				if ('' != $val)
				{
					$base .= $val;
				
			        if ('..' == $val || '.' == $val)
					{
						/* 如果目录为.或者..则直接补/继续下一个循环 */
						$base .= '/';
						continue;
					}
				}
				else
				{
					continue;
				}
           	 	$base .= '/';
            	if (!file_exists($base))
				{
					/* 尝试创建目录，如果创建失败则继续循环 */
					if (@mkdir(rtrim($base, '/'), 0777))
					{
					@chmod($base, 0777);
					$reval = true;
					}
				}
			}
		}
		else
		{
			/* 路径已经存在。返回该路径是不是一个目录 */
			$reval = is_dir($folder);
		}
    	clearstatcache();
    	return $reval;
	}
	
	// 查找字符串最后出现的位置
	function strpos_last($source , $find)
	{
		$first = 0 ;
		$rnpos = -1 ;
		while(true){
			$pos = strpos($source , $find , $first) ;
			if ($pos === false){
				return $rnpos ;
			}
			$rnpos = $pos ;
			$first = $rnpos + strlen($find) ;
		}
	}
	function getPath_dir($filepath)
	{
		return substr($filepath , 0 , strpos_last($filepath , "/") + 1) ;
	}
	function getPath_name($filepath)
	{
		return substr($filepath , strpos_last($filepath , "/") + 1) ;
	}
	// 图片缩小/放大后另存为保存
	function image_zoom_save($source_path , $zoom , $save_path , $save_width = 0)
	{
		make_dir(getPath_dir($source_path)) ;
		
		$rnt = getimagesize($source_path) ;
		if ($rnt['mime'] == "image/png"){
			$src_image = imagecreatefrompng($source_path) ;			
			imagesavealpha($src_image,true);			// 不要丢了$thumb图像的透明色;
		}else if ($rnt['mime'] == "image/jpeg" || $rnt['mime'] == "image/jpg"){
			$src_image = imagecreatefromjpeg($source_path) ;
		}
		if ($zoom == 0 && $save_width != 0){
			$zoom = $save_width / $rnt[0] ;
		}		
		$dst_image = imagecreatetruecolor($rnt[0] * $zoom , $rnt[1] * $zoom) ;
		imagealphablending($dst_image,false); 		// 不合并颜色,直接用其他图像颜色替换,包括透明色;
		imagesavealpha($dst_image,true);			// 不要丢了$thumb图像的透明色;
		
		// bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
		//$dst_image：新建的图片
		//$src_image：需要载入的图片
		$dst_x = 0 ;				// 设定需要载入的图片在新图中的x坐标
		$dst_y = 0 ;				// 设定需要载入的图片在新图中的y坐标
		$src_x = 0 ;				// 设定载入图片要载入的区域x坐标
		$src_y = 0 ;				// 设定载入图片要载入的区域y坐标
		$dst_w = $rnt[0] * $zoom ; 	// 设定载入的原图的宽度（在此设置缩放）
		$dst_h = $rnt[1] * $zoom ; 	// 设定载入的原图的高度（在此设置缩放）
		$src_w = $rnt[0] ;			// 原图要载入的宽度
		$src_h = $rnt[1] ;			// 原图要载入的高度
		
		imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) ;
		
		make_dir(getPath_dir($save_path)) ;
		imagejpeg($dst_image , $save_path , 100) ;
		
		unset($dst_image) ;
		unset($src_image) ;
		return array($dst_w , $dst_h) ;
	}
	function saveFile($fileName, $text) {
		if (!$fileName || !$text)
			return false;
		make_dir(getPath_dir($fileName)) ;
		         
        if ($fp = fopen($fileName, "w")) {
        	if (@fwrite($fp, $text)) {
            	fclose($fp);
                return true;
            } else {
            	fclose($fp);
                return false;
            } 
        }
     	return false;
     } 
		
	// 模拟post进行url请求  拼接url封装起来
	function request_post($url , $post_data)
	{
		if (empty($url) || empty($post_data)) {
			return false;
		}

		$ch = curl_init();//初始化curl
		curl_setopt($ch, CURLOPT_URL,$url);//抓取指定网页
		curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, getParamAuto($post_data));
		$data = curl_exec($ch);//运行curl
		curl_close($ch);
	
		return $data ;		
	}
	// 通过post向网络发送数据
	function post_net_data($str_text){
		$result = false ;
		try{
			$n1 = strpos($str_text , '?') ;
			$n2 = strpos($str_text , '//') ;
			if ($n1 <= 0)	return false ;
			if ($n2 <= 0)	return false ;
			$params = substr($str_text , $n1 + 1) ;
			$ip_str = substr($str_text , $n2 + 2 , $n1 - $n2 - 2) ;
			$n = strpos($ip_str,'/') ;
			$model = substr($ip_str , $n + 1) ;
			$ip_str = substr($ip_str , 0 , $n) ;	
			$n1 = strpos($ip_str , ':') ;
			if ($n1 > 0){
				$ip   = substr($ip_str , 0 , $n1) ;
				$pos  = substr($ip_str , $n1 + 1) ;
			}else{
				$ip  = $ip_str ;
				$pos = 80 ;
			}
			
			$errno = 0 ;
			$errstr = "" ;
			$length = strlen($params);//参数长度
			//创建socket连接
			$fp = fsockopen($ip,$pos,$errno,$errstr,100) ;
			
			//构造post请求的头
			$header = "POST /$model HTTP/1.1\r\n";
			$header .= "Host:".$ip_str."\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: ".$length."\r\n";
			$header .= "Connection: Close\r\n\r\n";
			//添加post的字符串
			$header .= $params."\r\n";
			//发送post的数据
			fputs($fp,$header);
			$inheader = 1;
			
			$result = "" ;
			$i = 0 ;
			while (!feof($fp)) {
				$i += 1 ;
				if ($i > 100)	break ;
			    $line = fgets($fp,1024); //去除请求包的头只显示页面的返回数据
			    if ($line == '' || empty($line)) break ;
			    if ($inheader && ($line == "\n" || $line == "\r\n")) {
			        $inheader = 0;
			    }
			    if ($inheader == 0){
			    	if ($line == "\n" || $line == "\r\n"){
			    		continue ;
			    	}else{
			        	$result .= $line ;
			    	}
			    }
			}
			fclose($fp);
		}catch (Exception $e){
			$result = false ;
		}
		return $result ;
	} 
	// 通过post向网络发送数据
	function post_net_data2($url , $data){
		$curl = curl_init(); // 启动一个CURL会话
    	curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    	curl_setopt($curl, CURLOPT_REFERER,'https://www.baidu.com');// 设置Referer
    	curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
   	 	curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    	curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    	curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    	$tmpInfo = curl_exec($curl); // 执行操作
    	if (curl_errno($curl)) {
       		$tmpInfo = 'Errno'.curl_error($curl);//捕抓异常
    	}
    	curl_close($curl); // 关闭CURL会话
    	return $tmpInfo; // 返回数据
	} 
	/** 
	 * 发送post请求 
	 * @param string $url 请求地址 
	 * @param array $post_data post键值对数据 
	 * @return string 
	 */  
	function post_net_data3($url, $post_data) 
	{ 
		$postdata = http_build_query($post_data);  
	  	$options = array(  
	    	'http' => array(  
	      		'method' => 'POST',  
	      		'header' => 'Content-type:application/x-www-form-urlencoded',  
	      		'content' => $postdata,  
	      		'timeout' => 15 * 60 // 超时时间（单位:s）  
	    	)  
		);
	  	$context = stream_context_create($options);  
	  	$result = file_get_contents($url, false, $context); 
	  	return $result;  
	}

	function getParamRSAData($param)
	{
		$rnt = "" ; $lj = "" ;
		foreach ($param as $k => $v )
		{
			$rnt .= $lj.$k."=\"".$v."\"" ;
			$lj = "&" ;
		}
		return $rnt ;
	}
	// 按照字典顺序 a-z 排列参数
	function getParamAuto($param,$url_encode_flg=true)
	{
		$rnt = "" ;
		while(true){
			if (is_null($param) || !is_array($param) || count($param) == 0)	break ;
			
			$sm = "" ;
			foreach ($param as $k => $v )
			{
				if (strlen($sm) == 0){
					$sm = $k ;
				}else{
					if (strcmp($sm, $k) > 0){
						$sm = $k ;
					}
				}
			}
			if ($url_encode_flg)
				$rnt.= "$sm=" . urlencode($param[$sm]). "&" ;
			else
				$rnt.= "$sm=" . $param[$sm]. "&" ;
			unset($param[$sm]) ;
		}
		return substr($rnt,0,-1) ;
	}
	function getParamXml($param)
	{
		$rnt = "<xml>\n" ;
		foreach ($param as $k => $v )
		{
			$rnt .= "\t\r<$k>$v</$k>\n" ;
		}
		$rnt .= "</xml>\n" ;
		return $rnt ;
	}
		
	/**
	 * 返回多少长度的字母数字混合的随机数
	 */
	function random_mix($len)
	{
		$an = array('1','2','3','4','5','6','7','8','9','q','w','e','r','t','y','u','i','o','p','a','s','d','f','g','h','j','k','l','z','x','c','v','b','n','m') ;

		$rnt = "" ;
		while ($len > 0) {
			$max = time() * rand(8392, 129483) ;
			$rk = abs(rand(1 , $max )) ;
			$i = $rk % count($an) ;
			$rnt .= $an[$i] ;
			$len -- ;
		}
		return $rnt ;
	}
	
	/**
	 * 图片数据存储
	 */
	function image_data_save($savetype , &$data_img , &$rnt)
	{
		if ($savetype == "")	$savetype = "413 , 116 , 115 , 114" ;
		
		$img_type = sql_fetch_rows("select * from prf_type where `id` in ($savetype) order by `id` desc") ;
		if (count($img_type) <= 0){
			$ret['status'] = $GLOBALS["ERR"]["ERR_HEAD_TYPE"] ;
			$ret['message'] = $GLOBALS["MES"]["ERR_HEAD_TYPE"] ;
			return 0 ;
		}
		foreach ($img_type as $one){
			$id = $one['id'] ;
			$addr = $one['addr'] ;
			$name = $one['typename'] ;
			$newid = sql_insert("insert into sys_image(`type` , `name` , `width` , `height` , `update_time`) values ('$id' , '$name' , '0' , '0', unix_timestamp())") ;
							
			$fnaer = md5($newid . "_" . $id . "_" . $name) ;
			while(strlen($fnaer) > 10){
				$addr.= "/".substr($fnaer, 0 , 3) ;
				$fnaer = substr($fnaer, 4) ;
			}
			
			$path = "images/" . $addr . "/" . $newid . "_" . $id . ".png";
			$save_path = $GLOBALS['upload_Path'] . $path ;
			sql_query("update `sys_image` set `url` = '$path' , `update_time` = unix_timestamp() where `id` = $newid") ;
			
			if (saveFile($save_path, $data_img) == false){
				$ret['status'] = $GLOBALS["ERR"]["ERR_FILE_SAVE"] ;
				$ret['message'] = $GLOBALS["MES"]["ERR_FILE_SAVE"] ;
				sql_query("delete from `sys_image` where `id` = $newid") ;
				return 0 ;
			}
			$size = getimagesize($save_path) ;
			if ($size == false){
				$ret['status'] = $GLOBALS["ERR"]["ERR_FILE_NOT_IMG"] ;
				$ret['message'] = $GLOBALS["MES"]["ERR_FILE_NOT_IMG"] ;
				sql_query("delete from `sys_image` where `id` = $newid") ;
				return 0 ;
			}
			
			$w = $size[0] ;
			$h = $size[1] ;
			sql_query("update sys_image set `width` = '$w' , `height` = '$h' where  `id` = $newid") ;			
			return $newid ;
		}
		return 0 ;
	}
	/**
	 * 图片数据更新
	 */
	function image_data_change($imgid , &$data_img , &$rnt)
	{
		$imgone = sql_fetch_one("select * from sys_image where `id` = $imgid") ;
		if ($imgone['id'] == $imgid){
			$save_path = $GLOBALS['upload_Path'] . $imgone['url'] ;
			unlink($save_path) ;
			
			if (saveFile($save_path, $data_img) == false){
				$ret['status'] = $GLOBALS["ERR"]["ERR_FILE_SAVE"] ;
				$ret['message'] = $GLOBALS["MES"]["ERR_FILE_SAVE"] ;
				return 0 ;
			}
			$size = getimagesize($save_path) ;
			if ($size == false){
				$ret['status'] = $GLOBALS["ERR"]["ERR_FILE_NOT_IMG"] ;
				$ret['message'] = $GLOBALS["MES"]["ERR_FILE_NOT_IMG"] ;
				return 0 ;
			}
			
			$w = $size[0] ;
			$h = $size[1] ;
			sql_query("update sys_image set `width` = '$w' , `height` = '$h' where  `id` = $imgid") ;			
			return $imgid ;
		}
		return 0 ;
	}
	
	function logsave($file , $message)
	{
		$time = date("Ymd H:i:s") ;
		file_put_contents(dirname(__FILE__)."/$file","$time -- $message.\r\n",FILE_APPEND);	
	}
?>

