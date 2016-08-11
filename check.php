<?php
require_once("database.php");
require_once("state_machine.php");
require_once("link_status_checker.php");

$database = Database::createAndConnect();
$stateMachine = new StateMachine($database);

try {
    $stateMachine->changeStateTo("PROCESSING");
} catch(Exception $e) {
    print("Unable to start processing links, received Exception: " . $e->getMessage() . "\n");
    return 1;
}

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

try {
    $database->changeRunStateFromXtoY("PROCESSING", "DONE");
    $database->completeRun($database->getLastRun()["run_id"]);
} catch(Exception $e) {
    print("Unable to complete last run, received exception: " . $e->getMessage() . "\n");
    return 1;
}

return 0;