<?php
namespace MySqlDB;

/**
 * A class file to connect to database
 * @author Michele Rosica <michelerosica@gmail.com>
 * 
 * MySQLi: MySQL Improved Extension
 * http://php.net/manual/en/book.mysqli.php
 *
 */
class DBConnection 
{
	var $db_connect_id;
	var $query_result;
	var $insert_id;

	var $persistency = false;
	var $user = '';
	var $server = '';
	var $dbname = '';
	
	// Holding the error information - only populated if sql_error_triggered is set
	var $sql_error_returned = array();
	

	/**
	* Constructor
	*/
    function __construct() 
	{
        // connecting to database
        $this->sql_connect();
    }

	/**
	* destructor
	*/
    function __destruct() 
	{
        // closing db connection
        $this->sql_close();
    }
	
	/**
	* Connect to server
	* @access private
	*/
	private function sql_connect()
	{
        // import database connection variables
        //require_once __DIR__ . '/db_config.php';
		require_once 'db_config.php';
		
		$this->user = DB_USER;
		$this->server = DB_SERVER;
		$this->dbname = DB_DATABASE;

        // Connecting to mysql database
        $this->db_connect_id = @mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
		
		// Check connection
		if (!mysqli_connect_errno())
		{
			//echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
			//echo "Host information: " . mysqli_get_host_info($this->db_connect_id) . PHP_EOL;
			return $this->db_connect_id;
		}
		
		//echo "Failed to connect to MySQL: " . mysqli_connect_error();
		$this->sql_error_returned = $this->sql_error();
		echo json_encode($this->sql_error_returned);
    }
	
	/**
	* Close sql connection
	* @access private
	*/
	private function sql_close()
	{
	    if ($this->db_connect_id)
		{
		  return @mysqli_close($this->db_connect_id);
		}

		return @mysqli_close();
	}
	
	/**
	* Base query method
	*
	* @param	string	$query		Contains the SQL query which shall be executed
	* @param	int		$cache_ttl	Either 0 to avoid caching or the time in seconds which the result shall be kept in cache
	* @return	mixed				When casted to bool the returned value returns true on success and false on failure
	*
	* @access	public
	*/
	function sql_query($query = '')
	{
		$sqlresult = mysqli_query($this->db_connect_id, $query);
		// Perform a query, check for error
		if (!$sqlresult)
		{
			$this->sql_error_returned = $this->sql_error();
			//echo json_encode($this->sql_error_returned);
			return null;
		}

		$this->insert_id = mysqli_insert_id($this->db_connect_id);

		return $sqlresult;
	}
	
   /**
	* Fetch current row
	*/
	function sql_fetchrow($result)
	{
		return mysqli_fetch_array($result);
	}
	
   /**
	* Escape string used in sql query
	*/
	function sql_escape($msg)
	{
		if (!$this->db_connect_id)
		{
			return @mysqli_real_escape_string($msg);
		}

		return @mysqli_real_escape_string($this->db_connect_id, $msg);
	}
	
   /**
	* Free sql result
	*/
	function sql_freeresult($query_id = false)
	{
		return @mysqli_free_result($query_id);
	}
	
	/**
	* return sql error array
	* @access private
	*/
	function sql_error()
	{
		if (!$this->db_connect_id)
		{
			return array(
				'message' => 'Failed to connect to MySQL',
				'code'    => mysqli_connect_errno()
			);
		}

		return array(
			'message' => @mysqli_error($this->db_connect_id),
			'code'    => @mysqli_errno($this->db_connect_id)
		);
	}
	
	/**
	* Gets the exact number of rows in a specified table.
	*
	* @param string $table_name		Table name
	*
	* @return string				Exact number of rows in $table_name.
	*
	* @access public
	*/
	function get_row_count($table_name)
	{
		$sql = 'SELECT COUNT(*) AS rows_total FROM ' . $this->sql_escape($table_name);
		$result = $this->sql_query($sql);
		// check result
        if ($result) {
		  $rows_total = mysqli_fetch_array($result);
		  $this->sql_freeresult($result);
		  return $rows_total['rows_total'];
		}
		
		$this->sql_error_returned = $this->sql_error();
		echo json_encode($this->sql_error_returned);
		return -1;
	}
	
	/**
	* Gets the exact number of rows in a specified table.
	*
	* @param string $table_name		Table name
	*
	* @return string				Exact number of rows in $table_name.
	*
	* @access public
	*/
	function get_num_rows($result)
	{
	    return @mysqli_num_rows($result);
	}
	
	/**
	* Gets the exact number of rows in a specified table.
	*
	* @param string $table_name		Table name
	*
	* @return string				Exact number of rows in $table_name.
	*
	* @access public
	*/
	function get_all_rows($table_name)
	{
		$sql = 'SELECT * FROM ' . $this->sql_escape($table_name);
		return $this->sql_query($sql);
	}

	/**
	* Gets the exact number of rows in a specified table.
	*
	* @param string $table_name		Table name
	*
	* @return string				Exact number of rows in $table_name. JSON
	*
	* @access public
	*/
	function get_row_json($table_name, $user_array = 'products')
	{
		$sql = 'SELECT * FROM ' . $this->sql_escape($table_name);
		$result = $this->sql_query($sql);
		// check result
        if ($result) {
		  // check for empty result
		  if ($this->get_num_rows($result) > 0) {
		   
		    // looping through all results
		    // products node
		    $response[$user_array] = array();
		    while ($row = mysqli_fetch_array($result)) 
		    {
				// push single product into final response array
				array_push($response[$user_array], $row);
		    }
			$this->sql_freeresult($result);
		   
			// echoing JSON response
			return json_encode($response);
		  }
		}
		
		$this->sql_error_returned = $this->sql_error();
		echo json_encode($this->sql_error_returned);
	}
	
	function delete_table($table_name)
	{
	    $rows_total = $this->get_row_count($table_name);
		if ($rows_total <= 0) return $rows_total;
		
		$sql = 'DELETE FROM ' . $this->sql_escape($table_name);
		$result = $this->sql_query($sql);
		// check result
        if ($result) {
		  $this->sql_freeresult($result);
		  return $rows_total;
		}
		
		$this->sql_error_returned = $this->sql_error();
		echo json_encode($this->sql_error_returned);
		return FAILURE;
	}
	
	function delete_row_byid($table_name, $id, $colum_name = '')
	{
	    $table = $this->sql_escape($table_name);
		if ($colum_name=="") $t_id = $table . '_id';
		else $t_id = $colum_name;
		
		$rows_total = $this->get_row_byid($table_name, $id, $t_id);
		if ($rows_total <= 0) return $rows_total;
		
		$sql = 'DELETE FROM ' . $table . ' WHERE ' . $t_id . ' = ' . $id;
		$result = $this->sql_query($sql);
		// check result
        if ($result) {
		  $this->sql_freeresult($result);
		  return SUCCESS;
		}
		
		$this->sql_error_returned = $this->sql_error();
		echo json_encode($this->sql_error_returned);
		return FAILURE;
	}
	
	function get_row_byid($table_name, $id, $colum_name = '')
	{
	    $table = $this->sql_escape($table_name);
		if ($colum_name=="") $t_id = $table . '_id';
		else $t_id = $colum_name;
		
		$sql = 'SELECT COUNT(*) AS rows_total FROM ' . $table . ' WHERE ' . $t_id . ' = ' . $id;
		$result = $this->sql_query($sql);
		// check result
        if ($result) {
		  $rows_total = mysqli_fetch_array($result);
		  $this->sql_freeresult($result);
		  return $rows_total['rows_total'];
		}
		
		$this->sql_error_returned = $this->sql_error();
		echo json_encode($this->sql_error_returned);
		return FAILURE;
	}
	
	function fromSql2Array($result) {
		// temp user array
		$rows = array();
			
		// check for empty result
		if ($result && $this->get_num_rows($result) > 0) 
		{
			// looping through all results
		    // products node
			while ($row = $this->sql_fetchrow( $result )) 
			{
				// push single product into final response array
				array_push($rows, $row);
			}
			$this->sql_freeresult($result);
		}

		return $rows;
    }
}
