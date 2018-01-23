<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];
$index =  $_REQUEST['index_reenable'];
$active =  $_REQUEST['active'];

$email_deleted =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-deleted'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-deleted');

$email_deleted[$index] = "0";

$letter = "R";
if($active == "false"){
    $letter = "N";
}


ExternalModules::setProjectSetting($prefix,$pid, 'email-deleted', $email_deleted);

echo json_encode(array(
    'status' => 'success',
    'message' => $letter
));

?>
