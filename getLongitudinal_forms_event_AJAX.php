<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

$form = $_REQUEST['form'];
$project_id = $_REQUEST['project_id'];
$index = $_REQUEST['index'];
$queue = $_REQUEST['queue'];

if(!empty($form) && !empty($project_id)){
    $Project = new \Project($project_id);

    $events_array = array();
    foreach ($Project->eventsForms as $id => $event){
        foreach ($event as $eform){
            if($eform == $form){
                array_push($events_array,$id);
            }
        }
    }

    if(!empty($events_array)){

        if($index != ""){
            $form_name_event = empty($module->getProjectSetting('form-name-event'))?array():$module->getProjectSetting('form-name-event');
            $selected_event = $form_name_event[$index];
        }

        $selector_id = "form_event";
        if($queue != "1"){
            $event_selector = '<td><span class="external-modules-instance-label"> </span><label>REDCap Instrument Event:</label></td><td class="external-modules-input-td">';
        }else{
            $event_selector = '<div>';
            $selector_id = "queue_event_select";
        }

        $event_selector .= '<select class="external-modules-input-element" id="'.$selector_id.'" name="'.$selector_id.'"><option value=""></option>';
        foreach ($events_array as $id){
            $event_unique_name = \REDCap::getEventNames("true","",$id);
            if($selected_event == $id){
                $event_selector .= '<option value="'.$id.'" selected event_name="'.$event_unique_name.'">'.$Project->eventInfo[$id]['name_ext'].'</option>';
            }else{
                $event_selector .= '<option value="'.$id.'" event_name="'.$event_unique_name.'">'.$Project->eventInfo[$id]['name_ext'].'</option>';
            }
        }
        $event_selector .= '</select>';
        if($queue != "1"){
            $event_selector .= '</td>';
        }else{
            $event_selector .= '</div>';
        }
    }
}

echo json_encode(array(
    'status' => 'success',
    'array' => json_encode($events_array),
    'pid' => $project_id,
    'event' => $event_selector
));

?>
