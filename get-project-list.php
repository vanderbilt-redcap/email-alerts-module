<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$searchTerms = htmlspecialchars($_REQUEST['parameters'],ENT_QUOTES);
$project_id = htmlspecialchars($_REQUEST['project_id'],ENT_QUOTES);

$matchingProjects = '';
if(!empty($_REQUEST['variables'])){
    $variables = explode(',',$_REQUEST['variables']);
    $sqlvariables = "";
    $numItems = count($variables);
    $i = 0;
    foreach ($variables as $var){
        if ($i == $numItems - 1) {
            $sqlvariables .= "'".substr($var, 1, strlen($var)-2)."'";
        }else{
            $sqlvariables .= "'".substr($var, 1, strlen($var)-2)."',";
        }
        $i++;
    }

    $q = $module->query("SELECT DISTINCT(value) from `redcap_data` where project_id = ? AND field_name in (?) AND value LIKE '?%' ", [$project_id,$sqlvariables,$searchTerms]);
    while($row = $q->fetch_assoc()) {
        $matchingProjects .= "<option value='".htmlspecialchars($row['value'],ENT_QUOTES)."'>";
    }
}

echo json_encode($matchingProjects);

