<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_preview'];

#get data from the DB
$email_from = empty($module->getProjectSetting('email-from'))?array():$module->getProjectSetting('email-from');
$email_to = empty($module->getProjectSetting('email-to'))?array():$module->getProjectSetting('email-to');
$email_cc =  empty($module->getProjectSetting('email-cc'))?array():$module->getProjectSetting('email-cc');
$email_bcc =  empty($module->getProjectSetting('email-bcc'))?array():$module->getProjectSetting('email-bcc');
$email_subject =  empty($module->getProjectSetting('email-subject'))?array():$module->getProjectSetting('email-subject');
$email_text =  empty($module->getProjectSetting('email-text'))?array():$module->getProjectSetting('email-text');

$preview = "<table style='margin:0 auto;width:100%'><tr><td>From:</td><td>".preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $email_from[$index])."</td></tr>";
$preview .= "<tr><td>To:</td><td>".str_replace(',',', ',preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $email_to[$index]))."</td></tr>";

if($email_cc[$index] != ''){
    $preview = "<tr><td>CC:</td><td>".str_replace(',',', ',preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $email_cc[$index]))."</td></tr>";
}
if($email_bcc[$index] != ''){
    $preview = "<tr><td>BCC:</td><td>".str_replace(',',', ',preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $email_bcc[$index]))."</td></tr>";
}
$preview .= "<tr><td>Subject:</td><td>".$email_subject[$index]."</td></tr>";
$preview .= "<tr><td>Message:</td><td>".$email_text[$index]."</td></tr></table>";


echo $preview;
//echo $email_text[$index];

?>
