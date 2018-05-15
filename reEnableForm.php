<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_reenable'];
$active =  $_REQUEST['active'];

$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');

$email_deleted[$index] = "0";

$letter = "R";
if($active == "false"){
    $letter = "N";
}

#RE-enable queued alerts
if(!empty($email_queue)){
    $scheduled_records_changed = "";
    $queue = $email_queue;
    foreach ($email_queue as $id=>$email){
        if($email['project_id'] == $pid && $email['alert']==$index){
            $queue[$id]['deactivated'] = 0;
            $scheduled_records_changed .= $email['record'].",";
        }
    }
    $module->setProjectSetting('email-queue', $queue);

    #Add logs
    $action_description = "Deactivated Scheduled Alert ".$index;
    $changes_made = "Record IDs re-enabled: ".rtrim($scheduled_records_changed,",");
    \REDCap::logEvent($action_description,$changes_made,NULL,NULL,NULL,$pid);
}


$module->setProjectSetting('email-deleted', $email_deleted);

echo json_encode(array(
    'status' => 'success',
    'message' => $letter
));

?>
