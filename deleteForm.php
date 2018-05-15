<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_delete_user'];

$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');
$email_queue =  empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');

$email_deleted[$index] = "1";
$message = "D";

$module->setProjectSetting('email-deleted', $email_deleted);

#Deactivate queued alerts
if(!empty($email_queue)){
    $scheduled_records_changed = "";
    $queue = $email_queue;
    foreach ($email_queue as $id=>$email){
        if($email['project_id'] == $pid && $email['alert']==$index){
            $queue[$id]['deactivated'] = 1;
            $scheduled_records_changed .= $email['record'].",";
        }
    }
    $module->setProjectSetting('email-queue', $queue);

    #Add logs
    $action_description = "Deactivated Scheduled Alert ".$index;
    $changes_made = "Record IDs deactivated: ".rtrim($scheduled_records_changed,",");
    \REDCap::logEvent($action_description,$changes_made,NULL,NULL,NULL,$pid);
}



echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
