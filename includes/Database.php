<?php

// requires pear directory to be included in the include_path
require_once("DB.php");

class Database extends DB
{

	public function connect($dsn)
	{
		$database =& DB::connect($dsn);
		$this->database = $database;
		if (PEAR::isError($this->database)) {
		    die($this->database->getMessage());
		}
		$this->database->setFetchMode(DB_FETCHMODE_OBJECT);
	}

	public function query($sql_query)
	{
		$res =& $this->database->query($sql_query);
		if (PEAR::isError($res)) {
			    die($res->getMessage());
		}
		return($res);
	}
	

	public function update($table, $fields, $values, $where="", $row_id=null, $log_message="")
	{
		if(!is_array($fields))
			$fields = array($fields);
		if(!is_array($values))
			$values = array($values);
				
		$sth = $this->database->autoPrepare($table,$fields,DB_AUTOQUERY_UPDATE,$where);

		
		if (PEAR::isError($sth)) {
		    die($sth->getMessage());
		}

		$res =& $this->database->execute($sth,$values);
		
		$query = $this->database->last_query;
		
		//print("query: $query<br>\n");
		
		if (PEAR::isError($res)) {
			print("Query Failed: $res->userinfo<br>\n");
		    die($res->getMessage());
		}
		
		$this->addToTransactionLog($table,"update",$query,$row_id,$log_message);

		return($this->database->affectedRows());
	}
	
	public function insert($table, $fields, $values)
	{
		$sth = $this->database->autoPrepare($table,$fields,DB_AUTOQUERY_INSERT);

		if (PEAR::isError($sth)) {
		    die($sth->getMessage());
		}

		$res =& $this->database->execute($sth,$values);
		//print("<pre>sth:\n");
		//var_dump($this->database);
		//print("</pre>\n");
		
		$query = $this->database->last_query;

		if (PEAR::isError($res)) {
		    die($res->getMessage());
		}

		$insert_id = $this->database->getOne( "SELECT LAST_INSERT_ID() FROM `$table`" );
		
		switch($table)
		{
			case 'reservations': $log_message = "Added a new reservation"; break;
			case 'users': $log_message = "Added a new user"; break;
			case 'study_rooms': $log_message = "Added a new room"; break;
			case 'fines': $log_message = "Added a new fine"; break;
			default: $log_message = "";
		}
		$this->addToTransactionLog($table,"insert",$query,$insert_id,$log_message);
		
		return($insert_id);
	}
	
	public function addToTransactionLog($table,$operation,$query,$id=null,$log_message=null)
	{
		$fields = array('table_name','row_id','operation','query','log_message','user_id');
		$values = array($table,$id,$operation,$query,$log_message,$_SESSION['LibRooms']['UserID']);
		
		
		$sth = $this->database->autoPrepare("transaction_log",$fields,DB_AUTOQUERY_INSERT);

		if (PEAR::isError($sth)) {
		    die($sth->getMessage());
		}

		$res =& $this->database->execute($sth,$values);
		//print("<pre>\n");
		//print_r($res);
		//print("</pre>\n");

		if (PEAR::isError($res)) {
		    die($res->getMessage());
		}

		$insert_id = $this->database->getOne( "SELECT LAST_INSERT_ID() FROM `transaction_log`" );
		return($insert_id);
	}
	
	public function getOne($query)
	{
		return($this->database->getOne($query));
	}
	
	public function noDuplicate($table,$fields,$values)
	{
		$sql  = "select * from $table where ";
		$sql .= implode(" = ? and ",$fields) . " = ?";
		$res =& $this->database->query($sql, $values);

		if (PEAR::isError($res)) {
		    die($res->getMessage());
		}
		
		//print_r($res);
		//print("num row in noDuplicate: " . $res->numRows() . "<br>\n");
		
		if($res->numRows() == 0)
			return(true);
		else
			return(false);
	}
}
 
 
$db = new Database();
$dsn = DB_TYPE."://".DB_USER.":".DB_PASS."@".DB_SERVER.":".DB_PORT."/".DB_NAME;
$db->connect($dsn);
 
?>