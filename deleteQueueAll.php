<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$alertid =  $_REQUEST['alertid'];

$email_queue = $module->getProjectSetting('email-queue', $project_id);
if ($email_queue != '') {
    foreach ($email_queue as $index => $queue) {
        if ($queue['alert'] == $alertid) {
            $module->deleteQueuedEmail($index, $project_id);

            #Add logs
            $changes_made = "Queue #".$index." from Alert #".$alertid." manually deleted by ".USERID;
            \REDCap::logEvent("Queue deleted - Alert ".$alertid,$changes_made,null,null,null,$project_id);
        }
    }
}



echo json_encode(array(
    'status' => 'success'
));