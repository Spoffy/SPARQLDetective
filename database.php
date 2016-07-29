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
`status` ENUM('DONE','PREPARING','PROCESSING','NOT_STARTED') NOT NULL DEFAULT 'NOT_STARTED' , 
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

function db_getConn() {
    $conn = new PDO("mysql:host=" . CONFIG_MYSQL_HOST . ";dbname=" . CONFIG_MYSQL_DB,
        CONFIG_MYSQL_USERNAME,
        CONFIG_MYSQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec($GLOBALS["createDatabaseQuery"]);
    $conn->exec($GLOBALS["createTablesQuery"]);

    return $conn;
}

function db_insertTestData(PDO $conn) {
    global $insertTestDataQuery;
    $conn->exec($insertTestDataQuery);
}

function db_urlStatusRows(PDO $conn) {
    global $listCheckedURLsQuery;
    $statement = $conn->query($listCheckedURLsQuery);
    return $statement->fetchAll(PDO::FETCH_NUM);
}

/*
} catch(PDOException $e) {
    error_log($e->getMessage());
    print("MySQL Database error. See PHP error log");
}
*/