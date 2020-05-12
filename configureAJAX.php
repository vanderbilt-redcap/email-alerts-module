<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';

$module->setProjectSetting('datapipe_label',htmlspecialchars($_POST['datapipe_label']));
$module->setProjectSetting('datapipe_var',htmlspecialchars($_POST['datapipe_var']));
$module->setProjectSetting('emailFromForm_var',htmlspecialchars($_POST['emailFromForm_var']));
$module->setProjectSetting('datapipeEmail_var',htmlspecialchars($_POST['datapipeEmail_var']));
$module->setProjectSetting('surveyLink_var',htmlspecialchars($_POST['surveyLink_var']));
$module->setProjectSetting('formLink_var',htmlspecialchars($_POST['formLink_var']));
$module->setProjectSetting('emailFailed_var',htmlspecialchars($_POST['emailFailed_var']));
$module->setProjectSetting('emailSender_var',htmlspecialchars($_POST['emailSender_var']));


echo json_encode(array(
    'status' => 'success',
    'message' => ''
));

?>
