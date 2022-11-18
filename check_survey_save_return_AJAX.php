<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';


$surveyLink_var = $_REQUEST['surveyLink_var'];
$project_id = (int)$_REQUEST['project_id'];
$message = '';
$prefixes = [
    "__SURVEYLINK_",
    "survey-link:",
    "survey-url:",
    "survey-return-code:",
];
$skipPrefixes = [
    "survey-queue-link:",
    "survey-queue-url",
];
if(!empty($surveyLink_var)){

    $datasurvey = explode("\n", $surveyLink_var);
    foreach ($datasurvey as $surveylink) {
        $var = preg_split("/[;,]+/", $surveylink)[0];
        $button = preg_split("/[;,]+/", $surveylink)[1];

        preg_match_all("/\[[^\]]*\]/", $var, $matches);

        $instrument_form = "";
        $skipFound = FALSE;
        if(sizeof($matches[0]) >= 1) {
            foreach ($matches[0] as $term) {
                foreach ($prefixes as $prefix) {
                    if (preg_match("/^\[$prefix/", $term)) {
                        $var = $term;
                        $instrument_form = str_replace('['.$prefix, '', $var);
                        $instrument_form = preg_replace("/:[^:]+\]$/", "", $instrument_form);
                        $instrument_form = filter_var(str_replace(']', '', $instrument_form), FILTER_SANITIZE_STRING);
                        break;
                    }
                }
                foreach ($skipPrefixes as $prefix) {
                    if (preg_match("/^\[$prefix/", $term)) {
                        $skipFound = TRUE;
                        break;
                    }
                }
            }
        }
        if (!$skipFound) {
            $sql = "SELECT save_and_return from `redcap_surveys` where project_id = ? AND form_name = ?";
            $result = $module->query($sql, [$project_id, $instrument_form]);

            if(APP_PATH_WEBROOT[0] == '/'){
                $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
            }
            $server = (isset($_SERVER['HTTPS']) ? "https://" : "http://").SERVER_NAME."/";

            $row = db_fetch_assoc($result);
            if(
                (
                    !empty($row)
                    && ($row['save_and_return'] == 0)
                )
                || (empty($row))
            ) {
                $link = '<a href="' .$server.$APP_PATH_WEBROOT_ALL. 'Surveys/edit_info.php?pid=' . $project_id . '&view=showform&page='.$instrument_form.'&redirectDesigner=1" target="_blank"><u>enable "<strong>Save and Return</strong>" in Survey Settings</u></a>';
                $message .= 'The survey titled "<strong>'.$instrument_form.'</strong>" is not activated as a Save and Return survey. Please ' . $link . ' to use the link. If you want the survey to be editable, also select "Allow respondents to modify completed responses."<br/>';
            }
        }
    }


}



echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>
