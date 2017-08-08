<?php
namespace ExternalModules;
require_once 'EmailTriggerExternalModule.php';


$surveyLink_var = $_REQUEST['surveyLink_var'];
$project_id = $_REQUEST['project_id'];
$messsage = '';
if(!empty($surveyLink_var)){
    $emailTriggerModule = new EmailTriggerExternalModule();

    $datasurvey = explode("\n", $surveyLink_var);
    foreach ($datasurvey as $surveylink) {
        $var = preg_split("/[;,]+/", $surveylink)[0];

        $instrument_form = str_replace('[', '', $var);
        $instrument_form = str_replace(']', '', $instrument_form);

        $sql = "SELECT survey_id from `redcap_surveys` where project_id = ".$project_id." AND form_name =".$instrument_form. "AND save_and_return=1";
        $result = db_query($sql);

        $matchingProjects = '';
        while($row = db_fetch_assoc($result)) {
            $messsage .= "<br/><strong>".$instrument_form."</strong>, is not activated as a save and return survey. Please activate it to use it as a link.";
        }
    }


}



echo json_encode(array(
    'status' => 'success',
    'message' => $messsage
));

?>