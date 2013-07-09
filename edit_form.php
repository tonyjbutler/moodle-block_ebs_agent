<?php
 
class block_ebs_agent_edit_form extends block_edit_form {
 
	protected function specific_definition($mform) {
 
		// Section header title according to language file.
		$mform->addElement("header", "configheader", "Settings");
 
		$mform->addElement("text", "config_db_host", "TNS name");
        $mform->setType('config_db_host', PARAM_TEXT);
		$mform->addRule("config_db_host", "TNS name is required.", "required", "", "server", false, false);

		$mform->addElement("text", "config_db_user", "Username");
        $mform->setType('config_db_user', PARAM_TEXT);
		$mform->addRule("config_db_user", "Username is required.", "required", "", "server", false, false);

		$mform->addElement("password", "config_db_password", "Password");
		$mform->addRule("config_db_password", "Password is required", "required", "server", false, false);
		
		$mform->addElement("text", "config_header_image_url", "Header image URL");
        $mform->setType('config_header_image_url', PARAM_TEXT);

		$mform->addElement("text", "config_header_text", "Header text");
        $mform->setType('config_header_text', PARAM_TEXT);

	}
}
 
?>