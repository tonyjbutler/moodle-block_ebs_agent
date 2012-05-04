<?php

include_once("lib/ebs_user.php");
include_once("lib/ebs_tutor_registers_collection.php");
include_once("lib/ebs_register_summary.php");
include_once("lib/ebs_utility.php");
include_once("lib/ebs_registers_utility.php");

class block_ebs_agent extends block_base {


	public function init() {
		$this->title = "ebs4 e-registers";
	}


	public function get_content() {

		global $USER;
	
		//This method is called more than once (!) during the rendering process so we need to check to see if the function has already been run
		if($this->content == null) {
			
			//Let's see if the current Moodle user is an EBS user as well
			$ebs_user = new ebs_user($USER->username);			
			$ebs_user->configure($this->config->db_host, $this->config->db_user, $this->config->db_password);
			$ebs_user->load();
			
			$html = "";
			
			if($ebs_user->is_valid_user() && $ebs_user->is_member_of_staff()) {
			
				//Register the CSS/Javascript used by the block
				ebs_utility::register_stylesheet("blocks/ebs_agent/block_ebs_agent.css");				
				
				//Build up the block HTML							
				$html .= "<div class=\"ebs_agent_block\">";
				
				//Add in the configurable header
				$header_image = $this->config->header_image_url;
				$header_text = $this->config->header_text;
				
				if(!empty($header_image) || !empty($header_text)) {
				
					$html .= "<p style=\"text-align:center;\">";				
					
					if(!empty($header_image)) {
						$html .= "<img style=\"width:45px;height:45px;vertical-align:middle;margin:3px;\" src=\"" . $this->config->header_image_url . "\" />";				
					}
					
					if(!empty($header_text)) {
						$html .= "<em style=\"font-size:0.8em;\">" . $this->config->header_text . "</em>";								
					}
					
					$html .= "</p>";
					
				}
				
				$html .= "<p style=\"text-align:center;\"><strong>" . date("dS M Y") . "</strong></p>";
				$html .= "<p style=\"text-align:center;\"><strong>" . $USER->firstname . " " . $USER->lastname . "</strong></p>";
				$html .= "<p style=\"text-align:center;\">Today's unmarked e-registers</p>";
				
				//Load e-registers waiting for marking for today only
				$registers = new ebs_tutor_registers_collection($ebs_user->get_staff_code(), date_create(), true, false);
				$registers->configure($this->config->db_host, $this->config->db_user, $this->config->db_password);
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
				
				global $ebs_db_host;
				global $ebs_db_user;
				global $ebs_db_password;
				
				$ebs_db_host = $this->config->db_host;
				$ebs_db_user = $this->config->db_user;
				$ebs_db_password = $this->config->db_password;
				
				$previous_unmarked_registers = ebs_registers_utility::count_unmarked_registers_before_date($ebs_user, strtotime(date("d F Y") . " 00:00:00"));
								
				if($previous_unmarked_registers > 0) {
				
					$html .= "<hr />";
					$html .= "<p style=\"text-align:center;\">You have <strong>$previous_unmarked_registers</strong> previous unmarked register(s) oustanding.</p>";
					
				}		
				
			}
			
			//Build the content
			$this->content = new stdClass;									
			$this->content->text = $html;
			$this->content->footer = "";			
		}
		
		return $this->content;

	}

	public function instance_allow_config() {
		return true;
	}
	
	public function get_config() {
		return $this->config;
	}
}


?>