<?php
//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once APP_PATH_DOCROOT.'Classes/Files.php';

$pid = (int)@$_GET['pid'];
$edoc = (int)$_POST['edoc'];

$doc_name = "";
if ($edoc != "") {
    $ary = \Files::getEdocContentsAttributes((integer) $edoc);
    $doc_name = $ary[1];
}

header('Content-type: application/json');
echo json_encode(array(
    'edoc_id' => $edoc,
    'doc_name' => $doc_name,
    'status' => 'success'
));

?>
