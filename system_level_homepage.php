<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once 'EmailTriggerExternalModule.php';


$exampleModule = new EmailTriggerExternalModule();

$HtmlPage = new \HtmlPage();
$HtmlPage->PrintHeaderExt();
?>

<h1>This is the Example Module system level homepage!</h1>
<p>Here is some data from the database: <?= $exampleModule->selectData('...') ?><p>

<?php
$HtmlPage->PrintFooterExt();