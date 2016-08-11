<?php

class TransitionException extends Exception {}

class StateMachine {
    public static $transitions = array (
        "NOT_STARTED" => array("PREPARING", "PROCESSING"),
        "PREPARING" => array("PREPARED", "DONE"),
        "PREPARED" => array("PROCESSING", "DONE"),
        "PROCESSING" => array("DONE"),
        //This until we get multiple runs
        "DONE" => array("PROCESSING", "PREPARING")
    );

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getCurrentState() {
        $currentRun = $this->database->getLastRunOrCreate();
        return $currentRun? $currentRun["state"] : null;
    }

    public function changeStateTo($nextState) {
        $currentState = $this->getCurrentState();
        if(!$currentState || !in_array($nextState, StateMachine::$transitions[$currentState])) {
            throw new TransitionException("Invalid transition from $currentState to $nextState");
        }
        if(!$this->database->changeRunStateFromXtoY($currentState, $nextState)) {
            throw new TransitionException("Couldn't change state in database. Was the state changed externally?");
        }
        return true;
    }


}