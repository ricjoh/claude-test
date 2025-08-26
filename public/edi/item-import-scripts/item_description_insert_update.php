<?php
// Deletes sku if it already exists and reinserts it with new data
include_once( 'logdump.php' );

function db_insert( $filepath ){

    global $config;

	// edit these values (columns start at 0) and filename at bottom.
	$EDIKEY = 'GLC';
	$VENDOR_PROD_NUM = 0;
	$DESC = 1;
	$CUST_PROD_NUM = 2;
	$WEIGHT = 3;

    $configFile = '../../../app/config/config.ini';
    $config = file_exists($configFile) ? parse_ini_file($configFile) : [];
    if (empty($config)) {
        error_log( "Error: Unable to load config file: {$configFile}\n" );
        exit;
    }

    $servername = $config[ 'host' ];
    $username = $config[ 'username' ];
    $password = $config[ 'password' ];
    $dbname = $config[ 'dbname' ];


    // Create connection
    $db = mysqli_connect($servername, $username, $password, $dbname);

    // Check connection
    if (! $db) {
        echo "Error: Unable to connect to MySQL." . PHP_EOL;
        echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }

    // Import file to Database
    $handle = fopen( $filepath, "r");
	$counter = 0;


	$sql = "SELECT Value3 FROM Parameter WHERE Value4 = '{$EDIKEY}' AND ParameterGroupID = '40E74C81-EF36-4700-A38C-F39B64F7E7D1'";
	$result = mysqli_query($db, $sql);
	while($row = mysqli_fetch_assoc($result)){
    	$exists[] = $row['Value3'];
	}

	// log_dump( $exists );

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

		if ( ! is_numeric( $data[$VENDOR_PROD_NUM] ) ) continue;
		$UUID = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

		$longdesc = "{$data[$DESC]} ITEM# {$data[$CUST_PROD_NUM]} | {$data[$VENDOR_PROD_NUM]}";

		if ( in_array( $data[$VENDOR_PROD_NUM], $exists ) ) {
			echo( "Item {$data[$VENDOR_PROD_NUM]} already exists, deleting...\n" );
			$sql = "DELETE FROM Parameter WHERE Value3 = '{$data[$VENDOR_PROD_NUM]}' AND Value4 = '{$EDIKEY}'";
			mysqli_query($db, $sql);
		}


$sql =<<<EOF
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
		'$data[$DESC]',
		'$data[$WEIGHT]',
		'$data[$VENDOR_PROD_NUM]',
		'{$EDIKEY}',
		'$longdesc',
		'2050-12-31 00:00:00',
		'2020-06-01 00:00:00',
	 	'00000000-0000-0000-0000-000000000000',
		1
	)
EOF;

//   echo $sql . "\n";
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

db_insert( 'glc_albertson.csv' );
db_insert( 'glc_walmart.csv' );
