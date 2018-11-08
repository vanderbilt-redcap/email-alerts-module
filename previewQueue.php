<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_queue'];

#User rights
$UserRights = \REDCap::getUserRights(USERID)[USERID];
$isAdmin = false;
if($UserRights['user_rights'] == '1'){
    $isAdmin = true;
}


#get data from the DB
$email_queue = empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');

$preview = "";
$queued_emails = false;
if($email_queue != '') {
    $preview = "<table style='margin:0 auto;width:100%;border: 1px;'>";
    $preview .= "<thead><tr><td>Created on</td><td>Times Sent</td><td>Last Sent</td><td>Record</td><td>Event</td><td>Instrument</td><td>Instance</td><td>Repeat Instrument</td><td>Option</td><td>Deactivated</td>";
    if($isAdmin) {
        $preview .= "<td>Delete</td>";
    }
    $preview .= "</tr></thead><tbody>";

    foreach ($email_queue as $id=>$queue) {
        if($queue['project_id'] == $project_id && $queue['alert'] == $index){
            $queued_emails = true;
            $preview .= "<tr><td>".$queue['creation_date']."</td><td>".$queue['times_sent']."</td><td>".$queue['last_sent']."</td><td>".$queue['record']."</td><td>".$queue['event_id']."</td><td>".$queue['instrument']."</td><td>".$queue['instance']."</td><td>".$queue['isRepeatInstrument']."</td><td>".$queue['option']."</td><td>".$queue['deactivated']."</td>";
            if($isAdmin) {
                $preview .= "<td><i class=\"far fa-trash-alt\" style='cursor:pointer' onclick='deleteEmailAlertQueue(\"".$id."\",\"".$index."\")'></i>";
            }
            $preview .= "</td></tr>";
        }
    }
    $preview .= "</tbody></table>";
}

if(!$queued_emails){
    $preview = "<i>No emails Queued</i>";
}


echo $preview;