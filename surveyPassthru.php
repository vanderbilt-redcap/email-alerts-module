<?php
define('NOAUTH',true);

$surveyLink = APP_PATH_SURVEY_FULL."?s=".$_REQUEST['hash'];
$link = ($_REQUEST['returnCode'] == "NULL")? "":"<input type='hidden' value='".$_REQUEST['returnCode']."' name='__code'/>";
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