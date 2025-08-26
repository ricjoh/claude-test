<?php

function db_insert($filepath) {

	// insert it all into db
	// PRODUCTION
	//$servername = "hades.xymmetrix.net";

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

	$counter = 0;

	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		// Skip rows where "Mat'l" is not numeric
		if (!is_numeric($data[0])) {
			continue;
		}

		$UUID = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

		$description = "$data[1] | $data[5] | $data[0]";

		// Assign Value2 based on "Random Wt" column
		$value2 = ($data[3] == "No") ? $data[4] : '';  // Assuming column index 8 is "Random Wt" and index 7 is "Case Weight"

		// Check if "Mat'l" already exists in the Parameter table
		$check_sql = "SELECT * FROM Parameter WHERE Value3 = '$data[0]'";
		$check_result = mysqli_query($db, $check_sql);

		if (mysqli_num_rows($check_result) > 0) {
			echo "Mat'l $data[0] already exists\n";
			continue;
		}

		$sql = <<<EOF
INSERT INTO Parameter
	(
		ParameterID,
		ParameterGroupID,
		Value1,
		Value2,
		Value3,
		Value4,
		Description,
		DeactivateDate,
		CreateDate,
		CreateID,
		ReadOnly
	)
VALUES
	(
		'$UUID',
		'40E74C81-EF36-4700-A38C-F39B64F7E7D1',
		'$data[1]',
		'$value2',
		'$data[0]',
		'GLC',
		'$description',
		'2050-12-31 00:00:00',
		'2020-06-01 00:00:00',
	 	'00000000-0000-0000-0000-000000000000',
		1
	)
EOF;

		$counter++;

		if (mysqli_query($db, $sql)) {
			echo "$counter New record created successfully\n";
		} else {
			echo "$counter Error: " . mysqli_error($db) . "\n";
		}
	}

	fclose($handle);

	echo "$counter items encountered\n";

	mysqli_close($db);
}

db_insert('/web/html/Trevor/tracker.oshkoshcheese.com/public/edi/item-import-scripts/Alb-Ply-Items.csv');
