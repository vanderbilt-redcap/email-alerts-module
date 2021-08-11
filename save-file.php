<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once APP_PATH_DOCROOT.'Classes/Files.php';

$index = (int)$_GET['index'];

$edoc = null;
$myfiles = array();
foreach($_FILES as $key=>$value){
    $myfiles[] = $key;
    if ($value) {
        # use REDCap's uploadFile
        $edoc = (int)\Files::uploadFile($_FILES[$key]);
        if ($edoc) {
            $email_attachment =  empty($module->getProjectSetting($key))?array():$module->getProjectSetting($key);

            if(!isset($index)){
                array_push($email_attachment,$edoc);
            }else{
                $email_attachment[$index] = $edoc;
            }
            $module->setProjectSetting($key, $email_attachment);
        } else {
            header('Content-type: application/json');
            echo json_encode(array(
                'status' => "You could not save a file properly."
            ));
        }
    }
}

if ($edoc) {
    header('Content-type: application/json');
    echo json_encode(array(
        'status' => 'success'
    ));
} else {
    header('Content-type: application/json');
    echo json_encode(array(
        'myfiles' => json_encode($myfiles),
        '_POST' => json_encode(db_escape($_POST)),
        'status' => 'You could not find a file.'
    ));
}
