<?php

require_once("requireHelper.php");
require_once(__ROOT__ . "/src/database.php");
require_once(__ROOT__ . "/src/state_machine.php");
require_once(__ROOT__ . "/src/find_urls.php");

$database = Database::createAndConnect();
$stateMachine = new StateMachine($database);

try {
    $stateMachine->changeStateTo("PREPARING");
} catch(Exception $e) {
    print("Unable to start retrieving links, received Exception: " . $e->getMessage() . "\n");
    return 1;
}

print("Started retrieving links");


$urls = sparql_get_urls("foaf:homepage");
foreach($urls as $urlInfo) {
    print("Adding: " . $urlInfo["url"] . "\n");
    $database->addUrl($urlInfo);
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