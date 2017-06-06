<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';

$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];

#get data from the DB
$form_name = empty(ExternalModules::getProjectSetting($prefix, $pid, 'form-name'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'form-name');
$email_to = empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-to'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-to');
$email_cc =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-cc'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-cc');
$email_subject =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-subject'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-subject');
$email_text =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-text'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-text');
$email_repetitive =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive');
$email_timestamp =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp');
$email_condition =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-condition'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-condition');

#checkboxes
if(empty($_REQUEST['email-repetitive'])){
    $repetitive = "0";
}else{
    $repetitive = "1";
}

if(empty($_REQUEST['email-timestamp'])){
    $timestamp = "0";
}else{
    $timestamp = "1";
}

#Add new data with old
array_push($form_name,$_REQUEST['form-name']);
array_push($email_to,$_REQUEST['email-to']);
array_push($email_cc,$_REQUEST['email-cc']);
array_push($email_subject,$_REQUEST['email-subject']);
array_push($email_text,$_REQUEST['email-text-editor']);
array_push($email_repetitive,$repetitive);
array_push($email_timestamp,$timestamp);
array_push($email_condition,$_REQUEST['email-condition']);

#Save data

ExternalModules::setProjectSetting($prefix,$pid, 'form-name', $form_name);
ExternalModules::setProjectSetting($prefix,$pid, 'email-to', $email_to);
ExternalModules::setProjectSetting($prefix,$pid, 'email-cc', $email_cc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-subject', $email_subject);
ExternalModules::setProjectSetting($prefix,$pid, 'email-text', $email_text);
ExternalModules::setProjectSetting($prefix,$pid, 'email-repetitive', $email_repetitive);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp', $email_timestamp);
ExternalModules::setProjectSetting($prefix,$pid, 'email-condition', $email_condition);

//Extra Data
$email_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-sent');
$email_timestamp_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent');
array_push($email_sent,"0");
array_push($email_timestamp_sent,"0");
ExternalModules::setProjectSetting($prefix,$pid, 'email-sent', $email_sent);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp-sent', $email_timestamp_sent);

//check if forms where uploaded and if not add blank values
for($i=1; $i<6; $i++){
    $email_attachment =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment'.$i))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment'.$i);
    if((count($form_name)-1 == count($email_attachment)) || (count($form_name) > count($email_attachment))){
        array_push($email_attachment,"");
        ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment'.$i, $email_attachment);
    }
}


//echo $_REQUEST['email-attachment1'];

?>