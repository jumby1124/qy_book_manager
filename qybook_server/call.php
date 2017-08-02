<?php                  
	require_once(dirname(__FILE__)."/config/include.php");


	$GLOBALS["ERR"]["OK"] = 0 ;						$GLOBALS["MES"]["OK"] = "调用成功" ;
	$GLOBALS["ERR"]["ERR_CALL_NO_CMD"] = 1 ;		$GLOBALS["MES"]["ERR_CALL_NO_CMD"] = "调用的接口CMD未找到" ;
	$GLOBALS["ERR"]["ERR_CALL_TRY"] = 2 ;			$GLOBALS["MES"]["ERR_CALL_TRY"]	= "程序崩溃错误" ;	
	
	
	try{
		ob_start();
		$val = getPostValue() ;
		$call_raw_data = $GLOBALS['HTTP_RAW_POST_DATA'] ;
		if (strlen($call_raw_data) > 512)	$call_raw_data = substr($call_raw_data, 0 , 256) ;
		
		$commandFunc 	= $val[0] ;
		$commandParam 	= $val[1] ;
		$callip		 	= $val[2] ;
		
		
		if ($GLOBALS['DEBUG_CALL_LOG'] == true && strlen($call_raw_data) > 0 ){
			$time = date("Ymd H:i:s") . " : $callip " ;
			file_put_contents(dirname(__FILE__)."/debug.txt","\r\n $time: $call_raw_data",FILE_APPEND);
		}
		
		if (function_exists($commandFunc))
	    {
	    	sql_connect();
	    	
	    	
	    	if ($commandFunc != "admin_user_login" && $commandFunc != "get_notive_and_image"){
	    		$uid 		= get_jsonValue($commandParam,"uid") ;
	    		$session	= get_jsonValue($commandParam,"session") ;
				$ret = check_session($uid , $session , $callip , $commandFunc);
				if(array_key_exists('cmd', $ret) == false)		$ret['cmd'] = $commandFunc ;
				if(array_key_exists('status', $ret) == false)	$ret['status'] = $GLOBALS["ERR"]["OK"] ;
				if($ret['status'] != 0){
					ob_end_clean();
			    	$show = json_encode($ret) ;
			    	echo $show ;
			    	ob_end_flush();
			    	
			    	if ($GLOBALS['DEBUG_CALL_LOG'] == true){
						$time = date("Ymd H:i:s")  . " : $callip " ;;
						$logshow = json_encode_utf8($ret) ;
						file_put_contents(dirname(__FILE__)."/debug.txt","\r\n $time : $logshow",FILE_APPEND);
					}
					exit(0) ;
				}
			}
	    	
	    	
	    	$ret = $commandFunc($commandParam);
			if(array_key_exists('cmd', $ret) == false)		$ret['cmd'] = $commandFunc ;
			if(array_key_exists('status', $ret) == false)	$ret['status'] = $GLOBALS["ERR"]["OK"] ;
	    	ob_end_clean();
	    	$show = json_encode($ret) ;
	    	echo $show ;
	    	ob_end_flush();
	    	
	    	if ($GLOBALS['DEBUG_CALL_LOG'] == true){
				$time = date("Ymd H:i:s")  . " : $callip " ;;
				$logshow = json_encode_utf8($ret) ;
				file_put_contents(dirname(__FILE__)."/debug.txt","\r\n $time : $logshow",FILE_APPEND);
			}
	    }else if ($GLOBALS['HTTP_RAW_POST_DATA'] != ""){
			$j_ok = "" ;			
	    	if ($jsonObj == null){
				$j_ok = " , Json format conversion failure " ;
			}
	    	$ret = array() ;
			$ret['cmd'] = $commandFunc ;
			$ret['status'] = $GLOBALS["ERR"]["ERR_CALL_NO_CMD"] ;
			$ret['message'] = $GLOBALS["MES"]["ERR_CALL_NO_CMD"]."[$commandFunc] , $j_ok ." ;
			$ret['call'] = $call_raw_data ;
			
	    	ob_end_clean();
			$show = json_encode($ret) ; // json_encode_utf8($ret) ;
	    	echo $show ;
			ob_end_flush();
			
			if ($GLOBALS['DEBUG_ERR_LOG'] == true){
				$time = date("Ymd H:i:s") . " : $callip " ;
				$logshow = json_encode_utf8($ret) ;
				file_put_contents(dirname(__FILE__)."/error.txt","\r\n $time : $logshow",FILE_APPEND);
			}
	    }else{
	    	if ($GLOBALS['DEBUG_ERR_LOG'] == true){
				$time = date("Ymd H:i:s") . " : $callip " ;
				file_put_contents(dirname(__FILE__)."/error.txt","\r\n $time : data is empty string ",FILE_APPEND);
				
				echo "$time : data is empty string ";
			}
	    }
	}catch(Exception $e){
		$ret = array() ;
		$ret['cmd'] = "error" ;
		$ret['status'] = $GLOBALS["ERR"]["ERR_CALL_TRY"] ;
		$ret['message'] = $GLOBALS["MES"]["ERR_CALL_TRY"]." , ".$e->getMessage() ;
		$ret['call'] = $call_raw_data ;
		
		ob_end_clean();
		$show = json_encode($ret) ; // json_encode_utf8($ret) ;
	    echo $show ;
		ob_end_flush();
		
		if ($GLOBALS['DEBUG_ERR_LOG'] == true){
			$time = date("Ymd H:i:s") . " : $callip " ;
			$logshow = json_encode_utf8($ret) ;
			file_put_contents(dirname(__FILE__)."/error.txt","\r\n $time : $logshow  -|||-  ".exceptionToString($e),FILE_APPEND);
		}
	}
?>