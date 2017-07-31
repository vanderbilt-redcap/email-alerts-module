<?php
namespace ExternalModules;
//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once APP_PATH_DOCROOT.'Classes/Files.php';

$pid = @$_GET['pid'];
$moduleDirectoryPrefix = ExternalModules::getPrefixForID($_GET['id']);
$index = $_GET['index'];

$edoc = null;
$myfiles = array();
foreach($_FILES as $key=>$value){
    $myfiles[] = $key;
    if ($value) {
        # use REDCap's uploadFile
        $edoc = \Files::uploadFile($_FILES[$key]);
        if ($edoc) {
            $email_attachment =  empty(ExternalModules::getProjectSetting($moduleDirectoryPrefix, $pid, $key))?array():ExternalModules::getProjectSetting($moduleDirectoryPrefix, $pid, $key);

            if(!isset($index)){
                array_push($email_attachment,$edoc);
            }else{
                $email_attachment[$index] = $edoc;
            }
            ExternalModules::setProjectSetting($moduleDirectoryPrefix,$pid, $key, $email_attachment);
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
        '_POST' => json_encode($_POST),
        'status' => 'You could not find a file.'
    ));
}
