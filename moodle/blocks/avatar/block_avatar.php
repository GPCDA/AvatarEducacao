<?php

require_once ($CFG->dirroot."/blocks/avatar/config.php");

/* avatar model */
require_once ($CFG->dirroot."/blocks/avatar/model_avatar.php");

/* avatar lib */
require_once ($CFG->dirroot."/blocks/avatar/lib/avatar_lib.php");

class block_avatar extends block_base {

	private $COURSE;
	private $USER;
	private $PAGE;
	private $CFG;
	private $DB;

	private $notification_phrase;

	public function init() {

		global $COURSE, $USER, $PAGE, $CFG, $DB;

		$this->COURSE = $COURSE;
		$this->USER = $USER;
		$this->PAGE = $PAGE;
		$this->CFG = $CFG;
		$this->DB = $DB;

		$this->title = get_string ( 'avatar', 'block_avatar' );
	}

	public function get_content() {

		$this->title = get_string ( 'avatar', 'block_avatar' );

		$model_avatar = new model_avatar();

		/* se o bloco tiver sido criado nesse momento, persistir alguma informação inicial no banco */
		if(!$model_avatar->exists_course_avatar($this->COURSE->id)) {
			$model_avatar->save_course_avatar($this->COURSE->id, "male", 1, 1, 1, 1, "", $this->USER->id);
		}

		$customize_avatar = $model_avatar->get_course_avatar($this->COURSE->id);

		/* mesmo se não tiver conseguido persistir, exiba alguma coisa para o usuário final */
		if($customize_avatar == null) {

			$customize_avatar = new stdClass();

			$customize_avatar->courseid = $this->COURSE->id;
			$customize_avatar->avatarid = 1;
			$customize_avatar->gender = "male";

		}

		$this->notification_phrase = $this->getNotificationPhrase($customize_avatar, $this->USER->id);

 		$this->PAGE->requires->jquery();
 		$this->PAGE->requires->js($this->CFG->_AVATAR_ROOT_PATH . "/js/jquery.tmpl.js");
		$this->PAGE->requires->js($this->CFG->_AVATAR_ROOT_PATH . "/js/avatar.js");
		$this->PAGE->requires->js($this->CFG->_AVATAR_ROOT_PATH . "/js/avatar_features_js.php?gender=" . $customize_avatar->gender .
				"&avatarid=" . $customize_avatar->avatarid .
				"&courseid=" . $customize_avatar->courseid .
				"&userid=" . $this->USER->id .
				"&visemsMessage=" . $this->getVisemsMessage($customize_avatar->courseid) .
				"&notificationPhrase=" . $this->notification_phrase .
				"&tempPrefix=" . $this->CFG->tempPrefix);

		if ($this->content !== null) {
			return $this->content;
		}

		$this->content = new stdClass ();

		$this->content->text = '';

		if(has_capability('block/avatar:addinstance', $this->context)) {
			$url = new moodle_url ($this->CFG->_AVATAR_ROOT_PATH.'/customize/view_customize_avatar.php', array (
					'courseid' => $this->COURSE->id
			) );

			$this->content->text .= html_writer::start_tag("div", array (
				'style' => "text-align: right;"
			));
// 			$this->content->text .= html_writer::link ( $url, get_string ( 'customize_link', 'block_avatar' ) );
			$edit_img = '<img src="' . $this->CFG->wwwroot . $this->CFG->_AVATAR_ROOT_PATH . '/img/avatar_edit.png" width="15">';
			$this->content->text .= html_writer::link ( $url, $edit_img );
			$this->content->text .= html_writer::end_tag("div");
		}

// 		$this->content->text = '<span class="simplehtml">The content of our Avatar block!</span><br><br>';

		$this->content->text .= $this->getFile($this->CFG->dirroot.$this->CFG->_AVATAR_ROOT_PATH . "/js/avatar_tmpl.html");
		$this->content->text .= '<div id="avatar_container"></div>';

		$this->content->footer = '';

		$this->content->footer .= $this->addAvatarButtons($customize_avatar);

		if (! empty ( $this->config->text )) {
			$this->content->text .= $this->config->text;
		}

		return $this->content;
	}

	private function getVisemsMessage($courseid) {

		$file_name = "";

		if($courseid > 1) {
			$file_name = $this->CFG->tempPrefix . 'message_course' . $courseid;
		} else {
			$file_name = $this->CFG->tempPrefix . 'message_home';
		}

		$file_name .= ".vis";

		$visemsMessage = @file_get_contents($this->CFG->engine . '/temp/' . $file_name);
		$visemsMessage = str_replace("\n", "*", $visemsMessage);

		return $visemsMessage;
	}

	private function getNotificationPhrase($customize_avatar, $userid) {
		$return = "";

		if($customize_avatar->courseid > 1) {
			$avatar_lib = new avatar_lib($userid, $customize_avatar->courseid, $customize_avatar->new_contents_sent, $customize_avatar->new_activities_sent, $customize_avatar->pending_activities);
			$return = $avatar_lib->assembleText();
		}

		return $return;
	}

	private function addAvatarButtons($customize_avatar) {

		$content = "";

		$content .= html_writer::start_tag("div", array (
				'style' => "text-align: center;"
		) );

		$content .= html_writer::start_tag("img", array (
				'id' => "avatar_loading",
				'src' => $this->CFG->wwwroot . $this->CFG->_AVATAR_ROOT_PATH . "/img/loading.gif",
				'style' => "display:none"
		) );

		$content .= html_writer::end_tag("div");


		$model_avatar = new model_avatar();

		$speaker = $customize_avatar->gender == "male" ? "cid" : "lis";

// 		print_r($this->USER);

		$badge = '<span class="avatar badge red">1</span></a>';

		$notification_badge = "";
		if(!$model_avatar->exists_audio_user_cache($this->USER->id, $this->COURSE->id, 'not', $this->notification_phrase, $speaker)
				|| $model_avatar->is_cache_newer($this->USER->id, $this->COURSE->id, 'not', $this->USER->lastlogin)) {
			$notification_badge = $badge;
		}

		$content .= '	<div id="avatar_audio_controls" class="avatar menu-container">
						    <nav class="avatar menu-nav">
						      <ul class="avatar menu-ul">';

		if($this->COURSE->id > 1) {
			$content .= '		<li class="avatar menu-li"><a id="btn_notification" href="javascript:void(0)">Notificação' . $notification_badge . '</a></li>';
		}

		if($model_avatar->exists_user_cache(0, $this->COURSE->id, 'msg')) {
			$msg_cache = $model_avatar->get_user_cache(0, $this->COURSE->id, 'msg');

			if(trim($msg_cache->phrase) != "") {
				$message_badge = "";

				if(isset($this->USER->lastlogin) && $model_avatar->is_cache_newer(0, $this->COURSE->id, 'msg', $this->USER->lastlogin)) {
					$message_badge = $badge;
				}
				$content .= '       <li class="avatar menu-li"><a id="btn_message" href="javascript:void(0)">Mensagem' . $message_badge . '</a></li>';
			}
		}

		$content .= '	      </ul>
						    </nav>
						  </div>';

		return $content;
	}

	private function getFile($file_name) {
		$file = fopen($file_name, "r") or die("Unable to open file!");

		$file_content =  fread($file, filesize($file_name));

		fclose($file);

		return $file_content;
	}
}
?>