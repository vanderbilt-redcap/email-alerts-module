<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];
$index =  $_REQUEST['index_duplicate'];

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


echo json_encode(array(
    'status' => 'success',
    'message' => ""
));

?>
