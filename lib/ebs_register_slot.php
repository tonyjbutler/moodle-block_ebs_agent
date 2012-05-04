<?php
/**
 MWatts, 16 Jun 2009
 
 Class representing a register slot.
*/
class ebs_register_slot {

	/**
	 Instance variables
	*/
	private $slot_id;
	private $register_event_id;
	private $session_code;
	private $start_date;
	private $end_date;
	private $break_length;
	private $status;

	/**
	 Constructor
	*/
	function __construct($slot_id, $register_event_id, $session_code, $start_date, $end_date, $break_length, $status) {
		$this->slot_id = $slot_id;
		$this->register_event_id = $register_event_id;
		$this->session_code = $session_code;
		$this->start_date = $start_date;
		$this->end_date = $end_date;
		$this->break_length = $break_length;
		$this->status = $status;
	}
	
	/**
	 Gets a description of the slot's date/time
	*/
	function get_description() {
		return date_format($this->start_date, "dS M Y H:i") . " - " . date_format($this->end_date, " H:i");
	}
	
	/**
	 Gets the date component of the slot's date/time.
	*/ 
	function get_date() {
		return date_format($this->start_date, "d/m/y");
	}
	
	/**
	 Gets the event ID for the slot
	*/
	function get_register_event_id() {
		return $this->register_event_id;
	}
	
	/**
	 Gets a flag indicating whether this slot is available for marking (i.e.
	 the slot status is either F, for finish later, or NULL, for not yet marked).
	*/
	function is_available_for_marking() {
		return (empty($this->status) || $this->status == "F");
	}
}
?>