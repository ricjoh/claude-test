<?php
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
echo "Connected successfully\n\n";

// More DB SQL operations here:
// https://www.php.net/manual/en/mysqli.examples-basic.php

mysqli_close($db);
?>