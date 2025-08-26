<?php

global $config;
$configFile = '../app/config/config.ini';
$config = file_exists($configFile) ? parse_ini_file($configFile) : [];
if (empty($config)) {
	error_log( "Error: Unable to load config file: {$configFile}\n" );
	exit;
}

$servername = $config[ 'host' ];
$username = $config[ 'username' ];
$password = $config[ 'password' ];
$dbname = $config[ 'dbname' ];
$db = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (! $db) {
	error_log( "Error: Unable to connect to MySQL." . PHP_EOL );
	error_log( "Debugging errno: " . mysqli_connect_errno() . PHP_EOL );
	error_log( "Debugging error: " . mysqli_connect_error() . PHP_EOL );
	exit;
}

$data =
[
	[
		'OCS Lot#' => 81357,
		'cust lot' => '21355LC1500',
		'Vat' => 15,
		'Weight' => 10.68,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81515,
		'cust lot' => '22011LC0500',
		'Vat' => 5,
		'Weight' => 14.01,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81515,
		'cust lot' => '21362LC1400',
		'Vat' => 14,
		'Weight' => 9.86,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81736,
		'cust lot' => '22015LC1000',
		'Vat' => 10,
		'Weight' => 8.96,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81741,
		'cust lot' => '22019LC1100',
		'Vat' => 11,
		'Weight' => 11.07,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81907,
		'cust lot' => '22028LC1100',
		'Vat' => 11,
		'Weight' => 10.42,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81909,
		'cust lot' => '22033LC1000',
		'Vat' => 10,
		'Weight' => 9.36,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 81911,
		'cust lot' => '22031LC1500',
		'Vat' => 15,
		'Weight' => 11.16,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 82790,
		'cust lot' => '22076LC1100',
		'Vat' => 11,
		'Weight' => 10.04,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 82790,
		'cust lot' => '22083LC1300',
		'Vat' => 13,
		'Weight' => 9.53,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 83534,
		'cust lot' => '22148LC1300',
		'Vat' => 13,
		'Weight' => 10.66,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 83578,
		'cust lot' => '22155LC1200',
		'Vat' => 12,
		'Weight' => 7.34,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 83619,
		'cust lot' => '22165LC1100',
		'Vat' => 11,
		'Weight' => 11.16,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 83687,
		'cust lot' => '22164LC1200',
		'Vat' => 12,
		'Weight' => 10.23,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84014,
		'cust lot' => '22200LC1300',
		'Vat' => 13,
		'Weight' => 10.56,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84039,
		'cust lot' => '22202LC1100',
		'Vat' => 11,
		'Weight' => 10.16,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84221,
		'cust lot' => '22221LC1300',
		'Vat' => 13,
		'Weight' => 11.74,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84221,
		'cust lot' => '22220LC1400',
		'Vat' => 14,
		'Weight' => 11.10,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84222,
		'cust lot' => '22225LC1100',
		'Vat' => 11,
		'Weight' => 11.23,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84392,
		'cust lot' => '22248LC1300',
		'Vat' => 13,
		'Weight' => 10.74,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84468,
		'cust lot' => '22253LC1000',
		'Vat' => 10,
		'Weight' => 11.34,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 84468,
		'cust lot' => '22249LC1400',
		'Vat' => 14,
		'Weight' => 8.45,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86438,
		'cust lot' => '23092LC1200',
		'Vat' => 12,
		'Weight' => 11.36,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86478,
		'cust lot' => '23094LC1100',
		'Vat' => 11,
		'Weight' => 10.78,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86478,
		'cust lot' => '23093LC1400',
		'Vat' => 14,
		'Weight' => 11.73,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86504,
		'cust lot' => '23096LC0900',
		'Vat' => 9,
		'Weight' => 10.51,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86524,
		'cust lot' => '23103LC1000',
		'Vat' => 10,
		'Weight' => 10.48,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86552,
		'cust lot' => '23106LC0400',
		'Vat' => 4,
		'Weight' => 10.48,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86552,
		'cust lot' => '23107LC0600',
		'Vat' => 6,
		'Weight' => 10.80,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 86942,
		'cust lot' => '23168LC1200',
		'Vat' => 12,
		'Weight' => 10.60,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 87003,
		'cust lot' => '23136LC1400',
		'Vat' => 14,
		'Weight' => 9.53,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 87357,
		'cust lot' => '23192LC0001',
		'Vat' => 0,
		'Weight' => 11.10,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 87357,
		'cust lot' => '23183LC0001',
		'Vat' => 0,
		'Weight' => 8.35,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 87357,
		'cust lot' => '23189LC0001',
		'Vat' => 0,
		'Weight' => 10.43,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88070,
		'cust lot' => '23249LC0300',
		'Vat' => 3,
		'Weight' => 10.04,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88070,
		'cust lot' => '23255LC0500',
		'Vat' => 5,
		'Weight' => 8.76,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88156,
		'cust lot' => '23257LC0700',
		'Vat' => 7,
		'Weight' => 10.47,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88215,
		'cust lot' => '23262LC0300',
		'Vat' => 3,
		'Weight' => 9.56,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88215,
		'cust lot' => '23262LC0600',
		'Vat' => 6,
		'Weight' => 10.41,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88218,
		'cust lot' => '23268LC0600',
		'Vat' => 6,
		'Weight' => 9.75,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88222,
		'cust lot' => '23271LC0300',
		'Vat' => 3,
		'Weight' => 7.22,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88222,
		'cust lot' => '23271LC0400',
		'Vat' => 4,
		'Weight' => 10.36,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88242,
		'cust lot' => '23257LC0400',
		'Vat' => 4,
		'Weight' => 11.07,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88276,
		'cust lot' => '23264LC0700',
		'Vat' => 7,
		'Weight' => 9.05,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88351,
		'cust lot' => '23290LC0400',
		'Vat' => 4,
		'Weight' => 6.86,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88351,
		'cust lot' => '23276LC0500',
		'Vat' => 5,
		'Weight' => 10.65,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88351,
		'cust lot' => '23272LC0900',
		'Vat' => 9,
		'Weight' => 10.46,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88422,
		'cust lot' => '23355LC0400',
		'Vat' => 4,
		'Weight' => 9.29,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88422,
		'cust lot' => '23342LC0900',
		'Vat' => 9,
		'Weight' => 8.83,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88425,
		'cust lot' => '23355LC0300',
		'Vat' => 3,
		'Weight' => 9.76,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88544,
		'cust lot' => '23355LC0200',
		'Vat' => 2,
		'Weight' => 10.36,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88618,
		'cust lot' => '24005LC0100',
		'Vat' => 1,
		'Weight' => 9.92,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88618,
		'cust lot' => '24005LC0200',
		'Vat' => 2,
		'Weight' => 8.82,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88695,
		'cust lot' => '24005LC0300',
		'Vat' => 3,
		'Weight' => 8.57,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88696,
		'cust lot' => '24026LC0100',
		'Vat' => 1,
		'Weight' => 11.03,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88748,
		'cust lot' => '23346LC0700',
		'Vat' => 7,
		'Weight' => 10.91,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88748,
		'cust lot' => '23346LC0800',
		'Vat' => 8,
		'Weight' => 9.35,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88780,
		'cust lot' => '24026LC0600',
		'Vat' => 6,
		'Weight' => 10.29,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88780,
		'cust lot' => '24040LC0900',
		'Vat' => 9,
		'Weight' => 9.28,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88879,
		'cust lot' => '24045LC1300',
		'Vat' => 13,
		'Weight' => 8.14,
		'Pc' => 1
	],
	[
		'OCS Lot#' => 88879,
		'cust lot' => '24038LC1500',
		'Vat' => 15,
		'Weight' => 9.07,
		'Pc' => 1
	]
];

foreach ( $data as &$record ) {
	$sql = "SELECT LotID FROM Lot WHERE LotNumber = '{$record['OCS Lot#']}'";
	if ($result = mysqli_query($db, $sql)) {
		$row = mysqli_fetch_assoc($result);
		$record['LotID'] = $row['LotID'];
	}
}

foreach ( $data as &$record ) {
	$sql = "SELECT VatID FROM Vat WHERE CustomerLotNumber = '{$record['cust lot']}' AND LotID = '{$record['LotID']}'";
	if ($result = mysqli_query($db, $sql)) {
		$row = mysqli_fetch_assoc($result);
		$record['VatID'] = $row['VatID'];
	}
}

foreach ( $data as $record ) {
	// get available
	$sql = "SELECT Pieces, Weight FROM InventoryStatus WHERE VatID = '{$record['VatID']}' AND InventoryStatusPID = 'D99FC80E-52BC-4AD0-9B10-3E5A5F07EAE0'";
	if ($result = mysqli_query($db, $sql)) {
		$row = mysqli_fetch_assoc($result);
		if ($row['Pieces'] >= $record['Pc']) {
			// available
			echo "Found vat {$record['OCS Lot#']} / {$record['cust lot']} with Pieces {$row['Pieces']}, Weight {$row['Weight']}\n";
			$newPieces = $row['Pieces'] + $record['Pc'];
			$newWeight = $row['Weight'] + $record['Weight'];
			echo "Going to update vat avail {$record['OCS Lot#']} / {$record['cust lot']}  with Pieces {$newPieces}, Weight {$newWeight}\n\n";
			$sql = "UPDATE InventoryStatus SET Pieces = {$newPieces}, Weight = {$newWeight} WHERE VatID = '{$record['VatID']}' AND InventoryStatusPID = 'D99FC80E-52BC-4AD0-9B10-3E5A5F07EAE0'";
			mysqli_query($db, $sql);

			$sql = "SELECT Pieces FROM InventoryStatus WHERE VatID = '{$record['VatID']}' AND InventoryStatusPID = '235E42CD-31BE-42F0-983A-24675305ED04'";
			if ($result = mysqli_query($db, $sql)) {
				$newPieces = $row['Pieces'] - $record['Pc'];
				$newWeight = $row['Weight'] - $record['Weight'];
				$sql = "UPDATE InventoryStatus SET Pieces = {$newPieces}, Weight = {$newWeight} WHERE VatID = '{$record['VatID']}' AND InventoryStatusPID = '235E42CD-31BE-42F0-983A-24675305ED04'";
				mysqli_query($db, $sql);

			}
		} else {
			echo "Can't update vat {$record['OCS Lot#']} / {$record['cust lot']}  Qty is {$row['Pieces']}\n\n";
		}
	} else {
		echo "No result for $sql\n";
	}
}
