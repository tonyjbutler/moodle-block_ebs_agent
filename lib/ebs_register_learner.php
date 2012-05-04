<?php
/**
 MWatts, 16 Jun 2009

 A class representing a learner on a register.
*/
class ebs_register_learner {

	/**
	 Instance variables
	*/
	private $person_code;
	private $forename;
	private $surname;
	private $progress_code;
	private $progress_description;
	private $marks;
	private $detail_id;
	private $is_deleted;
	private $temporary_name;
	private $temporary_progress_code;
	private $temporary_progress_date;
	private $notes;
	
	/**
	 Constructor
	*/
	function __construct($detail_id, $person_code, $forename, $surname, $temporary_name, $progress_code, $progress_description, $temporary_progress_code, $temporary_progress_date, $is_deleted, $notes) {
		$this->detail_id = $detail_id;
		$this->person_code = $person_code;
		$this->forename = $forename;
		$this->surname = $surname;
		$this->progress_code = $progress_code;
		$this->progress_description = $progress_description;
		$this->is_deleted = $is_deleted;
		$this->temporary_name = $temporary_name;
		$this->temporary_progress_code = $temporary_progress_code;
		$this->temporary_progress_date = $temporary_progress_date;
		$this->notes = $notes;
	}

	/**
	 Gets the persons full name
	*/
	function get_name($format = "[[forename]] [[surname]]") {		
		if($this->is_unconfirmed()) {
			$name = $this->temporary_name;
		} else {
			$name = strtolower($format);
			$name = str_replace("[[surname]]", $this->surname, $name);
			$name = str_replace("[[forename]]", $this->forename, $name);
			$name = str_replace("[[person_code]]", $this->person_code, $name);
		}
		
		return $name;
	}
	
	/**
	 Gets the person code.
	*/
	function get_person_code() {
		return $this->person_code;
	}
	
	/**
	 Gets the progress code.
	*/
	function get_progress_code() {
		return $this->progress_code;
	}
	
	/**
	 Gets a description of the learner's progress.
	*/
	function get_progress_description() {
		return $this->progress_description;
	}	

	/**
	 Gets the register event detail ID for the learner.
	*/
	function get_detail_id() {
		return $this->detail_id;
	}
	
	/**
	 Gets a flag indicating whethet this is an unconfirmed learner.
	*/
	function is_unconfirmed() {
		return ($this->person_code == -1);
	}
	
	/**
	 Gets the temporary progress code.
	*/ 
	function get_temporary_progress_code() {
		return $this->temporary_progress_code;
	}
	
	/**
	 Gets the temporary progress date.
	*/
	function get_temporary_progress_date() {
		return $this->temporary_progress_date;
	}
	
	/**
	 Gets the notes held against the learner for this register.
	*/
	function get_notes() {
		return $this->notes;
	}
}
?>