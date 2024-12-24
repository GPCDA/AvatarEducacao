<?php

/* moodle config */
require_once ("../../../config.php");
/* avatar config */
require_once ("../config.php");
/* avatar model */
require_once ("../model_avatar.php");

global $USER, $PAGE;

// Check for all required variables.
$action = required_param('action', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
	print_error('invalidcourse', 'block_avatar', $courseid);
}

require_login($course);

if(!has_capability('block/avatar:addinstance', $PAGE->context)) {
	print_error('insufficient permissions', 'block_avatar');
}

$model_avatar = new model_avatar();

if($action == "get") {

	print json_encode($model_avatar->get_course_avatar($courseid));

} else if($action == "save") {

	// Check for all required variables.
	$avatarid = required_param('avatarid', PARAM_INT);
	$new_contents_sent = (boolean) required_param('new_contents_sent', PARAM_BOOL);
	$new_activities_sent = (boolean) required_param('new_activities_sent', PARAM_BOOL);
	$pending_activities = (boolean) required_param('pending_activities', PARAM_BOOL);
	$message = required_param('message', PARAM_TEXT);


	if($avatarid < 10) {
		$avatarid++;
		$gender = "male";
	} else if($avatarid < 20) {
		$avatarid -= 9;
		$gender = "female";
	}

	validate($courseid, $gender, $avatarid, $new_contents_sent, $new_activities_sent, $pending_activities, $message);

	/* save in bd */
	if($model_avatar->save_course_avatar($courseid, $gender, $avatarid, $new_contents_sent, $new_activities_sent, $pending_activities, $message, $USER->id)) {
		/* success */
		print("true");
	} else {
		/* error */
		print("false");
	}
}


/* validate action */
function validate($courseid, $gender, $avatarid, $new_contents_sent, $new_activities_sent, $pending_activities, $message) {
	if(gettype($courseid) != "integer" ||
			(gettype($gender) != "string" || ($gender != "male" && $gender != "female")) ||
			(gettype($avatarid) != "integer" || $avatarid > 10 || $avatarid < 1) ||
			gettype($new_contents_sent) != "boolean" ||
			gettype($new_activities_sent) != "boolean" ||
			gettype($pending_activities) != "boolean" ||
			gettype($message) != "string") {
		print_error('invalidparameter', 'block_avatar');
	}
}


?>