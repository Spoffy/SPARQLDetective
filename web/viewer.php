<?php
    $BASE_DIR = dirname(__DIR__);
    require_once("$BASE_DIR/database.php");

    $conn = db_getConn();
    db_insertTestData($conn);

    function array2dToTable($data) {
        $tableHtml = "<table>";
        foreach($data as $row) {
            $tableHtml .= "<tr>";
            foreach($row as $item) {
                $tableHtml .= "<td>";
                $tableHtml .= $item;
                $tableHtml .= "</td>";
            }
            $tableHtml .= "</tr>";
        }
        $tableHtml .= "</table>";
        return $tableHtml;
    }

    $url_status_rows = db_urlStatusRows($conn);
    $url_status_content = array2dToTable($url_status_rows);
?>

<!DOCTYPE html>
<head>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>URL Checker Results View</title>
    <link rel="stylesheet" type="text/css" href="viewer.css" media="all" /
</head>
<body>
<?php print($url_status_content) ?>
</body>