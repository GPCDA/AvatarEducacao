<?php

require_once ("../config.php");

function block_avatar_images() {

	global $CFG;

	$elem_id = "avatar-block-avatar-images";
	$elem_class_css = "avatar block-avatar-images";

	$images = array();

	/*
	 * $i=2 (Masculino e Feminino)
	 * $j=10 (10 personagens por sexo)
	 */
	for($i = 1, $tot = 0; $i <= 2; $i ++) {
		for($j = 1; $j <= 10; $j ++, $tot ++) {

			$gender = "";

			if($i == 1) {
				$gender = "Mas";
				$_gender = "male";
			} else {
				$gender = "Fem";
				$_gender = "female";
			}

			$images [$tot] = html_writer::tag ( 'img', '', array (
					'style' => "display: none",
					'src' => $CFG->wwwroot . $CFG->_AVATAR_ROOT_PATH . "/characters/".$gender.$j."/merged/repouso.png",
					'id' => $elem_id."-".$_gender.$j,
					'class' => $elem_class_css . " " . $_gender
			) );
		}
	}

	return $images;
}
?>