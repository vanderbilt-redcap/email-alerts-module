<?php

$filename = $_REQUEST['file'];
$sname = $_REQUEST['sname'];

header('Content-type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile(EDOC_PATH.$sname);
?>