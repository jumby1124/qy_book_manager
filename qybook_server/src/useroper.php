<?php

/*
 * Created on 2017-6-30-forever
 *
 * 用户接口,查询,下单等
 */
/**
 * 查询及过滤书本,便于显示
 */
function user_get_book_list($json)
{
	$ret = array() ;
	$ret['data'] = array() ;	
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	
	$page = get_jsonValue($json,"page") ;
	$count = get_jsonValue($json,"count") ;
	if ($count == 0) $count = 100 ;
	
	if ($page < 1)	$page = 1 ;
	$start = ($page - 1) * $count ;
	
	// 过滤字段,根据名称或id进行查找过滤用户
	$filter = trim(get_jsonValue($json,"filter")) ;	
	$where = "" ;
	if (strlen($filter) > 0)
	{
		$fnm = "%" . $filter . "%" ;
		$where = "where (`booknum` like '$fnm' or `bookname` like '$fnm' or `press` like '$fnm' or `class` like '$fnm') ";
	}
	
	$allcount = sql_fetch_one_cell("select count(`id`) from `book_list` $where") ;
	
	$allpage = ceil($allcount / $count) ;
	if ($allpage < 1)	$allpage = 1 ;
	$list = sql_fetch_rows("select * from `book_list` $where limit $start , $count") ;
	
	
	$ret['data']['page'] = $page ;
	$ret['data']['allpage'] = $allpage ;
	$ret['data']['count'] = $count ;
	$ret['data']['list'] = $list ;
	
	return $ret ;
}

/**
 * 获取用户购物车的所有信息
 */
function user_get_car_list($json)
{
	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
		
	$ret['data']['list'] = sql_fetch_rows("select u.*,b.bookname,b.booknum from `user_car` u left join book_list b on u.book_id = b.id where u.`userid` = '$uid'") ;
	return $ret ;
} 

/**
 * 把某书加入某用户的购物车.
 */
function user_book_to_car($json)
{
	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$bookid = get_jsonValue($json,"bookid") ;
	$number = get_jsonValue($json,"number") ;
	
	if (intval(sql_fetch_one_cell("select count(*) from `user_car` where `userid` = '$uid'")) > 100){
		$ret['status'] = -1 ;
		$ret['message'] = "购物车已满,请先清理购物车." ;
		return $ret ;
	}
	
	sql_query("INSERT INTO `user_car` (`userid`, `book_id`, `number`, `addtime`) VALUES ('$uid', '$bookid', '$number', unix_timestamp())  on duplicate key update `number` = '$number' , `addtime` = unix_timestamp()") ;
	
	$ret['data']['item'] = sql_fetch_one("select u.*,b.bookname,b.booknum from `user_car` u left join book_list b on u.book_id = b.id where u.`userid` = '$uid' and u.`book_id` = '$bookid'") ;
	return $ret ;
}

/**
 * 删除购物车里选中的书本
 */
 function user_del_car_book($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	
 	$idlist =  get_jsonValue($json,"idlist") ;
 	
 	$ids = "0" ;
 	foreach($idlist as $k=>$v)
 	{
 		$ids .= ",$v" ;
 	}
 	
 	sql_query("delete from `user_car` where `userid` = '$uid' and `book_id` in ($ids)") ;
 	
 	$ret['data']['idlist'] = $idlist ;
 	return $ret ;
 }
/**
 * 把购物车里的书进行购买下单操作.
 */
 function user_buy_books($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	
 	$idlist =  get_jsonValue($json,"idlist") ;
 	
 	$maxid = intval(sql_fetch_one_cell("select max(`id`) from user_buy_book")) ;
 	if ($maxid <= 0)	$maxid = 1 ;
 	$order_id = $maxid . rand(283, 4294) ;
 	
 	$ids = "0" ;
 	foreach($idlist as $k=>$v)
 	{
 		$ids .= ",$v" ;
 		
 		$num = sql_fetch_one_cell("select `number` from `user_car` where `userid` = '$uid' and `book_id` = '$v'") ;
 		sql_query("INSERT INTO `user_buy_book` (`userid`, `book_id`, `number`, `buy_time`, `state` , `order_id`) VALUES ( '$uid', '$v', '$num', unix_timestamp(), '0' , '$order_id')") ;
 	}
 	
 	sql_query("delete from `user_car` where `userid` = '$uid' and `book_id` in ($ids)") ;
 	
 	$ret['data']['idlist'] = $idlist ;
 	return $ret ;
 }


/**
 * 显示用户订单列表
 */
 function user_get_list_buy($json)
 {
 	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
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
		$where .= " and (b.`booknum` like '$fnm' or b.`bookname` like '$fnm' or b.`press` like '$fnm' or b.`class` like '$fnm') ";
	}
	$start_day = get_jsonValue($json , "start_day") ;
	$end_day = get_jsonValue($json , "end_day") ;
	if (strlen($start_day) > 0 && strlen($end_day) > 0){
		$where .= "  and u.`buy_time` >= unix_timestamp('$start_day') and u.`buy_time` <= (unix_timestamp('$end_day') + 86400) " ;
	}
	$state = get_jsonValue($json , "state") ;
	if (strlen($state) > 0 && intval($state) >= 0){
		$where .= " and u.state = '$state'" ;
	}
	
	$allcount = sql_fetch_one_cell("select count(u.`id`) from user_buy_book u inner join book_list b on u.book_id = b.`id` where u.`userid` = '$uid' $where") ;
	
	$allpage = ceil($allcount / $count) ;
	if ($allpage < 1)	$allpage = 1 ;
	
	$list = sql_fetch_rows("select u.*,b.`booknum` , b.`bookname` , b.`press` , b.`class` from user_buy_book u inner join book_list b on u.book_id = b.`id` where u.`userid` = '$uid' $where order by u.`buy_time` desc limit $start , $count") ;
	
	$ret['data']['page'] = $page ;
	$ret['data']['allpage'] = $allpage ;
	$ret['data']['count'] = $count ;
	$ret['data']['list'] = $list ;
	
	return $ret ;
 }
 

function user_get_list_buy_admin_grp($json)
{
	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$page = get_jsonValue($json,"page") ;
	$count = get_jsonValue($json,"count") ;
	if ($count == 0) $count = 100 ;	
	if ($page < 1)	$page = 1 ;
	$start = ($page - 1) * $count ;
	
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
	
	$where = "" ;

	// 过滤字段,根据名称或id进行查找过滤用户
	$filter_name = trim(get_jsonValue($json,"filter_name")) ;	
	if (strlen($filter_name) > 0)
	{
		$fnm = "%" . $filter_name . "%" ;
		$where .= " and (b.`booknum` like '$fnm' or b.`bookname` like '$fnm' or b.`press` like '$fnm' or b.`class` like '$fnm') ";
	}
	$filter_user = trim(get_jsonValue($json,"filter_user")) ;
	if (strlen($filter_user) > 0)
	{
		$fnm = "%" . $filter_user . "%" ;
		$where .= " and (m.username like '$fnm' or m.userid like '$fnm' or m.`memo` like '$fnm')" ;
	}
	$start_day = get_jsonValue($json , "start_day") ;
	$end_day = get_jsonValue($json , "end_day") ;
	if (strlen($start_day) > 0 && strlen($end_day) > 0){
		$where .= "  and u.`buy_time` >= unix_timestamp('$start_day') and u.`buy_time` <= (unix_timestamp('$end_day') + 86400) " ;
	}
	$state = get_jsonValue($json , "state") ;
	if (strlen($state) > 0 && intval($state) >= 0){
		$where .= " and u.state = '$state'" ;
	}
	
	$allcount = sql_fetch_one_cell("select count(*) from (select count(u.`id`) from user_buy_book u inner join book_list b on u.book_id = b.`id` inner join admin_manager m on u.userid = m.userid where (1 = 1) $where  group by order_id) as cc") ;
	
	//$allcount = sql_fetch_one_cell("select count(u.`id`) from user_buy_book u inner join book_list b on u.book_id = b.`id` inner join admin_manager m on u.userid = m.userid where (1 = 1) $where") ;
	
	$allpage = ceil($allcount / $count) ;
	if ($allpage < 1)	$allpage = 1 ;
	
	$list = sql_fetch_rows("select u.*, m.`username` from user_buy_book u inner join book_list b on u.book_id = b.`id` inner join admin_manager m on u.userid = m.userid where (1 = 1) $where group by order_id limit $start , $count") ;
	//$list = sql_fetch_rows("select u.*,b.`booknum` , b.`bookname` , b.`press` , b.`class` , m.`username` from user_buy_book u inner join book_list b on u.book_id = b.`id` inner join admin_manager m on u.userid = m.userid where (1 = 1) $where order by u.`buy_time` desc limit $start , $count") ;
	
	$ret['data']['page'] = $page ;
	$ret['data']['allpage'] = $allpage ;
	$ret['data']['count'] = $count ;
	$ret['data']['list'] = $list ;
	
	return $ret ;
}
function user_get_list_buy_admin_info($json)
{
	$ret = array() ;
	$ret['data'] = array() ;
	$uid = get_jsonValue($json,"uid") ;		// 调用者
	$page = get_jsonValue($json,"page") ;
	$count = get_jsonValue($json,"count") ;
	if ($count == 0) $count = 100 ;	
	if ($page < 1)	$page = 1 ;
	$start = ($page - 1) * $count ;
	
	
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
	$order_id = get_jsonValue($json,"order_id") ;
	
	$allcount = sql_fetch_one_cell("select count(*) from user_buy_book u where u.order_id = '$order_id'") ;
	$allpage = ceil($allcount / $count) ;
	if ($allpage < 1)	$allpage = 1 ;
	
	$list = sql_fetch_rows("select u.*,b.`booknum` , b.`bookname` , b.`press` , b.`class` , m.`username` , m.people , m.photo , m.address  from user_buy_book u inner join book_list b on u.book_id = b.`id` inner join admin_manager m on u.userid = m.userid where u.order_id = '$order_id' order by u.`buy_time` desc limit $start , $count") ;
	
	$ret['data']['page'] = $page ;
	$ret['data']['allpage'] = $allpage ;
	$ret['data']['count'] = $count ;
	$ret['data']['list'] = $list ;
	return $ret ;
}
/**
 * 改变书的状态
 */
 function user_change_state_buy_book($json)
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
	
	
 	$idlist = get_jsonValue($json,"idlist") ;
 	$state	= get_jsonValue($json,"state") ;
 	
 	$ids = "0" ;
 	foreach($idlist as $k=>$v)
 	{
 		$ids .= ",$v" ;
 	}
 	
 	sql_query("update user_buy_book set `state` = $state where `id` in ($ids)") ;
 	
 	$ret['data']['idlist'] = $idlist ;
 	$ret['data']['state'] = $state ;
 	return $ret ;
 }
?>
