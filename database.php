<?php
require_once("config.php");

//Contains all the query constants.
//This is done so we don't need "global" every time we use one.
//Moving to constants might be the way forward.
class DBQueries
{
    public static $createDatabase = "CREATE DATABASE IF NOT EXISTS open_data";
    public static $createTables = <<< DB
CREATE TABLE IF NOT EXISTS `open_data`.`urls_found` ( 
    `subject` VARCHAR(2083) NOT NULL , 
    `predicate` TEXT NOT NULL , 
    `object` TEXT NOT NULL , 
    `graph` TEXT NOT NULL , 
    `label` TEXT NULL , 
    PRIMARY KEY (`subject`)
) 
ENGINE = InnoDB;

CREATE TABLE `open_data`.`url_statuses` ( 
    `url` VARCHAR(2083) NOT NULL , 
    `status` TEXT NOT NULL , 
    `success` BOOLEAN NOT NULL , 
    PRIMARY KEY (`url`(2083))
) 
ENGINE = InnoDB;

CREATE TABLE `open_data`.`system_status` ( 
`run_id` INT NOT NULL AUTO_INCREMENT , 
`status` ENUM('DONE','PREPARING','PREPARED','PROCESSING','NOT_STARTED') NOT NULL DEFAULT 'NOT_STARTED' , 
`start_time` DATETIME NULL , 
`end_time` DATETIME NULL , 
PRIMARY KEY (`run_id`)
) 
ENGINE = InnoDB;
DB;

    public static $insertTestData = <<< DB
INSERT IGNORE INTO open_data.url_statuses  VALUES
("http://www.google.co.uk", "200", True),
("https://www.yahoo.co.uk", "200", True),
("https://data.soton.ac.uk", "404", False);
DB;

    public static $listCheckedURLs = "SELECT url, status, success FROM open_data.url_statuses";
    public static $lastRun = "SELECT * FROM open_data.system_status ORDER BY run_id DESC LIMIT 1";
    public static $newRun = "INSERT INTO open_data.system_status(start_time) VALUES (NOW())";

    //TODO Add proper support for multiple runs.
    //This might not protect against a new run starting while the old one is running.
    public static $changeCurrentRunStatus = <<<DB
START TRANSACTION;
#Get the latest (current) run.
SELECT @run:=run_id FROM open_data.system_status ORDER BY run_id DESC LIMIT 1 FOR UPDATE;
#Update the status, if it's what we were expecting.
UPDATE open_data.system_status 
	SET status=:newStatus
	WHERE run_id=@run AND status=:oldStatus;
COMMIT;
DB;

}

class Database {
    public $conn;

    public function connect() {
        $this->conn = new PDO("mysql:host=" . CONFIG_MYSQL_HOST . ";dbname=" . CONFIG_MYSQL_DB,
            CONFIG_MYSQL_USERNAME,
            CONFIG_MYSQL_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->conn->exec(DBQueries::$createDatabase);
        $this->conn->exec(DBQueries::$createTables);

        return $this;
    }

    public static function createAndConnect() {
        $database = new Database();
        $database->connect();
        return $database;
    }

    public function populateWithTestData() {
        $this->conn->exec(DBQueries::$insertTestData);
        return $this;
    }

    public function getUrlStatusRows() {
        $statement = $this->conn->query(DBQueries::$listCheckedURLs);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastRun() {
        $statement = $this->conn->query(DBQueries::$lastRun);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function newRun() {
        $this->conn->exec(DBQueries::$newRun);
    }

    //Based on
    public function changeRunStatusFromXtoY($old, $new) {
        $statement = $this->conn->prepare(DBQueries::$changeCurrentRunStatus);
        $statement->execute(array(":oldStatus" => $old, ":newStatus" => $new));
        //Transition successful if we managed to update a row.
        //TODO Make this use SQL errors in the future?
        return $statement->rowCount() > 0;
    }
}

/*
} catch(PDOException $e) {
    error_log($e->getMessage());
    print("MySQL Database error. See PHP error log");
}
*/