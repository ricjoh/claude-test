<?php

function db_insert($final, $filepath){
    $data = json_decode($final);

    //insert it all into db
    $servername = "pluto.xymmetrix.com";
    $username = "oshcheese";
    $password = "copy2cluj";
    $dbname = "tracker_oshkoshcheese_com";

    // Create connection
    $db = mysqli_connect($servername, $username, $password, $dbname);

    // Check connection
    if (! $db) {
        echo "Error: Unable to connect to MySQL." . PHP_EOL;
        echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }
    // echo "Connected successfully\n\n";

    $sql = "INSERT INTO EDIDocument (EDIKey, Transaction, ControlNumber, Incoming, DocISAID, DocGSID, Status, X12FilePath, JsonObject, CreateDate, UpdateDate)
    VALUES (
        'GLC',
        '" . getValueByElement($data, 'ST', 1) . "',
        '" . getValueByElement($data, 'ST', 2) . "',
        1,
        '" . getValueByElement($data, 'ISA', 6) . "',
        '" . getValueByElement($data, 'GS', 2) . "',
        'New',
        '/web/html/edi-oshkosh/" . $filepath . "',
        '" . $db->real_escape_string(json_encode($data)) . "',
        '" . date('Y-m-d H:i:s') . "',
        '" . date('Y-m-d H:i:s') . "'
    )";

    // print($sql);
    // print("\n\n");

    if (mysqli_query($db, $sql)) {
        echo "New record created successfully\n";
    } else {
        echo "Error: " . mysqli_error($db) . "\n\n";
    }

    mysqli_close($db);
}