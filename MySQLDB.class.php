<?php 
/*
mysql的数据库工具类，其作用是：由它来完成数据的各种常见操作：
设计一个类，这个类要达到这个要求：
1，一实例化该类（得到一个对象），就连接上了目标数据库；
2，这个对象还可以选择（更换）新的数据库。
7，设计为单例模式

3，这个对象还可以执行“增删改”操作，并返回执行的结果——true或false
4，这个对象可以执行“返回一行数据”的查询操作，并返回一个一维数组！
5，可以返回多行多列数据（实际是二维数组）；
6，可以返回一行一列数据（实际是一个标量数据）；
*/
class MySQLDB{
	private $link = null;	//用于存放连接后的“连接资源”
	private $host;
	private $port;
	private $user;
	private $pass;
	private $charset;
	private $dbname = null;	//用于存放当前连接的数据库名

	//1，一实例化该类（得到一个对象），就连接上了目标数据库；
	private function __construct($conf){
		//考虑传过来的链接信息的默认值问题，并存储到当前对象的属性中
		$this->host = empty($conf['host']) ? "localhost" : $conf['host'];
		$this->port = empty($conf['port']) ? "3306" : $conf['port'];
		$this->user = empty($conf['user']) ? "root" : $conf['user'];
		$this->pass = empty($conf['pass']) ? "" : $conf['pass'];
		$this->charset = empty($conf['charset']) ? "utf8" : $conf['charset'];
		$this->dbname = empty($conf['dbname']) ? "php46" : $conf['dbname'];

		$this->link = mysql_connect("{$this->host}:{$this->port}","{$this->user}","{$this->pass}")
			or  die('连接数据库失败！');
		mysql_query("set names {$this->charset}", $this->link);	//mysql_set_charset("utf8");
		mysql_query("use {$this->dbname}", $this->link);		//myql_select_db("XX数据库");
	}
	static function GetDB($conf){
		static $db  = null;	//用于存储本类的唯一对象
		if(is_null($db)){
			$db = new self($conf);
		}
		return $db;
	}

	//2，这个对象还可以选择（更换）新的数据库。
	function select_db($db){
		mysql_query("use $db", $this->link);	
		$this->dbname = $db;
	}

	//3，这个对象还可以执行“增删改”等无返回数据的操作，并返回执行的结果——true或false
	function exec($sql){
		// $result = mysql_query($sql, $this->link);
		// if($result === false){
		// 	echo "<p>数据库执行失败，请参考如下信息：";
		// 	echo "<br />错误信息："  . mysql_error();
		// 	echo "<br />错误代号："  . mysql_errno();
		// 	echo "<br />错误语句："  . $sql;
		// 	die();
		// }
		$result = $this->query( $sql );//此行代替以上n行
		return $result;
	}

	//4，这个对象可以执行“返回一行数据”的查询操作，并返回一个一维数组！
	//类似这样的语句：select  *  from  user_info where id = 8;
	function GetOneRow($sql){
		$result = $this->query( $sql );//此行代替以上n行

		$rec = mysql_fetch_assoc($result);	//从结果集获得一行数据，并作为关联数组
		return $rec;
	}

	//5，这个对象可以执行“返回多行数据”的查询操作，并返回一个二维数组！
	//类似这样的语句：select  *  from  user_info where id > 8;
	function GetRows( $sql ){
		$result = $this->query($sql);//找query方法，获得结果集！
		$arr = array();	//空数组
		while ($rec = mysql_fetch_assoc($result)){
			$arr[] = $rec;	//$rec是一维数组
		}
		return $arr;	//这就是二维数组
	}
	//6，这个对象可以执行“返回一行一列数据”的查询操作，并返回一个“标量数据”（单个数据）！
	//类似这样的语句：select  age  from  user_info where id = 8;
	function GetOneData( $sql ){
		$result = $this->query($sql);//找query方法，获得结果集！
		$rec = mysql_fetch_row($result);
		$data = $rec[0];
		return $data;	//这就是单个数据
	}

	//此方法专门执行sql语句，包括所有增删改查等等，并处理执行失败的情形
	function query( $sql ){
		$result = mysql_query($sql, $this->link);
		if($result === false){
			echo "<p>数据库执行失败，请参考如下信息：";
			echo "<br />错误信息："  . mysql_error();
			echo "<br />错误代号："  . mysql_errno();
			echo "<br />错误语句："  . $sql;
			die();
		}
		return $result;
	}

	function close_link(){
		mysql_close($this->link);
	}
}
