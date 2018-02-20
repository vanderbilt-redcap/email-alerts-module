<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_deactivate'];
$status =  $_REQUEST['index_modal_status'];

$email_deactivate =  empty($module->getProjectSetting('email-deactivate'))?array():$module->getProjectSetting('email-deactivate');

$message = '';
if($status == "Activate"){
    //Active
    $email_deactivate[$index] = "0";
    $message = "T";
}else if($status == "Deactivate"){
    //Not Active
    $email_deactivate[$index] = "1";
    $message = "E";
}


$module->setProjectSetting('email-deactivate', $email_deactivate);

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
