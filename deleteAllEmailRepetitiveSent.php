<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$module->setProjectSetting('email-repetitive-sent', '');

echo json_encode(array(
    'status' => 'success',
    'message' => ''
));
?>