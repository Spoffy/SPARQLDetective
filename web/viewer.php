<?php
    define("__ROOT__", dirname(__dir__));
    require_once(__ROOT__ . "/src/requireHelper.php");
    require_once(__ROOT__ . "/src/database.php");

    $database = Database::createAndConnect();

    $lastRun = $database->getLastRun();
    $lastRun["SPARQL Endpoint"] = Config::SPARQL_ENDPOINT;
    $url_status_rows = $database->getUrlStatusRows();
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

  </head>

  <body>
    <table class="tablesorter">
      <thead>
        <tr><th>Parameter</th><th>Value</th></tr>
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
        <tr><th>URL</th><th>Return</th><th>Success</th></tr>
      </thead>
      <tbody>
<?php
        foreach($url_status_rows as $row) {
            print "<tr>";
            print "<td><a href='".htmlspecialchars($row['url'])."'>".htmlspecialchars($row['url'])."</a></td>";
            print "<td>";
            if( !preg_match( '/^\d/', $row['status'] ) ) { print "999 "; }
            print htmlspecialchars( $row['status'] );
            print "</td>";
            print "<td>";
            if( $row['success'] ) { 
                print "OK";
            } else {
                print "FAIL";
            }
            print "</td>"; 
            print "<td>".htmlspecialchars($row['label'])."</td>";
            print "<td>".htmlspecialchars($row['graph'])."</td>";
            print "<td>".htmlspecialchars($row['subject'])."</td>";
            print "<td>".htmlspecialchars($row['predicate'])."</td>";
            print "</tr>\n"; 
        }
?>
      </tbody>
    </table>

  </body>
</html>
