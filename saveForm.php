<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

#get data from the DB
$form_name = empty($module->getProjectSetting('form-name'))?array():$module->getProjectSetting('form-name');
$form_name_event =  empty($module->getProjectSetting('form-name-event'))?array():$module->getProjectSetting('form-name-event');
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
$cron_repeat_email =  empty($module->getProjectSetting('cron-repeat-email'))?array():$module->getProjectSetting('cron-repeat-email');
$cron_repeat_for =  empty($module->getProjectSetting('cron-repeat-for'))?array():$module->getProjectSetting('cron-repeat-for');
$cron_repeat_until =  empty($module->getProjectSetting('cron-repeat-until'))?array():$module->getProjectSetting('cron-repeat-until');
$cron_repeat_until_field =  empty($module->getProjectSetting('cron-repeat-until-field'))?array():$module->getProjectSetting('cron-repeat-until-field');
$alert_id =  empty($module->getProjectSetting('alert-id'))?array():$module->getProjectSetting('alert-id');

#checkboxes
if(!isset($_REQUEST['email-repetitive'])){
    $repetitive = "0";
}else{
    $repetitive = "1";
}

if(!isset($_REQUEST['email-incomplete'])){
    $incomplete = "0";
}else{
    $incomplete = "1";
}

if(!isset($_REQUEST['cron-repeat-email'])){
    $cron_repeat = "0";
}else{
    $cron_repeat = "1";
}

//If first time new alert naming, update all.
if(empty($alert_id)){
    foreach ($form_name as $index=>$value){
        $alert_id[$index] = $index;
    }
    $module->setProjectSetting('alert-id', $alert_id);
}
$new_alert_id = max($alert_id) + 1;

#Add new data with old
array_push($form_name,$_REQUEST['form-name']);
array_push($form_name_event,$_REQUEST['form-name-event']);
array_push($email_from,$_REQUEST['email-from']);
array_push($email_to,$_REQUEST['email-to']);
array_push($email_cc,$_REQUEST['email-cc']);
array_push($email_bcc,$_REQUEST['email-bcc']);
array_push($email_subject,$_REQUEST['email-subject']);
array_push($email_text,$_REQUEST['email-text-editor']);
array_push($email_attachment_variable,$_REQUEST['email-attachment-variable']);
array_push($email_repetitive,$repetitive);
array_push($email_condition,$_REQUEST['email-condition']);
array_push($email_incomplete,$incomplete);
array_push($cron_send_email_on,$_REQUEST['cron-send-email-on']);
array_push($cron_send_email_on_field,$_REQUEST['cron-send-email-on-field']);
array_push($cron_repeat_for,$_REQUEST['cron-repeat-for']);
array_push($cron_repeat_until,$_REQUEST['cron-repeat-until']);
array_push($cron_repeat_until_field,$_REQUEST['cron-repeat-until-field']);
array_push($cron_repeat_email,$cron_repeat);
array_push($alert_id,$new_alert_id);

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
$module->setProjectSetting('cron-repeat-email', $cron_repeat_email);
$module->setProjectSetting('cron-repeat-for', $cron_repeat_for);
$module->setProjectSetting('cron-repeat-until', $cron_repeat_until);
$module->setProjectSetting('cron-repeat-until-field', $cron_repeat_until_field);
$module->setProjectSetting('alert-id', $alert_id);

//Extra Data
$email_sent =  empty($module->getProjectSetting('email-sent'))?array():$module->getProjectSetting('email-sent');
$email_timestamp_sent =  empty($module->getProjectSetting('email-timestamp-sent'))?array():$module->getProjectSetting('email-timestamp-sent');
$email_deactivate =  empty($module->getProjectSetting('email-deactivate'))?array():$module->getProjectSetting('email-deactivate');
$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');
array_push($email_sent,"0");
array_push($email_timestamp_sent,"0");
array_push($email_deactivate,"0");
array_push($email_deleted,"0");
$module->setProjectSetting('email-sent', $email_sent);
$module->setProjectSetting('email-timestamp-sent', $email_timestamp_sent);
$module->setProjectSetting('email-deactivate', $email_deactivate);
$module->setProjectSetting('email-deleted', $email_deleted);

//check if forms where uploaded and if not add blank values
for($i=1; $i<6; $i++){
    $email_attachment =  empty($module->getProjectSetting('email-attachment'.$i))?array():$module->getProjectSetting('email-attachment'.$i);
    if((count($form_name)-1 == count($email_attachment)) || (count($form_name) > count($email_attachment))){
        array_push($email_attachment,"");
        $module->setProjectSetting('email-attachment'.$i, $email_attachment);
    }
}

echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
