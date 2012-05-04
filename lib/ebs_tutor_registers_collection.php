<?php
/**
 MWatts, 15 Mar 2009
 
 A class representing a collection of e-registers for a tutor. Provides
 helper methods for looking up, sorting and manipulating registers.
*/
include_once("ebs_base_object.php");
include_once("ebs_register_summary.php");

class ebs_tutor_registers_collection extends ebs_base_object {

	/**
	 Instance variables
	*/
	private $staff_code;
	private $is_marked;
	private $start_date;
	private $registers;
	private $this_date_only;

	/**	
	 Constructor
	*/
	function __construct($staff_code, $start_date, $this_date_only, $is_marked) {
			
		$this->staff_code = $staff_code;
		$this->start_date = $start_date;
		$this->this_date_only = $this_date_only;
		$this->is_marked = $is_marked;
	}

	/**
	 Overridden load method.
	*/
	function do_load() {
	
		$register_start_date_clause = "";
		
		if(!empty($this->start_date)) {
			if(!$this->this_date_only) {				
				$register_start_date_clause = "AND RES.startdate >= To_Date('" . date_format($this->start_date, "d/m/Y H:i:s") . "','dd/MM/yyyy HH24:mi:ss')";
			} else {
				$register_start_date_clause = "AND To_Char(RES.startdate,'dd/MM/yyyy') = '" . date_format($this->start_date, "d/m/Y") . "'";
			}
		}		
		
		$register_status;
		
		if($this->is_marked) {
			$register_status = "'M','O'"; # Marked, Marked by OMR
		} else {
			$register_status = "'^','F'"; # Null (Unmarked), Finish Later
		}
	
		/**
		 SQL statement to retrieve all registers for this user where they are a member
		 of staff with a role which can allows them to mark registers.
		*/
		$sql = "
			SELECT
				RE.id AS register_event_id,
				RES.id AS register_event_slot_id,
				RE.event_code,
				RE.description AS event_description,
				To_Char(RES.startdate, 'yyyy-MM-dd HH24:mi:ss') AS start_date,
				To_Char(RES.enddate, 'yyyy-MM-dd HH24:mi:ss') AS end_date,
				RES.slot_status AS status
			FROM
				register_event_slots RES
				INNER JOIN register_events RE ON RE.id = RES.register_event_id
			WHERE
				Nvl(RES.slot_status,'^') IN (" . $register_status . ")
				$register_start_date_clause				
				AND RE.register_type = 'EREG'
				AND EXISTS (
				SELECT
					NULL
				FROM
					register_event_details_slots REDS
					INNER JOIN register_event_details RED ON RED.id = REDS.register_event_detail_id
					INNER JOIN person_functions PF ON PF.function_code = RED.person_function
					INNER JOIN people P ON P.person_code = RED.object_id
				WHERE
					REDS.object_type = 'T'
					AND RED.detail_status = 'A'
					AND REDS.register_event_slot_id = RES.id
					AND Nvl(PF.can_tutor_mark,'Y') = 'Y'
					AND Upper(P.fes_staff_code) = Upper(:staff_code)
				)
				AND EXISTS (
					SELECT
						NULL
					FROM
						register_event_details_slots REDS
						INNER JOIN register_event_details RED ON RED.id = REDS.register_event_detail_id
					WHERE
						RED.object_type = 'L'
						AND RED.detail_status = 'A'
						AND REDS.register_event_slot_id = RES.id
				)
			ORDER BY
				RES.startdate ASC, RE.event_code ASC, RE.id ASC				
		";
		
		$connection = $this->connect();
		$statement = oci_parse($connection, $sql);
		
		//Trap errors
		if(!$statement) {
			$error = oci_error();
			die("An error was encountered loading register listing for " . $this->staff_code . ": " . $error["message"]);
		}
		
		//Bind variables		
		oci_bind_by_name($statement, ":staff_code", $this->staff_code);
		
		//Execute the query
		if(!oci_execute($statement, OCI_DEFAULT)) {
			$error = oci_error();
			die("An error was encountered executing register listing query " . $this->staff_code . ": " . $error["message"]);
		}	
		
		//Iterate
		$this->registers = array();
		
		while($row = oci_fetch_array($statement, OCI_DEFAULT)) {
		
			$uios = array();
		
			//SQL to get a list of UIOs against the event
			$uio_sql = "
				SELECT
					UIO.fes_uins_instance_code AS course_code,
					UIO.calocc_occurrence_code AS calocc_code,
					Nvl(UI.fes_short_description, UI.fes_long_description) AS course_description
				FROM
					register_event_details_slots REDS
					INNER JOIN unit_instance_occurrences UIO ON UIO.uio_id = REDS.object_id
					INNER JOIN unit_instances UI ON UI.fes_unit_instance_code = UIO.fes_uins_instance_code AND UI.ctype_calendar_type_code = UIO.calocc_calendar_type_code
				WHERE
					REDS.object_type = 'U'
					AND REDS.register_event_slot_id = :register_event_slot_id
			";
			
			$uio_statement = oci_parse($connection, $uio_sql);
			
			if(!$uio_statement) {
				$error = oci_error($connection);
				die("An error was encountered parsing the UIO list query: " . $error["message"]);
			}			
			
			$register_event_slot_id = $row["REGISTER_EVENT_SLOT_ID"];
			oci_bind_by_name($uio_statement, ":register_event_slot_id", $register_event_slot_id);
			
			if(!oci_execute($uio_statement, OCI_DEFAULT)) {
				$error = oci_error($uio_statement);
				die("An error was encountered executing the UIO list query: " . $error["message"]);
			}
			
			while($uio_row = oci_fetch_array($uio_statement, OCI_DEFAULT)) {			
				$uios[$uio_row["COURSE_CODE"] . " (" . $uio_row["CALOCC_CODE"] . ")"] = $uio_row["COURSE_DESCRIPTION"];
			}			
			
			//function __construct($register_event_id, $register_event_slot_id, $code, $description, $start_date, $end_date, $status) {
			$summary = new ebs_register_summary(
							$row["REGISTER_EVENT_ID"],
							$row["REGISTER_EVENT_SLOT_ID"],
							$row["EVENT_CODE"],
							$row["EVENT_DESCRIPTION"],
							strtotime($row["START_DATE"]),
							strtotime($row["END_DATE"]),
							$row["STATUS"],
							$uios
						);
						
			$this->registers[] = $summary;
		
		}
		
		oci_close($connection);
	}
	
	/**
	 Gets the number of registers found.
	*/
	function get_count() {
		if(!empty($this->registers)) {
			return count($this->registers);
		} else {
			return 0;
		}
	}
	
	/**
	 Gets a list of registers for the member of staff.
	*/
	function get_registers() {
		return $this->registers;
	}
}
?>