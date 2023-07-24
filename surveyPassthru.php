<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'EmailTriggerExternalModule.php';

$project_id = (int)$_REQUEST['pid'];
$instance = (int)$_REQUEST['instance'] ?: 1;

$instrument = strtolower($_REQUEST['instrument']);
$returnCode = \REDCap::getSurveyReturnCode($_REQUEST['record'], $instrument, $_REQUEST['event'], $instance);
$surveyLink = \REDCap::getSurveyLink($_REQUEST['record'], $instrument, $_REQUEST['event'], $instance);

if(
        (!$returnCode && $surveyLink)
        || (strcasecmp($returnCode, $_REQUEST['returnCode']) == 0)
) {

    $link = (!$returnCode || ($_REQUEST['returnCode'] == "NULL")) ? "":"<input type='hidden' value='$returnCode' name='__code'/>";
    ?>

    <html>
    <body>
    <form id='passthruform' name='passthruform' action='<?=$surveyLink?>' method='post' enctype='multipart/form-data'>
        <?= $link ?>
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
