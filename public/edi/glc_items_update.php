<?php

function db_update($filepath) {

    // mysql -h hermes -u oshcheese -p tracker_oshkoshcheese_com

    //insert it all into db
    // PRODUCTION
    // $servername = "hermes.xymmetrix.net";

    // DEVELOPMENT
    $servername = "arrokoth.xymmetrix.com";

    $username = "oshcheese";
    $password = "copy2cluj";
    $dbname = "tracker_oshkoshcheese_com_trevor";

    // Create connection
    $db = mysqli_connect($servername, $username, $password, $dbname);

    // Check connection
    if (!$db) {
        echo "Error: Unable to connect to MySQL." . PHP_EOL;
        echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }
    // echo "Connected successfully\n\n";

    // Import file to Database
    $handle = fopen($filepath, "r");
    if (!$handle) {
        echo "Error: Unable to open file: $filepath" . PHP_EOL;
        exit;
    }

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

        // TODO: Hardcoded GLC
        $sql = <<<EOF
UPDATE Parameter SET Value2 = '$data[1]' WHERE Value4 = 'GLC' AND Value3 = '$data[0]';
EOF;

        echo $sql . "\n";

        if (mysqli_query($db, $sql)) {
            echo "Record updated successfully\n";
        } else {
            echo "Error: " . mysqli_error($db) . "\n\n";
        }
    }

    fclose($handle);

    mysqli_close($db);
}

db_update('/web/html/Trevor/tracker.oshkoshcheese.com/public/edi/item-import-scripts/Alb-Ply-Items.csv');
