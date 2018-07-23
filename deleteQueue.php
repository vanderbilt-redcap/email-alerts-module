<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_queue'];
$alertid =  $_REQUEST['alertid'];

$module->deleteQueuedEmail($index, $project_id);

#Add logs
$changes_made = "Queue #".$index." from Alert #".$alertid." manually deleted by ".USERID;
\REDCap::logEvent("Queue deleted - Alert ".$alertid,$changes_made,NULL,NULL,NULL,$project_id);

echo json_encode(array(
    'status' => 'success'
));