<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];
$index =  $_REQUEST['index_modal_delete'];


#get data from the DB
$form_name = empty(ExternalModules::getProjectSetting($prefix, $pid, 'form-name'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'form-name');
$form_name_event = empty(ExternalModules::getProjectSetting($prefix, $pid, 'form-name-event'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'form-name-event');
$email_from = empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-from'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-from');
$email_to = empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-to'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-to');
$email_cc =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-cc'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-cc');
$email_bcc =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-bcc'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-bcc');
$email_subject =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-subject'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-subject');
$email_text =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-text'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-text');
$email_attachment_variable =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment-variable'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment-variable');
$email_attachment1 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment1'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment1');
$email_attachment2 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment2'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment2');
$email_attachment3 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment3'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment3');
$email_attachment4 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment4'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment4');
$email_attachment5 =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment5'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-attachment5');
$email_repetitive =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive');
$email_condition =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-condition'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-condition');
$email_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-sent');
$email_timestamp_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-timestamp-sent');
$email_deactivate =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-deactivate'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-deactivate');

//Add some logs
$action_description = "Deleted Alert #".$index;
$changes_made = "[Subject]: ".$email_subject[$index].", [Message]: ".$email_text[$index];
\REDCap::logEvent($action_description,$changes_made,NULL,NULL,NULL,NULL);

$action_description = "Deleted Alert #".$index." To";
\REDCap::logEvent($action_description,$email_to[$index].$email_cc[$index].$email_bcc[$index],NULL,NULL,NULL,NULL,NULL);


#Delete email repetitive sent from JSON before deleting all data
$email_repetitive_sent =  empty(ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive-sent'))?array():ExternalModules::getProjectSetting($prefix, $pid, 'email-repetitive-sent');
$email_repetitive_sent = json_decode($email_repetitive_sent);

//if(!empty($email_repetitive_sent)){
//    if(array_key_exists($form_name[$index],$email_repetitive_sent)){
//        foreach ($email_repetitive_sent->$form_name[$index] as $alert =>$value){
//            if($alert == $index){
//                unset($email_repetitive_sent->$form_name[$index]->$alert);
//                ExternalModules::setProjectSetting($prefix,$pid, 'email-repetitive-sent', json_encode($email_repetitive_sent));
//            }
//        }
//    }
//}
if(!empty($email_repetitive_sent)) {
    $one_less = 0;
    foreach ($email_repetitive_sent as $form => $form_value) {
        $number_of_children = count((array)$form_value);
        foreach ($email_repetitive_sent->$form as $alert => $value) {
            $found = false;

            if ($alert == $index) {
                $one_less = 1;
                $found = true;
            }

            if ($number_of_children == 1 && $one_less == 1) {
                //we simply don't add it
            } else if (!$found) {
                $jsonArray[$form][$alert - $one_less] = $value;
            }

        }
    }
    ExternalModules::setProjectSetting($prefix, $pid, 'email-repetitive-sent', json_encode($jsonArray));
}

#Delete one element in array
unset($form_name[$index]);
unset($form_name_event[$index]);
unset($email_from[$index]);
unset($email_to[$index]);
unset($email_cc[$index]);
unset($email_bcc[$index]);
unset($email_subject[$index]);
unset($email_text[$index]);
unset($email_attachment_variable[$index]);
unset($email_attachment1[$index]);
unset($email_attachment2[$index]);
unset($email_attachment3[$index]);
unset($email_attachment4[$index]);
unset($email_attachment5[$index]);
unset($email_repetitive[$index]);
unset($email_condition[$index]);
unset($email_sent[$index]);
unset($email_timestamp_sent[$index]);
unset($email_deactivate[$index]);

#Rearrange the indexes
$form_name = array_values($form_name);
$form_name_event = array_values($form_name_event);
$email_from = array_values($email_from);
$email_to = array_values($email_to);
$email_cc = array_values($email_cc);
$email_bcc = array_values($email_bcc);
$email_subject = array_values($email_subject);
$email_text = array_values($email_text);
$email_attachment_variable = array_values($email_attachment_variable);
$email_attachment1 = array_values($email_attachment1);
$email_attachment2 = array_values($email_attachment2);
$email_attachment3 = array_values($email_attachment3);
$email_attachment4 = array_values($email_attachment4);
$email_attachment5 = array_values($email_attachment5);
$email_repetitive = array_values($email_repetitive);
$email_condition = array_values($email_condition);
$email_sent = array_values($email_sent);
$email_timestamp_sent = array_values($email_timestamp_sent);
$email_deactivate = array_values($email_deactivate);

#Save data
ExternalModules::setProjectSetting($prefix,$pid, 'form-name', $form_name);
ExternalModules::setProjectSetting($prefix,$pid, 'form-name-event', $form_name_event);
ExternalModules::setProjectSetting($prefix,$pid, 'email-from', $email_from);
ExternalModules::setProjectSetting($prefix,$pid, 'email-to', $email_to);
ExternalModules::setProjectSetting($prefix,$pid, 'email-cc', $email_cc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-bcc', $email_bcc);
ExternalModules::setProjectSetting($prefix,$pid, 'email-subject', $email_subject);
ExternalModules::setProjectSetting($prefix,$pid, 'email-text', $email_text);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment-variable', $email_attachment_variable);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment1', $email_attachment1);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment2', $email_attachment2);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment3', $email_attachment3);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment4', $email_attachment4);
ExternalModules::setProjectSetting($prefix,$pid, 'email-attachment5', $email_attachment5);
ExternalModules::setProjectSetting($prefix,$pid, 'email-repetitive', $email_repetitive);
ExternalModules::setProjectSetting($prefix,$pid, 'email-condition', $email_condition);
ExternalModules::setProjectSetting($prefix,$pid, 'email-sent', $email_sent);
ExternalModules::setProjectSetting($prefix,$pid, 'email-timestamp-sent', $email_timestamp_sent);
ExternalModules::setProjectSetting($prefix,$pid, 'email-deactivate', $email_deactivate);

echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
