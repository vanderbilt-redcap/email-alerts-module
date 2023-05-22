<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


$searchTerms = htmlspecialchars($_REQUEST['parameters'],ENT_QUOTES);
$project_id = htmlspecialchars($_REQUEST['project_id'],ENT_QUOTES);

$matchingProjects = '';
if(!empty($_REQUEST['variables'])){
    $variables = explode(',',$_REQUEST['variables']);
    $numItems = count($variables);
    $i = 0;
	$variables = [$project_id];
	$sql = "SELECT DISTINCT(value) from `redcap_data` where project_id = ? AND field_name in (";
    foreach ($variables as $var){
		$sql .= "?".(($i == $numItems - 1) ? "" : ",");
		$variables[] = $var;
        $i++;
    }
	$sql .= ") AND value LIKE '?%'";
	$variables[] = $searchTerms;

    $q = $module->query($sql, $variables);
    while($row = $q->fetch_assoc()) {
        $matchingProjects .= "<option value='".htmlspecialchars($row['value'],ENT_QUOTES)."'>";
    }
}

echo json_encode($matchingProjects);

