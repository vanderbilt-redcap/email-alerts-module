<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__.'/vendor/autoload.php';

$project_id = (int)$_GET['pid'];
$index =  htmlentities($_REQUEST['index_modal_alert'],ENT_QUOTES);

#get data from the DB
$form_name_event =  empty($module->getProjectSetting('form-name-event'))?array():$module->getProjectSetting('form-name-event')[$index];
if($form_name_event != ""){
    $form_name_event = \REDCap::getEventIdFromUniqueEvent($form_name_event);
}

$sql = "SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id ='".db_escape($project_id)."'";
$q = db_query($sql);
$repeatable = false;
if (db_num_rows($q)) {
    while($row = db_fetch_assoc($q)) {
        $form_name = empty($module->getProjectSetting('form-name'))?array():$module->getProjectSetting('form-name');
        foreach ($form_name as $form){
            $project_name = db_escape(\Project::getValidProjectName($form));
            $event_id = $row['event_id'];
            $sql = "SELECT * FROM redcap_events_repeat WHERE event_id='".db_escape($event_id)."' AND form_name='".db_escape($project_name)."'";
            $q = db_query($sql);
            $row = db_fetch_assoc($q);
            if ($row) {
                $repeatable = true;
                break;
            }
        }


    }
}


$events_array = array();
$event_selector = "";
if(\REDCap::isLongitudinal() || $repeatable){
    $event_selector = "<div style='padding-bottom: 60px;'><input type='text' name='preview_record_id' id='preview_record_id' placeholder='Type a record' style='width: 80%;float: left;'>
                                    <a href='#' class='btn btn-default save' onclick='loadPreviewEmailAlertRecord()' id='preview_record_id_btn' style='float: left;margin-left: 20px;padding-top: 8px;padding-bottom: 7px;'>Preview</a></div>";
}else {
    if($form_name_event != ""){
        $data = \REDCap::getData($project_id, 'array',null,'record_id',$form_name_event);
    }else{
        $data = \REDCap::getData($project_id, 'array',null,'record_id');
    }
    if (count($data) < 500) {
        foreach ($data as $record_id => $event) {
            array_push($events_array, $record_id);
        }

        if (!empty($events_array)) {
            $event_selector = '<div style="padding-bottom:10px">'.
                                '<select class="external-modules-input-element" name="preview_record_id" onchange="loadPreviewEmailAlertRecord()"><option value="">Select a Record</option>';
            foreach ($events_array as $id) {
                $event_selector .= '<option value="' . htmlentities($id,ENT_QUOTES) . '" >' . htmlentities($id,ENT_QUOTES) . '</option>';
            }
            $event_selector .= '</select></div>';
        }
    } else {
        $event_selector = "<div style='margin-bottom: 60px;'><input type='text' name='preview_record_id' id='preview_record_id' placeholder='Type a record' style='width: 80%;float: left;'>
                                    <a href='#' class='btn btn-default save' onclick='loadPreviewEmailAlertRecord()' id='preview_record_id_btn' style='float: left;margin-left: 20px;padding-top: 8px;padding-bottom: 7px;'>Preview</a></div>";
    }
}

echo $event_selector;
?>