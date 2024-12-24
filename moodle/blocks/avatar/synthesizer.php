<?php

require_once ("../../config.php");
require_once ("config.php");
require_once ("model_avatar.php");

// show errors
ini_set('display_errors', true);

// Report all PHP errors
error_reporting(E_ALL);

require_once ("lib/avatar_lib.php");

// $phrase = $_GET["phrase"];
// $speaker = $_GET["speaker"];
// $parameter = $_GET["parameter"];
// $user = $_GET["user"];
// $course = $_GET["course"];

// $phrase = "Olá Ygor. O professor liberou um novo material, e seis novas atividades. Ao todo, você possui duas atividades pendentes. Não perca tempo e estude agora mesmo!";
// $speaker = "Cid";
// $parameter = 1;
// $userid = 3;
// $courseid = 2;

// $avatar_lib = new avatar_lib($userid, $courseid, TRUE, TRUE, TRUE);


// echo $avatar_lib->assembleText();



// $speaker = strtolower($speaker);


class synthesizer {

	private $CFG;
	private $DB;

	private $model_avatar;

	private $userid;
	private $courseid;
	private $parameter;
	private $type;
	private $file;
	private $phrase;
	private $speaker;

	function __construct($userid, $courseid, $parameter, $phrase, $speaker) {

		global $CFG, $DB;

		$this->CFG = $CFG;
		$this->DB = $DB;

		$this->userid = $userid;
		$this->courseid = $courseid;
		$this->parameter = $parameter;

		if($parameter == 1) {
			$this->file = $this->CFG->tempPrefix . "notifications_user" . $this->userid . "_course" . $this->courseid;
			$this->type = "not";
		} else if($parameter == 2) {
			$this->file = $this->CFG->tempPrefix . "message_course" . $this->courseid;
			$this->type = "msg";
			$this->userid = 0; //anulando usuario, essa msg é comum a todos os usuários!
		} else if($parameter == 3) {
			$this->file = $this->CFG->tempPrefix . "message_home";
			$this->type = "msg";
			$this->userid = 0; //anulando usuario, essa msg é comum a todos os usuários!
		}

		$this->phrase = $phrase;
		$this->speaker = $speaker;

		$this->model_avatar = new model_avatar();

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->lame = "lame.exe";
		} else {
			$this->lame = "lame";
		}

	}

	public function execute() {

		/*
		 * Definindo o charset para a função exec(), sem isso teremos problemas com encoding.
		 */

		$locale = 'en_US.UTF-8';
		setlocale(LC_ALL, $locale);
		putenv('LC_ALL='.$locale);

		if ($this->model_avatar->exists_user_cache($this->userid, $this->courseid, $this->type)) { //verifica se o usuario já possui um registro, se posuir, aproveita esse registro
			if (!$this->model_avatar->exists_audio_user_cache($this->userid, $this->courseid, $this->type, $this->phrase, $this->speaker)) { //se o log não existir para a fala/locutor atual
				if ($this->model_avatar->update_user_cache($this->userid, $this->courseid, $this->type, $this->phrase, $this->speaker)) {
					//$cmd = "java -jar " . $this->CFG->avatar_vocalico . " " . $this->CFG->engine . " \"" . utf8_decode($this->phrase) . "\" $this->speaker \"$this->file\"";
					$cmd = "java -jar " . $this->CFG->avatar_vocalico . " " . $this->CFG->engine . " \"" . utf8_encode($this->phrase) . "\" $this->speaker \"$this->file\"";
					exec($cmd, $output, $exit_code);

					$_visems = file_get_contents($this->CFG->engine . '/temp/' . $this->file . '.vis');
					$visems = str_replace("\n", "*", $_visems);

					exec($this->CFG->engine ."/resources/". $this->lame . " " . $this->CFG->engine ."/temp/".$this->file.".wav -v  ".$this->CFG->engine_temp ."/".$this->file.".mp3");

				} else {
					$exit_code = "bd_error_update";
				}

				if (!isset($_POST["mobile"])) {
					if ($this->parameter == 1) {
						echo $exit_code . "#" . $visems;
					} else {
						echo $exit_code;
					}
				}
			} else { //o log existe, não precisa sintetizar novamente
				$_visems = file_get_contents($this->CFG->engine . '/temp/' . $this->file . '.vis');
				$visems = str_replace("\n", "*", $_visems);

				if(!isset($_POST["mobile"])) {
					//o usuario ja possui cache
					if ($this->parameter == 1) {
						echo "0#" . $visems;
					} else {
						echo "0";
					}
				}
			}
		} else { //o usuário não possui registro, então não precisa verificar nada, apenas inserir

			if ($this->model_avatar->insert_user_cache($this->userid, $this->courseid, $this->type, $this->phrase, $this->speaker)) {

				$cmd = "java -jar " . $this->CFG->avatar_vocalico . " " . $this->CFG->engine . " \"" . utf8_encode($this->phrase) . "\" $this->speaker \"$this->file\"";

				//echo "<br><br>" . $cmd . "<br><br>";

				exec($cmd, $output, $exit_code);

// 				echo "--<br>--<pre>";
// 				 print_r($output);
// 				echo "</pre>--<br>--";

				$_visems = file_get_contents($this->CFG->engine . '/temp/' . $this->file . '.vis');
				$visems = str_replace("\n", "*", $_visems);

				exec($this->CFG->engine ."/resources/". $this->lame . " ". $this->CFG->engine ."/temp/".$this->file.".wav -v  ".$this->CFG->engine_temp ."/".$this->file.".mp3");
			} else {
				$exit_code = "[1]bd_error_insert";
			}

			if(!isset($_POST["mobile"])) {
				if ($this->parameter == 1) {
					echo $exit_code . "#" . $visems;
				} else {
					echo $exit_code;
				}
			}
		}
	}

}

if (isset($_POST["userid"]) && isset($_POST["courseid"]) && isset($_POST["parameter"]) && isset($_POST["phrase"]) && isset($_POST["speaker"])) {

	$userid = $_POST["userid"];
	$courseid = $_POST["courseid"];
	$parameter = $_POST["parameter"];
	$phrase = $_POST["phrase"];
	$speaker = strtolower($_POST["speaker"]);

	$synthesizer = new synthesizer($userid, $courseid, $parameter, $phrase, $speaker);

	$synthesizer->execute();
} else {
	echo "unknown error";
}
?>