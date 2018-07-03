<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_queue'];
$queue_ids = $_POST['queue_ids'];

if(!isset($_POST['already_sent'])){
    $already_sent = "0";
}else{
    $already_sent = "1";
}

$message = "";


if (strpos($queue_ids, ";") !== false) {
    $record = explode(";", $queue_ids);
} else if (strpos($queue_ids, ",") !== false) {
    $record = explode(",", $queue_ids);
} else if (strpos($queue_ids, "\n") !== false) {
    $record = explode("\n", $queue_ids);
} else if ($queue_ids != "") {
    $module->addQueueEmailFromInterface($project_id, $index, $queue_ids, $already_sent);
} else {
    //ERROR
    $message = "Incorrect format. Couldn't generate PDF.";
}

if ($record != "") {
    foreach ($record as $id) {
        $module->addQueueEmailFromInterface($project_id, $index, $record, $already_sent);
    }
}




echo json_encode(array(
    'status' => 'success',
    'message' => $message
));