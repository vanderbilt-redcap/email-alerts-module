<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_delete_user'];

$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');

$email_deleted[$index] = "1";
$message = "D";


$module->setProjectSetting('email-deleted', $email_deleted);

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
