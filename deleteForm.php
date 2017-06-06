<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';

$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_delete'];
echo $index;

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
$email_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-sent');
$email_timestamp_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent');


#Delete one element in array
unset($form_name[$index]);
unset($email_to[$index]);
unset($email_cc[$index]);
unset($email_subject[$index]);
unset($email_text[$index]);
unset($email_attachment1[$index]);
unset($email_attachment2[$index]);
unset($email_attachment3[$index]);
unset($email_attachment4[$index]);
unset($email_attachment5[$index]);
unset($email_repetitive[$index]);
unset($email_timestamp[$index]);
unset($email_condition[$index]);
unset($email_sent[$index]);
unset($email_timestamp_sent[$index]);

#Rearrange the indexes
array_values($form_name);
array_values($email_to);
array_values($email_cc);
array_values($email_subject);
array_values($email_text);
array_values($email_attachment1);
array_values($email_attachment2);
array_values($email_attachment3);
array_values($email_attachment4);
array_values($email_attachment5);
array_values($email_repetitive);
array_values($email_timestamp);
array_values($email_condition);
array_values($email_sent);
array_values($email_timestamp_sent);

#Save data
ExternalModules::setProjectSetting($prefix,$pid, 'form-name', $form_name);
ExternalModules::setProjectSetting($prefix,$pid, 'email-to', $email_to);
ExternalModules::setProjectSetting($prefix,$pid, 'email-cc', $email_cc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-subject', $email_subject);
ExternalModules::setProjectSetting($prefix,$pid, 'email-text', $email_text);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment1', $email_attachment1);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment2', $email_attachment2);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment3', $email_attachment3);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment4', $email_attachment4);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment5', $email_attachment5);
ExternalModules::setProjectSetting($prefix,$pid, 'email-repetitive', $email_repetitive);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp', $email_timestamp);
ExternalModules::setProjectSetting($prefix,$pid, 'email-condition', $email_condition);
ExternalModules::setProjectSetting($prefix,$pid, 'email-sent', $email_sent);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp-sent', $email_timestamp_sent);

echo json_encode(array(
    'status' => 'success'
));

?>