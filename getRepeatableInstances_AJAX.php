<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

$event_id = (int)$_REQUEST['event'];
$project_id = (int)$_REQUEST['project_id'];
$alert_id = htmlentities($_REQUEST['index_modal_queue']);
$show_instance = "";
$form_name = empty($module->getProjectSetting('form-name'))?array():$module->getProjectSetting('form-name')[$alert_id];

$q = $this->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id =?", [$project_id]);

while($row = $q->fetch_assoc()) {
    if ($row['event_id'] == $event_id) {
        $project_name = db_escape(\Project::getValidProjectName($form_name));
        $event_id = $row['event_id'];
        $q2 = $this->query("SELECT * FROM redcap_events_repeat WHERE event_id='$event_id' AND form_name=?", [$project_name]);
        $row2 = $q2->fetch_assoc();
        if ($row2) {
            $show_instance = '<div style="float:left;width: 280px;"><label style="font-weight: normal;padding-left: 15px;padding-right: 15px">Add Instances<br><span style="color:red">*If none is typed and if smart variables are not used, only the first instance added</span></label></div><div style="float:left;"><textarea class="form-control" id="queue_instances" rows="6"></textarea></div>';
        }
    }
}

echo json_encode(array(
    'status' => 'success',
    'instance' => $show_instance
));

?>
