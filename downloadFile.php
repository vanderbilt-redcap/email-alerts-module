<?php

$filename = db_escape($_REQUEST['file']);
$sname = db_escape($_REQUEST['sname']);

header('Content-type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($module->getSafePath($sname, EDOC_PATH));
?>