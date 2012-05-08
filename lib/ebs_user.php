<?php

include_once("ebs_base_object.php");

/**
 Class representing an EBS user.
*/
class ebs_user extends ebs_base_object {

	//Instance variables
	private $person_code = 0;
	private $username;	
	private $forename;
	private $surname;
	private $staff_code;
	
	/**
	 Constructor method
	*/
	function __construct($username) {
	
		$this->username = $username;	
		
	}
	
	/**
	 Loads data into this instance.
	*/
	function do_load() {
	
		$sql = "
			SELECT
				P.person_code,
				P.forename,
				P.surname,
				P.fes_staff_code AS staff_code
			FROM
				users U
				INNER JOIN people P ON P.person_code = U.ebs_person_code
			WHERE
				Upper(U.name) = Upper(:username)
		";
	
		//Connect and parse the query
		$connection = $this->connect();
		$statement = oci_parse($connection, $sql);
		
		//Trap errors
		if(!$statement) {
			$error = oci_error();
			//die("An error was encountered loading user details for " . $this->username . ": " . $error["message"]);
		}
		
		//Bind the user name
		oci_bind_by_name($statement, ":username", $this->username);
		
		//Execute the query
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error();
			//die("An error was encountered executing user details query " . $this->username . ": " . $error["message"]);
		}	
		
		$row = oci_fetch_array($statement, OCI_RETURN_NULLS);
		$this->person_code = $row["PERSON_CODE"];
		$this->forename = $row["FORENAME"];
		$this->surname = $row["SURNAME"];
		$this->staff_code = $row["STAFF_CODE"];
		
		oci_close($connection);
	}

	/**
	 Determines whether the current Moodle user is a valid
	 EBS user.
	*/
	function is_valid_user() {		
		return ($this->person_code > 0);	
	}
	
	/**
	 Gets the user person code.
	*/
	function get_person_code() {
		return $this->person_code;
	}
	
	/**
	 Gets the user's full name.
	*/
	function get_name() {
		return $this->forename . " " . $this->surname;
	}
	
	/**
	 Gets a flag indicating whether the user is a member of staff.
	*/
	function is_member_of_staff() {
		return !empty($this->staff_code);
	}
	
	/**
	 Gets the user's staff code.
	*/
	function get_staff_code() {
		return $this->staff_code;
	}	
	
	/**
	 Gets the user's username.
	*/
	function get_username() {
		return $this->username;
	}
}
?>