<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$pid = $_GET['pid'];
$index =  $_REQUEST['index_reenable'];
$active =  $_REQUEST['active'];

$email_deleted =  empty($module->getProjectSetting('email-deleted'))?array():$module->getProjectSetting('email-deleted');

$email_deleted[$index] = "0";

$letter = "R";
if($active == "false"){
    $letter = "N";
}


$module->setProjectSetting('email-deleted', $email_deleted);

echo json_encode(array(
    'status' => 'success',
    'message' => $letter
));

?>
