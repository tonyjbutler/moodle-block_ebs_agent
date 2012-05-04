<?php
/**
 MWatts, 12 Jun 2009
 
 Basic object for all other EBS objects.
*/

include_once("ebs_utility.php");

class ebs_base_object {

	//Instance variables
	private $db_host_name;
	private $db_user_name;
	private $db_password;
	protected $is_configured = false;
	protected $is_loaded = false;
	
	/**
	 Connects the object to the database.
	*/
	function configure($db_host_name, $db_user_name, $db_password) {
		
		$this->db_host_name = $db_host_name;
		$this->db_user_name = $db_user_name;
		$this->db_password = $db_password;
		$this->is_configured = true;
	
		if(!ebs_utility::is_connection_valid($this->db_host_name, $this->db_user_name, $this->db_password)) {
			die("The database credentials provided are not valid.");
		}
	}
	
	/**
	 Loads data into the object
	*/
	function load() {
		if(!$this->is_configured) {
			die("The object cannot be initialised until the database credentials have been configured.");
		} else {
			$this->do_load();
			$this->is_loaded = true;
		}
	}
	
	/**
	 Function overridden in derivced classes to perform data loading.
	*/
	protected function do_load() {
	}
	
	/**
	 Gets a connection to the database configured for this instance.
	*/
	protected function connect() {
		return ebs_utility::get_connection($this->db_host_name, $this->db_user_name, $this->db_password);
	}
	
	/**
	 Gets a flag indicating whether this instance is configured.
	*/
	function is_configured() {
		return $this->is_configured;
	}
	
	/** 
	 Gets a flag indicating whether this instance has been loaded.
	*/
	function is_loaded() {
		return $this->is_loaded;
	}
}

?>