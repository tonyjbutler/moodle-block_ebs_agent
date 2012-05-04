<?php
/**
 MWatts, 12 Jun 2009
 
 Utility library for the EBS Agent Moodle module.
*/ 
class ebs_utility {

	/**
	 Gets a flag indicating whether the given database connection details are valid.
	*/
	public static function is_connection_valid($db_host_name, $db_user_name, $db_password) {	
	
		$result = true;
		$connection = self::get_connection($db_host_name, $db_user_name, $db_password);
	
		if(!$connection) {
			$result = false;
		} else {		
			oci_close($connection);
		}
		
		return $result;		
	}
	
	/**
	 Connects to the Moodle configured database and returns a handle to it.
	*/
	public static function connect() {
	
		global $ebs_db_host;
		global $ebs_db_user;
		global $ebs_db_password;
		
		return self::get_connection($ebs_db_host, $ebs_db_user, $ebs_db_password);
	}	
	
	/**
	 Gets a connection to the given database.
	*/
	public static function get_connection($db_host_name, $db_user_name, $db_password) {
		return oci_new_connect($db_user_name, $db_password, $db_host_name);
	}

	/**
	 Registers a stylesheet <link /> tag.
	*/
	public static function register_stylesheet($stylesheet) {
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$stylesheet\" />";
	}
	
	/**
	 Outputs an HTML meta-redirect to the given URL and then ends processing.
	*/
	public static function meta_redirect($url) {
	
		echo "<html>";
		echo "<head>";
		
		echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\" />";
		
		echo "</head>";
		echo "</html>";
	
		exit();
	}
	
	/**
	 Checks if the given value is a valid number.
	*/
	public static function is_numeric($input) {
		return preg_match("/^([0-9]+)((\.([0-9]+))?)$", $input);
	}
	
	/**
	 Checks if the input is an integral type.
	*/
	public static function is_integer($input) {
		return preg_match("/^([0-9]+)$/", $input);
	}
	
	/**
	 Gets a parameter value from the web_config table.
	*/
	public static function get_web_config_parameter($parameter) {
	
		global $CFG;
		
		$sql = "SELECT parameter_value FROM web_config WHERE Upper(parameter) = Upper(:parameter)";
	
		//Connect
		$connection = self::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		//Parse the statement
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the web_config lookup query: " . $error["message"]);
		}
		
		//Bind parameter name
		oci_bind_by_name($statement, "parameter", $parameter);
		
		//Execute
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the web_config lookup query: " . $error["message"]);
		}
		
		$data = oci_fetch_array($statement, OCI_DEFAULT);
		
		oci_close($connection);
		
		return $data["PARAMETER_VALUE"];
	}
}
?>