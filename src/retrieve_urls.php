<?php
require_once(dirname(__DIR__) . "/requireHelper.php");
require_once(__ROOT__ . "/src/sparqllib.php");

$sparql_prefixes = array(
    "foaf" => "http://xmlns.com/foaf/0.1/",
    "rdfs" => "http://www.w3.org/2000/01/rdf-schema#"
);



function sparql_run_query_fetch_all($query) {
    global $sparql_prefixes;

    $db = sparql_connect(Config::SPARQL_ENDPOINT);
    if( !$db ) {
        print($db->error()."\n");
        return;
    }

    foreach($sparql_prefixes as $prefix => $url) {
        $db->ns($prefix, $url);
    }

    $result = $db->query($query);
    if(!$result) {
        print($db->error()."\n");
        return;
    }

    return $result->fetch_all();

}

function sparql_get_urls($predicate) {
    $find_urls_query = <<<SPARQL
SELECT DISTINCT ?subject ?predicate ?url ?graph ?label WHERE {
   GRAPH ?graph {
   ?subject $predicate ?url ;
            ?predicate ?url ;
            rdfs:label ?label ;
  }
}
SPARQL;

    return sparql_run_query_fetch_all($find_urls_query);
}
