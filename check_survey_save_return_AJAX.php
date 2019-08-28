<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';


$surveyLink_var = $_REQUEST['surveyLink_var'];
$project_id = $_REQUEST['project_id'];
$message = '';
if(!empty($surveyLink_var)){

    $datasurvey = explode("\n", $surveyLink_var);
    foreach ($datasurvey as $surveylink) {
        $var = preg_split("/[;,]+/", $surveylink)[0];
        $button = preg_split("/[;,]+/", $surveylink)[1];

        preg_match_all("/\[[^\]]*\]/", $var, $matches);

        if(sizeof($matches[0]) > 1){
            $var = $matches[0][1];
        }
        $instrument_form = str_replace('[__SURVEYLINK_', '', $var);
        $instrument_form = str_replace(']', '', $instrument_form);


        $sql = "SELECT save_and_return from `redcap_surveys` where project_id = ".$project_id." AND form_name ='".db_escape($instrument_form)."'";
        $result = $module->query($sql);

        if(APP_PATH_WEBROOT[0] == '/'){
            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
        }

        if(!empty($row = db_fetch_assoc($result))) {
            if($row['save_and_return'] == 0){
                $link = '<a href="' .APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL. 'Surveys/edit_info.php?pid=' . $project_id . '&view=showform&page='.$instrument_form.'&redirectDesigner=1" target="_blank"><u>enable "<strong>Save and Return</strong>" in Survey Settings</u></a>';
                $message .= 'The survey titled "<strong>'.$instrument_form.'</strong>" is not activated as a Save and Return survey. Please ' . $link . ' to use the link. If you want the survey to be editable, also select "Allow respondents to modify completed responses."<br/>';
            }
        }else{
            $link = '<a href="' .APP_PATH_WEBROOT_FULL. $APP_PATH_WEBROOT_ALL. 'Surveys/edit_info.php?pid=' . $project_id . '&view=showform&page='.$instrument_form.'&redirectDesigner=1" target="_blank"><u>enable "<strong>Save and Return</strong>" in Survey Settings</u></a>';
            $message .= 'The survey titled "<strong>'.$instrument_form.'</strong>" is not activated as a Save and Return survey. Please ' . $link . ' to use the link. If you want the survey to be editable, also select "Allow respondents to modify completed responses."<br/>';
        }
    }


}



echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
