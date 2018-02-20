<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once APP_PATH_DOCROOT.'Classes/Files.php';

$pid = @$_GET['pid'];
$edoc = $_POST['edoc'];
$key = $_POST['key'];
$index = $_POST['index'];

# delete the edoc
if (($edoc) && (is_numeric($edoc))) {
    ExternalModules::deleteEDoc($edoc);

    $email_attachment =  empty($module->getProjectSetting($key))?array():$module->getProjectSetting($key);
    $email_attachment[$index] = "";
    $module->setProjectSetting($key, $email_attachment);
    $type = "Delete $edoc";
}


header('Content-type: application/json');
echo json_encode(array(
    'type' => $type,
    'status' => 'success'
));

?>
