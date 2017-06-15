<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once 'EmailTriggerExternalModule.php';

$emailTriggerModule = new EmailTriggerExternalModule();
$emailTriggerModule->setProjectSetting('datapipe_enable',$_POST['datapipe_enable']);
$emailTriggerModule->setProjectSetting('datapipe_label',$_POST['datapipe_label']);
$emailTriggerModule->setProjectSetting('datapipe_var',$_POST['datapipe_var']);
$emailTriggerModule->setProjectSetting('emailFromForm_enable',$_POST['emailFromForm_enable']);
$emailTriggerModule->setProjectSetting('emailFromForm_var',$_POST['emailFromForm_var']);
$emailTriggerModule->setProjectSetting('datapipeEmail_enable',$_POST['datapipeEmail_enable']);
$emailTriggerModule->setProjectSetting('datapipeEmail_var',$_POST['datapipeEmail_var']);
$emailTriggerModule->setProjectSetting('surveyLink_enable',$_POST['surveyLink_enable']);
$emailTriggerModule->setProjectSetting('surveyLink_var',$_POST['surveyLink_var']);
$emailTriggerModule->setProjectSetting('emailFailed_enable',$_POST['emailFailed_enable']);
$emailTriggerModule->setProjectSetting('emailFailed_var',$_POST['emailFailed_var']);

?>