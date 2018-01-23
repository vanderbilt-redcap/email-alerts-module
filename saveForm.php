<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';

$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];

#get data from the DB
$form_name = empty(ExternalModules::getProjectSetting($prefix, $pid, 'form-name'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'form-name');
$form_name_event =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'form-name-event'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'form-name-event');
$email_from = empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-from'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-from');
$email_to = empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-to'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-to');
$email_cc =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-cc'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-cc');
$email_bcc =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-bcc'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-bcc');
$email_subject =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-subject'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-subject');
$email_text =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-text'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-text');
$email_attachment_variable =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment-variable'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment-variable');
$email_repetitive =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive');
$email_condition =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-condition'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-condition');
$email_incomplete =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-incomplete'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-incomplete');

#checkboxes
if(!isset($_REQUEST['email-repetitive'])){
    $repetitive = "0";
}else{
    $repetitive = "1";
}

if(!isset($_REQUEST['email-incomplete-update'])){
    $incomplete = "0";
}else{
    $incomplete = "1";
}

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

#Save data
ExternalModules::setProjectSetting($prefix,$pid, 'form-name', $form_name);
ExternalModules::setProjectSetting($prefix,$pid, 'form-name-event', $form_name_event);
ExternalModules::setProjectSetting($prefix,$pid, 'email-from', $email_from);
ExternalModules::setProjectSetting($prefix,$pid, 'email-to', $email_to);
ExternalModules::setProjectSetting($prefix,$pid, 'email-cc', $email_cc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-bcc', $email_bcc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-subject', $email_subject);
ExternalModules::setProjectSetting($prefix,$pid, 'email-text', $email_text);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment-variable', $email_attachment_variable);
ExternalModules::setProjectSetting($prefix,$pid, 'email-repetitive', $email_repetitive);
ExternalModules::setProjectSetting($prefix,$pid, 'email-condition', $email_condition);
ExternalModules::setProjectSetting($prefix,$pid, 'email-incomplete', $email_incomplete);

//Extra Data
$email_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-sent');
$email_timestamp_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent');
$email_deactivate =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-deactivate'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-deactivate');
$email_deleted =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-deleted'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-deleted');
array_push($email_sent,"0");
array_push($email_timestamp_sent,"0");
array_push($email_deactivate,"0");
array_push($email_deleted,"0");
ExternalModules::setProjectSetting($prefix,$pid, 'email-sent', $email_sent);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp-sent', $email_timestamp_sent);
ExternalModules::setProjectSetting($prefix,$pid, 'email-deactivate', $email_deactivate);
ExternalModules::setProjectSetting($prefix,$pid, 'email-deleted', $email_deleted);

//check if forms where uploaded and if not add blank values
for($i=1; $i<6; $i++){
    $email_attachment =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment'.$i))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment'.$i);
    if((count($form_name)-1 == count($email_attachment)) || (count($form_name) > count($email_attachment))){
        array_push($email_attachment,"");
        ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment'.$i, $email_attachment);
    }
}


echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
