<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

$event_id = $_REQUEST['event'];
$project_id = $_REQUEST['project_id'];
$alert_id = $_REQUEST['index_modal_queue'];
$show_instance = "";
$form_name = empty($module->getProjectSetting('form-name'))?array():$module->getProjectSetting('form-name')[$alert_id];

$sql = "SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id ='$project_id'";
$q = db_query($sql);

if (db_num_rows($q)) {
    while($row = db_fetch_assoc($q)) {
        if ($row['event_id'] == $event_id) {
            $project_name = db_escape(\Project::getValidProjectName($form_name));
            $event_id = $row['event_id'];
            $sql = "SELECT * FROM redcap_events_repeat WHERE event_id='$event_id' AND form_name='$project_name'";
            $q = db_query($sql);
            $row = db_fetch_assoc($q);
            if ($row) {
                $show_instance = '<div style="float:left;width: 280px;"><label style="font-weight: normal;padding-left: 15px;padding-right: 15px">Add Instances<br><span style="color:red">*If none is typed, only the first instance added</span></label></div><div style="float:left;"><textarea class="form-control" id="queue_instances" rows="6"></textarea></div>';
            }
        }
    }
}

echo json_encode(array(
    'status' => 'success',
    'instance' => $show_instance
));

?>
