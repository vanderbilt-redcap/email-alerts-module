<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once APP_PATH_DOCROOT.'Classes/Files.php';

$pid = @$_GET['pid'];
$edoc = $_POST['edoc'];
$key = $_POST['key'];
$prefix = ExternalModules::getPrefixForID($_GET['id']);
$index = $_POST['index'];

# delete the edoc
if (($edoc) && (is_numeric($edoc))) {
    ExternalModules::deleteEDoc($edoc);

    $email_attachment =  empty(ExternalModules::getProjectSetting($prefix, $pid, $key))?array():ExternalModules::getProjectSetting($prefix, $pid, $key);
    $email_attachment[$index] = "";
    ExternalModules::setProjectSetting($prefix,$pid, $key, $email_attachment);
    $type = "Delete $edoc";
}


header('Content-type: application/json');
echo json_encode(array(
    'type' => $type,
    'status' => 'success'
));

?>
