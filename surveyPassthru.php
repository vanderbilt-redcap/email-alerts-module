<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';

/* version 10.6 introduces new params for REDCap::getSurveyLink */
$funcArray = [$_REQUEST['record'], $_REQUEST['instrument'], $_REQUEST['event']];

if(\REDCap::versionCompare(REDCAP_VERSION, '10.6.1') >= 0) {
        $funcArray = [$_REQUEST['record'], $_REQUEST['instrument'], $_REQUEST['event'], 1, '', false];
} else {
        $funcArray = [$_REQUEST['record'], $_REQUEST['instrument'], $_REQUEST['event']];
}
$returnCode = \REDCap::getSurveyReturnCode($_REQUEST['record'], $_REQUEST['instrument'], $_REQUEST['event']);
$surveyLink = call_user_func_array(['REDCap', 'getSurveyLink'], $funcArray);

if(strcasecmp($returnCode, $_REQUEST['returnCode']) == 0) {

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
	echo "Error: Incorrect return code specified.<br /><br />This error can also be caused by using an outdated version of the External Modules framework with a longitudinal study project. You may be able to correct this error by updating to a version of REDCap above 8.7.0";
}?>
