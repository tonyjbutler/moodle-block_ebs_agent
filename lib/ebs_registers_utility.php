<?php
/**
 MWatts, 15 Jun 2009
 
 Registers specific utility class providing static methods
 for manipulating registers.
*/
include_once("ebs_utility.php");
include_once("ebs_register_summary.php");
include_once("ebs_register_learner.php");
include_once("ebs_register_slot.php");
include_once("ebs_register_detail_slot.php");

class ebs_registers_utility {


	/**
	 Determines whether the given tutor can mark the 
	 specified register event slot.
	*/
	public static function can_tutor_mark($staff_code, $register_event_slot_id) {
			
		$sql = "
			SELECT
				Count(1)
			FROM
				register_event_details_slots REDS
				INNER JOIN register_event_details RED ON RED.id = REDS.register_event_detail_id
				INNER JOIN people P ON P.person_code = RED.object_id
				INNER JOIN person_functions PF ON PF.function_code = RED.person_function
			WHERE
				RED.object_type = 'T'
				AND RED.detail_status = 'A'
				AND REDS.register_event_slot_id = :slot_id
				AND Upper(P.fes_staff_code) = Upper(:staff_code)
				AND Nvl(PF.can_tutor_mark,'Y') = 'Y'
		";
		
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		//Prepare the statement
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the tutor marking query: " . $error["message"]);
		}
		
		//Bind variables
		oci_bind_by_name($statement, ":slot_id", $register_event_slot_id);
		oci_bind_by_name($statement, ":staff_code", $staff_code);
		
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the tutor marking query: " . $error["message"]);
		}
	
		$data = oci_fetch_array($statement, OCI_NUM);
		
		oci_close($connection);
		
		return ($data[0] > 0);
	}
	
	/**
	 Gets a summary of a register.
	*/
	public static function get_register_summary($register_event_slot_id) {
	
		$summary = null;
		
		$sql = "
			SELECT
				RE.id AS register_event_id,
				RES.id AS register_event_slot_id,
				RE.event_code,
				RE.description AS event_description,
				RES.startdate AS start_date,
				RES.enddate AS end_date,
				RES.slot_status AS status
			FROM
				register_event_slots RES
				INNER JOIN register_events RE ON RE.id = RES.register_event_id
			WHERE
				RES.id = :slot_id
		";
		
		//Connect to the database
		$connection = ebs_utility::connect();
	
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		//Parse the SQL statement and set parameters
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the register summary SQL: " . $error["message"]);
		}
		
		oci_bind_by_name($statement, ":slot_id", $register_event_slot_id);
		
		//Execute
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the register summary SQL: " . $error["message"]);			
		}
		
		if($data = oci_fetch_array($statement, OCI_DEFAULT)) {
		
			$summary = new ebs_register_summary(
							$data["REGISTER_EVENT_ID"],
							$data["REGISTER_EVENT_SLOT_ID"],
							$data["EVENT_CODE"],
							$data["EVENT_DESCRIPTION"],
							date_create($data["START_DATE"]),
							date_create($data["END_DATE"]),
							$data["STATUS"]
						);					
			
		}
		
		oci_close($connection);
		
		return $summary;
	}

	/**
	 Gets a list of learners on a register.
	*/
	public static function get_register_learners($register_event_slot_id, $include_deleted = false, $include_inactive = false) {
		
		$learners = array();
		
		$sql = "
			SELECT
				RED.id AS detail_id,
				RED.object_id AS person_code,
				RED.progress_code,
				Nvl(CSP.short_description, CSP.long_description) AS progress_description,
				P.forename,
				P.surname,
				RED.unconfirmed_learner AS temporary_name,
				CASE RED.detail_status
					WHEN 'A' THEN 0
					ELSE 1
					END AS is_deleted,
				RED.temp_progress_code,
				CASE
					WHEN RED.temp_progress_date IS NULL THEN NULL
					ELSE To_Char(RED.temp_progress_date, 'yyyy-MM-dd HH24:mi:ss')
					END AS temp_progress_date,
				N.notes
			FROM
				register_event_details RED
				INNER JOIN register_event_details_slots REDS ON REDS.register_event_detail_id = RED.id
				LEFT JOIN people P ON P.person_code = RED.object_id
				LEFT JOIN cms_students_progress CSP ON CSP.student_progress_code = RED.progress_code
				LEFT JOIN note_links NL ON NL.parent_table = 'REGISTER_EVENT_DETAILS_SLOTS' AND NL.parent_id = REDS.id
				LEFT JOIN notes N ON N.notes_id = NL.notes_id
			WHERE
				RED.object_type = 'L'				
				AND RED.detail_status = CASE :include_deleted WHEN 'Y' THEN RED.detail_status ELSE 'A' END
				AND Nvl(CSP.progress_status_code,'^') = CASE :include_inactive WHEN 'Y' THEN Nvl(CSP.progress_status_code,'^') ELSE 'A' END
				AND REDS.register_event_slot_id = :register_event_slot_id
			ORDER BY
				P.surname ASC, P.forename ASC, P.person_code ASC
		";
	
		//Get a connection
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		//Prepare the statement and bind
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the register learners query: " . $error["message"]);
		}
		
		oci_bind_by_name($statement, ":register_event_slot_id", $register_event_slot_id);
		
		$temp = ($include_deleted ? "Y" : "N"); //Cannot use the expression in the actual function call, so assign to a temporary variable
		oci_bind_by_name($statement, ":include_deleted", $temp);
		
		$temp = ($include_inactive ? "Y" : "N");
		oci_bind_by_name($statement, ":include_inactive", $temp);
		
		//Execute
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the register learners query: " . $error["message"]);
		}
		
		//Iterate through results
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
			
			$learners[] = new ebs_register_learner(
							$row["DETAIL_ID"],
							$row["PERSON_CODE"],
							$row["FORENAME"],
							$row["SURNAME"],
							$row["TEMPORARY_NAME"],
							$row["PROGRESS_CODE"],
							$row["PROGRESS_DESCRIPTION"],
							$row["TEMP_PROGRESS_CODE"],
							date_create($row["TEMP_PROGRESS_DATE"]),
							($row["IS_DELETED"] == 1),
							$row["NOTES"]
						);
		}
		
		oci_close($connection);
		
		return $learners;
	}
	
	/**
	 Gets a certain number of slots before a given slot.
	*/	
	public static function get_previous_event_slots($register_event_slot_id, $number) {
	
		$slots = array();		
		
		$sql = "
			SELECT
				*
			FROM (
				SELECT
					RES.id,
					RES.register_event_id,
					RES.session_code,
					To_Char(RES.startdate, 'yyyy-MM-dd HH24:mi:ss') AS start_date,
					To_Char(RES.enddate, 'yyyy-MM-dd HH24:mi:ss') AS end_date,
					RES.break,
					RES.slot_status
				FROM
					register_event_slots RES
					INNER JOIN register_event_slots RES2 ON RES2.register_event_id = RES.register_event_id
				WHERE
					RES.startdate < RES2.startdate
					AND RES2.id = :register_event_slot_id
				ORDER BY
					RES.startdate DESC
			)
			WHERE
				rownum <= :number_of_slots
			ORDER BY
				start_date ASC
		";
		
		//Connect to the database
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		//Parse the query
		$statement = oci_parse($connection, $sql);
	
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the previous register slots query: " . $error["message"]);
		}
		
		//Bind variables
		oci_bind_by_name($statement, ":number_of_slots", $number);
		oci_bind_by_name($statement, ":register_event_slot_id", $register_event_slot_id);
		
		//Execute
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the previous register slots query: " . $error["message"]);
		}
		
		//Iterate over the results
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
		
			$slots[] = new ebs_register_slot(
							$row["ID"],
							$row["REGISTER_EVENT_ID"],
							$row["SESSION_CODE"],
							date_create($row["START_DATE"]),
							date_create($row["END_DATE"]),
							$row["BREAK"],
							$row["SLOT_STATUS"]
						);
		}
		
		oci_close($connection);
	
		return $slots;
	}	
	
	/**
	 Gets an associative array (usage code => description) of active usages configured
	 for the specified object type.
	*/
	public static function get_usage_codes($object_type) {
	
		$usages = array();
		
		$sql = "
			SELECT
				usage_code,
				description
			FROM
				usages
			WHERE
				Nvl(fes_active,'Y') = 'Y'
				AND object_type = :object_type
			ORDER BY
				usage_code ASC				
		";
		
		//Connect
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		//Parse
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the usage codes query: " . $error["message"]);
		}
		
		//Bind variables
		oci_bind_by_name($statement, ":object_type", $object_type);
		
		//Execute
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the usage codes query: " . $error["message"]);
		}
	
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
			
			$usages[$row["USAGE_CODE"]] = $row["DESCRIPTION"];
			
		}
		
		oci_close($connection);
		
		return $usages;
	}
	
	/**
	 Gets the previous N detail slots for a given detail and slot.
	*/
	public static function get_previous_detail_slots($register_event_detail_id, $register_event_slot_id, $number) {
		
		$slots = array();
		
		$sql = "
			SELECT
				*
			FROM (
				SELECT
					Nvl(REDS.id,0) AS id,
					RED.id AS register_event_detail_id,
					RES.id AS register_event_slot_id,
					REDS.usage_code,
					RES.startdate AS start_date,
					RES.enddate AS end_date,
					RES.break,
					RED.object_id,
					RED.object_type
				FROM
					register_event_slots RES
					INNER JOIN register_event_slots RES2 ON RES2.id = :slot_id
					INNER JOIN register_event_details RED ON RED.id = :detail_id
					LEFT JOIN register_event_details_slots REDS ON REDS.register_event_slot_id = RES.id AND REDS.register_event_detail_id = RED.id
				WHERE
					RES.startdate < RES2.startdate
					AND RES.register_event_id = RES2.register_event_id
				ORDER BY
					RES.startdate DESC
			)
			WHERE
				rownum <= :number_of_slots
			ORDER BY
				start_date ASC
		";
		
		//Connect
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
	
		//Parse the statement
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the previous detail slots query: " . $error["message"]);
		}
		
		//Bind variables
		oci_bind_by_name($statement, ":slot_id", $register_event_slot_id);
		oci_bind_by_name($statement, ":detail_id", $register_event_detail_id);
		oci_bind_by_name($statement, ":number_of_slots", $number);
		
		//Execute
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the previous detail slots query: " . $error["message"]);
		}
		
		//Iterate through results
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
		
			$slots[] = new ebs_register_detail_slot (
							$row["ID"],
							$row["REGISTER_EVENT_DETAIL_ID"],
							$row["REGISTER_EVENT_SLOT_ID"],
							$row["REGISTER_EVENT_ID"],
							$row["OBJECT_TYPE"],
							$row["OBJECT_ID"],
							date_create($row["START_DATE"]),
							date_create($row["END_DATE"]),
							$row["BREAK"],
							$row["USAGE_CODE"]
						);
		
		}
		
		oci_close($connection);
		
		return $slots;
	}
	
	/**
	 Marks a register slot for a given detail.
	*/
	public static function mark_register_slot($connection, $register_event_slot_id, $register_event_detail_id, $usage_code, $progress_code, $progress_date, $notes) {
	
		global $USER;
		
		$sql = "BEGIN ea_reg_events_pkg.MarkSlot(:slot_id, NULL, :detail_id, :usage_code, :progress_code, :progress_date, :notes, :username); END;";
	
		//Parse the query
		$statement = oci_parse($connection, trim($sql));		
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the register marking query: " . $error["message"]);
		}
		
		if(!empty($progress_date)) {
			$progress_date = date("d-M-Y", $progress_date);
		}		
		
		//Bind variables
		oci_bind_by_name($statement, ":slot_id", $register_event_slot_id);
		oci_bind_by_name($statement, ":detail_id", $register_event_detail_id);
		oci_bind_by_name($statement, ":usage_code", $usage_code);
		oci_bind_by_name($statement, ":progress_code", $progress_code);
		oci_bind_by_name($statement, ":progress_date", $progress_date);
		oci_bind_by_name($statement, ":notes", $notes);
		oci_bind_by_name($statement, ":username", $USER->username);
		
		//Go!
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error($statement);
			die("An error was encountered executing the register marking query: " . $error["message"]);
		}
	}
	
	/**
	 Gets details of a specific register slot.
	*/
	public static function get_register_slot($register_event_slot_id) {
	
		$slot = null;
		
		$sql = "
			SELECT
				id,
				register_event_id,
				session_code,
				To_Char(startdate, 'yyyy-MM-dd HH24:mi:ss') AS start_date,
				To_Char(enddate, 'yyyy-MM-dd HH24:mi:ss') AS end_date,
				break,
				slot_status
			FROM
				register_event_slots
			WHERE
				id = :register_event_slot_id
		";
		
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error($connection);
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the register slot query: " . $error["message"]);
		}
		
		oci_bind_by_name($statement, ":register_event_slot_id", $register_event_slot_id);
		
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error($statement);
			die("An error was encountered executing the register slot query: " . $error["message"]);
		}
		
		$row = oci_fetch_array($statement, OCI_DEFAULT);
	
		if($row) {		
			$slot = new ebs_register_slot(
						$row["ID"],
						$row["REGISTER_EVENT_ID"],
						$row["SESSION_CODE"],
						date_create($row["START_DATE"]),
						date_create($row["END_DATE"]),
						$row["BREAK"],
						$row["SLOT_STATUS"]
					);		
		}
		
		oci_close($connection);
		
		return $slot;
	}
	
	/**
	 Performs auto-marking of a slot for tutors and rooms.
	*/
	public static function auto_mark_slot($connection, $register_event_slot_id) {
	
		global $USER;
	
		//Query to get default marks
		$lookup_sql = "SELECT object_type, usage_code FROM usages WHERE object_type IN ('T','R') AND is_default_usage = 'Y' AND is_positive = 'Y'";
		
		$lookup_statement = oci_parse($connection, $lookup_sql);
		
		if(!$lookup_statement) {
			$error = oci_error($lookup_statement);
			die("An error was encountered parsing the default usages lookup query: " . $error["message"]);
		}
	
		if(!oci_execute($lookup_statement, OCI_DEFAULT)) {
			$error = oci_error($lookup_statement);
			die("An error was encountered executing the default usages lookup query: " . $error["message"]);
		}
		
		while($row = oci_fetch_array($lookup_statement, OCI_DEFAULT)) {
		
			$mark_sql = "
				UPDATE
					register_event_details_slots
				SET
					usage_code = :usage_code,
					actual_start_date = planned_start_date,
					actual_end_date = planned_end_date,
					updated_by = :username,
					updated_date = SYSDATE
				WHERE
					register_event_slot_id = :register_event_slot_id
					AND object_type = :object_type
					AND usage_code IS NULL
			";
			
			$mark_statement = oci_parse($connection, $mark_sql);
			
			if(!$mark_statement) {
				$error = oci_error($mark_startement);
				die("An error was encountered parsing the detail slot marking query: " . $error["message"]);
			}
			
			oci_bind_by_name($mark_statement, ":usage_code", $row["USAGE_CODE"]);
			oci_bind_by_name($mark_statement, ":username", $USER->username);
			oci_bind_by_name($mark_statement, ":register_event_slot_id", $register_event_slot_id);
			oci_bind_by_name($mark_statement, ":object_type", $row["OBJECT_TYPE"]);
			
			if(!oci_execute($mark_statement, OCI_DEFAULT)) {
				$error = oci_error($mark_statement);
				die("An error was encountered executing the detail slot marking query: " . $error["message"]);
			}
		}				
	}
	
	/**
	 Updates a slot's status depending on how many details are marked and the submission mode
	 used when marking.
	*/
	public static function update_slot_status($connection, $register_event_slot_id, $submission_mode) {
	
		global $USER;
	
		$sql = "
			UPDATE 
				register_event_slots RES
			SET
				slot_status = :slot_status,
				updated_by = :username,
				updated_date = SYSDATE
			WHERE
				id = :register_event_slot_id
				AND (
					:slot_status = 'F'
					OR NOT EXISTS (
						SELECT 
							null 
						FROM 
							register_event_details_slots REDS 
						WHERE 
							REDS.register_event_slot_id = RES.id
							AND REDS.object_type IN ('T','R','L')
							AND REDS.usage_code IS NULL
					)
				)
		";
		
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the slot status refresh query: " . $error["message"]);
		}
	
		oci_bind_by_name($statement, ":slot_status", $submission_mode);
		oci_bind_by_name($statement, ":username", $USER->username);
		oci_bind_by_name($statement, ":register_event_slot_id", $register_event_slot_id);
		
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error($statement);
			die("An error was encountered executing the slot status refresh query: " . $error["message"]);
		}
	}
	
	/**
	 Gets learner marks in an associative array for a given slot.
	*/
	public static function get_learner_marks($register_event_slot_id) {
	
		$marks = array();
		
		$sql = "
			SELECT
				register_event_detail_id,
				usage_code
			FROM
				register_event_details_slots
			WHERE
				register_event_slot_id = :register_event_slot_id
				AND object_type = 'L'
		";
		
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
	
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($statement);
			die("An error was encountered parsing the learner marks query: " . $error["message"]);
		}
		
		oci_bind_by_name($statement, ":register_event_slot_id", $register_event_slot_id);
		
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the learner marks query: " . $error["message"]);
		}
		
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
		
			$marks[$row["REGISTER_EVENT_DETAIL_ID"]] = $row["USAGE_CODE"];
		
		}
		
		oci_close($connection);
		
		return $marks;
	}
	
	/**
	 Gets a list of valid progress codes for a learner.
	*/
	public static function get_register_progress_codes() {
	
		$progress_codes = array();
		
		$sql = "
			SELECT
				student_progress_code,
				Nvl(short_description, long_description) AS description
			FROM
				cms_students_progress
			WHERE
				Nvl(fes_active,'Y') = 'Y'
			ORDER BY
				student_progress_code ASC
		";
		
		//Connect
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error($connection);
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
		
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($connection);
			die("An error was encountered parsing the register progress codes query: " . $error["message"]);
		}		
		
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the register progress codes query: " . $error["message"]);
		}
		
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
			$progress_codes[$row["STUDENT_PROGRESS_CODE"]] = $row["DESCRIPTION"];
		}
		
		oci_close($connection);
	
		return $progress_codes;
	}
	
	/**
	 Gets the default register progress code for a learner.
	*/
	public static function get_default_register_progress_code() {
	
		$sql = "
			SELECT
				student_progress_code AS progress_code,
				Nvl(short_description, long_description) AS description
			FROM
				cms_students_progress
			WHERE
				progress_status_code = 'A'
				AND Nvl(fes_active,'Y') = 'Y'
				AND generation_default = 'Y'
		";
		
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error($connection);
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
	
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($connection);
			die("An error was encountered parsing the default register progress code query: " . $error["message"]);
		}
		
		if(!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			die("An error was encountered executing the default register progress code query: " . $error["message"]);
		}
		
		$data = oci_fetch_array($statement, OCI_DEFAULT);
		
		oci_close($connection);
		
		return array( "Code" => $data["PROGRESS_CODE"], "Description" => $data["DESCRIPTION"] );
	}
	
	/**
	 Adds a new learner to the e-register.
	*/
	public static function add_new_learner_to_register($connection, $register_event_slot_id, $register_event_id, $ebs_user, $name, $usage_code, $default_progress_code, $submission_mode, $new_progress_code, $new_progress_date, $notes) {
	
		//Get some information about the user
		$username = $ebs_user->get_username();
		$person_code = $ebs_user->get_person_code();
	
		//First, we need to get the week pattern of the new learner - i.e. from this slot to the end of the event
		$week_pattern = str_pad("", 53, "0", STR_PAD_RIGHT);	
	
		$week_pattern_sql = "
			SELECT
				RES.id,
				ebs_register_events_pkg.GetWeekNumberFromDates(S.start_date, RES.startdate) AS week_number
			FROM
				register_event_slots RES
				INNER JOIN register_event_slots RES2 ON RES2.register_event_id = REs.register_event_id
				INNER JOIN sessions S ON S.session_code = RES.session_code
			WHERE
				RES2.id = :register_event_slot_id
				AND RES.startdate > RES2.startdate
			ORDER BY
				RES.startdate ASC
		";
	
		$week_pattern_statement = oci_parse($connection, $week_pattern_sql);
		
		if(!$week_pattern_statement) {
			$error = oci_error($week_pattern_statement);
			die("An error was encountered parsing the week pattern query: " . $error["message"]);
		}
		
		oci_bind_by_name($week_pattern_statement, ":register_event_slot_id", $register_event_slot_id);
		
		if(!oci_execute($week_pattern_statement, OCI_DEFAULT)) {
			$error = oci_error($week_pattern_statement);
			die("An error was encountered executing the week pattern query: " . $error["message"]);
		}
		
		while($week_pattern_data = oci_fetch_array($week_pattern_statement, OCI_DEFAULT)) {
			
			$week_number = $week_pattern_data["WEEK_NUMBER"];
			$week_pattern = substr($week_pattern, 0, $week_number - 2) . "1" . substr($week_pattern, $week_number);			
		}
		
		//Now build the query to add the learner
		$create_learner_sql = "BEGIN ebs_register_events_pkg.CreateEventDetailExt(:register_event_id, 'L', -1, :username, :user_person_code, :week_pattern, 'A', :name, :register_event_slot_id, :submission_mode, :detail_id); END;";

		$create_learner_statement = oci_parse($connection, $create_learner_sql);
		
		if(!$create_learner_statement) {
			$error = oci_error($connection);
			die("An error was encountered parsing the create learner query: " . $error["message"]);
		}
		
		//Bind variables
		oci_bind_by_name($create_learner_statement, ":register_event_id", $register_event_id, 10, SQLT_INT);		
		oci_bind_by_name($create_learner_statement, ":username", $username, 30, SQLT_CHR);
		oci_bind_by_name($create_learner_statement, ":user_person_code", $person_code, 10, SQLT_INT);
		oci_bind_by_name($create_learner_statement, ":week_pattern", $week_pattern, 53, SQLT_CHR);
		oci_bind_by_name($create_learner_statement, ":name", $name, 200, SQLT_CHR);
		oci_bind_by_name($create_learner_statement, ":register_event_slot_id", $register_event_slot_id, 10, SQLT_INT);		
		oci_bind_by_name($create_learner_statement, ":submission_mode", $submission_mode, 1, SQLT_CHR);
		oci_bind_by_name($create_learner_statement, ":detail_id", $detail_id, 10, SQLT_INT);
		
		if(!oci_execute($create_learner_statement, OCI_DEFAULT)) {
			$error = oci_error($create_learner_statement);
			die("An error was encountered executing the create learner query: " . $error["message"]);			
		}
		
		if($detail_id != 1) { //1 is returned if there are no slots and so the learner is not actually added. Should not happen in this scenario.
		
			//Set the default progress code
			$progress_code_sql = "UPDATE register_event_details SET progress_code = :progress_code, updated_by = :username, updated_date = SYSDATE WHERE id = :detail_id";
				
			$progress_code_statement = oci_parse($connection, $progress_code_sql);
			
			if(!$progress_code_statement) {
				$error = oci_error($connection);
				die("An error was encountered parsing the progress code query: " . $error["message"]);
			}
			
			oci_bind_by_name($progress_code_statement, ":progress_code", $default_progress_code);
			oci_bind_by_name($progress_code_statement, ":username", $username);
			oci_bind_by_name($progress_code_statement, ":detail_id", $detail_id);
			
			if(!oci_execute($progress_code_statement, OCI_DEFAULT)) {
				$error = oci_error($progress_code_statement);
				die("An error was encountered executing the progress code query: " . $error["message"]);
			}
			
			//Now mark the learner and set the provisional progress code if applicable
			self::mark_register_slot($connection, $register_event_slot_id, $detail_id, $usage_code, $new_progress_code, $new_progress_date, $notes);
		
		}
	}
	
	/**
	 Counts the number of unmarked e-registers for the current member of staff before
	 the date provided, which defaults to the current date/time.
	*/
	public static function count_unmarked_registers_before_date($ebs_user, $date = null) {
	
		if(!$date) {
			$date = time();
		}
	
		$person_code = $ebs_user->get_person_code();
		$date_string = date("Y-m-d H:is", $date);

		$sql = "
			SELECT
				Count(1) AS number_of_registers
			FROM
				register_event_slots RES
				INNER JOIN register_events RE ON RE.id = RES.register_event_id
				INNER JOIN sessions S ON S.session_code = RE.session_code
			WHERE
				RES.startdate < To_Date(:date_string,'yyyy-MM-dd HH24:mi:ss')
				AND Nvl(RES.slot_status,'^') NOT IN ('M','O')
				AND RE.register_type = 'EREG'
				AND S.start_date <= To_Date(:date_string, 'yyyy-MM-dd HH24:mi:ss')
				AND S.end_date >= To_Date(:date_string, 'yyyy-MM-dd HH24:mi:ss')
				AND EXISTS ( -- There are learners
					SELECT
						NULL
					FROM
						register_event_details_slots REDS
						INNER JOIN register_event_details RED ON RED.id = REDS.register_event_detail_id
					WHERE
						RED.object_type = 'L'
						AND REDS.register_event_slot_id = RES.id
						AND RED.detail_status = 'A'
				)
				AND EXISTS ( -- The user is assigned as a marking member of staff
					SELECT
						NULL
					FROM
						register_event_details_slots REDs
						INNER JOIN register_event_details RED ON RED.id = REDS.register_event_detail_id
						INNER JOIN person_functions PF ON PF.function_code = RED.person_function
					WHERE
						RED.object_type = 'T'
						AND RED.object_id = :person_code
						AND REDS.register_event_slot_id = RES.id
						AND PF.can_tutor_mark = 'Y'
				)
		";
		
		//Connect
		$connection = ebs_utility::connect();
		
		if(!$connection) {
			$error = oci_error();
			die("An error was encountered connecting to the database: " . $error["message"]);
		}
	
		//Parse the query
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($connection);
			die("An error was encountered parsing the unmarked e-registers query: " . $error["message"]);
		}
		
		//Bind variables
		oci_bind_by_name($statement, ":date_string", $date_string);
		oci_bind_by_name($statement, ":person_code", $person_code);
		
		//Execute
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error($statement);
			die("An error was encountered executing the unmarked e-registers query: " . $error["message"]);
		}
		
		$data = oci_fetch_array($statement, OCI_DEFAULT);
		
		oci_close($connection);
		
		return $data["NUMBER_OF_REGISTERS"];
	}
	
	/**
	 Updates the "requires attention" flag for a given event.
	*/
	public static function update_attention_flags($connection, $register_event_id) {
	
		$sql = "BEGIN ebs_register_events_pkg.UpdateAttentionFlags(:register_event_id); END;";
	
		$statement = oci_parse($connection, $sql);
		
		if(!$statement) {
			$error = oci_error($connection);
			die("An error was encountered parsing the attention flags query: " . $error["message"]);
		}
		
		oci_bind_by_name($statement, ":register_event_id", $register_event_id);
		
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error($statement);
			die("An error was encountered executing the attention flags query: " . $error["message"]);
		}
	}
}

?>
	