<?php
define('NOAUTH',true);
namespace ExternalModules;
//require_once dirname(__FILE__) . '/../../external_modules/classes/ExternalModules.php';
require_once 'EmailTriggerExternalModule.php';



$emailTriggerModule = new EmailTriggerExternalModule();

$passthruData = $emailTriggerModule->resetSurveyAndGetCodes($_REQUEST['pid'], $_REQUEST['record'], $_REQUEST['instrument']);

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
<?php } ?>