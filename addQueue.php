<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = (int)$_GET['pid'];
$index =  htmlentities($_REQUEST['index_modal_queue'],ENT_QUOTES);
$times_sent =  htmlentities($_REQUEST['times_sent'],ENT_QUOTES);
$last_sent =  htmlentities($_REQUEST['last_sent'],ENT_QUOTES);
$queue_ids = htmlentities($_POST['queue_ids'],ENT_QUOTES);
$event_id = htmlentities($_POST['queue_event_select'],ENT_QUOTES);
$queue_instances = htmlentities($_POST['queue_instances'],ENT_QUOTES);

if($queue_instances == "") {
    $instance = "1";
}if (strpos($queue_instances, ";") !== false) {
    $instance = explode(";", $queue_instances);
} else if (strpos($queue_instances, ",") !== false) {
    $instance = explode(",", $queue_instances);
} else if (strpos($queue_instances, "\n") !== false) {
    $instance = explode("\n", $queue_instances);
}else if ($queue_instances != ""){
    $instance = $queue_instances;
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