<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';

$passthruData = $module->resetSurveyAndGetCodes($_REQUEST['pid'], $_REQUEST['record'], $_REQUEST['instrument'], $_REQUEST['event']);

$returnCode = $passthruData['return_code'];
$hash = $passthruData['hash'];
if($returnCode == $_REQUEST['returnCode']){

    $surveyLink = APP_PATH_SURVEY_FULL."?s=".$hash;
    $link = ($_REQUEST['returnCode'] == "NULL")? "":"<input type='hidden' value='".$returnCode."' name='__code'/>";
    ?>


    <html>
    <body>
    <form id='passthruform' name='passthruform' action='<?=$surveyLink?>' method='post' enctype='multipart/form-data'>
             <?=$link?>
            <input type='hidden' value='1' name='__prefill' />
    </form>
        <script type='text/javascript'>
        window.onload = function(){
            document.passthruform.submit();
        }

    </script>
    </body>
    </html>
<?php }
else {
	echo "Error: Incorrect return code specified";
}?>
