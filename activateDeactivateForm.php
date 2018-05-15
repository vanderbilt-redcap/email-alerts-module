<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_deactivate'];
$status =  $_REQUEST['index_modal_status'];

$email_deactivate =  empty($module->getProjectSetting('email-deactivate'))?array():$module->getProjectSetting('email-deactivate');
$email_queue =  empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');

$message = '';
if($status == "Activate"){
    //Active
    $email_deactivate[$index] = "0";
    $message = "T";
    $deactivated = 0;
}else if($status == "Deactivate"){
    //Not Active
    $email_deactivate[$index] = "1";
    $message = "E";
    $deactivated = 1;
}

#Deactivate queued alerts
if(!empty($email_queue)){
    $scheduled_records_changed = "";
    $queue = $email_queue;
    foreach ($email_queue as $id=>$email){
        if($email['project_id'] == $pid && $email['alert']==$index){
            $queue[$id]['deactivated'] = $deactivated;
            $scheduled_records_changed .= $email['record'].",";
        }
    }
    $module->setProjectSetting('email-queue', $queue);

    #Add logs
    $action_description = $status." Scheduled Alert ".$index;
    $changes_made = "Record IDs deactivated: ".rtrim($scheduled_records_changed,",");
    \REDCap::logEvent($action_description,$changes_made,NULL,NULL,NULL,$pid);
}


$module->setProjectSetting('email-deactivate', $email_deactivate);

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
