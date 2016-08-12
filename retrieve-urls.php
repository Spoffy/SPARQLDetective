<?php

require_once("requireHelper.php");
require_once(__ROOT__ . "/src/database.php");
require_once(__ROOT__ . "/src/state_machine.php");
require_once(__ROOT__ . "/src/retrieve_urls.php");


$database = Database::createAndConnect();

//Functions as a lock, no other instance of check or retrieve-urls can run on the same database while we're running.
$systemController = new StateMachine($database);
try {
    $systemController->changeStateTo("PREPARING");
} catch(Exception $e) {
    print("Unable to start retrieving links, received Exception: " . $e->getMessage() . "\n");
    return 1;
}

print("Started retrieving links");

//Reads in list of newline-seperated predicates and returns them as an array.
function parsePredicates($predicateFilePath) {
    $fileContents = file_get_contents($predicateFilePath);
    //Split into rows, filter any blank rows.
    return array_filter(explode("\n", $fileContents));
}

$predicates = parsePredicates(Config::PREDICATE_FILE_PATH);

foreach($predicates as $predicate) {
    $results = sparql_get_urls_for_predicate($predicate);
    if(!$results) {
        print("Error: Invalid SPARQL result, aborting.");
        return 1;
    }

    //TODO Clear the existing database before repopulating, or delete old entries.
    foreach($results as $urlInfo) {
        print("Adding: " . $urlInfo["url"] . "\n");
        $database->addUrl($urlInfo);
    }
}

print("\n=========\nProcessing complete\n=========\n");

try {
    $systemController->changeStateTo("DONE");
    $database->completeRun($database->getLastRun()["run_id"]);
} catch(Exception $e) {
    print("Unable to complete last run, received exception: " . $e->getMessage() . "\n");
    return 1;
}

return 0;