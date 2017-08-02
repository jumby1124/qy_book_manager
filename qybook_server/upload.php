<?php                  
	require_once(dirname(__FILE__)."/config/include.php");

	try{
		ob_start();
		$filearray = getFileValue() ;
		
		$file = $filearray[0] ;
		$name = $filearray[1] ;
		$param= $filearray[2] ;
		
		$deb = true ;
		if ($deb == true){
			$time = date("Ymd H:i:s") ;
			$param_str = serialize($param) ;
			file_put_contents(dirname(__FILE__)."/debug_upload.txt","\r\n $time : $file , $name , $param_str",FILE_APPEND);
		}
		
		
		sql_connect();
		
		$ret = file_image_save($file , $name , $param) ;
		ob_end_clean();
    	$show = json_encode($ret); 
    	echo $show ;
    	
		if ($deb == true){
			$time = date("Ymd H:i:s") ;
			file_put_contents(dirname(__FILE__)."/debug_upload.txt","\r\n $time : $show",FILE_APPEND);
		}
    	
    	ob_end_flush();
	}catch(Exception $e){
		$ret = array() ;
		$ret['result'] = 0 ;
		$ret['message'] = $e->getMessage() ;
		
		ob_end_clean();
		$show = json_encode($ret); 
	    echo $show ;
		ob_end_flush();
		
		$time = date("Ymd H:i:s") ;
		file_put_contents(dirname(__FILE__)."/error_file.txt","\r\n $time : $show  -|||-  ".exceptionToString($e),FILE_APPEND);
	}
?>