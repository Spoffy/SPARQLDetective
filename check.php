<?php
require_once("database.php");
require_once("link_status_checker.php");

//Transition to correct state, if not in a correct state.
//Read list of URLs from database
//Batch process them into status codes
//Record status codes into Database
//Transition out

$statesTransitioningToProcessing = array(
    "NOT_STARTED",
    "PREPARED",
    "DONE"
);

$database = Database::createAndConnect();
$currentRun = $database->getLastRun();
if(!$currentRun) {
    $database->newRun();
}
$currentRun = $database->getLastRun();
$runState = $currentRun? $currentRun["state"] : null;

if(!in_array($runState, $statesTransitioningToProcessing) || !$database->changeRunStateFromXtoY($runState, "PROCESSING")) {
    print("Unable to begin processing, system is currently " . $runState . "\n");
    //TODO Make this return
} else {
    print("Successfully started processing links");
}

$urlsToCheck = $database->getUrls();
foreach($urlsToCheck as $url) {
    print($url);
}
//$checkedUrls = new LinkCheck($urlsToCheck);
