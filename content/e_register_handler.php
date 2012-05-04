<?php
/**
 MWatts, 18 Jun 2009
 
 Handler for the register marking screen - processes
 learner marks, progress code changes etc.
*/

include_once("../../../config.php");
include_once("../lib/ebs_utility.php");
include_once("../lib/ebs_registers_utility.php");
include_once("../lib/ebs_user.php");

//Get some information about the register being submitted
if(isset($_POST["register_event_slot_id"]) && ebs_utility::is_integer($_POST["register_event_slot_id"])) {
	$register_event_slot_id = $_POST["register_event_slot_id"];
} else {	
	die("A valid register event slot ID was not supplied.");	
}

if(isset($_POST["register_event_id"]) && ebs_utility::is_integer($_POST["register_event_id"])) {
	$register_event_id = $_POST["register_event_id"];
} else {
	die("A valid register event ID was not supplied.");
}

//Get the submission mode
$submission_mode = $_POST["submission_mode"];

if($submission_mode != "M" && $submission_mode != "F") {
	die("The submission mode provided was not valid.");
}

//Is the current user actually logged in?
require_login();

//Get user information
$ebs_user = new ebs_user($USER->username);
$ebs_user->configure($CFG->db_host_name, $CFG->db_user_name, $CFG->db_password);
$ebs_user->load();

//Make sure they are actually a member of staff and can mark this register
if(!$ebs_user->is_member_of_staff() || !ebs_registers_utility::can_tutor_mark($ebs_user->get_staff_code(), $register_event_slot_id)) {	
	die("You do not have permissions to mark this register.");
}

//Get some information about the current slot
$current_slot = ebs_registers_utility::get_register_slot($register_event_slot_id);

if(!$current_slot->is_available_for_marking()) {
	ebs_utility::meta_redirect("../../../index.php?ebs_unavailable_register=true");
}

//Get some config data
$valid_usage_codes = ebs_registers_utility::get_usage_codes("L");
$valid_progress_codes = ebs_registers_utility::get_register_progress_codes();
$mark_all_learners = ebs_utility::get_web_config_parameter("MARKALLLEARNERS");

//Split the input arrays
$detail_ids = $_POST["register_event_detail_id"];
$usage_codes = $_POST["usage_code"];
$progress_codes = $_POST["progress_code"];
$progress_dates = $_POST["progress_date"];
$notes = $_POST["notes"];
$new_learner_names = $_POST["new_learner_name"];
$new_learner_usage_codes = $_POST["new_learner_usage_code"];
$new_learner_progress_codes = $_POST["new_learner_progress_code"];
$new_learner_progress_dates = $_POST["new_learner_progress_date"];
$new_learner_notes = $_POST["new_learner_notes"];

$are_all_marked = true;
$are_all_named = true;

//Do some input validation
if((count($detail_ids) != count($usage_codes)) || (count($new_learner_names) != count($new_learner_usage_codes))) {
	die("One or more of the register event details specified does not have an accompanying usage code.");
}

//Check marks for existing learners
for($i = 0; $i < count($detail_ids); $i++) {
		if(!ebs_utility::is_integer($detail_ids[$i])) {
			die("One or more of the register event detail IDs provided was not valid.");
		}
		
		if($usage_codes[$i] != "") {
			if(!array_key_exists($usage_codes[$i], $valid_usage_codes)) {
				die("One or more of the usage codes provided was not valid.");
			}
		} else {			
			$are_all_marked = false;
		}
}

//Now for new learners
for($i = 0; $i < count($new_learner_names); $i++) {
	if(trim($new_learner_names[$i]) == "") {
		$are_all_named = false;
	}
	
	if($new_learner_usage_codes[$i] != "") {
			if(!array_key_exists($new_learner_usage_codes[$i], $valid_usage_codes)) {
				die("One or more of the usage codes provided was not valid.");
			}
		} else {			
			$are_all_marked = false;
		}
}

//If all registers must be marked then we need to fail if they are not
if($submission_mode == "M" && $mark_all_learners == "Y" && !$are_all_marked) {
	die("All learners must be marked when submitting an e-register.");
}

//All new learners must be named
if(!$are_all_named) {
	die("All newly added learners must have a name.");
}

//Check progress codes and dates
if(count($progress_codes) != count($progress_dates)) {
	die("One or more progress code change does not have a corresponding progress date.");
}

if(count($new_learner_progress_codes) != count($new_learner_progress_dates)) {
	die("One or more new learner has a progress code change with no corresponding progress date.");
}

//CHeck codes and dates
for($i = 0; $i < count($progress_codes); $i++) {

	$progress_code = $progress_codes[$i];
	$progress_date = $progress_dates[$i];
	
	if(empty($progress_code) != empty($progress_date)) {
		die("Progress codes and progress dates are mismatched for $i.");			
	}

	if(!empty($progress_code) && !array_key_exists($progress_code, $valid_progress_codes)) {
		die("One or more of the progress codes provided was invalid.");
	}
	
	if(!empty($progress_date) && !date_create($progress_date)) {
		die("One or more of the progress dates provided was invalid.");
	}
}

//Same again for the new learners
for($i = 0; $i < count($new_learner_progress_codes); $i++) {

	$progress_code = $new_learner_progress_codes[$i];
	$progress_date = $new_learner_progress_dates[$i];

	if(empty($progress_code) != empty($progress_date)) {
		die("New leanrer progress codes and progress dates are mismatched.");
	}

	if(!empty($progress_code) && !array_key_exists($progress_code, $valid_progress_codes)) {
		die("One or more of the new learner progress codes provided was invalid.");
	}
	
	if(!empty($progress_date) && !date_create($progress_date)) {
		die("One or more of the new learner progress dates provided was invalid.");
	}
}

$connection = ebs_utility::connect();

//Process the marks data
for($i = 0; $i < count($detail_ids); $i++) {

	$progress_code = null;
	$progress_date = null;
	$n = null;
	
	if(!empty($progress_codes[$i])) {
		$progress_code = $progress_codes[$i];
		$progress_date = strtotime($progress_dates[$i] . " 00:00:00");
	}		
	
	if(!empty($notes[$i])) {
		$n = htmlspecialchars($notes[$i]);
	}
	
	ebs_registers_utility::mark_register_slot(
		$connection,
		$register_event_slot_id,
		$detail_ids[$i],
		$usage_codes[$i],
		$progress_code,
		$progress_date,
		$n
	);	
}


//Add any new learners
$default_progress_code = ebs_registers_utility::get_default_register_progress_code();

for($i = 0; $i < count($new_learner_names); $i++) {

	$progress_code = null;
	$progress_date = null;
	$n = null;
	
	if(!empty($new_learner_progress_codes[$i])) {
		$progress_code = $new_learner_progress_codes[$i];
		$progress_date = strtotime($new_learner_progress_dates[$i] . " 00:00:00");
	}
	
	if(!empty($new_learner_notes[$i])) {
		$n = htmlspecialchars($new_learner_notes[$i]);
	}

	ebs_registers_utility::add_new_learner_to_register(
		$connection, 
		$register_event_slot_id,
		$register_event_id,
		$ebs_user,
		$new_learner_names[$i],
		$new_learner_usage_codes[$i],
		$default_progress_code["Code"],
		$submission_mode,
		$progress_code,
		$progress_date,
		$n
	);

}

//Now auto mark rooms and tutors
ebs_registers_utility::auto_mark_slot($connection, $register_event_slot_id);

//Set the slot status
ebs_registers_utility::update_slot_status($connection, $register_event_slot_id, $submission_mode);

//Finally, make sure the event is marked as requiring attention if necessary
ebs_registers_utility::update_attention_flags($connection, $register_event_id);

if(!oci_commit($connection)) {
	$error = oci_error($connection);
	die("An error was encountered when committing the register marking transaction: " . $error["message"]);
}

oci_close($connection);

//Redirect to the register details page
ebs_utility::meta_redirect("../../../?marked=true");
?>