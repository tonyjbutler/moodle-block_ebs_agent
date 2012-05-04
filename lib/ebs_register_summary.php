<?php
/**
 MWatts, 12 Jun 2009

 A class which encapsulates a register event.
*/
class ebs_register_summary {

	//Instance variables
	private $register_event_id;
	private $register_event_slot_id;
	private $code;
	private $description;
	private $start_date;
	private $end_date;
	private $status;
	private $uios;

	/**
	 Constructor method.
	*/
	function __construct($register_event_id, $register_event_slot_id, $code, $description, $start_date, $end_date, $status, $uios) {
		$this->register_event_id = $register_event_id;
		$this->register_event_slot_id = $register_event_slot_id;
		$this->code = $code;
		$this->description = $description;
		$this->start_date = $start_date;
		$this->end_date = $end_date;
		$this->status = $status;
		$this->uios = $uios;
	}
	
	/**
	 Gets the title of the event.
	*/
	function get_title() {
	
		$title = "";
		
		if(!empty($this->code)) {
			$title .= "(" . $this->code . ") ";
		}
	
		$title .= $this->description;
		
		return $title;
	}
	
	/**
	 Returns a string representation of this object.
	*/
	function to_string() {
	
		$str = date_format($this->start_date, "d/m/Y H:i:s");
		$str .= " - ";
		$str .= date_format($this->end_date, "d/m/Y H:i:s");
		$str .= " ";
		$str .= $this->get_title();
		$str .= " [" . $this->register_event_id . "]";
	
		return $str;
	}
	
	/** 
	 Gets the shortest available description for the event
	*/
	function get_short_description() {
		if(empty($this->code)) {
			return $this->description;
		} else {
			return $this->code;
		}	
	}
	
	/** 
	 Gets the ID of the register event slot.
	*/
	function get_slot_id() {
		return $this->register_event_slot_id;
	}
	
	/** 
	 Gets a long description of the event, including start time.
	*/
	function get_long_description() {
	
		$desc = "";
		
		if(!empty($this->description)) {
			$desc = $this->description;
		} else {
			$desc = $this->code;
		}
	
		$desc .= " (" . date("H:i", $this->start_date) . " - " . date("H:i", $this->end_date) . ")";
		
		//List courses
		if($this->uios && count($this->uios) > 0) {
						
			$desc .= "\n\r";
		
			if(count($this->uios) > 1) {			
				$desc .= "Courses:\n\r";
			}
			
			foreach($this->uios as $code => $description) {
				$desc .= "  " . $code . " - " . $description . "\n\r";
			}			
		}		
		
		return trim($desc);
	}
}
?>