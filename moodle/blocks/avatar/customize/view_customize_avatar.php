<?php

/* moodle config */
require_once ("../../../config.php");
/* avatar config */
require_once ("../config.php");
require_once ("avatar_form.php");

class view_customize_avatar {

	public function get_content() {
		global $DB, $PAGE, $OUTPUT, $CFG, $USER;


		// Check for all required variables.
		$courseid = required_param('courseid', PARAM_INT);

		// Next look for optional variables.
		$id = optional_param('id', 0, PARAM_INT);

		if (!$course = $DB->get_record('course', array('id' => $courseid))) {
			print_error('invalidcourse', 'block_avatar', $courseid);
		}

		require_login($course);

		if(!has_capability('block/avatar:addinstance', $PAGE->context)) {
			print_error('insufficient permissions', 'block_avatar');
		}

		/*
		 * PAGE settings begin
		 */
		$PAGE->set_title(get_string('config_block_avatar', 'block_avatar'));
		$PAGE->set_url($CFG->_AVATAR_ROOT_PATH . '/customize/view_customize_avatar.php', array(
				'id' => $courseid
		));
		$PAGE->set_pagelayout('standard');
		$PAGE->requires->jquery();
		$PAGE->requires->js($CFG->_AVATAR_ROOT_PATH . "/customize/js/customize.js");
		$PAGE->set_heading(get_string('config_block_avatar', 'block_avatar'));

		/*
		 * Pass lang labels to javascript
		 */
		$stringman = get_string_manager();
		$strings = $stringman->load_component_strings('block_avatar', 'en');
		$PAGE->requires->strings_for_js(array_keys($strings), 'block_avatar');

		/*
		 * PAGE settings end
		 */

		/*
		 * Breadcrumb begin
		 */
		$settingsnode = $PAGE->settingsnav->add(get_string('config_block_avatar', 'block_avatar'));
		$editurl = new moodle_url($CFG->_AVATAR_ROOT_PATH . '/customize/view_customize_avatar.php', array(
				'id' => $id,
				'courseid' => $courseid
		));
		$editnode = $settingsnode->add(get_string('customize_block_avatar', 'block_avatar'), $editurl);
		$editnode->make_active();
		/*
		 * Breadcrumb end
		 */


		$avatar = new avatar_form(null, array('method'=>'s', 'username'=>'d' ) );

		echo $OUTPUT->header();
		$avatar->display();
		echo $OUTPUT->footer();
	}

}

$view = new view_customize_avatar();
$view->get_content();
?>