<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$record_id = $_REQUEST['record'];

#Delete email repetitive sent from JSON before deleting all data
$email_repetitive_sent =  empty($module->getProjectSetting('email-repetitive-sent'))?array():$module->getProjectSetting('email-repetitive-sent');
$email_repetitive_sent = json_decode($email_repetitive_sent,true);

if(!empty($email_repetitive_sent)) {
    foreach ($email_repetitive_sent as $form => $form_value) {
        foreach ($form_value as $alert => $alert_value) {
            $one_less = 0;
            foreach ($alert_value as $record => $value) {
                //we don't add the deleted alert and rename the old ones.
                if ($value == $record_id) {
                    $one_less = 1;
                }else if($record >= 0){
                    //if the record is -1 do not add it. When copying a project sometimes it has a weird config.
                    $jsonArray[$form][$alert][$record - $one_less] = $value;
                }
            }
        }
    }
    $module->setProjectSetting('email-repetitive-sent', json_encode($jsonArray));
}


echo json_encode(array(
    'status' => 'success',
    'message' => ''
));
?>