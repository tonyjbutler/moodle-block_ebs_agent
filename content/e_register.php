<?php
/**
 MWatts, 15 Jun 2009
 
 Register marking page for the EBS Agent Moodle block.
*/
include_once("../../../config.php");
include_once("../lib/ebs_utility.php");
include_once("../lib/ebs_registers_utility.php");
include_once("../lib/ebs_user.php");
require_once("../lib/fetch_db_config.php");

//Make sure the user is logged in 
require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

$register_event_slot_id = optional_param('slot_id', 0, PARAM_INT);
$pageurl = new moodle_url('/blocks/ebs_agent/content/e_register.php', array('slot_id' => $register_event_slot_id));
$PAGE->set_url($pageurl);

//Get request information
if(isset($_GET["slot_id"]) && ebs_utility::is_integer($_GET["slot_id"])) {
	$register_event_slot_id = $_GET["slot_id"];
} else {
	die("A valid register event slot ID was not supplied.");
}

$include_inactive = false;
if(isset($_GET["include_inactive"]) && $_GET["include_inactive"] == "1") {
	$include_inactive = true;
}

$display_photos = false;
if(isset($_GET["display_photos"]) && $_GET["display_photos"] == "1") {
	$display_photos = true;
}

//Make sure the person logged in can actually access this information
$ebs_user = new ebs_user($USER->username);
$ebs_user->configure($ebs_db_host, $ebs_db_user, $ebs_db_password);
$ebs_user->load();

if(!$ebs_user->is_member_of_staff()) {
	ebs_utility::meta_redirect("../../../index.php?ebs_unauthorised_user=true");	
} else if(!ebs_registers_utility::can_tutor_mark($ebs_user->get_staff_code(), $register_event_slot_id)) {
	ebs_utility::meta_redirect("../../../index.php?ebs_unauthorised_user=true");	
}

//Get some information about the current slot
$current_slot = ebs_registers_utility::get_register_slot($register_event_slot_id);

if(!$current_slot->is_available_for_marking()) {
	ebs_utility::meta_redirect("../../../index.php?ebs_unavailable_register=true");
}

//Get some information on the register and a list of learners
$mark_all_learners = ebs_utility::get_web_config_parameter("MARKALLLEARNERS");
$preserve_existing_marks = ebs_utility::get_web_config_parameter('FILLDOWNPRESERVESEXISTINGMARK');
$register_summary = ebs_registers_utility::get_register_summary($register_event_slot_id);
$register_learners = ebs_registers_utility::get_register_learners($register_event_slot_id, false, $include_inactive);
$previous_slots = ebs_registers_utility::get_previous_event_slots($register_event_slot_id, 3);
$current_marks = ebs_registers_utility::get_learner_marks($register_event_slot_id);
$default_progress_code = ebs_registers_utility::get_default_register_progress_code();

//Some other data we need
$usages = ebs_registers_utility::get_usage_codes("L");

$page_title = $register_summary->get_title();
$regcode = $register_summary->get_short_description();

$PAGE->set_pagelayout('standard');
$PAGE->set_title($page_title);
$PAGE->set_heading('Marking e-register for ' . $regcode);

$PAGE->navbar->add(get_string('pluginname', 'block_ebs_agent'));
$PAGE->navbar->add($regcode, $pageurl);

//Print the page header
echo $OUTPUT->header();

?>		

		<link rel="stylesheet" type="text/css" href="../block_ebs_agent.css" />
		<script type="text/javascript" src="../block_ebs_agent.js"></script>		
	
		<form action="e_register_handler.php" method="post" name="register_form" onsubmit="return do_register_marking_validation(this);">
			<input type="hidden" name="mark_all_learners" value="<?php echo $mark_all_learners; ?>" />
			<input type="hidden" name="register_event_slot_id" value="<?php echo $register_event_slot_id; ?>" />
			<input type="hidden" name="register_event_id" value="<?php echo $current_slot->get_register_event_id(); ?>" />			
			<input type="hidden" name="submission_mode" value="M" />
			
			<div class="ebs_errors" id="ebs_error_container">
				
				<span>Please correct the following errors before submitting the e-register:</span>
			
				<ul id="ebs_error_list" />					
			</div>
			
			<table class="ebs_register">
			<thead>
				<tr>				
					<td style="text-align:right;">Person Code</td>
				
					<?php if($display_photos) { ?>
					<td style="width:72px;">
						&nbsp;
					</td>					
					<?php } ?>
				
					<td>Name</td>									
					<td class="centered">Progress</td>
					<td class="centered">
					<?php
						if(count($previous_slots) == 3) {
							echo $previous_slots[0]->get_date();
						} else {
							echo "&nbsp;";
						}
					?>
					</td>
					<td class="centered">
					<?php
						if(count($previous_slots) > 1) {
							echo $previous_slots[count($previous_slots) - 2]->get_date();
						} else {
							echo "&nbsp;";
						}
					?>
					</td>
					<td class="centered">
					<?php
						if(count($previous_slots) > 0) {
							echo $previous_slots[count($previous_slots) - 1]->get_date();
						} else {
							echo "&nbsp;";
						}
					?>
					</td>
					<td style="width:245px;" class="today centered">
						Today
						<a href="javascript:do_fill_down('<?php echo $preserve_existing_marks; ?>',new Array('usage_code[]','new_learner_usage_code[]'));" title="Fill down">
							<img src="../resources/icons/filldown.gif" alt="Fill down" />
						</a>
					</td>
				</tr>
			</thead>
			
			<tbody id="register_body">
				<?php
					$alternate = false;
				
					$i = 0;
					foreach($register_learners as $learner) {
					
						//Get the last 3 marks
						$marks = ebs_registers_utility::get_previous_detail_slots($learner->get_detail_id(), $register_event_slot_id, 3);
						
						//And get the mark for this slot if there is one
						$current_mark = "";
						if(count($current_marks) > 0 && array_key_exists($learner->get_detail_id(), $current_marks)) {
							$current_mark = $current_marks[$learner->get_detail_id()];
						}
						
						$cssClass = "";
					
						//Set the CSS class of the row
						if($alternate) {
							$cssClass .= "alternate ";
						}
						
						if($learner->is_unconfirmed()) {
							$cssClass .= "unconfirmed ";
						}
						
						$cssClass = trim($cssClass);
						
						if(empty($cssClass)) {
							echo "<tr>";
						} else {
							echo "<tr class=\"$cssClass\">";
						}
						
						$alternate = !$alternate;								
						
						$person_code = $learner->get_person_code();
						
						if($person_code == -1) {
							$person_code = "(New Learner)";
						}
						
						echo "<td style=\"text-align:right;\"><span class=\"learner_identity\">" . $person_code . "</span></td>" . "\n";
						
						if($display_photos) {
							echo "<td style=\"text-align:center;\"><img class=\"learner_photo\" src=\"blob.php?domain=PEOPLE&name=PERSON_PICTURE&owner_ref=" . $learner->get_person_code() . "\" alt=\"" . $learner->get_name() . "\" title=\"" . $learner->get_name() . "\" /></td>" . "\n";
						}
						
						echo "<td><span class=\"learner_identity\">" . $learner->get_name("[[surname]], [[forename]]") . "</span></td>" . "\n";
						echo "<td class=\"centered\">";												
						
						$temp_progress_code = $learner->get_temporary_progress_code();
						$temp_progress_date = $learner->get_temporary_progress_date();
						
						if(empty($temp_progress_code)) {
							$temp_progress_date = "";
						} else {
							$temp_progress_date = date_format($temp_progress_date, "Y-m-d");
						}
						
						echo "<input type=\"hidden\" name=\"progress_code[]\" value=\"" . $temp_progress_code . "\" />" . "\n";
						echo "<input type=\"hidden\" name=\"progress_date[]\" value=\"" . $temp_progress_date . "\" />" . "\n";
						echo "<input type=\"hidden\" name=\"notes[]\" value=\"" . $learner->get_notes() . "\" />" . "\n";
						
						$progress_code_description = $learner->get_progress_description();
						
						if(empty($progress_code_description)) {
							$progress_code_description = "(Not Set)";
						}						
						
						echo "<span>" . $progress_code_description . "</span>";
						echo "<a href=\"javascript:change_learner_progress($i,'" . $learner->get_progress_code() . "', false);\" title=\"Change student progress\">";
						echo "<img src=\"../resources/icons/change.gif\" alt=\"Change student progress\" title=\"Change student progress\" />" ;
						echo "</a>" . "\n";
						echo "</td>" . "\n";
						
						$mark = "";
						$css = "centered";
						if(count($previous_slots) == 3) {
							$mark = $marks[0]->get_usage_code();
							
							if(!$marks[0]->is_active()) {
								$css .= " inactive";
							}
						} 
						
						echo "<td class=\"$css\">$mark</td>" . "\n";
						
						$mark = "";
						$css = "centered";
						if(count($previous_slots) > 1) {
							$mark = $marks[count($previous_slots) - 2]->get_usage_code();
							
							if(!$marks[count($previous_slots) - 2]->is_active()) {
								$css .= " inactive";
							}
						}
						
						echo "<td class=\"$css\">$mark</td>" . "\n";
						
						$mark = "";
						$css = "centered";
						if(count($previous_slots) > 0) {
							$mark = $marks[count($previous_slots) - 1]->get_usage_code();
							
							if(!$marks[count($previous_slots) - 1]->is_active()) {
								$css .= " inactive";
							}
						}
						
						echo "<td class=\"$css\">$mark</td>" . "\n";
						echo "<td class=\"centered\">";
						
						//Display a dropdown of possible marks						
						echo "<input type=\"hidden\" name=\"register_event_detail_id[]\" value=\"" . $learner->get_detail_id() . "\" />";
						echo "<select name=\"usage_code[]\">";
						echo "<option value=\"\"></option>";
						
						foreach($usages as $code => $description) {
						
							$selected = "";							
							if(strcmp($code, $current_mark) == 0) {
								$selected = " selected=\"selected\"";
							}
						
							echo "<option value=\"$code\"$selected>$code - $description</option>";
						}
						
						echo "</select>";						
						
						echo "</td>" . "\n";
						echo "</tr>";
					
						$i++;
					}
				?>
			</tbody>		
			</table>
			
			<div class="ebs_register_footer">			
				<div class="options">
					Active learners only: 
					<?php
					if($include_inactive) {
					?>
						<a href="<?php echo get_page_url(false, $display_photos) ?>" title="Only show active learners on this e-register">Yes</a>
						| <strong>No</strong><br />
					<?php
					} else {
					?>
						<strong>Yes</strong>
						| <a href="<?php echo get_page_url(true, $display_photos); ?>" title="Show all learners on this e-register">No</a><br />
					<?php
					}					
					?>					
					Show learner photos:
					<?php
					if($display_photos) {
					?>
						<strong>Yes</strong> |
						<a href="<?php echo get_page_url($include_inactive, false); ?>" title="Hide learner photos">No</a><br />
					<?php
					} else {
					?>
						<a href="<?php echo get_page_url($include_inactive, true); ?>" title="Show learner photos">Yes</a>
						| <strong>No</strong><br />
					<?php
					}
					?>
					<a href="javascript:add_new_learner(<?php echo $alternate ? "true" : "false"; ?>, <?php echo $display_photos ? "true" : "false"; ?>,'<?php echo $default_progress_code["Code"]; ?>');" title="Add learner to e-register">Add new learner</a>
				</div>
			
				<div class="buttons">
					<button class="default" type="submit" onclick="set_register_submission_mode('M');">Submit</button>
					<button type="submit" onclick="set_register_submission_mode('F');">Finish Later</button>
				</div>	

				<div class="ebs_spacer">&nbsp;</div>

				<div style="margin-top:10px;">
					<strong>Marking Key</strong>

					<table class="ebs_register" style="width:auto;margin:0px;">
					<thead>
						<tr>
							<td style="width:75px;">Mark</td>
							<td style="width:210px;">Description</td>
						</tr>
					</thead>

					<tbody>
					<?php
						$alternate = false;
						foreach($usages AS $code => $description) {

							if($alternate) {
								echo "<tr class=\"alternate\" />";
							} else {
								echo "<tr>";
							}

							$alternate = !$alternate;

							echo "<td>$code</td>";
							echo "<td>$description</td>";
							echo "</td>";
						}
					?>
					</tbody>
					</table>
				
				</div>
			</div>
		</form>

		<div class="ebs_spacer">&nbsp;</div>

	<script type="text/javascript">
		
		var default_progress_code = "<?php echo $default_progress_code["Code"]; ?>";
		var default_progress_description = "<?php echo $default_progress_code["Description"]; ?>";
		
		var usage_codes = new Array();
		
		<?php
		foreach($usages as $code => $description) {
			echo "usage_codes[\"$code\"] = \"$description\";\n";
		}		
		?>
		
	</script>
	
<?php

echo $OUTPUT->footer();

function get_page_url($include_inactive, $display_photos) {

	global $register_event_slot_id;

	$url = "e_register.php?slot_id=" . $register_event_slot_id;
	
	if($include_inactive) {
		$url .= "&include_inactive=1";
	}
	
	if($display_photos) {
		$url .= "&display_photos=1";
	}

	return $url;
}

?>