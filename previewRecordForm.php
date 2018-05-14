<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_record_preview'];
$record =  $_REQUEST['preview_record_id'];

echo $module->getPreviewByRecord($project_id, $record, $index);
?>
