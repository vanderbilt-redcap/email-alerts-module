<?php
namespace ExternalModules;
//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once 'EmailTriggerExternalModule.php';

$emailTriggerModule = new EmailTriggerExternalModule();
$emailTriggerModule->setProjectSetting('datapipe_label',$_POST['datapipe_label']);
$emailTriggerModule->setProjectSetting('datapipe_var',$_POST['datapipe_var']);
$emailTriggerModule->setProjectSetting('emailFromForm_var',$_POST['emailFromForm_var']);
$emailTriggerModule->setProjectSetting('datapipeEmail_var',$_POST['datapipeEmail_var']);
$emailTriggerModule->setProjectSetting('surveyLink_var',$_POST['surveyLink_var']);
$emailTriggerModule->setProjectSetting('emailFailed_var',$_POST['emailFailed_var']);


echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>