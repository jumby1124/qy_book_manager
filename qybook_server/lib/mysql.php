<?php
	require_once(dirname(__FILE__)."/../config/dbconfig.php");
	
	function untime() {
		$time = microtime ();
		$arr = explode ( " ", $time );
		return $arr [0] + $arr [1];
	}
	function sql_connect()
	{
		$GLOBALS['currentLink'] = get_center_dblink();
	}

	function sql_close()
	{
		if (isset($GLOBALS['currentLink']))
		{
			mysqli_close($GLOBALS['currentLink']);
		}
		$GLOBALS['currentLink'] = NULL;
	}
	function get_center_dblink() {
		try{
			if (!isset($GLOBALS['currentLink']) || $GLOBALS['currentLink'] == NULL)
			{
				$GLOBALS['currentLink'] = mysqli_connect($GLOBALS['db']['server'], $GLOBALS['db']['username'],$GLOBALS['db']['password'], $GLOBALS['db']['database']);
				mysqli_set_charset($GLOBALS['currentLink'] , $GLOBALS['db']['charset']);
			}
			mysqli_select_db($GLOBALS['currentLink'] , $GLOBALS['db']['database'] );
		}catch (Exception $e){
			$time = date("Ymd H:i:s") ;
			$errno=mysqli_errno($GLOBALS['currentLink']);
			file_put_contents(dirname(__FILE__)."/error_log.txt","$time(errno: $errno)\r\n    ----".exceptionToString($e)."\r\n",FILE_APPEND);
		}
		return $GLOBALS['currentLink'];
	}
	function sql_query($sql)
	{	
		$time = date("Ymd H:i:s") ;
		try{
			$r = mysqli_query($GLOBALS['currentLink'] , $sql);
			if ($r == false)
			{
				$errno=mysqli_errno($GLOBALS['currentLink']);

				file_put_contents(dirname(__FILE__)."/error_log.txt","$time(errno: $errno)$sql\r\n",FILE_APPEND);

			}
		}catch (Exception $e){
			$errno=mysqli_errno($GLOBALS['currentLink']);
			file_put_contents(dirname(__FILE__)."/error_log.txt","$time(errno: $errno)$sql\r\n".exceptionToString($e)."\r\n",FILE_APPEND);
		}
		if($GLOBALS['DEBUG_SQL_LOG'])
			file_put_contents(dirname(__FILE__)."/sqllog.txt","$time -- $sql.\r\n",FILE_APPEND);
		return $r ;
	}
	
	function sql_fetch_one($sql)
	{
		$r = sql_query($sql);
		
		if ((!empty($r))&&($row = mysqli_fetch_array($r , MYSQLI_ASSOC))) {
			@mysqli_free_result($r);
			return $row;
		}
		else
		{
			return mysqli_error($GLOBALS['currentLink']);
		}
	}
	
	function sql_fetch_one_cell($sql)
	{
		$r =sql_query($sql);
		
		if ((!empty($r))&&($row = mysqli_fetch_array($r , MYSQLI_NUM))) {
			@mysqli_free_result($r);
			return $row[0];
		}
		else
		{
			return mysqli_error($GLOBALS['currentLink']);
		}
	}
	
	function sql_fetch_rows($sql)
	{
		$r =sql_query($sql);
			
		$ret = array();
		if (!empty($r))
		{
			while($row = mysqli_fetch_array($r , MYSQLI_ASSOC)) {
				$ret[] = $row;
			}
		}
		else
		{
			return mysqli_error($GLOBALS['currentLink']);
		}
		@mysqli_free_result($r);
		return $ret;
	}
	//返回 array[key] = value;
	function sql_fetch_array($sql,$key)
	{
		$r =sql_query($sql);
			
		$ret = array();
		if (!empty($r))
		{
			$i = 0;
			while($row = mysqli_fetch_array($r , MYSQLI_ASSOC)) 
			{
				if($key == '')
				{
					$ret[$i] = $row;
					$i++;
				}
				else	
					$ret[$row[$key]] = $row;
			}
		}
		else
		{
			return mysqli_error($GLOBALS['currentLink']);
		}
		@mysqli_free_result($r);
		return $ret;
	}
	//返回array[key] = [value1,value2....], by lee;
	function sql_fetch_collection($sql, $key){
		$starttime = untime();
		$r = sql_query($sql);
			
		$ret = array();
		if (!empty($r))
		{
			$i = 0;
			while($row = mysqli_fetch_array($r,MYSQLI_ASSOC)) 
			{
				if($key == '')
				{
					$ret[$i][] = $row;
					$i++;
				}
				else	
					$ret[$row[$key]][] = $row;
			}
			@mysqli_free_result($r);
		}
		else
		{
			return mysqli_error($GLOBALS['currentLink']);
		}
		return $ret;
	}
	function sql_insert($sql)
	{
		$r =sql_query($sql);
		if(!$r)
		{
			throw new Exception(mysqli_error($GLOBALS['currentLink']));
		}
		return intval(sql_fetch_one_cell('select last_insert_id()'));
	}
	
	// 插入一个对象
	function sql_insert_object($table, $obj)
	{
		if(!$obj)
			return 0;
			
		$sql = "INSERT INTO `$table` ";
		$keys = "(";
		$values = "(";
		$r = "";
		foreach ($obj as $key=>$value)
		{
			$keys .= ( $r . "`".$key."`");
			$values .= ( $r . "'" . addslashes( $value ). "'");
			$r = ",";
		}
		$keys .= ")";
		$values .= ")";
		$sql = $sql . $keys . " VALUES " . $values;	
		return sql_insert( $sql);
	}
	
	
	function sql_insert_objects($table,$objs)
	{
		$keys=array();
		if(count($objs)==0 || ! is_array($objs))
			return false;
		$index=0;
		$clos=$objs[0];
		foreach ($clos as $key => $value) {
			array_push($keys, "`".$key."`");
		}
		$keyStr=implode(",", $keys);
		
		while (count($objs)>=$index*100)
		{
			$values=array();
			for($i=$index*100;$i<($index+1)*100;$i++)
			{
				if($i>=count($objs))
					break;
				$obj=$objs[$i];
				$datas=array();				
				foreach ($obj as $value)
				{
					array_push($datas,"'".addslashes($value)."'");
				}
				$dataStr=implode(",", $datas);
				$dataStr="(".$dataStr.")";
				array_push($values,$dataStr);
			
			}
			
			if(count($values)>0)
			{
				$valueStr=implode(",", $values);
				sql_query("insert into `$table`($keyStr) values $valueStr");	
				//echo("\r\n\r\n"."insert into `$table`($keyStr) values $valueStr"."\r\n\r\n\r\n");			
				$index++;
			}else {
				break;
			}
				
		}
	}
	
	
	// 替换一个对象
	function sql_replace_object($table, $obj)
	{
		if(!$obj)
			return 0;
			
		$sql = "REPLACE INTO $table ";
		$keys = "(";
		$values = "(";
		$r = "";
		foreach ($obj as $key=>$value)
		{
			$keys .= ( $r . $key);
			$values .= ( $r . "'" . $value . "'");
			$r = ",";
		}
		$keys .= ")";
		$values .= ")";
		$sql = $sql . $keys . " VALUES " . $values;
		return sql_insert( $sql);
	}
	
	
	function sql_replace_objects($table,$objs)
	{
		$keys=array();
		if(count($objs)==0 || ! is_array($objs))
			return false;
		$index=0;
		$clos=$objs[0];
		foreach ($clos as $key => $value) {
			array_push($keys, "`".$key."`");
		}
		$keyStr=implode(",", $keys);
		
		while (count($objs)>=$index*100)
		{
			$values=array();
			for($i=$index*100;$i<($index+1)*100;$i++)
			{
				if($i>=count($objs))
					break;
				$obj=$objs[$i];
				$datas=array();				
				foreach ($obj as $value)
				{
					array_push($datas,"'".addslashes($value)."'");
				}
				$dataStr=implode(",", $datas);
				$dataStr="(".$dataStr.")";
				array_push($values,$dataStr);
			
			}
			
			if(count($values)>0)
			{
				$valueStr=implode(",", $values);
				sql_query("replace into `$table`($keyStr) values $valueStr");
				
				$index++;
			}else {
				break;
			}
				
		}
	}	
	function sql_check($sql) {
		$r = sql_query($sql);		
		if (mysqli_num_rows($r) > 0) {
			return true;
		} else {
			return false;
		}
	}
	function sql_field_types($table){
		$r = sql_query("select * from `$table` limit 1", $GLOBALS['currentLink']);
		$ret = array();

	    $finfo = mysqli_fetch_fields($r);	
	    foreach ($finfo as $val) {
//	        printf("Name:     %s\n", $val->name);
//	        printf("Table:    %s\n", $val->table);
//	        printf("max. Len: %d\n", $val->max_length);
//	        printf("Flags:    %d\n", $val->flags);
//	        printf("Type:     %d\n\n", $val->type);
	        $ret[$val->name] = $val->type;
	    }
    	return $ret;
	}
	function sql_field_flags($table){
		$r = sql_query("select * from `$table` limit 1", $GLOBALS['currentLink']);
		$ret = array();

	    $finfo = mysqli_fetch_fields($r);	
	    foreach ($finfo as $val) {
//	        printf("Name:     %s\n", $val->name);
//	        printf("Table:    %s\n", $val->table);
//	        printf("max. Len: %d\n", $val->max_length);
//	        printf("Flags:    %d\n", $val->flags);
//	        printf("Type:     %d\n\n", $val->type);
	        $ret[$val->name] = $val->flags;
	    }
    	return $ret;
	}
?>