<?php
$logic = $_REQUEST['logic'];
$logicQueue = $_REQUEST['logicQueueField'];
$logicExpQueue = $_REQUEST['logicExpQueueField'];

$newBranchingIsValid = true;
if($logic != ""){
    $newBranchingIsValid = \LogicTester::isValid($logic);
}

$newBranchingIsValidQueue = true;
if($_REQUEST['logicQueueCond'] == 'calc' && $logicQueue != ""){
    $newBranchingIsValidQueue = \LogicTester::isValid($logicQueue);
}

$newBranchingIsValidExpQueue = true;
if($_REQUEST['logicExpQueueCond'] == 'calc' && $logicExpQueue != ""){
    $newBranchingIsValidExpQueue = \LogicTester::isValid($logicExpQueue);
}

echo json_encode(array(
    'status' => 'success',
    'isBranchingValid' => $newBranchingIsValid,
    'isBranchingValidQueue' => $newBranchingIsValidQueue,
    'isBranchingValidExpQueue' => $newBranchingIsValidExpQueue,
));
?>