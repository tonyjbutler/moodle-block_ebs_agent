<?php
include_once("../../../config.php");

global $CFG;
global $DB;

require_once($CFG->libdir . "/blocklib.php");

//Load the ebs Agent block so we can access the database connectivity details
$conditions = array("blockname" => "ebs_agent", "parentcontextid" => 1);
$block_data = $DB->get_record("block_instances", $conditions, "*", MUST_EXIST);
$block_instance = block_instance("ebs_agent", $block_data);

$ebs_db_host = $block_instance->config->db_host;
$ebs_db_user = $block_instance->config->db_user;
$ebs_db_password = $block_instance->config->db_password;
?>