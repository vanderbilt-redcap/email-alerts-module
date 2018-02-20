<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$index =  $_REQUEST['index_modal_update'];

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

#checkboxes
if(!isset($_REQUEST['email-repetitive-update'])){
    $repetitive = "0";
}else{
    $repetitive = "1";
}

if(!isset($_REQUEST['email-incomplete-update'])){
    $incomplete = "0";
}else{
    $incomplete = "1";
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

echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
