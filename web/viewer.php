<?php
    require_once(dirname(__DIR__) . "/src/requireHelper.php");
    require_once(__ROOT__ . "/src/database.php");

    $database = Database::createAndConnect();

    function array2dToTableBody($data) {
        $tableHtml = "";
        foreach($data as $row) {
            $tableHtml .= "<tr>";
            foreach($row as $item) {
                $tableHtml .= "<td>";
                $tableHtml .= $item;
                $tableHtml .= "</td>";
            }
            $tableHtml .= "</tr>";
        }
        return $tableHtml;
    }

    $url_status_rows = $database->getUrlStatusRows();
    $url_status_content = array2dToTableBody($url_status_rows);
?>

<!DOCTYPE html>
<head>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>URL Checker Results View</title>
    <link rel="stylesheet" type="text/css" href="viewer.css" media="all" /
</head>
<body>
<table class="results">
<?php print($url_status_content) ?>
</table>

</body>
