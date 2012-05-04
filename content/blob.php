<?php
/**
 MWatts, 15 Jun 2009
 
 Retrieves the binary data for a BLOB
 and streams it to the client.
*/

include_once("../../../config.php");
include_once("../lib/ebs_utility.php");

global $CFG;

$connection = ebs_utility::get_connection($CFG->db_host_name, $CFG->db_user_name, $CFG->db_password);

//Get some information about the blob we're looking for
$domain = $_GET["domain"];
$name = $_GET["name"];
$owner_ref = $_GET["owner_ref"];

//Generate the query
$sql = "SELECT binary_object FROM blobs WHERE domain = :domain AND blob_name = :name AND owner_ref = :owner_ref";

//Prepare the statement and bind variables
$statement = oci_parse($connection, $sql);

if(!$statement) {
	$error = oci_error();
	die("An error was encountered parsing the SQL statement: $error");
}

oci_bind_by_name($statement, ":domain", $domain);
oci_bind_by_name($statement, ":name", $name);
oci_bind_by_name($statement, ":owner_ref", $owner_ref);

if(!oci_execute($statement, OCI_DEFAULT)) {
	$error = oci_error();
	die("An error was encountered executing the SQL statement: $error");
}

$data = oci_fetch_assoc($statement);

if($data) {

	$binary_data = $data["BINARY_OBJECT"]->load();

} else {

	//Load a default picture instead
	$default_image_path = "";
	
	switch($name) {
		case "PERSON_PICTURE":
			$default_image_path = "../resources/icons/no_learner_picture.gif";
			break;
	}
	
	$file = fopen($default_image_path, "rb");
	$binary_data = fread($file, filesize($default_image_path));
	
	fclose($file);
}

oci_close($connection);

//Stream to the client
header("Content-type: image/JPEG");
echo $binary_data;
?>