<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_duplicate'];

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
$cron_repeat_for =  empty($module->getProjectSetting('cron-repeat-for'))?array():$module->getProjectSetting('cron-repeat-for');
$cron_queue_expiration_date =  empty($module->getProjectSetting('cron-queue-expiration-date'))?array():$module->getProjectSetting('cron-queue-expiration-date');
$cron_queue_expiration_date_field =  empty($module->getProjectSetting('cron-queue-expiration-date-field'))?array():$module->getProjectSetting('cron-queue-expiration-date-field');
$alert_id =  empty($module->getProjectSetting('alert-id'))?array():$module->getProjectSetting('alert-id');

//If first time new alert naming, update all.
if(empty($alert_id)){
    foreach ($form_name as $index=>$value){
        $alert_id[$index] = $index;
    }
    $module->setProjectSetting('alert-id', $alert_id);
}
$alert_id =  empty($module->getProjectSetting('alert-id'))?array():$module->getProjectSetting('alert-id');
$new_alert_id = max($alert_id) + 1;

#Add new data with old
array_push($form_name,$form_name[$index]);
array_push($form_name_event,$form_name_event[$index]);
array_push($email_from,$email_from[$index]);
array_push($email_to,$email_to[$index]);
array_push($email_cc,$email_cc[$index]);
array_push($email_bcc,$email_bcc[$index]);
array_push($email_subject,$email_subject[$index]);
array_push($email_text,$email_text[$index]);
array_push($email_attachment_variable,"");
array_push($email_repetitive,$email_repetitive[$index]);
array_push($email_condition,$email_condition[$index]);
array_push($email_incomplete,$email_incomplete[$index]);
array_push($cron_send_email_on,$cron_send_email_on[$index]);
array_push($cron_send_email_on_field,$cron_send_email_on_field[$index]);
array_push($cron_repeat_for,$cron_repeat_for[$index]);
array_push($cron_queue_expiration_date,$cron_queue_expiration_date[$index]);
array_push($cron_queue_expiration_date_field,$cron_queue_expiration_date_field[$index]);
array_push($alert_id,$new_alert_id);

//Form Attachments
for($i=1; $i<6; $i++){
    ${"email_attachment_".$i} =  empty($module->getProjectSetting('email-attachment'.$i))?array():$module->getProjectSetting('email-attachment'.$i);
    $doc_id_old = ${"email_attachment_".$i}[$index];
    $doc_id_copy = "";
    if($doc_id_old !== "") {
        $q = $module->query("SELECT doc_name,stored_name FROM redcap_edocs_metadata WHERE doc_id=?",[$doc_id_old]);
        if ($row = db_fetch_assoc($q)) {
            $doc_id_copy = \REDCap::storeFile($module->getSafePath(EDOC_PATH.$row['stored_name'],EDOC_PATH), $pid, $row['doc_name']);
        }
    }
    array_push(${"email_attachment_".$i},$doc_id_copy);
    $module->setProjectSetting('email-attachment'.$i, ${"email_attachment_".$i});
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
$module->setProjectSetting('email-condition', $email_condition);
$module->setProjectSetting('email-incomplete', $email_incomplete);
$module->setProjectSetting('cron-send-email-on', $cron_send_email_on);
$module->setProjectSetting('cron-send-email-on-field', $cron_send_email_on_field);
$module->setProjectSetting('cron-repeat-for', $cron_repeat_for);
$module->setProjectSetting('cron-queue-expiration-date', $cron_queue_expiration_date);
$module->setProjectSetting('cron-queue-expiration-date-field', $cron_queue_expiration_date_field);
$module->setProjectSetting('alert-id', $alert_id);

//Extra Data
$email_deactivate =  empty($module->getProjectSetting('email-deactivate'))?array():$module->getProjectSetting('email-deactivate');
$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');
array_push($email_deactivate,"0");
array_push($email_deleted,"0");
$module->setProjectSetting('email-deactivate', $email_deactivate);
$module->setProjectSetting('email-deleted', $email_deleted);


echo json_encode(array(
    'status' => 'success',
    'message' => ""
));

?>
