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

?>