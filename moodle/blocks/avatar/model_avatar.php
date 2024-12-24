<?php

// require_once ("../config.php");
require_once ("config.php");

class model_avatar {

	private $DB, $CFG, $DATE;

	function __construct() {
		global $DB, $CFG;
		$this->DB = $DB;
		$this->CFG = $CFG;
		$this->DATE = new DateTime();
	}

	/*
	 * _AVATAR_TABLE
	 */

	public function save_course_avatar($courseid, $gender, $avatarid, $new_contents_sent, $new_activities_sent, $pending_activities, $message, $modfied_userid) {

		if($this->DB->record_exists($this->CFG->_AVATAR_TABLE, array('courseid' => $courseid))) {
			/* update */

			$record = $this->DB->get_record($this->CFG->_AVATAR_TABLE, array('courseid' => $courseid));

			return $this->DB->update_record($this->CFG->_AVATAR_TABLE, array(
					'id' => $record->id,
					'courseid' => $courseid,
					'gender' => $gender,
					'avatarid' => $avatarid,
					'new_contents_sent' => $new_contents_sent,
					'new_activities_sent' => $new_activities_sent,
					'pending_activities' => $pending_activities,
					'message' => $message,
					'modfied_userid' => $modfied_userid,
					'last_change' => $this->DATE->getTimestamp(),

			));
		} else {
			/* insert */

			$record_id =  $this->DB->insert_record($this->CFG->_AVATAR_TABLE, array(
					'courseid' => $courseid,
					'gender' => $gender,
					'avatarid' => $avatarid,
					'new_contents_sent' => $new_contents_sent,
					'new_activities_sent' => $new_activities_sent,
					'pending_activities' => $pending_activities,
					'message' => $message,
					'created_userid' => $modfied_userid,
					'modfied_userid' => $modfied_userid,
					'created' => $this->DATE->getTimestamp(),
					'last_change' => $this->DATE->getTimestamp(),

			));

			return $record_id;
		}
	}

	public function get_course_avatar($courseid) {
		return $this->DB->get_record($this->CFG->_AVATAR_TABLE, array (
				'courseid' => $courseid
		));
	}

	public function exists_course_avatar($courseid) {
		return $this->DB->record_exists($this->CFG->_AVATAR_TABLE, array (
				'courseid' => $courseid
		));
	}

	/*
	 * _AVATAR_CACHE_TABLE
	 */

	public function is_cache_newer($userid, $courseid, $type, $user_timestamp) {
		$message = $this->get_user_cache($userid, $courseid, $type);

		if($message->last_change >= $user_timestamp) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function get_user_cache($userid, $courseid, $type) {

		return $this->DB->get_record($this->CFG->_AVATAR_CACHE_TABLE, array (
				'userid' => $userid,
				'courseid' => $courseid,
				'type' => $type
		));
	}

	public function insert_user_cache($userid, $courseid, $type, $phrase, $speaker) {

		return $this->DB->insert_record($this->CFG->_AVATAR_CACHE_TABLE, array (
				'userid' => $userid,
				'courseid' => $courseid,
				'type' => $type,
				'phrase' => $phrase,
				'speaker' => $speaker,
				'created' => $this->DATE->getTimestamp(),
				'last_change' => $this->DATE->getTimestamp()
		));
	}

	public function update_user_cache($userid, $courseid, $type, $phrase, $speaker) {

		$id = $this->DB->get_field($this->CFG->_AVATAR_CACHE_TABLE, 'id',  array (
				'userid' => $userid,
				'courseid' => $courseid,
				'type' => $type
		));

		return $this->DB->update_record($this->CFG->_AVATAR_CACHE_TABLE, array (
				'id' => $id,
				'userid' => $userid,
				'courseid' => $courseid,
				'type' => $type,
				'phrase' => $phrase,
				'speaker' => $speaker,
				'last_change' => $this->DATE->getTimestamp()
		));
	}

	public function exists_user_cache($userid, $courseid, $type) {

		return $this->DB->record_exists($this->CFG->_AVATAR_CACHE_TABLE, array (
			'userid' => $userid,
			'courseid' => $courseid,
			'type' => $type
		));
	}

	public function exists_audio_user_cache($userid, $courseid, $type, $phrase, $speaker) {

		$return = FALSE;

		$exists = $this->DB->record_exists($this->CFG->_AVATAR_CACHE_TABLE, array (
				'userid' => $userid,
				'courseid' => $courseid,
				'type' => $type,
				/*'phrase' => $phrase,*/ /* nao é permitido o tipo TEXT nessa consulta */
				'speaker' => $speaker
		));

		/*
		 * Checando o campo que falta, do tipo TEXT
		 */
		if($exists) {
			$result = $this->DB->get_record($this->CFG->_AVATAR_CACHE_TABLE, array (
					'userid' => $userid,
					'courseid' => $courseid,
					'type' => $type,
					'speaker' => $speaker
			), "phrase");

			if($result->phrase == $phrase) {
				$return = TRUE;
			}

		}

		return $return;
	}

	/*
	 * _AVATAR_MOBILE_TABLE
	 */

	public function exists_user_mobile($userid) {

		return $this->DB->record_exists($this->CFG->_AVATAR_MOBILE_TABLE, array (
			'userid' => $userid
		));
	}

	public function save_token_mobile($userid, $token, $expires) {

		if($this->exists_user_mobile($userid)) {
			/* update */

			$record = $this->DB->get_record($this->CFG->_AVATAR_MOBILE_TABLE, array('userid' => $userid));

			return $this->DB->update_record($this->CFG->_AVATAR_MOBILE_TABLE, array(
					'id' => $record->id,
					'userid' => $userid,
					'token' => $token,
					'expires' => $expires
			));
		} else {
			/* insert */

			$record_id =  $this->DB->insert_record($this->CFG->_AVATAR_MOBILE_TABLE, array(
					'id' => $record->id,
					'userid' => $userid,
					'token' => $token,
					'expires' => $expires,
					'last_sync' => 0
			));

			return $record_id;
		}
	}

	public function validate_token_mobile($token) {

		$sql = "SELECT 1 FROM {" . $this->CFG->_AVATAR_MOBILE_TABLE . "} WHERE token = '".$token."' AND expires >= ".$_SERVER['REQUEST_TIME'];

		return $this->DB->record_exists_sql($sql);
	}

	public function get_token($token) {

		$sql = "SELECT * FROM {" . $this->CFG->_AVATAR_MOBILE_TABLE . "} WHERE token = '".$token."'";

		return $this->DB->get_record_sql($sql);
	}

	public function get_last_sync($userid) {

		return $this->DB->get_record($this->CFG->_AVATAR_MOBILE_TABLE, array (
			'userid' => $userid
		), 'last_sync')->last_sync;
	}

	public function sync_user($userid, $last_sync) {
				
		$record = $this->DB->get_record($this->CFG->_AVATAR_MOBILE_TABLE, array('userid' => $userid));
		
		return $this->DB->update_record($this->CFG->_AVATAR_MOBILE_TABLE, array(
					'id' => $record->id,
					'last_sync' => $last_sync
			));
	}

	/*
	 * MOODLE USER TABLE
	 */

	public function get_user($userid) {

		return $this->DB->get_record('user', array ('id' => $userid));
	}
};