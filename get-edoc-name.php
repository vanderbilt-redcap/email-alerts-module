<?php
//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once APP_PATH_DOCROOT.'Classes/Files.php';

$pid = (int)@$_GET['pid'];
$edoc = (int)$_POST['edoc'];

$doc_name = "";
if ($edoc != "") {
    $q = $module->query("SELECT doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=?", [$edoc]);
    if ($row = $q->fetch_assoc()) {
        $doc_name = $row['doc_name'].$module->formatBytes($row['doc_size']);
    }
}

header('Content-type: application/json');
echo json_encode(array(
    'edoc_id' => $edoc,
    'doc_name' => $doc_name,
    'status' => 'success'
));

?>
