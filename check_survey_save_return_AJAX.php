<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';


$surveyLink_var = $_REQUEST['surveyLink_var'];
$project_id = $_REQUEST['project_id'];
$messsage = '';
if(!empty($surveyLink_var)){

    $datasurvey = explode("\n", $surveyLink_var);
    foreach ($datasurvey as $surveylink) {
        $var = preg_split("/[;,]+/", $surveylink)[0];
        $button = preg_split("/[;,]+/", $surveylink)[1];

        $instrument_form = str_replace('[', '', $var);
        $instrument_form = str_replace(']', '', $instrument_form);

        $sql = "SELECT survey_id from `redcap_surveys` where project_id = ".$project_id." AND form_name ='".$instrument_form."' AND save_and_return=0";
        $result = db_query($sql);

        while($row = db_fetch_assoc($result)) {
            $link = '<a href="'.APP_PATH_WEBROOT_FULL.APP_PATH_WEBROOT.'Surveys/edit_info.php?pid='.$project_id.'&view=showform&page=request&redirectDesigner=1" target="_blank"><u>enable "<strong>Save and Return</strong>" in Survey Settings</u></a>';
            $messsage .= 'The survey titled "<strong>prescreening_survey</strong>" is not activated as a Save and Return survey. Please '.$link.' to use the link. If you want the survey to be editable, also select "Allow respondents to modify completed responses."<br/>';
        }
    }


}



echo json_encode(array(
    'status' => 'success',
    'message' => $messsage
));

?>
