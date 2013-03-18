<?php

require_once(dirname(__FILE__) . '../crendentials.php');

class DatabaseManager {
	
	private $mysqli;
	private $lastQuery;
	private $environment;
	
	public function __construct($environment = "PRODUCTION")
	{
		$this->mysqli = new MySQLi($host, $username, $password, $dbname);
		$this->environment = $environment;
	}
	
	/**
	 * Execute a query string
	 * @param String the query
	 * @return void
	 */
	public function executeQuery($query)
	{
		if ( !$result = $this->mysqli->query($query) ) {
			trigger_error('Error executing query: ' . $query . ' - ' . $this->mysqli->error, E_USER_ERROR);
			return FALSE;
		}else {
			$this->lastQuery = $result;
		}
		return $result;
	}
	
	/**
	 * Get the row from the most recently executed query
	 * @return array
	 */
	public function getRow($type = MYSQLI_ASSOC)
	{
		return $this->lastQuery->fetch_array($type);	
	}
	
	/**
	 * Get all rows from the last executed query
	 * @return array of rows
	 */
	public function getAllRows($type = MYSQLI_ASSOC)
	{
		$result = array();
		while ( ($oneRow = $this->lastQuery->fetch_array($type)) != NULL ) {
			$result[] = $oneRow;
		}
		return $result;
	}
	
	/**
	 * Delete records from the database
	 * @param String the table to remove rows from
	 * @param String the condition for which rows are to be removed
	 * @param int the number of rows to be removed
	 * @return void
	 */
	public function deleteRecords($table, $condition, $limit)
	{
		$limit = ($limit == '') ? '' : 'Limit ' . $limit;
		$delete = "DELETE FROM {$table} WHERE {$contion} {$limit}";
		if ( $this->environment != 'PRODUCTION' )
			echo $delete . "<br />";
		$this->executeQuery($delete);
	}
	
	/**
	 * Update records in the database
	 * @param String the table
	 * @param array of changes field => value
	 * @param String the condition
	 * @return bool
	 */
	public function updateRecords($table, $changes, $condition)
	{
		$update = "UPDATE " . $table . " SET ";
		foreach ( $changes as $field=>$value ) {
			$update .= "`" . $field . "`='{$value}',";	
		}
		
		// remove our trailling ,
		$update = substr($update, 0, -1);
		if ( $condition != '' ) {
			$update .= "WHERE " . $condition;	
		}
		
		if ( $this->environment != 'PRODUCTION' )
			echo $update;
		$this->executeQuery($update);
	}
	
	/**
	 * Insert records into the database
	 * @param String the database table
	 * @param array data to insert field=>value
	 * @return bool
	 */
	public function insertRecords($table, $data)
	{
		// setup some variable for fields and values
		$fields = '';
		$values = '';
		
		// populate them
		foreach ( $data as $f=>$v ) {
			$fields .= "`$f`,";
			$values .= ( is_numeric($v) && intval($v) == $v ) ? $v . ',' : "'" . $this->sanitizeData($v) . "',";	
		}
		
		// remove our trailling , 
		$fields = substr($fields, 0, -1);
		$values = substr($values, 0, -1);
		
		$insert = "INSERT INTO $table ({$fields}) VALUES({$values}) ";
		
		if ( $this->environment != 'PRODUCTION' )
			echo $insert . "<br />";
		
		$this->executeQuery($insert);
		return true;
	}
	
	/**
	 * Sanitize data
	 * @param String the data to be sanitized
	 * @return String the sanitized data
	 */
	public function sanitizeData($value)
	{
		// Stripslashes
		if ( get_magic_quotes_gpc() ) {
			$value = stripslashes($value);
		}
		
		// Quote value
		$value = $this->mysqli->real_escape_string($value);
		return $value;
	}
	
	/**
	 * Returns the number of rows in the last results set
	 */
	public function numRows()
	{
		return $this->lastQuery->num_rows;	
	}
	
	/**
	 * Gets the number of affected rows from the previous query
	 * @return int the number of affected rows
	 */
	public function affectedRows()
	{
		return $this->mysqli->affected_rows;
	}
	
	/**
	 * Gets the last inser id
	 * @return int the insert id
	 */
	public function getInsertID()
	{
		return $this->mysqli->insert_id;	
	}
	
	/**
	 * Closes the current database connection
	 */
	public function closeConnection() {
		return $this->mysqli->close();
	}
}

?>