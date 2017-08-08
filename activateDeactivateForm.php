<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';

$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_deactivate'];
$status =  $_REQUEST['index_modal_status'];

$email_deactivate =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-deactivate'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-deactivate');

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


ExternalModules::setProjectSetting($prefix,$pid, 'email-deactivate', $email_deactivate);

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
