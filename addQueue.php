<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_queue'];
$times_sent =  $_REQUEST['times_sent'];
$last_sent =  $_REQUEST['last_sent'];
$queue_ids = $_POST['queue_ids'];
$event_id = $_POST['queue_event_select'];
$queue_instances = $_POST['queue_instances'];

if($queue_instances == "") {
    $instance = "1";
}if (strpos($queue_instances, ";") !== false) {
    $instance = explode(";", $queue_instances);
} else if (strpos($queue_instances, ",") !== false) {
    $instance = explode(",", $queue_instances);
} else if (strpos($queue_instances, "\n") !== false) {
    $instance = explode("\n", $queue_instances);
}else{
    $instance = "1";
}


$failed_records = array();

if (strpos($queue_ids, ";") !== false) {
    $record = explode(";", $queue_ids);
} else if (strpos($queue_ids, ",") !== false) {
    $record = explode(",", $queue_ids);
} else if (strpos($queue_ids, "\n") !== false) {
    $record = explode("\n", $queue_ids);
} else if ($queue_ids != "") {
    if(is_array($instance)){
        foreach ($instance as $one_instance){
            $failed = $module->addQueueEmailFromInterface($project_id, $index, $queue_ids, $times_sent, $event_id, $last_sent, $one_instance);
            if($failed != ""){
                array_push($failed_records,$failed);
            }
        }
    }else{
        $failed = $module->addQueueEmailFromInterface($project_id, $index, $queue_ids, $times_sent, $event_id, $last_sent, $instance);
        if($failed != ""){
            array_push($failed_records,$failed);
        }
    }
} else {
    //ERROR
    $message = "Incorrect format. Couldn't generate PDF.";
}

if ($record != "") {
    foreach ($record as $id) {

        if(is_array($instance)){
            foreach ($instance as $one_instance){
                $failed = $module->addQueueEmailFromInterface($project_id, $index, $id, $times_sent, $event_id, $last_sent, $one_instance);
                if($failed != ""){
                    array_push($failed_records,$failed);
                }
            }
        }else{
            $failed = $module->addQueueEmailFromInterface($project_id, $index, $id, $times_sent, $event_id, $last_sent, $instance);
            if($failed != ""){
                array_push($failed_records,$failed);
            }
        }
    }
}



echo json_encode(array(
    'status' => 'success',
    'failed_records' => $failed_records
));