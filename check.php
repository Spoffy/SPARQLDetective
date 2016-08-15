<?php
require_once("requireHelper.php");
require_once(__ROOT__ . "/src/database.php");
require_once(__ROOT__ . "/src/state_machine.php");
require_once(__ROOT__ . "/src/link_status_checker.php");

//TODO Add KLogger rather than using print for debugging messages.

$options = getopt("r", array("resume"));
$option_resume = key_exists("r", $options) || key_exists("resume", $options);

$database = Database::createAndConnect();
$stateMachine = new StateMachine($database);

$start_offset = 0;
if($option_resume) {
    if($stateMachine->getCurrentState() == "PROCESSING") {
        $start_offset = $database->getAmountProcessed();
    } else {
        print("Unable to resume, the system wasn't interrupted or has started doing something else.");
        return 1;
    }
} else {
    try {
        $stateMachine->changeStateTo("PROCESSING");
    } catch(Exception $e) {
        print("Unable to start processing links, received Exception: " . $e->getMessage() . "\n");
        return 1;
    }
}


print("Started processing links");


$urlsToCheck = $database->getUrlsWithStartingOffset($start_offset);

//Implement our own checks and filters here? I suspect we'll get a lot of edge cases.

function checkAndOutput($urls) {
    global $database;

    $linkChecker = new LinkCheck($urls);
    foreach($linkChecker->getResults() as $result) {
        print("URL: " . $result["url"] . " Success: " . $result["success"] . " Code: " . $result["statusMessage"] . "\n");
        $database->setUrlStatus($result);
    }
}

$amountProcessed = $start_offset;
$batchAmount = 10;
$batch = [];
foreach($urlsToCheck as $url) {
    $batch[] = $url;
    if(count($batch) >= $batchAmount) {
        checkAndOutput($batch);
        $batch = [];
        $amountProcessed += $batchAmount;
        $database->updateAmountProcessed($amountProcessed);
    }
}


print("\n=========\nProcessing complete\n=========\n");

try {
    $stateMachine->changeStateTo("DONE");
    $database->completeRun($database->getLastRun()["run_id"]);
} catch(Exception $e) {
    print("Unable to complete last run, received exception: " . $e->getMessage() . "\n");
    return 1;
}

return 0;