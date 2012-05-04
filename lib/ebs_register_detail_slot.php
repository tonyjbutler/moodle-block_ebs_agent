<?php
/**
 MWatts, 17 Jun 2009
 
 Class representing a detail slot.
*/
class ebs_register_detail_slot {

	/**
	 Instance variables
	*/
	private $detail_slot_id;
	private $detail_id;
	private $slot_id;
	private $event_id;
	private $usage_code;
	private $object_id;
	private $object_type;
	private $start_date;
	private $end_date;
	private $break_length;

	/**
	 Constructor
	*/
	function __construct($detail_slot_id, $detail_id, $slot_id, $event_id, $object_type, $object_id, $start_date, $end_date, $break_length, $usage_code) {
		$this->detail_slot_id = $detail_slot_id;
		$this->detail_id = $detail_id;
		$this->slot_id = $slot_id;
		$this->event_id = $event_id;
		$this->object_type = $object_type;
		$this->object_id = $object_id;
		$this->start_date = $start_date;
		$this->end_date = $end_date;
		$this->break_length = $break_length;
		$this->usage_code = $usage_code;
	}
	
	/**
	 Gets the usage code.
	*/
	function get_usage_code() {
		return $this->usage_code;
	}
	
	/**
	 Gets a flag indicating whether the detail is active for the specified slot.
	*/
	function is_active() {
		return ($this->detail_slot_id > 0);
	}
}
?>