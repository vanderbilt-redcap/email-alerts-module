<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

$form = $_REQUEST['form'];
$project_id = $_REQUEST['project_id'];
$index = $_REQUEST['index'];
$prefix = ExternalModules::getPrefixForID($project_id);
$message ='';
if(!empty($form) && !empty($project_id)){
    $Project = new \Project($project_id);
//    print_array($Project->eventsForms);
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
            $message = 'entro';
            $form_name_event = empty(ExternalModules::getProjectSetting($prefix, $project_id, 'form-name-event'))?array():ExternalModules::getProjectSetting($prefix, $project_id, 'form-name-event');
            $selected_event = $form_name_event[$index];
        }

        $event_selector = '<td><span class="external-modules-instance-label"> </span><label>REDCap Instrument Event:</label></td>';
        $event_selector .= '<td class="external-modules-input-td"><select class="external-modules-input-element" name="form-name-event"><option value=""></option>';
        foreach ($events_array as $id){
            if($selected_event == $id){
                $event_selector .= '<option value="'.$id.'" selected>'.$Project->eventInfo[$id]['name_ext'].'</option>';
            }else{
                $event_selector .= '<option value="'.$id.'">'.$Project->eventInfo[$id]['name_ext'].'</option>';
            }
        }
        $event_selector .= '</select></td>';
    }

//    $event_id = 1216;
//    $form = 'labs_basic_metabolic_panel';
//    print_array(REDCap::getData($project_id, 'array', '1', array($form.'_complete'),array($event_id)));
}

echo json_encode(array(
    'status' => 'success',
    'event' => $event_selector
));

?>