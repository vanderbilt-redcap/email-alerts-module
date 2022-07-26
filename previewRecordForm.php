<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_record_preview'];
$record =  $_REQUEST['preview_record_id'];

#get data from the DB
$form_name = empty($module->getProjectSetting('form-name'))?array():$module->getProjectSetting('form-name')[$index];
$form_name_event =  empty($module->getProjectSetting('form-name-event'))?array():$module->getProjectSetting('form-name-event')[$index];
$email_from = empty($module->getProjectSetting('email-from'))?array():$module->getProjectSetting('email-from')[$index];
$email_subject =  empty($module->getProjectSetting('email-subject'))?array():$module->getProjectSetting('email-subject')[$index];
$email_text =  empty($module->getProjectSetting('email-text'))?array():$module->getProjectSetting('email-text')[$index];
$datapipe_var = $module->getProjectSetting("datapipe_var", $project_id);

$data = \REDCap::getData($project_id,"array",$record);

if(empty($form_name_event)){
    if(array_key_exists('repeat_instances',$data[$record])){
        foreach ($data[$record]['repeat_instances'] as $event_id=>$value){
            $form_name_event = $event_id;
            break;
        }
    }else{
        foreach ($data[$record] as $event_id=>$value){
            $form_name_event = $event_id;
            break;
        }
    }
}

#Email Addresses
$array_emails = array();
$array_emails = $module->setEmailAddresses($array_emails, $project_id, $record, $event_id, $form_name, 1, $data, $index, \REDCap::isLongitudinal());

$email_to = "";
foreach ($array_emails['to'] as $address){
    $email_to .= $address[0].", ";
}

$email_cc = "";
foreach ($array_emails['cc'] as $address){
    $email_cc .= $address[0].", ";
}

$email_bcc = "";
foreach ($array_emails['bcc'] as $address){
    $email_bcc .= $address[0].", ";
}


$preview = "<table style='margin:0 auto;width:100%'><tr><td>From:</td><td>".preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $email_from)."</td></tr>";
$preview .= "<tr><td>To:</td><td>".str_replace(',',', ',preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', rtrim($email_to,', ')))."</td></tr>";

if($email_cc != ''){
    $preview = "<tr><td>CC:</td><td>".str_replace(',',', ',preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', rtrim($email_cc,', ')))."</td></tr>";
}
if($email_bcc != ''){
    $preview = "<tr><td>BCC:</td><td>".str_replace(',',', ',preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', rtrim($email_bcc,', ')))."</td></tr>";
}

$email_text = $module->setDataPiping($datapipe_var, $email_text, $project_id, $data, $record, $form_name_event, $form_name, 1,\REDCap::isLongitudinal());
$email_subject = $module->setDataPiping($datapipe_var, $email_subject, $project_id, $data, $record, $form_name_event, $form_name, 1,\REDCap::isLongitudinal());

$preview .= "<tr><td>Subject:</td><td>".$email_subject."</td></tr>";
$preview .= "<tr><td>Message:</td><td>".$email_text."</td></tr></table>";


echo $preview;
?>
