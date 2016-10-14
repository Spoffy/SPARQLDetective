<?php
    define("__ROOT__", dirname(__dir__));
    require_once(__ROOT__ . "/src/requireHelper.php");
    require_once(__ROOT__ . "/src/database.php");

    $INCLUDE_OK = false;
    $HUMAN_READABLE_SPARQL_OPTIONS = '&show_inline=1&output=htmltab';

    $database = Database::createAndConnect();

    $lastRun = $database->getLastRun();
    $lastRun["SPARQL Endpoint"] = Config::SPARQL_ENDPOINT;
    $url_status_rows = $database->getUrlStatusRows();

    $frequency = array();
    foreach($url_status_rows as $row) {
        if( !isset( $frequency[$row['status']] ) ) { 
            $frequency[$row['status']] = 0;
        }
        $frequency[$row['status']]++;
    }

?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>URL Checker Results View</title>
    <script src="tablesorter/jquery-latest.js"></script>
    <script src="tablesorter/jquery.tablesorter.min.js"></script>
    <script>$(document).ready(function() { $(".tablesorter").tablesorter(); } );</script>
    <link rel="stylesheet" type="text/css" media="all" href="tablesorter/themes/blue/style.css" />
    <style>
      .tablesorter td { white-space: nowrap; }
    </style>
  </head>

  <body>
    <h1>SPARQL Detective Report</h1>

    <table class="tablesorter">
      <thead>
        <tr>
          <th>Parameter</th>
          <th>Value</th>
        </tr>
      </thead>
      <tbody>
<?php
        foreach( $lastRun as $k=>$value ) {
            print "<tr><td>".htmlspecialchars($k)."</td><td>".htmlspecialchars($value)."</td></tr>";
        }
?>
      </tbody>
    </table>

    <table class="tablesorter">
      <thead>
        <tr>
          <th>Status</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
<?php
        foreach( $frequency as $status=>$count ) {
            print "<tr><td>".htmlspecialchars($status)."</td><td>".htmlspecialchars($count)."</td></tr>";
        }
?>
      </tbody>
    </table>

    <table class="tablesorter">
      <thead>
        <tr>
          <th>Uses</th>
          <th>URL</th>
          <th>Return</th>
<?php if( $INCLUDE_OK ) { print "          <th>Success</th>"; } ?>
          <th>Label</th>
          <th>Graph</th>
        </tr>
      </thead>
      <tbody>
<?php
        foreach($url_status_rows as $row) {
            if( $row['success'] && !$INCLUDE_OK ) { continue; }
            print "<tr>";
            print "<td>";
            print "<a href='".htmlspecialchars( $sparqlurl )."'>Uses</a>";
            print "</td>";
            print "<td>";
            print "<a href='".htmlspecialchars($row['url'])."'>".htmlspecialchars($row['url'])."</a>";
            $sparql = 'SELECT ?graph ?subject ?predicate WHERE { GRAPH ?graph { ?subject ?predicate <'.$row['url'].'> } }';
            $sparqlurl = Config::SPARQL_ENDPOINT."?query=".urlencode( $sparql ).$HUMAN_READABLE_SPARQL_OPTIONS;
            print "</td>";
            print "<td>";
            if( !preg_match( '/^\d/', $row['status'] ) ) { print "999 "; }
            print htmlspecialchars( $row['status'] );
            print "</td>";
            if( $INCLUDE_OK ) { 
                print "<td>";
                if( $row['success'] ) { 
                    print "OK";
                } else {
                    print "FAIL";
                }
                print "</td>"; 
            }
            print "<td>".htmlspecialchars($row['label'])."</td>";
            print "<td>".htmlspecialchars($row['graph'])."</td>";
            print "</tr>\n"; 
        }
?>
      </tbody>
    </table>

  </body>
</html>
