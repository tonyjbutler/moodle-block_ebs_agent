<?php
/**
 MWatts, 11 Jun 2009

 This Moodle block displays a list of today's registers for the 
 current user. The user can then navigate to the register
 marking page.,
*/

//Includes
include_once("lib/ebs_user.php");
include_once("lib/ebs_tutor_registers_collection.php");
include_once("lib/ebs_register_summary.php");
include_once("lib/ebs_utility.php");
include_once("lib/ebs_registers_utility.php");

/**
 Block class definition.
*/
class block_ebs_agent extends block_base {

	/**
	 Initialises the block.
	*/
	function init() {	
		
		$this->title = "e-Registers";
		$this->version = 2009061100;
	
	}
	
	/**
	 Specifies whether this block supports configuration.
	*/
	function has_config() {
		return true;
	}
	
	/**
	 Retrieves content for the block.
	*/
	function get_content() {
	
		global $CFG;
		global $USER;
	
		//This method is called more than once (!) during the rendering process so we need to check to see if the function has already been run
		if($this->content == null) {
		
			$html = "";

			//Let's see if the current Moodle user is an EBS user as well
			$ebs_user = new ebs_user($USER->username);			
			$ebs_user->configure($CFG->db_host_name, $CFG->db_user_name, $CFG->db_password);
			$ebs_user->load();
			
			if($ebs_user->is_valid_user() && $ebs_user->is_member_of_staff()) {
		
				//Register the CSS/Javascript used by the block
				ebs_utility::register_stylesheet("blocks/ebs_agent/block_ebs_agent.css");				
				
				//Build up the block HTML							
				$html .= "<div class=\"ebs_agent_block\">";
				
				//Add in the configurable header
				$header_image = $CFG->header_image_url;
				$header_text = $CFG->header_text;
				
				if(!empty($header_image) || !empty($header_text)) {
				
					$html .= "<p style=\"text-align:center;\">";				
					
					if(!empty($header_image)) {
						$html .= "<img style=\"width:45px;height:45px;vertical-align:middle;margin-right:3px;\" src=\"" . $CFG->header_image_url . "\" />";
					}
					
					if(!empty($header_text)) {
						$html .= "<em style=\"font-size:0.8em;\">" . $CFG->header_text . "</em>";								
					}
					
					$html .= "</p>";
					
				}
				
				$html .= "<p style=\"text-align:center;\"><strong>" . date("D jS M Y") . "</strong></p>";
				$html .= "<p style=\"text-align:center;\"><strong>" . $USER->firstname . " " . $USER->lastname . "</strong></p>";
				$html .= "<p style=\"text-align:center;\">Today's unmarked e-registers:</p>";
				
				//Load e-registers waiting for marking for today only
				$registers = new ebs_tutor_registers_collection($ebs_user->get_staff_code(), date_create(), true, false);
				$registers->configure($CFG->db_host_name, $CFG->db_user_name, $CFG->db_password);
				$registers->load();
				
				if($registers->get_count() == 0) {
					$html .= "<p style=\"text-align:center;\"><em>You have no unmarked e-registers.</em></p>";					
				} else {
					
					$html .= "<hr />";
					$html .= "<table>";
					$html .= "<tbody>";
					
					foreach($registers->get_registers() as $register) {

						$marking_url = "/blocks/ebs_agent/content/e_register.php?slot_id=" . $register->get_slot_id();
						
						$html .= "<tr>";
						$html .= "<td class=\"event\"><a title=\"" . $register->get_long_description() . "\" href=\"$marking_url\">" . $register->get_short_description() . "</a></td>";
						$html .= "<td class=\"mark\"><a title=\"Mark this register\" href=\"$marking_url\">&raquo;</a></td>";
						$html .= "</tr>";
						
					}
					
					$html .= "</tbody>";
					$html .= "</table>";
				}
				
				$previous_unmarked_registers = ebs_registers_utility::count_unmarked_registers_before_date($ebs_user, strtotime(date("d F Y") . " 00:00:00"));
								
				if($previous_unmarked_registers > 0) {
				
					$html .= "<hr />";
					$html .= "<p style=\"text-align:center;\">You have <strong>$previous_unmarked_registers</strong> previous unmarked register(s) oustanding.</p>";
					
				}

                $html .= "</div>";
			}
			
			//Build the content
			$this->content = new stdClass;									
			$this->content->text = $html;
			$this->content->footer = "";
		}
		
		return $this->content;
	}

	/**
	 This method handles global configuration save, including server side validation.
	*/
	function config_save($data) {
	
		$result = true;		
		
		//Validate required fields
		
		$required_fields = array("db_host_name", "db_user_name", "db_password");						
		
		foreach($required_fields as $i => $field) {	
		
			//Get the value to be validated (note that $data[$field] does not work)
			$value = $data->$field;
					
			if(empty($value)) {
				$result = false;
				break;
			} else if(trim($value) == "") {
				$result = false;
				break;
			}				
		}
		
		//Validate the preferred width
		if(isset($data->preferred_width)) {
		
			$width = $data->preferred_width;
		
			if(!empty($width)) {			
				if(preg_match("/^([0-9]+)$/", $width)) {
					if($width < 180 || $width > 210) {
						$result = false;
					}
				} else {
					$result = false;
				}			
			}
		}
		
		//Test the database connection
		if($result) {
			if(!ebs_utility::is_connection_valid($data->db_host_name, $data->db_user_name, $data->db_password)) {
				$result = false;
			}
		}		
		
		//If validation was passed, then do the updates
		if($result) {
			foreach($data as $key => $value) {			
				set_config($key, $value);
			}
		}	
	
		return $result;
	}
	
	/** 
	 Gets the preferred width of the block
	*/
	function preferred_width() {
			
		global $CFG;
		
		$width = 200;
		
		if(isset($CFG->preferred_width)) {
			$width = (int)$CFG->preferred_width;
		}
		
		return $width;
	}
}
?>