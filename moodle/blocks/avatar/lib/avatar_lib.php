<?php
// require_once ("../config.php");

// require_once ("../../config.php");
// require_once ("config.php");

class avatar_lib {

	private $CFG;
	private $DB;

	private $userid;
	private $courseid;
	private $new_contents_sent_notification;
	private $new_activities_sent_notification;
	private $pending_activities_notification;

	private $user_first_name;
	private $user_last_login;
	private $is_user_enrolled;

	/**
	 * avatar_lib class
	 *
	 * @param number $userid
	 * @param number $courseid
	 * @param boolean $new_contents_sent_notification
	 * @param boolean $new_activities_sent_notification
	 * @param boolean $pending_activities_notification
	 */
	function __construct($userid, $courseid, $new_contents_sent_notification, $new_activities_sent_notification, $pending_activities_notification) {

		global $CFG, $DB;

		$this->CFG = $CFG;

		$this->DB = $DB;

		$this->userid = $userid;
		$this->courseid = $courseid;
		$this->new_contents_sent_notification = $new_contents_sent_notification;
		$this->new_activities_sent_notification = $new_activities_sent_notification;
		$this->pending_activities_notification = $pending_activities_notification;

		$this->user_first_name = $this->DB->get_field($this->CFG->_USER_TABLE, "firstname", array(
				'id' => $this->userid
		));

		$this->user_last_login = $this->DB->get_field($this->CFG->_USER_TABLE, "lastlogin", array(
				'id' => $this->userid
		));

		$context = get_context_instance(CONTEXT_COURSE, $this->courseid, MUST_EXIST);

		$this->is_user_enrolled = is_enrolled($context, $this->userid, '', true);
	}

	/**
	 * Montador do texto de acordo com as opcoes de notificação.
	 *
	 * @return string
	 */
	public function assembleText() {
		$text = "";

		// saudacao inicial
		$text = "Olá " . $this->user_first_name . ". ";

		$totalResource = 0;
		$totalActivities = 0;
		$totalActivityPending = 0;

		// Notificacoes de materiais
		if ($this->new_contents_sent_notification == true) {
			$totalResource = $this->getRecentAllResources();

			if ($totalResource != 0) {
				$text = $text . "O professor liberou" . $this->amountInWords ( $totalResource, 'M' );

				if ($totalResource > 1) {
					$text = $text . "novos materiais";
				} else {
					$text = $text . "novo material";
				}
			}

		}

		// Notificacoes de atividades
		if ($this->new_activities_sent_notification == true) {

			$totalActivities = $this->getRecentActivities ( 'assign,assignment,chat,choice,forum,glossary,lesson,lti,quiz,survey,wiki,workshop');

			if ($this->new_contents_sent_notification == true && $totalResource > 0) {

				if ($totalActivities > 0) {
					$text = $text . ", e" . $this->amountInWords ( $totalActivities, 'F' );

					if ($totalActivities > 1) {
						$text = $text . "novas atividades. ";
					} else {
						$text = $text . "nova atividade. ";
					}
				} else {
					$text = $text . ". ";
				}
			} else {
				if ($totalActivities > 0) {
					$text = $text . "O professor liberou" . $this->amountInWords ( $totalActivities, 'F' );

					if ($totalActivities > 1) {
						$text = $text . "novas atividades. ";
					} else {
						$text = $text . "nova atividade. ";
					}
				}
			}
		} else {
			if ($totalResource > 0) {
				$text = $text . ". ";
			}
		}

		// Notificacao de atividades pendentes
		if ($this->pending_activities_notification == true) {
			$totalActivityPending = $this->getAssignments ();

			if ($totalActivityPending > 0) {

				if ($totalActivities > 0 && $totalActivityPending > 1 && $totalActivities != $totalActivityPending) {
					$text = $text . "Ao todo, você possui" . $this->amountInWords ( $totalActivityPending, 'F' );
				} else if ($totalActivities == $totalActivityPending) {
					if ($totalActivityPending == 1) {
						$text = $text . "Lembre-se de responder essa atividade no prazo estabelecido. ";
					} else {
						$text = $text . "Lembre-se de responder essas atividades no prazo estabelecido. ";
					}
				} else {
					$text = $text . "Você possui" . $this->amountInWords ( $totalActivityPending, 'F' );
				}

				if ($totalActivities != $totalActivityPending) {
					if ($totalActivityPending > 1) {
						$text = $text . "atividades pendentes. ";
					} else {
						$text = $text . "atividade pendente. ";
					}

					if ($totalActivityPending > 2) {
						$text = $text . " Sugiro que tenha mais cuidado, você está acumulando muitas atividades não respondidas. ";
					}
				}
			}
		}

		if (($totalResource > 2 || $totalActivities > 2) && $totalActivityPending == 0) {
			$text = $text . " Não deixe de verificar todas as novidades!";
		} else if ($totalResource > 2 || $totalActivities > 2 || $totalActivityPending > 2) {
			$text = $text . " Não perca tempo e estude agora mesmo!";
		}

		if ($totalResource == 0 && $totalActivities == 0 && $totalActivityPending == 0) {
			$text = $text . " Nesse momento eu não tenho nenhuma novidade para você.";
		}

		return $text;
	}

	/**
	 * Atividades pendentes
	 *
	 * @return number
	 */
	private function getAssignments() {
		global $CFG;

		$return = 0;

		/*
		 * Atualmente só está considerando tarefas como atividades pendentes
		 */

		if($this->is_user_enrolled) {

			$sql = "SELECT COUNT('x') FROM {assign} a WHERE NOT EXISTS
						(SELECT 1 FROM {assign_submission} a_s WHERE a_s.userid = ". $this->userid ." AND a_s.assignment = a.id)
								AND course = ".$this->courseid." AND timemodified >= ".$this->user_last_login;

			$return = $this->DB->count_records_sql($sql);

		}

		return $return;
	}

	/**
	 * Novos materiais
	 *
	 * @return number
	 */
	private function getRecentAllResources() {
		$return = 0;

		if($this->is_user_enrolled) {
			$resources = array("resource", "book", "page", "folder", "url");

			foreach ($resources as $resource) {
				$return = $this->getRecentResource($resource, $return);
			}

		}

		return $return;
	}

	private function getRecentResource($table, $totAll) {
		$sql = "SELECT COUNT('x') FROM {".$table."} WHERE course = ? AND timemodified >= ?";

		$tot = $this->DB->count_records_sql($sql, array(
				'course' => $this->courseid,
				'timemodified' => $this->user_last_login,
		));
		return $totAll + $tot;
	}

	/**
	 * Novas atividades postadas
	 *
	 * @param unknown $activitiesList
	 * @return number
	 */
	private function getRecentActivities($activitiesList) {
		global $CFG;

		$return = 0;
		$contador = 0;

		// $teste = "";

		$activities = explode ( ",", $activitiesList );

		foreach ( $activities as $activitytable ) {

			if($this->is_user_enrolled) {

				$sql = "SELECT COUNT('x') FROM {".$activitytable."} WHERE course = ? AND timemodified >= ?";

				$contador = $this->DB->count_records_sql($sql, array(
						'course' => $this->courseid,
						'timemodified' => $this->user_last_login,
				));

			}

			$return = $return + $contador;
			$contador = 0;
		}

		return $return;
	}

	/**
	 * Função auxiliar para converter os números por extenso
	 *
	 * @param number $valor
	 * @param char $gender
	 * @return string
	 */
	private function amountInWords($valor = 0, $gender) {
		$rt = "";
		$singular = array (
				"",
				"",
				"",
				"",
				"",
				"",
				""
		);
		$plural = array (
				"",
				"",
				"",
				"",
				"",
				"",
				""
		);

		if ($gender == 'M') {
			$c = array (
					"",
					"cem",
					"duzentos",
					"trezentos",
					"quatrocentos",
					"quinhentos",
					"seiscentos",
					"setecentos",
					"oitocentos",
					"novecentos"
			);
		} else {
			$c = array (
					"",
					"cem",
					"duzentas",
					"trezentas",
					"quatrocentas",
					"quinhentas",
					"seiscentas",
					"setecentas",
					"oitocentas",
					"novecentas"
			);
		}

		$d = array (
				"",
				"dez",
				"vinte",
				"trinta",
				"quarenta",
				"cinquenta",
				"sessenta",
				"setenta",
				"oitenta",
				"noventa"
		);
		$d10 = array (
				"dez",
				"onze",
				"doze",
				"treze",
				"quatorze",
				"quinze",
				"dezesseis",
				"dezesete",
				"dezoito",
				"dezenove"
		);

		if ($gender == 'M') {
			$u = array (
					"",
					"um",
					"dois",
					"três",
					"quatro",
					"cinco",
					"seis",
					"sete",
					"oito",
					"nove"
			);
		} else {
			$u = array (
					"",
					"uma",
					"duas",
					"três",
					"quatro",
					"cinco",
					"seis",
					"sete",
					"oito",
					"nove"
			);
		}

		$z = 0;

		$valor = number_format ( $valor, 2, ".", "." );
		$inteiro = explode ( ".", $valor );
		for($i = 0; $i < count ( $inteiro ); $i ++)
			for($ii = strlen ( $inteiro [$i] ); $ii < 3; $ii ++)
				$inteiro [$i] = "0" . $inteiro [$i];

			// $fim identifica onde que deve se dar jun��o de centenas por "e" ou por "," ;)
		$fim = count ( $inteiro ) - ($inteiro [count ( $inteiro ) - 1] > 0 ? 1 : 2);
		for($i = 0; $i < count ( $inteiro ); $i ++) {
			$valor = $inteiro [$i];
			$rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c [$valor [0]];
			$rd = ($valor [1] < 2) ? "" : $d [$valor [1]];
			$ru = ($valor > 0) ? (($valor [1] == 1) ? $d10 [$valor [2]] : $u [$valor [2]]) : "";

			$r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
			$t = count ( $inteiro ) - 1 - $i;
			$r .= $r ? " " . ($valor > 1 ? $plural [$t] : $singular [$t]) : "";
			if ($valor == "000")
				$z ++;
			elseif ($z > 0)
				$z --;
			if (($t == 1) && ($z > 0) && ($inteiro [0] > 0))
				$r .= (($z > 1) ? " de " : "") . $plural [$t];
			if ($r)
				$rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro [0] > 0) && ($z < 1)) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
		}

		return ($rt ? $rt : "zero");
	}
}

?>