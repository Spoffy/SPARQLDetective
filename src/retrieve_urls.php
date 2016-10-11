<?php
require_once(dirname(__DIR__) . "/requireHelper.php");
require_once(__ROOT__ . "/src/sparqllib.php");

function sparql_run_query_fetch_all($query, $namespaces=array()) {
    $sparql_connection = sparql_connect(Config::SPARQL_ENDPOINT);
    if( !$sparql_connection ) {
        print($sparql_connection->error()."\n");
        return;
    }

    foreach($namespaces as $prefix => $url) {
        $sparql_connection->ns($prefix, $url);
    }

    $result = $sparql_connection->query($query, Config::SPARQL_TIMEOUT);
    if(!$result) {
        print($sparql_connection->error()."\n");
        return;
    }

    return $result->fetch_all();

}

function sparql_get_urls_for_predicate($predicate, $extra_prefixes=array()) {
    $find_urls_query = <<<SPARQL
SELECT DISTINCT ?subject ?predicate ?url ?graph ?label WHERE {
   GRAPH ?graph {
   ?subject $predicate ?url ;
            ?predicate ?url ;
            rdfs:label ?label ;
  }
}
SPARQL;

    return sparql_run_query_fetch_all($find_urls_query, $extra_prefixes);
}
