<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

$form = $_REQUEST['form'];
$project_id = $_REQUEST['project_id'];
$event_id = $_REQUEST['event_id'];

$repeating = 0;

if(!empty($form) && !empty($event_id) && !empty($project_id)){
    $sql = "SELECT form_name, event_id FROM redcap_events_repeat WHERE form_name = '".db_real_escape_string($form)."' AND event_id = ".db_real_escape_string($event_id);
    $q = db_query($sql);
    $repeating = db_num_rows($q);

    if ($repeating === 0) {
        $sql = "SELECT form_name FROM redcap_events_repeat WHERE form_name IS NULL AND event_id = ".db_real_escape_string($event_id);
        $q = db_query($sql);
        $repeating = db_num_rows($q);
    }
}

echo json_encode(array(
    'status' => 'success',
    'repeating' => $repeating
));

?>
