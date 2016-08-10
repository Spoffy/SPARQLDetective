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
//TODO tidy this up,
$currentRun = $database->getLastRun();
if(!$currentRun) {
    $database->newRun();
}
$currentRun = $database->getLastRun();
$runState = $currentRun? $currentRun["state"] : null;

if(!in_array($runState, $statesTransitioningToProcessing)) {
    print("Unable to begin processing, system is currently " . $runState . "\n");
    return 1;
}
if(!$database->changeRunStateFromXtoY($runState, "PROCESSING")) {
    print("Unable to begin processing, couldn't transition to state PROCESSING from $runState. Maybe another process changed the state?\n");
    return 1;
};

print("Started processing links");


$urlsToCheck = $database->getUrls();

//Implement our own checks and filters here? I suspect we'll get a lot of edge cases.
//TODO Remove this or hide behind debugging log level
foreach($urlsToCheck as $url) {
    print($url . "\n");
}

//TODO Add some kind of batch handling to this;

$linkChecker = new LinkCheck($urlsToCheck);
foreach($linkChecker->getResults() as $result) {
    print("URL: " . $result->url . " Success: " . $result->success . " Code: " . $result->statusMessage . "\n");
    $database->setUrlStatus($result);
}

print("\n=========\nProcessing complete\n=========\n");
$database->changeRunStateFromXtoY("PROCESSING", "DONE");
$database->completeRun($currentRun["run_id"]);

return 0;