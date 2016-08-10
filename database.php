<?php
require_once("config.php");

//Contains all the query constants.
//This is done so we don't need "global" every time we use one.
//TODO Make all of these constants
class DBQueries
{
    public static $createDatabase = "CREATE DATABASE IF NOT EXISTS open_data";
    //TODO change schema to correctly refer to the "remainder" of the triple, as URL not always object
    public static $createTables = <<< DB
CREATE TABLE IF NOT EXISTS `open_data`.`urls_found` ( 
    `subject` VARCHAR(2083) NOT NULL , 
    `predicate` TEXT NOT NULL , 
    `url` TEXT NOT NULL , 
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
`state` ENUM('DONE','PREPARING','PREPARED','PROCESSING','NOT_STARTED') NOT NULL DEFAULT 'NOT_STARTED' , 
`start_time` DATETIME NULL , 
`end_time` DATETIME NULL , 
PRIMARY KEY (`run_id`)
) 
ENGINE = InnoDB;
DB;

    public static $insertTestData = <<< DB
INSERT IGNORE INTO url_statuses  VALUES
("http://www.google.co.uk", "200", True),
("https://www.yahoo.co.uk", "200", True),
("https://data.soton.ac.uk", "404", False);

INSERT IGNORE INTO urls_found (`subject`, `predicate`, `url`, `graph`, `label`) VALUES 
('http://id.southampton.ac.uk/point-of-service/university-post-office', 'foaf:homepage', 'http://www.soton.ac.uk/bcs/postoffice/index.html', 'http://id.southampton.ac.uk/dataset/amenities/latest', 'University Post Office'),
('http://id.southampton.ac.uk/point-of-service/126-burgess-road', 'foaf:homepage', 'http://www.co-operative.coop', 'http://id.southampton.ac.uk/dataset/amenities/latest', 'Co-op Food'),
('http://id.southampton.ac.uk/point-of-service/108-burgess-road', 'foaf:homepage', 'http://www.santander.co.uk', 'http://id.southampton.ac.uk/dataset/amenities/latest', 'Santander'),
('http://id.southampton.ac.uk/point-of-service/106-burgess-road', 'foaf:homepage', 'http://www.barclays.co.uk', 'http://id.southampton.ac.uk/dataset/amenities/latest', 'Barclays');
DB;

    public static $insertUpdateURLStatus = <<< DB
INSERT INTO url_statuses (url, status, success) VALUES
(:url, :status, :success)
ON DUPLICATE KEY UPDATE url=VALUES(url), status=VALUES(status), success=VALUES(success);
DB;


    public static $listCheckedURLs = "SELECT url, status, success FROM open_data.url_statuses";
    public static $lastRun = "SELECT * FROM open_data.system_status ORDER BY run_id DESC LIMIT 1";
    public static $newRun = "INSERT INTO open_data.system_status(start_time) VALUES (NOW())";

    public static $getUrls = "SELECT DISTINCT url FROM open_data.urls_found";

    //TODO Add proper support for multiple runs.
    //This might not protect against a new run starting while the old one is running.
    public static $lastRunLocking = "SELECT run_id FROM open_data.system_status ORDER BY run_id DESC LIMIT 1 FOR UPDATE;";
    //Only update the old state if it hasn't changed since the program last read it.
    public static $transitionRunState = <<<DB
UPDATE open_data.system_status 
	SET state=:newState
	WHERE run_id=:runId AND state=:oldState;
DB;

}

class Database {
    public $conn;

    public static function toBool($value) {
        return (int) $value;
    }

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

    public function setUrlStatus($checkResult) {
        $statement = $this->conn->prepare(DBQueries::$insertUpdateURLStatus);
        $statement->execute(array(
           ":url" => $checkResult->url,
            ":success" => Database::toBool($checkResult->success),
            ":status" => $checkResult->statusMessage
        ));
    }

    public function getUrlStatusRows() {
        $statement = $this->conn->query(DBQueries::$listCheckedURLs);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUrls() {
        $statement = $this->conn->query(DBQueries::$getUrls);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        //Turns rows into array of URLs.
        return array_map(function($row) {return $row["url"];}, $rows);
    }

    public function getLastRun() {
        $statement = $this->conn->query(DBQueries::$lastRun);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function newRun() {
        $this->conn->exec(DBQueries::$newRun);
    }

    //Based on
    public function changeRunStateFromXtoY($old, $new) {
        $this->conn->beginTransaction();

        $lastRunLockingStatement = $this->conn->prepare(DBQueries::$lastRunLocking);
        $lastRunLockingStatement->execute();
        //TODO Check we actually get a result back.
        $lastRunId = $lastRunLockingStatement->fetch(PDO::FETCH_ASSOC)["run_id"];

        $transitionStatement = $this->conn->prepare(DBQueries::$transitionRunState);
        $transitionStatement->execute(array(":oldState" => $old, ":newState" => $new, ":runId" => $lastRunId));

        $this->conn->commit();

        //Transition successful if we managed to update a row.
        //TODO Make this use SQL errors in the future?
        return $transitionStatement->rowCount() > 0;
    }
}

/*
} catch(PDOException $e) {
    error_log($e->getMessage());
    print("MySQL Database error. See PHP error log");
}
*/