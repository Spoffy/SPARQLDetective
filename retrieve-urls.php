<?php

require_once("requireHelper.php");
require_once(__ROOT__ . "/src/database.php");
require_once(__ROOT__ . "/src/state_machine.php");
require_once(__ROOT__ . "/src/retrieve_urls.php");


$database = Database::createAndConnect();
$stateMachine = new StateMachine($database);

try {
    $stateMachine->changeStateTo("PREPARING");
} catch(Exception $e) {
    print("Unable to start retrieving links, received Exception: " . $e->getMessage() . "\n");
    return 1;
}

print("Started retrieving links");

$predicateFile = file_get_contents(Config::PREDICATE_FILE_PATH);
$predicates = explode("\n", $predicateFile);
//Filter out empty strings (Anything that PHP considers FALSE)
$predicates = array_filter($predicates);

foreach($predicates as $predicate) {
    $urls = sparql_get_urls($predicate);
    //TODO Validate return value of get_urls
    foreach($urls as $urlInfo) {
        print("Adding: " . $urlInfo["url"] . "\n");
        $database->addUrl($urlInfo);
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