<?php
/*
 * Created on 2017-6-30-forever
 *
 * 登陆相关
 */
 
  // 检测用户调用是否合法 , cmd = 调用接口 ， 这里使用接口判定权限。
 function check_session($uid , $session , $callip , $cmd)
 {
 	$ret = array() ;
 	$ret["status"] = 0 ;
 	$ret["message"] = "ok" ;
 	
 	$userinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($userinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	
 	if ($userinfo['login_ip'] != $callip)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "换设备请重新登陆." ;
 		return $ret ;
 	}
 	if ($userinfo['login_session'] != $session)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "校验失败,请重新登陆." ;
 		return $ret ;
 	}
 	
 	// 判定此用户是否有调用cmd接口的权限
 	
 	return $ret ;
 }
 
 // 获取公告及广告图片
 function get_notive_and_image($json)
 {
 	$version = trim(get_jsonValue($json,"version")) ;
 	
 	$ret = array() ;
	$ret['data'] = array() ;
	
	$ret['image_list']  = sql_fetch_rows("select * from `prf_login_image` where `type` = 1 order by add_time desc") ; 
	$ret['notive_list'] = sql_fetch_rows("select * from `prf_login_notice` where `type` = 1 order by add_time desc") ;
	
	if ($GLOBALS['NEW_VERSION'] != $version)
	{
		$ret['update'] = 1 ;
		$ret['update_new'] = $GLOBALS['NEW_VERSION'] ;
		$ret['update_url'] = $GLOBALS['NEW_VERSION_URL'] ;
	}else{
		$ret['update'] = 0 ;
	}
	return $ret ;  
 }
 // 用户登陆接口
 function admin_user_login($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;
	
 	$username = get_jsonValue($json,"name") ;
 	$userpass = get_jsonValue($json,"pass") ;
 	
 	$userinfo = sql_fetch_one("select * from admin_manager where `username` = '$username'") ;
 	if (is_array($userinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户名不存在." ;
 		return $ret ;
 	}
 	if ($userinfo['userpass'] != $userpass)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "密码错误,登陆失败." ;
 		return $ret ;
 	}
 	 	
 	$uid = $userinfo['userid'] ;
 	
 	// 登陆成功,返回新的session
 	$session = (rand(343,9999999) * rand(2223,3333322)) % 1000000 ;
 	$userinfo["login_ip"] = getRealIp() ;
 	$userinfo["login_session"] = $session ;
 	
 	sql_query("update admin_manager set `login_ip` = '{$userinfo["login_ip"]}' , `login_session` = '{$userinfo["login_session"]}' where `userid` = '$uid'") ;
 	
 	$ret['data']['session'] 	= $session ;
 	$ret['data']['uid'] 		= $uid ; 	
 	$ret['data']['info']		= $userinfo ;
 	
 	return $ret ;
 }
 
  // 获取管理员列表
 function admin_get_list($json)
 {
 	$uid = get_jsonValue($json,"uid") ;		// 调用者 	
 	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret = array() ;
		$ret['data'] = array() ;
		$ret['data']['list'] 		= sql_fetch_rows("select * from `admin_manager` where `userid` = '$uid'") ;
		return $ret ;
 	}
 	
 	$page = get_jsonValue($json,"page") ;
	$count = get_jsonValue($json,"count") ;
	if ($count == 0) $count = 100 ;	
	if ($page < 1)	$page = 1 ;
	$start = ($page - 1) * $count ;
	
	$where = "" ;

	// 过滤字段,根据名称或id进行查找过滤用户
	$filter_name = trim(get_jsonValue($json,"filter_name")) ;	
	if (strlen($filter_name) > 0)
	{
		$fnm = "%" . $filter_name . "%" ;
		$where .= " where (`username` like '$fnm') ";
	}
	
	$allcount = sql_fetch_one_cell("select count(*) from admin_manager $where") ;
	
	$allpage = ceil($allcount / $count) ;
	if ($allpage < 1)	$allpage = 1 ;
	
	$list = sql_fetch_rows("select * from admin_manager $where limit  $start , $count") ;
	
	$ret['data']['page'] = $page ;
	$ret['data']['allpage'] = $allpage ;
	$ret['data']['count'] = $count ;
	$ret['data']['list'] = $list ;
	
	return $ret ;
 }
 
 // 更改用户密码
 function admin_change_password($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	
	$changeuid = get_jsonValue($json,"changeuid") ;	// 更改者
	$oldpass = get_jsonValue($json,"oldpass") ;		// 老密码
	$newpass = get_jsonValue($json,"newpass") ;		// 新密码
		
	$userinfo = sql_fetch_one("select * from admin_manager where `userid` = '$changeuid'") ;
 	if (is_array($userinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	if ($uid == $changeuid){
 		if ($oldpass != $userinfo['userpass']){
 			$ret["status"] = -1 ;
 			$ret["message"] = "密码输入错误，无法更改，请联系管理员修改." ;
 			return $ret ;
 		}
 	}
 	
 	sql_query("update admin_manager set `userpass` = '$newpass' where `userid` = '$changeuid'") ;
 	
 	return $ret ;
 }
 
// 添加新的用户
 function admin_add_new_admin($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	
	$username = get_jsonValue($json,"username") ;
 	$userpass = get_jsonValue($json,"userpass") ;
 	$permissions = get_jsonValue($json,"permissions") ;
 	$memo = get_jsonValue($json,"memo") ;
 	
 	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$userinfo = sql_fetch_one("select * from admin_manager where `username` = '$username'") ;
 	if (is_array($userinfo) == true)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户已经存在." ;
 		return $ret ;
 	}
 	$newid = sql_insert("insert into admin_manager(`username` , `userpass` , `permissions` , `memo`) values ('$username' , '$userpass' ,'$permissions' , '$memo')") ;
 	if ($newid <= 0)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "添加失败，请联系客服." ;
 		return $ret ;
 	}
 	
 	return $ret ;
 }
  // 删除管理员
 function admin_del_admin($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
	$deleuid = get_jsonValue($json,"deleuid") ;
	
	$userinfo = sql_fetch_one("select * from admin_manager where `userid` = '$deleuid'") ;
 	if (is_array($userinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	
	sql_query("delete from admin_manager where `userid` = '$deleuid'") ;
	return $ret ;	
 }
  // 更新权限
 function admin_update_admin($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
	$changeuid = get_jsonValue($json,"changeuid") ;
	$permissions = get_jsonValue($json,"permissions") ;
 	$memo = get_jsonValue($json,"memo") ;
 	
 	if ($uid == $changeuid)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "无法修改自己的权限." ;
 		return $ret ;
 	}
 	
 	$userinfo = sql_fetch_one("select * from admin_manager where `userid` = '$changeuid'") ;
 	if (is_array($userinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}

 	sql_query("update admin_manager set `permissions` = '$permissions' , `memo` = '$memo'  where `userid` = '$changeuid'") ;
 	return $ret ;
 }
 // 更新基本信息
 function admin_update_base_info($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	
	$changeuid = get_jsonValue($json,"changeuid") ;
 	if (intval($myinfo['permissions']) != 1 && $uid != $changeuid)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$userinfo = sql_fetch_one("select * from admin_manager where `userid` = '$changeuid'") ;
 	if (is_array($userinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}

	$photo = get_jsonValue($json,"photo") ;
 	$address = get_jsonValue($json,"address") ;
 	$people = get_jsonValue($json,"people") ;
 	
 	sql_query("update admin_manager set `photo` = '$photo' , `address` = '$address',`people` = '$people'  where `userid` = '$changeuid'") ;
 	return $ret ;
 }
 
 // 删除一本库存的书
 function admin_del_book($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$id = get_jsonValue($json,"bookid") ;
 	
 	sql_query("delete from book_list where `id` = '$id'") ;
 	return $ret ;
 }
 // 添加一本新书
 function admin_add_new_book($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	}
 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$booknum = get_jsonValue($json,"booknum") ;
 	$bookname = get_jsonValue($json,"bookname") ;
 	$class = get_jsonValue($json,"class") ;
 	$press = get_jsonValue($json,"press") ;
 	
 	if (intval(sql_fetch_one_cell("select count(*) from `book_list` where `booknum` = '$booknum' and `bookname` = '$bookname'")) > 0){
 		$ret["status"] = -1 ;
 		$ret["message"] = "此书已经存在.无法重复添加" ;
 		return $ret ;
 	}
 	
 	sql_query("INSERT INTO `book_list` (`booknum`, `bookname`, `press`, `class`) VALUES ('$booknum', '$bookname', '$press', '$class')") ;
 	return $ret ;
 }
 
 function get_all_notive($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	} 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
	$ret['data']['notive_list'] = sql_fetch_rows("select * from `prf_login_notice` order by add_time desc") ;	
	return $ret ;
 }
 function del_notive_one($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	} 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	
 	$delid = get_jsonValue($json,"delid") ;
 	
 	sql_query("delete from `prf_login_notice` where `id` = '$delid'") ;
 	
 	$ret['data']['notive_list'] = sql_fetch_rows("select * from `prf_login_notice` order by add_time desc") ;
 	return $ret ;
 }
 function update_notive_one($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	} 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$upid = get_jsonValue($json,"upid") ;
 	$type = get_jsonValue($json,"type") ;
 	$title = get_jsonValue($json,"title") ;
 	$notice = get_jsonValue($json,"notice") ;
 	$url_goto = get_jsonValue($json,"url_goto") ;
 	$notice_color = get_jsonValue($json,"notice_color") ;
 	
 	sql_query("update `prf_login_notice` set `type` = '$type' , `title` = '$title' , `notice` = '$notice' , `url_goto` = '$url_goto' , `notice_color` = '$notice_color' , `add_time` = unix_timestamp()  where `id` = '$upid'") ;
 	
 	$ret['data']['notive_list'] = sql_fetch_rows("select * from `prf_login_notice` order by add_time desc") ; 	
 	return $ret ;
 }
 
 function add_notive_one($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	} 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$type = get_jsonValue($json,"type") ;
 	$title = get_jsonValue($json,"title") ;
 	$notice = get_jsonValue($json,"notice") ;
 	$url_goto = get_jsonValue($json,"url_goto") ;
 	$notice_color = get_jsonValue($json,"notice_color") ;
 	
 	$newid = sql_insert("INSERT INTO `prf_login_notice` (`type`, `title`, `notice`, `url_goto`, `notice_color`, `add_time`) VALUES ('$type', '$title', '$notice', '$url_goto', '$notice_color', unix_timestamp())") ;
 	
	$ret['data']['notive_list'] = sql_fetch_rows("select * from `prf_login_notice` order by add_time desc") ; 	
 	return $ret ;
 }
 
 
  function get_all_image($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	} 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
	$ret['data']['image_list'] = sql_fetch_rows("select * from `prf_login_image` order by add_time desc") ;	
	return $ret ;
 }
 function admin_del_image($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$myinfo = sql_fetch_one("select * from admin_manager where `userid` = '$uid'") ;
 	if (is_array($myinfo) == false)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "用户不存在." ;
 		return $ret ;
 	} 	
 	if (intval($myinfo['permissions']) != 1)
 	{
 		$ret["status"] = -1 ;
 		$ret["message"] = "你没有此权限." ;
 		return $ret ;
 	}
 	
 	$id = intval(get_jsonValue($json,"id")) ;
 	
 	sql_query("delete from `prf_login_image` where `id` = '$id'") ;
 	
 	$ret['data']['image_list'] = sql_fetch_rows("select * from `prf_login_image` order by add_time desc") ;	
	return $ret ;
 }
?>
