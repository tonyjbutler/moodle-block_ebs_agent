<?php
/**
 MWatts, 25 Jun 2009
 
 Popup window for changing a learner's progress code
 on an e-register. 
*/
include_once("../../../config.php");
include_once("../lib/ebs_utility.php");
include_once("../lib/ebs_registers_utility.php");
require_once("../lib/fetch_db_config.php");

//Get some information on what we're looking at
$index = -1;
if(isset($_GET["index"]) && ebs_utility::is_integer($_GET["index"])) {
	$index = $_GET["index"];
}

$is_new_learner = 0;
if(isset($_GET["is_new_learner"]) && ebs_utility::is_integer($_GET["is_new_learner"])) {
	$is_new_learner = $_GET["is_new_learner"];
}

$current_progress_code = "";
if(isset($_GET["current_progress_code"])) {
	$current_progress_code = $_GET["current_progress_code"];
}

//Make sure we have a valid set of inputs
if($index < 0) {
	die("The index value supplied was not valid.");
}

$progress_codes = ebs_registers_utility::get_register_progress_codes();

?>
<html>
<head>
	<?php
	foreach($CFG->stylesheets as $stylesheet) {
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$stylesheet\" />" . "\n";
	}	
	?>
	<link rel="stylesheet" type="text/css" href="../block_ebs_agent.css" />
	<script type="text/javascript" src="../block_ebs_agent.js"></script>
</head>
<body onload="get_learner_progress_values(<?php echo $index; ?>,<?php echo $is_new_learner ? "true" : "false"; ?>)">
	<div id="page">
		<div id="header" class="clearfix">
			<h3 class="headermain">Change process code</h3>
		</div>
	
		<div id="content" class="ebs_progress_code_change">			
			<form name="form">
			
				<label for="progress_code">
					<span class="title">Progress code :</span>
					<select id="progress_code" name="progress_code">
						<option value=""></option>
						<?php
						foreach($progress_codes as $code => $description) {
						
							$selected = "";
							if(strcmp($code, $current_progress_code) == 0) {
								$selected = "selected=\"selected\"";
							}
						
							echo "<option value=\"$code\" $selected>$code - $description</option>" . "\n";
						
						}					
						?>
					</select>
					
					&nbsp;
				</label>
				
				<label for="progress_date_day">
					<span class="title">Progress date :</span>
					<input type="text" id="progress_date_day" size="2" maxlength="2" value="dd" />
					<span style="margin-left:2px;margin-right:2px;">/</span>
					<select name="progress_date_month">
						<option value="01">1 - Jan</option>
						<option value="02">2 - Feb</option>
						<option value="03">3 - Mar</option>
						<option value="04">4 - Apr</option>
						<option value="05">5 - May</option>
						<option value="06">6 - Jun</option>
						<option value="07">7 - Jul</option>
						<option value="08">8 - Aug</option>
						<option value="09">9 - Sep</option>
						<option value="10">10 - Oct</option>
						<option value="11">11 - Nov</option>
						<option value="12">12 - Dec</option>
					</select>
					<span style="margin-left:2px;margin-right:2px;"> / </span>
					<input type="text" name="progress_date_year" size="4" maxlength="4" value="yyyy" />
					
					&nbsp;
				</label>
				
				<label for="notes">
					<span class="title">Notes :</span>
					<textarea name="notes" id="notes" rows="4" cols="20"></textarea>
					&nbsp;
				</label>
				
				<div class="ebs_spacer">&nbsp;</div>
				
				<input type="button" value="Submit" class="button" onclick="do_progress_change(<?php echo $index; ?>,<?php echo $is_new_learner == 1 ? "true" : "false"; ?>,'<?php echo $current_progress_code; ?>');" />
			
			</form>
		</div>
	</div>
</body>
</html>