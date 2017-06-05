<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';

$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_update'];

#get data from the DB
$form_name = empty(ExternalModules::getProjectSetting($prefix, $pid, 'form-name'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'form-name');
$email_to = empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-to'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-to');
$email_cc =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-cc'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-cc');
$email_subject =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-subject'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-subject');
$email_text =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-text'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-text');
$email_attachment1 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment1'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment1');
$email_attachment2 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment2'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment2');
$email_attachment3 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment3'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment3');
$email_attachment4 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment4'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment4');
$email_attachment5 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment5'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment5');
$email_repetitive =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive');
$email_timestamp =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp');
$email_condition =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-condition'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-condition');

#checkboxes
if(!isset($_REQUEST['email-repetitive-update'])){
    $repetitive = 0;
}else{
    $repetitive = 1;
}

if(!isset($_REQUEST['email-timestamp-update'])){
    $timestamp = 0;
}else{
    $timestamp = 1;
}

#Replace new data with old
$form_name[$index] = $_REQUEST['form-name-update'];
$email_to[$index] = $_REQUEST['email-to-update'];
$email_cc[$index] = $_REQUEST['email-cc-update'];
$email_subject[$index] = $_REQUEST['email-subject-update'];
$email_text[$index] = $_REQUEST['email-text-update-editor'];
$email_attachment1[$index] = $_REQUEST['email-attachment1-update'];
$email_attachment2[$index] = $_REQUEST['email-attachment2-update'];
$email_attachment3[$index] = $_REQUEST['email-attachment3-update'];
$email_attachment4[$index] = $_REQUEST['email-attachment4-update'];
$email_attachment5[$index] = $_REQUEST['email-attachment5-update'];
$email_repetitive[$index] = $repetitive;
$email_timestamp[$index] = $timestamp;
$email_condition[$index] = $_REQUEST['email-condition-update'];

#Save data
ExternalModules::setProjectSetting($prefix,$pid, 'form-name', $form_name);
ExternalModules::setProjectSetting($prefix,$pid, 'email-to', $email_to);
ExternalModules::setProjectSetting($prefix,$pid, 'email-cc', $email_cc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-subject', $email_subject);
ExternalModules::setProjectSetting($prefix,$pid, 'email-text', $email_text);
ExternalModules::setProjectSetting($prefix,$pid, 'email-repetitive', $email_repetitive);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp', $email_timestamp);
ExternalModules::setProjectSetting($prefix,$pid, 'email-condition', $email_condition);

?>