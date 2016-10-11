SPARQL Detective
================

A command line script that analyses a SPARQL endpoint to detect erroneous links. Populates a MySQL databases with a full list of links found, as well as the status of each link. Capable of generating a simple webpage displaying the status of each link.

## Dependencies

```
PHP 5.3+ (Untested on lower)
php_curl (Unfortunately)
php_pdo_mysql
```

Requires an active MySQL deployment.

## Installation

Download/checkout the repository. That's it! Scripts are runnable from the top level directory.

## Configuration

Config takes places in the files in /etc/ called "config.php", "namespaces.txt" and "predicates.txt". Example files are included. Start by copying the example configuration and setting the values you need.

```
cp etc/config.php.example etc/config.php
cp etc/predicates.txt.example etc/predicates.php
cp etc/predicates.txt.example etc/predicates.php
```

### etc/config.php
```
class Config {
    //The address of the MySQL host, excluding port. There's currently no configuration option for port.
    const MYSQL_HOST = "localhost";
    //The database in the MySQL server to be used. This must already exist.
    const MYSQL_DB = "open_data";
    const MYSQL_USERNAME = "root";
    const MYSQL_PASSWORD = "";

    //The address of the SPARQL endpoint for the Open Data provider of choice
    const SPARQL_ENDPOINT = "http://sparql.data.southampton.ac.uk/";
    //How long to wait for the SPARQL query to complete before aborting.
    const SPARQL_TIMEOUT = 20;

    //Path to the list of predicates and namespaces relative to the root of the software
    const NAMESPACE_FILE_PATH = "etc/namespaces.txt";
    const PREDICATE_FILE_PATH = "etc/predicates.txt";
}
```

### predicates.txt

A newline seperated list of predicates, using whatever line endings are suitable for the platform.

Example:
```
foaf:homepage
foaf:page
soton:disabledGoPage
```




