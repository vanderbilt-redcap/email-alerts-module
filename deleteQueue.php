<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = $_GET['pid'];
$index =  $_REQUEST['index_modal_queue'];

$module->deleteQueuedEmail($index, $project_id);

echo json_encode(array(
    'status' => 'success'
));