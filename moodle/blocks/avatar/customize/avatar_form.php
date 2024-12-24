<?php

require_once ("{$CFG->libdir}/formslib.php");
require_once ("lib_customize.php");

class avatar_form extends moodleform {
	function definition() {

		global $COURSE;

		$mform = & $this->_form;

		$mform->addElement ( 'header', 'customize_block_avatar_displayinfo', get_string ( 'customize_block_avatar', 'block_avatar' ) );

		$this->add_avatar_radio_gender ();
		$this->add_avatar_radio_images ();
		$this->add_avatar_checkbox_notifications ();

		$mform->addElement('textarea', 'message', get_string("message", "block_avatar"),  array (
				'class' => "avatar block-avatar-textarea"
		));

		$mform->addElement('static', 'characters-left', '', '<span class="avatar block-avatar-characters-left-count">300</span> <span class="avatar block-avatar-characters-left">'. get_string("characters-left", "block_avatar").'</span>');

		$mform->addElement('html', '<input id="avatar_courseid" name="avatar_courseid" type="hidden" value="'.$COURSE->id.'">');
		$mform->addElement('html', '<br>');

		$this->add_info_space();
		$this->add_avatar_buttons();
	}
	function add_avatar_radio_gender() {
		$mform = & $this->_form;

		$elem_class_css = "avatar block-avatar-radio-gender";

		$radioarray = array ();

		$radioarray [] = & $mform->createElement ( 'radio', 'gender', '', get_string ( 'male', 'block_avatar' ), "male", array (
				'class' => $elem_class_css
		) );

		$mform->setDefault('radioarray[0]', true);

		$radioarray [] = & $mform->createElement ( 'radio', 'gender', '', get_string ( 'female', 'block_avatar' ), "female", array (
				'class' => $elem_class_css
		) );

		$mform->setDefault('radioarray[1]', false);

		$mform->addGroup ( $radioarray, 'choose_gender', get_string ( 'choose_gender', 'block_avatar' ), array (
				' '
		), FALSE );

	}
	function add_avatar_radio_images() {
		$mform = & $this->_form;

		$elem_class_css = "avatar block-avatar-radio-images";

		// add image selector radio buttons
		$images = block_avatar_images ();
		$radioarray = array ();
		for($i = 0; $i < count ( $images ); $i ++) {
			$radioarray [] = & $mform->createElement ( 'radio', 'picture', '', $images [$i], $i, array (
					'class' => $elem_class_css
			) );
		}

		$mform->addGroup ( $radioarray, 'choose_avatar', get_string ( 'choose_avatar', 'block_avatar' ), array (
				' '
		), FALSE );
	}
	function add_avatar_checkbox_notifications() {
		$mform = & $this->_form;

		$elem_class_css = "avatar block-avatar-radio-notifications";

		$checkboxarray = array ();

		$checkboxarray [] = & $mform->createElement ( 'checkbox', 'new_contents_sent_notification', '', get_string ( 'new_contents_sent_notification', 'block_avatar' ), array (
				'class' => $elem_class_css
		) );

		$checkboxarray [] = & $mform->createElement ( 'checkbox', 'new_activities_sent_notification', '', get_string ( 'new_activities_sent_notification', 'block_avatar' ), array (
				'class' => $elem_class_css
		) );

		$checkboxarray [] = & $mform->createElement ( 'checkbox', 'pending_activities_notification', '', get_string ( 'pending_activities', 'block_avatar' ), array (
				'class' => $elem_class_css
		) );

		$mform->addGroup ( $checkboxarray, 'notifications', get_string ( 'notifications', 'block_avatar' ), array (
				' '
		), FALSE );
	}
	function add_info_space() {
		$mform = & $this->_form;


		$mform->addElement('html',  '
				<div id="fgroup_id_info_space" class="fitem fitem_fgroup">
					<div class="fitemtitle">
						<div class="fgrouplabel"><label></label></div>
					</div>
					<fieldset class="felement fgroup">
						<span id="id_avatar_customize_error" class="error" style="display:none"></span>
						<img id="id_avatar_customize_loading" class="avatar block-avatar-loading" src="../img/loading.gif" style="display:none">
					</fieldset>
				</div>
				');

	}
	function add_avatar_buttons() {
		$mform = & $this->_form;

		$buttonarray = array ();

		$buttonarray [] = & $mform->createElement ( 'button', 'save_customize', '  ' .get_string('save', 'block_avatar'). '  ');

		$buttonarray [] = & $mform->createElement ( 'button', 'cancel_customize', '  '.get_string('cancel', 'block_avatar'). '  ', array (
				'onclick' => "javascript:history.go(-1);"
		) );


		$mform->addGroup ( $buttonarray, 'buttons', '', array (
				' '
		), FALSE );
	}
}

?>