<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once ExternalModules::getProjectHeaderPath();
require_once 'ExampleExternalModule.php';


$exampleModule = new ExampleExternalModule();

?>

<h1>This is the Example Module project homepage!</h1>
<p>Here is some data from the database: <?= $exampleModule->selectData('...') ?><p>

<?php

require_once ExternalModules::getProjectFooterPath();