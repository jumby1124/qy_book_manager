<?php

	function file_image_save($file , $filename , $param)
	{
		$rnt = array() ;
		if (count($param) < 1){
			$rnt['result'] = 0 ;
			$rnt['message'] = "参数错误." ;
			return $rnt ;
		}
		
		
		$newid = sql_insert("INSERT INTO `prf_login_image` (`type`, `url_img`, `add_time`) VALUES ('1', '', unix_timestamp())") ;
		
		$fne = explode(".",$filename) ;
		if (count($fne) >= 2){
			$fne = "." . $fne[count($fne) - 1] ;
		}else{
			$fne = ".mc.jpg" ;
		}

		$path = "images/" . $newid . "_" . $fne ;
		$save_path = $GLOBALS['upload_Path'] . $path ;		
				
		sql_query("update `prf_login_image` set `url_img` = '{$GLOBALS['URL_IMG_HEAD']}$path' where `id` = $newid") ;
		
		$upok = is_uploaded_file($file) ;
		make_dir(getPath_dir($save_path)) ;
		if (move_uploaded_file($file, $save_path)){    
		 	$rnt['result'] = 1 ;	// 成功保存文件
		 	$rnt['message'] = "文件保存成功.path=".$save_path ;
		}else{
			sql_query("delete from `sys_image` where `id` = $newid") ;
		 	$rnt['result'] = 0 ;
		 	$rnt['message'] = "文件保存失败,请检查php设置.".$save_path." , $upok " ;
		}
		return $rnt ;
	}
	
?>