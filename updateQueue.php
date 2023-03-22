<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_GET['pid'];
$alert =  $_REQUEST['index_modal_queue'];


$email_queue = empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');
$queue_aux = $email_queue;
foreach ($email_queue as $index=>$queue){
    if($alert == $queue['alert'] && $project_id == $queue['project_id']){
        $queue_aux[$index]['last_sent'] = date('Y-m-d');
        $queue_aux[$index]['times_sent'] = "1";
        $module->setProjectSetting('email-queue', $queue_aux,$queue['project_id']);
    }
}

echo json_encode(array(
    'status' => 'success'
));