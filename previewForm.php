<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_preview'];

#get data from the DB
$email_text =  empty($module->getProjectSetting('email-text'))?array():$module->getProjectSetting('email-text');


echo $email_text[$index];

?>
