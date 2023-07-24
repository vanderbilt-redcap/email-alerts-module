<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_GET['pid'];

#Delete Debug Information
$module->removeLogs('
                project_id = ?
                and scheduledemails = 1', [$project_id]);
$module->setProjectSetting('remove-logs-date', date('Y-m-d'), $project_id);

$message = 'The logs have been successfully deleted';

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>