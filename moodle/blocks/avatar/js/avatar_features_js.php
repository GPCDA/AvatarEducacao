<?php

header('Content-Type: application/javascript');

$gender = $_GET['gender'];
$avatarid = $_GET['avatarid'];
$courseid = $_GET['courseid'];
$userid = $_GET['userid'];
$visems_message = $_GET['visemsMessage'];
$notification_phrase = $_GET['notificationPhrase'];
$temp_prefix = $_GET['tempPrefix'];

?>
jQuery( document ).ready(function() {
	AvatarUI.init("<?=$gender?>", <?=$avatarid?>, <?=$courseid?>, <?=$userid?>, '<?=$visems_message?>', '<?=$notification_phrase?>', '<?=$temp_prefix?>');
});
