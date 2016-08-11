<?php
require_once(dirname(__DIR__) . "/requireHelper.php");
require_once(__ROOT__ . "/src/sparqllib.php");

$sparql_prefixes = array(
    "foaf" => "http://xmlns.com/foaf/0.1/",
    "rdfs" => "http://www.w3.org/2000/01/rdf-schema#"
);

$sparql_find_urls_query = <<<SPARQL
SELECT DISTINCT ?subject ?predicate ?url ?graph ?label WHERE {
  GRAPH ?graph {
   ?subject foaf:homepage ?url ;
            ?predicate ?url ;
            rdfs:label ?label ;
  }
}
SPARQL;

function sparql_get_urls() {
    global $sparql_prefixes;
    global $sparql_find_urls_query;

    $db = sparql_connect(Config::SPARQL_ENDPOINT);
    if( !$db ) {
        print($db->error()."\n");
        return;
    }

    foreach($sparql_prefixes as $prefix => $url) {
        $db->ns($prefix, $url);
    }

    $result = $db->query($sparql_find_urls_query);
    if(!$result) {
        print($db->error()."\n");
        return;
    }

    return $result->fetch_all();
}
