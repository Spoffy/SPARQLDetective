<?php
require_once("requireHelper.php");
require_once(__ROOT__ . "/src/database.php");
require_once(__ROOT__ . "/src/state_machine.php");

$database = Database::createAndConnect();
$stateMachine = new StateMachine($database);

$stateMachine->changeStateTo("DONE");