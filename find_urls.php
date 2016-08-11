<?php
require_once("config.php");
require_once("sparqllib.php");

$sparql_prefixes = array(
    "foaf" => "http://xmlns.com/foaf/0.1/",
    "rdfs" => "http://www.w3.org/2000/01/rdf-schema#"
);

$sparql_find_urls_query = <<<SPARQL
SELECT DISTINCT ?subject ?graph ?url ?label WHERE {
  GRAPH ?g {
   ?subject foaf:homepage ?url ;
      rdfs:label ?label ;
  }
}
SPARQL;

function find_urls() {
    global $sparql_prefixes;
    global $sparql_find_urls_query;

    $db = sparql_connect(CONFIG_SPARQL_ENDPOINT);
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
