/**
 MWatts, 11 Jun 2009
 
 This file contains JavaScript functions used by the
 EBS Agent block for Moodle.
*/

/**

	GENERAL VALIDATION FUNCTIONS
	
*/

/* Validates the given required form fields */
function do_validate_required_fields(fields) {

	var result = true;

	for(var f in fields) {
	
		var field = document.getElementById(f);
		var isValid = true;
		
		//Check the value
		if(field.type == "text" || field.type == "password") {
			isValid = (trim_string(field.value) != "");
		}		
		
		//Set the error status
		if(!isValid) {		
			do_display_validation_message(f, fields[f]);		
			result = false;
		}
	}

	return result;
}

/* Resets errors on the given fields */
function do_reset_validation_messages(fields) {

	for(var i = 0; i < fields.length; i++) {	
		
		do_reset_validation_message(fields[i]);
	
	}

}

/* Displays a validation error message  */
function do_display_validation_message(fieldname, message) {	

	var error = document.getElementById(fieldname + "_error");	
	error.style.display = "block";
	error.innerHTML = message;

}

/* Resets the validation error message for a given field */
function do_reset_validation_message(fieldname) {
	
	var error = document.getElementById(fieldname + "_error");
	error.style.display = "none";
	error.innerHTML = "";

}

/**

	GLOBAL CONFIGURATION VALIDATION

*/

/* Validates the global configuration for the EBS Agent block */
function do_global_config_validation() {
	
	var result = true;
	
	//Reset error messages
	do_reset_validation_messages(new Array("db_host_name", "db_user_name", "db_password"));
	
	//Define required fields
	var requiredFields = {
		"db_host_name" : "Please enter a TNS name.",
		"db_user_name" : "Please enter a user name.",
		"db_password" : "Please enter the user password."
	};
	
	result = do_validate_required_fields(requiredFields);
	
	//Check that the preferred width is sensible
	var widthField = document.getElementById("preferred_width");	
	
	if(widthField.value != "") {
		if(widthField.value.match(/^([0-9]+)$/)) {
		
			var width = parseInt(widthField.value);
			
			if(width < 200 || width > 1000) {
			
				do_display_validation_message("preferred_width", "Width must be between 200 and 1000.");
				result = false;
			
			}
		
		} else {
		
			do_display_validation_message("preferred_width", "Width entered is not valid.");
			result = false;
		
		}
	}

	return result;
}

/**
 
	REGISTER MARKING FUNCTIONS
 
*/

function set_register_submission_mode(mode) {
	document.forms["register_form"].elements["submission_mode"].value = mode;
}

function do_register_marking_validation(form) {

	var error_container = document.getElementById("ebs_error_container");
	var error_messages = document.getElementById("ebs_error_list");
	var mark_all_learners = (form.elements["mark_all_learners"].value == "Y");
	var submission_mode = form.elements["submission_mode"].value;
	var result = true;	
	var errors = new Array();
	
	//You need to give all learners a name
	for(var i = 0; i < form.elements.length; i++) {
		
		var all_named = true;
		
		if(form.elements[i].name == "new_learner_name[]" && trim_string(form.elements[i].value) == "") {
			all_named = false;
		}		
		
		if(!all_named) {
			errors[errors.length] = "All new learners must have a name.";
			result = false;
		}
	}
			
	//Make sure all learners are marked if the web_config flag says they should be and this isn't a "finish later" submission
	if(mark_all_learners && submission_mode == "M") {
		
		var all_marked = true;
		
		for(var i = 0; i < form.elements.length; i++) {
			if((form.elements[i].name == "usage_code[]" || form.elements[i].name == "new_learner_usage_code[]") && form.elements[i].value == "") {
				all_marked = false;
				break;
			}
		}
	
		if(!all_marked) {
			errors[errors.length] = "All learners must be marked";
			result = false;
		}
	}
	
	//Display any errors to the user	
	if(!result) {
		
		error_messages.innerHTML = "";
	
		for(var i = 0; i < errors.length; i++) {
		
			error_messages.innerHTML += "<li>" + errors[i] + "</li>";
					
		}
		
		error_container.style.display = "block";
	}		

	return result;
}

var new_learner_rows = new Array();

/**
 Creates a new learner row
*/
function add_new_learner(is_alternate, display_photos, default_progress_code) {

	var register_body = document.getElementById("register_body");
	
	//Create new row
	var row = document.createElement("TR");
	
	//If other rows have been added, work out if this one should be alternate or not
	if(new_learner_rows.length > 0) {	
		is_alternate = (new_learner_rows[new_learner_rows.length - 1].className.indexOf("alternate") < 0);
	}
	
	//Record in the global array
	var current_row_index = (new_learner_rows.push(row) - 1);	
	
	//Create cells
	var person_code_cell = document.createElement("TD");
	var photo_cell = null;
	var name_cell = document.createElement("TD");
	var progress_cell = document.createElement("TD");
	var previous_mark_cells = new Array();
	previous_mark_cells[0] = document.createElement("TD");
	previous_mark_cells[1] = document.createElement("TD");
	previous_mark_cells[2] = document.createElement("TD");
	var current_mark_cell = document.createElement("TD");
	
	//Set the style if appropriate
	var css_class = "new_learner";
	
	if(is_alternate) {
		css_class += " alternate";
	}
	
	row.className = css_class;
	
	//Create a cancel button in the person code cell
	var cancel_link = document.createElement("A");
	cancel_link.setAttribute("title", "Cancel new learner.");
	cancel_link.setAttribute("href", "javascript:cancel_new_learner(" + current_row_index + ",'" + default_progress_code + "')");
	
	var cancel_image = document.createElement("IMG");
	cancel_image.setAttribute("alt", "Remove new learner from register.");
	cancel_image.setAttribute("title", "Remove new learner from register.");
	cancel_image.setAttribute("src", "../resources/icons/cancel.gif");
	
	cancel_link.appendChild(cancel_image);
		
	person_code_cell.className = "right";
	person_code_cell.appendChild(cancel_link);
	
	//Create a learner photo cell if appropriate
	if(display_photos) {
	
		photo_cell = document.createElement("TD");
		
		var learner_image = document.createElement("IMG");
		learner_image.setAttribute("alt", "New learner");
		learner_image.setAttribute("title", "New learner");
		learner_image.className = "learner_photo";		
		learner_image.setAttribute("src", "blob.php?domain=PEOPLE&name=PERSON_PICTURE&owner_ref=-1");
	
		photo_cell.appendChild(learner_image);
	}
	
	//Create a textbox for the name
	var name_field = document.createElement("INPUT");
	name_field.setAttribute("name", "new_learner_name[]");
	name_field.setAttribute("type", "text");
	name_field.setAttribute("length", "35");
	name_field.setAttribute("maxlength", "200");
	
	name_cell.appendChild(name_field);
	
	//Add the default progress code
	var progress_link = document.createElement("A");
	progress_link.setAttribute("href", "javascript:change_learner_progress(" + current_row_index + ",'" + default_progress_code + "',true)");
	progress_link.setAttribute("title", "Change student progress.");	
	
	var progress_image = document.createElement("IMG");
	progress_image.setAttribute("src", "../resources/icons/change.gif");
	progress_image.setAttribute("alt", "Change student progress.");
	progress_image.setAttribute("title", "Change student progress.");
	
	progress_link.appendChild(progress_image);
	
	var progress_text = document.createElement("SPAN");
	progress_text.innerHTML = default_progress_description;
	
	var progress_code_field = document.createElement("INPUT");
	progress_code_field.setAttribute("type", "hidden");
	progress_code_field.setAttribute("name", "new_learner_progress_code[]");
	
	var progress_date_field = document.createElement("INPUT");
	progress_date_field.setAttribute("type", "hidden");
	progress_date_field.setAttribute("name", "new_learner_progress_date[]");
	
	var notes_field = document.createElement("INPUT");
	notes_field.setAttribute("type", "hidden");
	notes_field.setAttribute("name", "new_learner_notes[]");
		
	progress_cell.className = "centered";
	progress_cell.appendChild(progress_code_field);
	progress_cell.appendChild(progress_date_field);
	progress_cell.appendChild(notes_field);
	progress_cell.appendChild(progress_text);			
	progress_cell.appendChild(progress_link);			

	//Create "disabled" cells for the previous marks
	previous_mark_cells[0].className = "inactive";
	previous_mark_cells[1].className = "inactive";
	previous_mark_cells[2].className = "inactive";
	
	//Create the mark for this week
	current_mark_cell.className = "centered";
	
	var mark_dropdown = document.createElement("SELECT");
	mark_dropdown.setAttribute("name", "new_learner_usage_code[]");
	
	var blank_option = document.createElement("OPTION");
	blank_option.setAttribute("value", "");
	
	mark_dropdown.appendChild(blank_option);
	
	for(var code in usage_codes) {
		
		var mark_option = document.createElement("OPTION");
		mark_option.setAttribute("value", code);
		mark_option.innerHTML = code + " - " + usage_codes[code];
		
		mark_dropdown.appendChild(mark_option);
	}
	
	current_mark_cell.appendChild(mark_dropdown);

	//Construct the row and cells
	row.appendChild(person_code_cell);
	
	//Add the photo if we're displaying them
	if(display_photos) {
		row.appendChild(photo_cell);
	}
	
	row.appendChild(name_cell);
	row.appendChild(progress_cell);
	row.appendChild(previous_mark_cells[0]);
	row.appendChild(previous_mark_cells[1]);
	row.appendChild(previous_mark_cells[2]);
	row.appendChild(current_mark_cell);
	
	//Append to the table body
	register_body.appendChild(row);
}

/**
 Cancels addition of the new learner at the given index.
*/
function cancel_new_learner(new_learner_index, default_progress_code) {

	if(confirm("Are you sure you wish to remove this new learner from the register?")) {

		var register_body = document.getElementById("register_body");
		var remaining_rows = new Array();
		var is_alternate = false;

		for(var i = 0; i < new_learner_rows.length; i++) {
			if(i == new_learner_index) {
				is_alternate = (new_learner_rows[i].className.indexOf("alternate") >= 0);
				register_body.removeChild(new_learner_rows[i]);
			} else {
				if(i > new_learner_index) {			
					new_learner_rows[i].className = is_alternate ? "alternate" : "";				
					is_alternate = !is_alternate;				
				}
			
				var index = remaining_rows.push(new_learner_rows[i]) - 1;
				var cancel_link = remaining_rows[index].getElementsByTagName("A")[0];
				cancel_link.setAttribute("href", "javascript:cancel_new_learner(" + index + ")");
				
				var progress_link = remaining_rows[index].getElementsByTagName("A")[1];
				progress_link.setAttribute("href", "javascript:change_learner_progress(" + index + ", '" + default_progress_code + "',true)");
			}
		}
		
		new_learner_rows = remaining_rows;
		
	}
}

/**
 Launches the progress code change popup for a given learner.
*/
function change_learner_progress(index, current_progress_code, is_new_learner) {

	var url = "change_progress.php?index=" + index + "&is_new_learner=" + (is_new_learner ? 1 : 0) + "&current_progress_code=" + current_progress_code;
	var handle = window.open(url, "progress_window", "toolbar=0,location=0,menubar=0,resizable=1,scrollbars=0,width=400,height=250");	
	handle.focus();
}

/**
 Actions a progress change from the popup - values are passed back into the register
 marking page for processing.
*/
function do_progress_change(index, is_new_learner, current_progress_code) {

	var form = window.opener.document.forms[0];	
	var do_update = true;
	var error_message = "";
	var progress_code;
	var progress_date_day;
	var progress_date_month;
	var progress_date_year;
	
	//Look at the appropriate field for the type of learner
	var progress_fields = find_learner_progress_fields(index, is_new_learner);
	
	//Validate and process progress code changes
	if(current_progress_code != document.forms[0].elements["progress_code"].value) {
	
		//Do some validation, innit
		progress_code = document.forms[0].elements["progress_code"].value;
		progress_date_day = document.forms[0].elements["progress_date_day"].value;
		progress_date_month = document.forms[0].elements["progress_date_month"].value;
		progress_date_year = document.forms[0].elements["progress_date_year"].value;
		
		if(progress_code == "") {
			do_update = false;
			error_message += "Please select a progress code.\n";
		}
		
		if(progress_date_day == "" || progress_date_day == "dd" || progress_date_year == "" || progress_date_year == "yyyy") {
			do_update = false;
			error_message += "Please enter a progress date";
		}
		
		//Do date validation
		if(do_update) {
		
			var day_regex = new RegExp("^([0-9]{2})$");
			var year_regex = new RegExp("^([0-9]{4})$");
		
			if(!day_regex.test(progress_date_day)) {
				do_update = false;				
			}
			
			if(!year_regex.test(progress_date_year)) {
				do_update = false;
			}
			
			if(do_update) {
				
				var progress_date = new Date();
				var first_of_month = new Date();
				
				progress_date.setFullYear(parseInt(progress_date_year), parseInt(progress_date_month) - 1, parseInt(progress_date_day));
				first_of_month.setFullYear(parseInt(progress_date_year), parseInt(progress_date_month) - 1, 1);
				
				//Look for invalid day/month combinations (e.g. 31 Feb)
				if(progress_date.getMonth() != first_of_month.getMonth()) {
					do_update = false;
				}
			}
			
			if(!do_update) {
				error_message += "Please enter a valid progress date in dd/mm/yyyy format";
			}
		}
		
		if(do_update) {
			
			progress_fields[0].value = progress_code;
			progress_fields[1].value = progress_date_year + "-" + progress_date_month + "-" + progress_date_day;			
			progress_fields[2].value = document.forms[0].elements["notes"].value;
						
			window.close();
			
		} else {
		
			alert(error_message);
		
		}
		
	} else {	
	
		//Set the notes and blank the other fields
		progress_fields[0].value = "";
		progress_fields[1].value = "";
		progress_fields[2].value = document.forms[0].elements["notes"].value;		
	
		window.close();
	}
}

/**
 Gets current learner progress values from the opening page and loads
 them into the popup.
*/
function get_learner_progress_values(index, is_new_learner) {

	var form = window.opener.document.forms[0];
	var progress_fields = find_learner_progress_fields(index, is_new_learner);

	//There has already been a progress code change so persist it
	if(progress_fields[0].value != "") {		
		document.forms[0].elements["progress_code"].value = progress_fields[0].value;
	}
	
	//And if there is a date, persist that too
	if(progress_fields[1].value != "") {
	
		var bits = progress_fields[1].value.split("-");
		document.forms[0].elements["progress_date_day"].value = bits[2];
		document.forms[0].elements["progress_date_month"].value = bits[1];
		document.forms[0].elements["progress_date_year"].value = bits[0];
	
	}
	
	if(progress_fields[2].value != "") {
		document.forms[0].elements["notes"].value = progress_fields[2].value;
	}
}

/**
 Finds the progress code and date fields for a learner
 with the given index.
*/
function find_learner_progress_fields(index, is_new_learner) {

	var form = window.opener.document.forms[0];
	var progress_code_index = -1;
	var progress_date_index = -1;
	var notes_index = -1;
	var progress_code_field_name;	
	var progress_date_field_name;
	var notes_field_name;
	var progress_code_field = null;
	var progress_date_field = null;	
	var notes_field = null;

	//Look at the appropriate field for the type of learner
	if(!is_new_learner) {
		progress_code_field_name = "progress_code[]";
		progress_date_field_name = "progress_date[]";
		notes_field_name = "notes[]";
	} else {
		progress_code_field_name = "new_learner_progress_code[]";		
		progress_date_field_name = "new_learner_progress_date[]";
		notes_field_name = "new_learner_notes[]";
	}
	
	//Find the fields
	for(var i = 0; i < form.elements.length; i++) {
		if(form.elements[i].name == progress_code_field_name) {
			progress_code_index++;
			
			if(progress_code_index == index && progress_code_field == null) {
				progress_code_field = form.elements[i];
			}
			
		} else if(form.elements[i].name == progress_date_field_name) {
			progress_date_index++;
			
			if(progress_date_index == index && progress_date_field == null) {
				progress_date_field = form.elements[i];
			}
		} else if(form.elements[i].name == notes_field_name && notes_field == null) {
			notes_index++;
			
			if(notes_index == index && notes_field == null) {
				notes_field = form.elements[i];
			}
		}
		
		if(progress_code_field != null && progress_date_field != null && notes_field != null) {
			break;
		}
	}	

	return new Array(progress_code_field, progress_date_field, notes_field);
}

/**

	UTILITY FUNCTIONS

*/

/* Trims whitespace from either end of a string */
function trim_string(str) {
	return str.replace(/^\s*/, "").replace(/\s*$/, "");
}

/* Performs a fill down on the given field */
function do_fill_down(preserve_values, fieldnames) {

	var fields = new Array();
	
	for(var i = 0; i < document.forms[0].elements.length; i++) {
		for(var j = 0; j < fieldnames.length; j++) {
			if(document.forms[0].elements[i].name == fieldnames[j]) {
				fields[fields.length] = document.forms[0].elements[i];
				break;
			}
		}
	}

	if(fields.length > 1) {
		
		var fill_down_value = fields[0].value;
		
		if(fill_down_value != "" || preserve_values == "N") {
			for(var i = 1; i < fields.length; i++) {
			
				var current_value = fields[i].value;
				
				if(current_value == "" || preserve_values == "N") {
					fields[i].value = fill_down_value;
				}
			}
		}
	}
}