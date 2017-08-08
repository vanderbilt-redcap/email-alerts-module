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
            $messsage .= "Button <strong>".$button."</strong> for <strong>".$instrument_form."</strong> survey, is not activated as a <strong>Save And Return</strong> survey. Please activate it to use it as a link.<br/>";
        }
    }


}



echo json_encode(array(
    'status' => 'success',
    'message' => $messsage
));

?>
