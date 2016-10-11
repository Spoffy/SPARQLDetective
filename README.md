SPARQL Detective
================

A command line script that analyses a SPARQL endpoint to detect erroneous links. Populates a MySQL databases with a full list of links found, as well as the status of each link. Capable of generating a simple webpage displaying the status of each link.

## Dependencies

```
PHP 5.3+ (Untested on lower)
php_curl (Unfortunately)
php_pdo_mysql
jquery.tablesorter (for web interface)
```

Requires an active MySQL deployment.

## Installation

Download/checkout the repository. That's it! Scripts are in the bin directory.

If checking out from git, use the following to get tablesorter:

```
git submodules init
git submodules update
```

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

A newline seperated list of predicates, using whatever line endings are suitable for the platform. Raw predicates without a namespace listed, should be wrapped in angle brackets.

Example:
```
foaf:homepage
foaf:page
soton:disabledGoPage
<http://my.namespace.org/page>
```

### namesapces.txt

Example:
```
foaf http://xmlns.com/foaf/0.1/
rdfs http://www.w3.org/2000/01/rdf-schema#
soton http://id.southampton.ac.uk/ns/
```

## Running SPARQL Detetive

The system works in two passes. The first is ``bin/retrieve-urls`` which builds up a todo list of URLs to check from the SPARQL endpoint. The second phase is ``bin/check`` which does the actual checks. If check is stopped, it can be restarted from where it was. If you want to reset when check has not completed, use ``bin/transition-to-done``.

We could probably improve this by making it into a single command with command line arguments. We will do that if people show interest in using this tool.

For now to run a check do:

```
bin/retrieve-urls
bin/check
```

If check is interrupted, it can restarted using ``bin/check -r`` or reset using ``bin/transition-to-done``

The results are then stored in the url_statuses table which you can 
* access directly, 
* or use web/viewer.php to view it on the web
* or run web/viewer.php on the command line to generate an HTML report. 

The HTML report assumes that it can find the handy javascript tool tableviewer in a directory of that name relative to the HTML file.

## Internals

This uses 3 database tables which you can interact with directly if you want.

* urls_found - contains URLs found in the endpoint by ``retrieve-urls``
* url_statuses - contains the results of testing all the urls with ``check``
* system_status - the current check run, and if it's complete or in progress
