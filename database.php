<?php
require_once("config.php");

$createDatabaseQuery = "CREATE DATABASE IF NOT EXISTS open_data";

$createTablesQuery = <<< DB
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

$insertTestDataQuery = <<< DB
INSERT IGNORE INTO open_data.url_statuses  VALUES
("http://www.google.co.uk", "200", True),
("https://www.yahoo.co.uk", "200", True),
("https://data.soton.ac.uk", "404", False);
DB;

$addDataQuery = "INSERT INTO open_data.app_data VALUES (:time, :label, :type, :lat, :long, :accuracy)";
$listCheckedURLsQuery = "SELECT url, status, success FROM open_data.url_statuses";

class Database {
    public $conn;

    public function connect() {
        $this->conn = new PDO("mysql:host=" . CONFIG_MYSQL_HOST . ";dbname=" . CONFIG_MYSQL_DB,
            CONFIG_MYSQL_USERNAME,
            CONFIG_MYSQL_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->conn->exec($GLOBALS["createDatabaseQuery"]);
        $this->conn->exec($GLOBALS["createTablesQuery"]);

        return $this;
    }

    public static function createAndConnect() {
        $database = new Database();
        $database->connect();
        return $database;
    }

    public function populateWithTestData() {
        global $insertTestDataQuery;
        $this->conn->exec($insertTestDataQuery);
        return $this;
    }

    public function getUrlStatusRows() {
        global $listCheckedURLsQuery;
        $statement = $this->conn->query($listCheckedURLsQuery);
        return $statement->fetchAll(PDO::FETCH_NUM);
    }

    public function getLastRun() {

    }
}

/*
} catch(PDOException $e) {
    error_log($e->getMessage());
    print("MySQL Database error. See PHP error log");
}
*/