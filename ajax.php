<?php

define('AJAX_SCRIPT', true);

/** Include config */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/ajaxlib.php');

require_sesskey();

$ajax = new onlinesurvey_ajax();
$content = $ajax->get_content();
echo json_encode(array(
    "footer" => $content->footer,
    "text" => $content->text
));
