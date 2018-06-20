<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';

$module->setProjectSetting('datapipe_label',$_POST['datapipe_label']);
$module->setProjectSetting('datapipe_var',$_POST['datapipe_var']);
$module->setProjectSetting('emailFromForm_var',$_POST['emailFromForm_var']);
$module->setProjectSetting('datapipeEmail_var',$_POST['datapipeEmail_var']);
$module->setProjectSetting('surveyLink_var',$_POST['surveyLink_var']);
$module->setProjectSetting('formLink_var',$_POST['formLink_var']);
$module->setProjectSetting('emailFailed_var',$_POST['emailFailed_var']);
$module->setProjectSetting('emailSender_var',$_POST['emailSender_var']);


echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
