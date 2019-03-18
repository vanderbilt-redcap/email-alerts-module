<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$index =  $_REQUEST['index_modal_update'];
$pid = $_GET['pid'];

#get data from the DB
$form_name = empty($module->getProjectSetting('form-name'))?array():$module->getProjectSetting('form-name');
$form_name_event = empty($module->getProjectSetting('form-name-event'))?array():$module->getProjectSetting('form-name-event');
$email_from = empty($module->getProjectSetting('email-from'))?array():$module->getProjectSetting('email-from');
$email_to = empty($module->getProjectSetting('email-to'))?array():$module->getProjectSetting('email-to');
$email_cc =  empty($module->getProjectSetting('email-cc'))?array():$module->getProjectSetting('email-cc');
$email_bcc =  empty($module->getProjectSetting('email-bcc'))?array():$module->getProjectSetting('email-bcc');
$email_subject =  empty($module->getProjectSetting('email-subject'))?array():$module->getProjectSetting('email-subject');
$email_text =  empty($module->getProjectSetting('email-text'))?array():$module->getProjectSetting('email-text');
$email_attachment_variable =  empty($module->getProjectSetting('email-attachment-variable'))?array():$module->getProjectSetting('email-attachment-variable');
$email_repetitive =  empty($module->getProjectSetting('email-repetitive'))?array():$module->getProjectSetting('email-repetitive');
$email_condition =  empty($module->getProjectSetting('email-condition'))?array():$module->getProjectSetting('email-condition');
$email_incomplete =  empty($module->getProjectSetting('email-incomplete'))?array():$module->getProjectSetting('email-incomplete');
$cron_send_email_on =  empty($module->getProjectSetting('cron-send-email-on'))?array():$module->getProjectSetting('cron-send-email-on');
$cron_send_email_on_field =  empty($module->getProjectSetting('cron-send-email-on-field'))?array():$module->getProjectSetting('cron-send-email-on-field');
$cron_repeat_for =  empty($module->getProjectSetting('cron-repeat-for'))?array():$module->getProjectSetting('cron-repeat-for');
$cron_queue_expiration_date =  empty($module->getProjectSetting('cron-queue-expiration-date'))?array():$module->getProjectSetting('cron-queue-expiration-date');
$cron_queue_expiration_date_field =  empty($module->getProjectSetting('cron-queue-expiration-date-field'))?array():$module->getProjectSetting('cron-queue-expiration-date-field');
$alert_id =  empty($module->getProjectSetting('alert-id'))?array():$module->getProjectSetting('alert-id');
$alert_name =  empty($module->getProjectSetting('alert-name'))?array():$module->getProjectSetting('alert-name');

#checkboxes
if(!isset($_REQUEST['email-repetitive-update'])){
    $repetitive = "0";
    $deactivated = 0;
    $deactivated_text_changes = "activated";
    $deactivated_text_action = "Deactivated";
}else{
    $repetitive = "1";
    $deactivated = 1;
    $deactivated_text_changes = "deactivated";
    $deactivated_text_action = "Activated";
}

if(!isset($_REQUEST['email-incomplete-update'])){
    $incomplete = "0";
}else{
    $incomplete = "1";
}

//If first time new alert naming, update all.
if(empty($alert_id)){
    foreach ($form_name as $index=>$value){
        $alert_id[$index] = $index;
    }
    $module->setProjectSetting('alert-id', $alert_id);
}else if(empty($alert_id[$index])){
    $new_alert_id = max($alert_id) + 1;
    array_push($alert_id,$new_alert_id);
    $module->setProjectSetting('alert-id', $alert_id);
}

#Add logs
$action_description = "Modifications on Scheduled Alert ".$index;
$schedule_changed = false;
if($cron_send_email_on[$index] != $_REQUEST['cron-send-email-on-update'] || $cron_send_email_on_field[$index] != $_REQUEST['cron-send-email-on-field-update'] ||
    $cron_repeat_for[$index] != $_REQUEST['cron-repeat-for-update'] ||
    $cron_queue_expiration_date[$index] != $_REQUEST['cron-queue-expiration-date-update'] || $cron_queue_expiration_date_field[$index] != $_REQUEST['cron-queue-expiration-date-field-update']){
    $schedule_changed = true;
    $module->addQueueLog($pid, $action_description." - Old Settings", $cron_send_email_on[$index], $cron_send_email_on_field[$index], $cron_repeat_for[$index], $cron_queue_expiration_date[$index], $cron_queue_expiration_date_field[$index]);
}

#Change from non re-send to re-send we need to deactivate queued emails
if($email_repetitive[$index] == "0" && $repetitive == "1" || $email_repetitive[$index] == "1" && $repetitive == "0"){
    $email_queue =  empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');
    if(!empty($email_queue)){
        $scheduled_records_changed = "";
        $queue = $email_queue;
        foreach ($email_queue as $id=>$email){
            if($email['project_id'] == $pid && $email['alert'] == $index){
                $queue[$id]['deactivated'] = $deactivated;
                $scheduled_records_changed .= $email['record'].",";
            }
        }
        $module->setProjectSetting('email-queue', $queue);
        #Add logs
        $changes_made = "Record IDs ".$deactivated_text_changes.": ".rtrim($scheduled_records_changed,",");
        \REDCap::logEvent($action_description." - Records","Re-send ".$deactivated_text_action." with queued emails.\n".$changes_made,null,null,null,$pid);
    }
}

#Replace new data with old
$form_name[$index] = $_REQUEST['form-name-update'];
$form_name_event[$index] = $_REQUEST['form-name-event'];
$email_from[$index] = $_REQUEST['email-from-update'];
$email_to[$index] = $_REQUEST['email-to-update'];
$email_cc[$index] = $_REQUEST['email-cc-update'];
$email_bcc[$index] = $_REQUEST['email-bcc-update'];
$email_subject[$index] = $_REQUEST['email-subject-update'];
$email_text[$index] = $_REQUEST['email-text-update-editor'];
$email_attachment_variable[$index] = $_REQUEST['email-attachment-variable-update'];
$email_repetitive[$index] = $repetitive;
$email_condition[$index] = $_REQUEST['email-condition-update'];
$email_incomplete[$index] = $incomplete;
$cron_send_email_on[$index] = $_REQUEST['cron-send-email-on-update'];
$cron_send_email_on_field[$index] = $_REQUEST['cron-send-email-on-field-update'];
$cron_repeat_for[$index] = $_REQUEST['cron-repeat-for-update'];
$cron_repeat_until[$index] = $_REQUEST['cron-repeat-until-update'];
$cron_repeat_until_field[$index] = $_REQUEST['cron-repeat-until-field-update'];
$cron_queue_expiration_date[$index] = $_REQUEST['cron-queue-expiration-date-update'];
$cron_queue_expiration_date_field[$index] = $_REQUEST['cron-queue-expiration-date-field-update'];
$alert_name[$index] = $_REQUEST['alert-name-update'];

if($schedule_changed){
    $module->addQueueLog($pid, $action_description, $cron_send_email_on[$index], $cron_send_email_on_field[$index], $cron_repeat_for[$index], $cron_queue_expiration_date[$index], $cron_queue_expiration_date_field[$index]);
}

#Already scheduled emails need to be updated
if(isset($_REQUEST['cron-queue-update'])){
    if(($email_repetitive[$index] == '0' && ($cron_send_email_on[$index] != 'now' && $cron_send_email_on[$index] != '' && $cron_send_email_on_field[$index] !='')) || $cron_send_email_on[$index] == 'now'){
        $email_queue =  empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');
        if(!empty($email_queue)){
            $scheduled_records_changed = "";
            $queue = $email_queue;
            foreach ($email_queue as $id=>$email){
                if($email['project_id'] == $pid && $email['alert'] == $index){
                    $queue[$id]['option'] = $cron_send_email_on[$index];
                    $scheduled_records_changed .= $email['record'].",";
                }
            }
            $module->setProjectSetting('email-queue', $queue);
        }

        #Add logs
        $changes_made = "Record IDs changed: ".rtrim($scheduled_records_changed,",");
        \REDCap::logEvent($action_description." - Records",$changes_made,null,null,null,$pid);
    }
}

#Save data
$module->setProjectSetting('form-name', $form_name);
$module->setProjectSetting('form-name-event', $form_name_event);
$module->setProjectSetting('email-from', $email_from);
$module->setProjectSetting('email-to', $email_to);
$module->setProjectSetting('email-cc', $email_cc);
$module->setProjectSetting('email-bcc', $email_bcc);
$module->setProjectSetting('email-subject', $email_subject);
$module->setProjectSetting('email-text', $email_text);
$module->setProjectSetting('email-attachment-variable', $email_attachment_variable);
$module->setProjectSetting('email-repetitive', $email_repetitive);
$module->setProjectSetting('email-condition', $email_condition);
$module->setProjectSetting('email-incomplete', $email_incomplete);
$module->setProjectSetting('cron-send-email-on', $cron_send_email_on);
$module->setProjectSetting('cron-send-email-on-field', $cron_send_email_on_field);
$module->setProjectSetting('cron-repeat-for', $cron_repeat_for);
$module->setProjectSetting('cron-repeat-until', $cron_repeat_until);
$module->setProjectSetting('cron-repeat-until-field', $cron_repeat_until_field);
$module->setProjectSetting('cron-queue-expiration-date', $cron_queue_expiration_date);
$module->setProjectSetting('cron-queue-expiration-date-field', $cron_queue_expiration_date_field);
$module->setProjectSetting('alert-name', $alert_name);

echo json_encode(array(
    'status' => 'success',
    'cron_queue_expiration_date_field' => json_encode($cron_queue_expiration_date_field),
    'message' => ''
));

?>
