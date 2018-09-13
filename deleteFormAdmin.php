<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_delete'];


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
$email_attachment1 =  empty($module->getProjectSetting('email-attachment1'))?array():$module->getProjectSetting('email-attachment1');
$email_attachment2 =  empty($module->getProjectSetting('email-attachment2'))?array():$module->getProjectSetting('email-attachment2');
$email_attachment3 =  empty($module->getProjectSetting('email-attachment3'))?array():$module->getProjectSetting('email-attachment3');
$email_attachment4 =  empty($module->getProjectSetting('email-attachment4'))?array():$module->getProjectSetting('email-attachment4');
$email_attachment5 =  empty($module->getProjectSetting('email-attachment5'))?array():$module->getProjectSetting('email-attachment5');
$email_repetitive =  empty($module->getProjectSetting('email-repetitive'))?array():$module->getProjectSetting('email-repetitive');
$email_condition =  empty($module->getProjectSetting('email-condition'))?array():$module->getProjectSetting('email-condition');
$email_sent =  empty($module->getProjectSetting('email-sent'))?array():$module->getProjectSetting('email-sent');
$email_timestamp_sent =  empty($module->getProjectSetting('email-timestamp-sent'))?array():$module->getProjectSetting('email-timestamp-sent');
$email_deactivate =  empty($module->getProjectSetting('email-deactivate'))?array():$module->getProjectSetting('email-deactivate');
$email_incomplete =  empty($module->getProjectSetting('email-incomplete'))?array():$module->getProjectSetting('email-incomplete');
$cron_send_email_on =  empty($module->getProjectSetting('cron-send-email-on'))?array():$module->getProjectSetting('cron-send-email-on');
$cron_send_email_on_field =  empty($module->getProjectSetting('cron-send-email-on-field'))?array():$module->getProjectSetting('cron-send-email-on-field');
$cron_repeat_email =  empty($module->getProjectSetting('cron-repeat-email'))?array():$module->getProjectSetting('cron-repeat-email');
$cron_repeat_for =  empty($module->getProjectSetting('cron-repeat-for'))?array():$module->getProjectSetting('cron-repeat-for');
$cron_repeat_until =  empty($module->getProjectSetting('cron-repeat-until'))?array():$module->getProjectSetting('cron-repeat-until');
$cron_repeat_until_field =  empty($module->getProjectSetting('cron-repeat-until-field'))?array():$module->getProjectSetting('cron-repeat-until-field');
$cron_queue_expiration_date =  empty($module->getProjectSetting('cron-queue-expiration-date'))?array():$module->getProjectSetting('cron-queue-expiration-date');
$cron_queue_expiration_date_field =  empty($module->getProjectSetting('cron-queue-expiration-date-field'))?array():$module->getProjectSetting('cron-queue-expiration-date-field');
$email_records_sent =  empty($module->getProjectSetting('email-records-sent'))?array():$module->getProjectSetting('email-records-sent');
$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');
$alert_id =  empty($module->getProjectSetting('alert-id'))?array():$module->getProjectSetting('alert-id');
$email_queue =  empty($module->getProjectSetting('email-queue'))?array():$module->getProjectSetting('email-queue');

#Add some logs
$action_description = "Deleted Alert #".$index;
$changes_made = "[Subject]: ".$email_subject[$index].", [Message]: ".$email_text[$index];
\REDCap::logEvent($action_description,$changes_made,null,null,null,null);

$action_description = "Deleted Alert #".$index." To";
\REDCap::logEvent($action_description,$email_to[$index].$email_cc[$index].$email_bcc[$index],null,null,null,null,null);

#Delete email repetitive sent from JSON before deleting all data
$email_repetitive_sent =  empty($module->getProjectSetting('email-repetitive-sent'))?array():$module->getProjectSetting('email-repetitive-sent');
$email_repetitive_sent = json_decode($email_repetitive_sent);

if(!empty($email_repetitive_sent)) {
    $one_less = 0;
    foreach ($email_repetitive_sent as $form => $form_value) {
        foreach ($email_repetitive_sent->$form as $alert => $value) {
            //we don't add the deleted alert and rename the old ones.
            if ($alert == $index) {
                $one_less = 1;
            }else if($alert >= 0){
                //if the alert is -1 do not add it. When copying a project sometimes it has a weird config.
                $jsonArray[$form][$alert - $one_less] = $value;
            }
        }
    }
    $module->setProjectSetting('email-repetitive-sent', json_encode($jsonArray));
}

#Delete queued alerts
if(!empty($email_queue)){
    $scheduled_records_changed = "";
    $queue = $email_queue;
    foreach ($email_queue as $id=>$email){
        if($email['project_id'] == $pid && $email['alert']==$index){
            $scheduled_records_changed .= $email['record'].",";
            unset($queue[$id]);
        }
    }
    $queue = array_values($queue);
    $module->setProjectSetting('email-queue', $queue);

    #Add logs
    $action_description = "Deleted Scheduled Alert ".$index;
    $changes_made = "Record IDs deleted: ".rtrim($scheduled_records_changed,",");
    \REDCap::logEvent($action_description,$changes_made,null,null,null,$pid);
}




#Delete one element in array
unset($form_name[$index]);
unset($form_name_event[$index]);
unset($email_from[$index]);
unset($email_to[$index]);
unset($email_cc[$index]);
unset($email_bcc[$index]);
unset($email_subject[$index]);
unset($email_text[$index]);
unset($email_attachment_variable[$index]);
unset($email_attachment1[$index]);
unset($email_attachment2[$index]);
unset($email_attachment3[$index]);
unset($email_attachment4[$index]);
unset($email_attachment5[$index]);
unset($email_repetitive[$index]);
unset($email_condition[$index]);
unset($email_sent[$index]);
unset($email_timestamp_sent[$index]);
unset($email_deactivate[$index]);
unset($email_incomplete[$index]);
unset($cron_send_email_on[$index]);
unset($cron_send_email_on_field[$index]);
unset($cron_repeat_email[$index]);
unset($cron_repeat_for[$index]);
unset($cron_repeat_until[$index]);
unset($cron_repeat_until_field[$index]);
unset($cron_queue_expiration_date[$index]);
unset($cron_queue_expiration_date_field[$index]);
unset($email_records_sent[$index]);
unset($email_deleted[$index]);
unset($alert_id[$index]);

#Rearrange the indexes
$form_name = array_values($form_name);
$form_name_event = array_values($form_name_event);
$email_from = array_values($email_from);
$email_to = array_values($email_to);
$email_cc = array_values($email_cc);
$email_bcc = array_values($email_bcc);
$email_subject = array_values($email_subject);
$email_text = array_values($email_text);
$email_attachment_variable = array_values($email_attachment_variable);
$email_attachment1 = array_values($email_attachment1);
$email_attachment2 = array_values($email_attachment2);
$email_attachment3 = array_values($email_attachment3);
$email_attachment4 = array_values($email_attachment4);
$email_attachment5 = array_values($email_attachment5);
$email_repetitive = array_values($email_repetitive);
$email_condition = array_values($email_condition);
$email_sent = array_values($email_sent);
$email_timestamp_sent = array_values($email_timestamp_sent);
$email_deactivate = array_values($email_deactivate);
$email_incomplete = array_values($email_incomplete);
$cron_send_email_on = array_values($cron_send_email_on);
$cron_send_email_on_field = array_values($cron_send_email_on_field);
$cron_repeat_email = array_values($cron_repeat_email);
$cron_repeat_for = array_values($cron_repeat_for);
$cron_repeat_until = array_values($cron_repeat_until);
$cron_repeat_until_field = array_values($cron_repeat_until_field);
$cron_queue_expiration_date = array_values($cron_queue_expiration_date);
$cron_queue_expiration_date_field = array_values($cron_queue_expiration_date_field);
$email_records_sent = array_values($email_records_sent);
$email_deleted = array_values($email_deleted);
$alert_id = array_values($alert_id);

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
$module->setProjectSetting('email-attachment1', $email_attachment1);
$module->setProjectSetting('email-attachment2', $email_attachment2);
$module->setProjectSetting('email-attachment3', $email_attachment3);
$module->setProjectSetting('email-attachment4', $email_attachment4);
$module->setProjectSetting('email-attachment5', $email_attachment5);
$module->setProjectSetting('email-repetitive', $email_repetitive);
$module->setProjectSetting('email-condition', $email_condition);
$module->setProjectSetting('email-sent', $email_sent);
$module->setProjectSetting('email-timestamp-sent', $email_timestamp_sent);
$module->setProjectSetting('email-deactivate', $email_deactivate);
$module->setProjectSetting('email-incomplete', $email_incomplete);
$module->setProjectSetting('cron-send-email-on', $cron_send_email_on);
$module->setProjectSetting('cron-send-email-on-field', $cron_send_email_on_field);
$module->setProjectSetting('cron-repeat-email', $cron_repeat_email);
$module->setProjectSetting('cron-repeat-for', $cron_repeat_for);
$module->setProjectSetting('cron-repeat-until', $cron_repeat_until);
$module->setProjectSetting('cron-repeat-until-field', $cron_repeat_until_field);
$module->setProjectSetting('cron-queue-expiration-date', $cron_queue_expiration_date);
$module->setProjectSetting('cron-queue-expiration-date-field', $cron_queue_expiration_date_field);
$module->setProjectSetting('email-records-sent', $email_records_sent);
$module->setProjectSetting('email-deleted', $email_deleted);
$module->setProjectSetting('alert-id', $alert_id);

#we rename the alert number in the queued emails
if(!empty($email_queue)){
    $queue = $email_queue;
    foreach ($email_queue as $id=>$email){
        foreach ($alert_id as $alert=>$alert_name){
            if($alert_name == $email['alert']){
                $queue[$id]['alert'] = $alert;
            }
        }
    }
    $module->setProjectSetting('email-queue', $queue);
}

echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
