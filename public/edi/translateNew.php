<?php

require 'utilities.php';
require 'generate997.php';

global $config;
$configFile = '../../app/config/config.ini';
$config = file_exists($configFile) ? parse_ini_file($configFile) : [];
if (empty($config)) {
	error_log( "Error: Unable to load config file: {$configFile}\n" );
	exit;
}

$servername = $config[ 'host' ];
$username = $config[ 'username' ];
$password = $config[ 'password' ];
$dbname = $config[ 'dbname' ];

global $EDI_KEY; // TODO
$EDI_KEY = 'SAP'; // TODO: have Todd pass this into command line or get from ISA ID.

const INVENTORY_INQUIRY_ADVICE = '846';
const ADVANCE_SHIP_NOTICE_MANIFEST = '856';
const WAREHOUSE_STOCK_TRANSFER_SHIPMENT_ADVICE_TO_RETAILER = '940';
const WAREHOUSE_STOCK_TRANSFER_SHIPMENT_ADVICE = '943';
const WAREHOUSE_STOCK_TRANSFER_RECEIPT_ADVICE = '944';
const WAREHOUSE_SHIPPING_ADVICE = '945';
const WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE = '947';
const FUNCTIONAL_ACKNOWLEDGEMENT = '997';

// Create connection
$db = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (! $db) {
	error_log( "Error: Unable to connect to MySQL." . PHP_EOL );
	error_log( "Debugging errno: " . mysqli_connect_errno() . PHP_EOL );
	error_log( "Debugging error: " . mysqli_connect_error() . PHP_EOL );
	exit;
}

function translateToJson($path) {
	// $file = fopen($path, "r");
	$file = file_get_contents( $path );
	$lines = preg_split( '/[\r\n~]+/', $file );
	$mainArray = array();

	$counter = 1;
	foreach ( $lines as $line )
	{
		$thisLine = explode("*", $line);
		$thisKey = $thisLine[0];
		$thisLine = array_slice($thisLine, 1);

		$arrayGroup = array();
		$arrayGroup['elements'] = $thisLine;
		$arrayGroup['segment'] = $thisKey;
		$arrayGroup['line'] = $counter;

		array_push($mainArray, $arrayGroup);

		$counter++;
	}

	$identifierCodeId = array_search('ST', array_column($mainArray, 'segment'));
	$identifierCode = $mainArray[$identifierCodeId]['elements'][0];

	if (!$identifierCodeId){
		error_log("No ST ID\n");
		return false;
	}

	require 'dictionaries/' . $identifierCode . '.php';

	foreach($mainArray as $index => $elementArray){
		foreach(array_keys($dictionary) as $dictionarySection){
			if (array_key_exists($elementArray['segment'], $dictionary[$dictionarySection])){
				$mainArray[$index]['level'] = $dictionarySection;
				$mainArray[$index]['name'] = $dictionary[$dictionarySection][$elementArray['segment']]['name'];

				foreach($elementArray['elements'] as $elementIndex => $element){
					$mainArray[$index]['elements'][$elementIndex] = array(
						'label' => $elementArray['segment'] . sprintf('%02d', $elementIndex + 1),
						'value' => $element,
						'name' => $dictionary[$dictionarySection][$elementArray['segment']]['elements'][$elementIndex]
					);
				}
			}
		}
	}

	// fclose($file);

	// print_r($mainArray);
	$finalJson = json_encode($mainArray, JSON_PRETTY_PRINT);
	return $finalJson;
}

function updateDB($docId, $json, $db){
	$json = trim(str_ireplace(array('\r', '\n', "~"),'', $json));

	$status = 'Translated';

	$sql = "UPDATE EDIDocument SET JsonObject = '" . $db->real_escape_string($json) . "', status = '" . $status . "', UpdateDate = NOW() WHERE DocID = " . $docId;

	if (mysqli_query($db, $sql)) {
		error_log( "Record " . $docId . " updated\n" );
	} else {
		error_log( "Error: " . mysqli_error($db) . "\n\n" );
	}
}

function errorStatus($docId, $db){
	$sql = "UPDATE EDIDocument SET status = 'Error', UpdateDate = NOW() WHERE DocID = " . $docId;

	if (mysqli_query($db, $sql)) {
		error_log( "Record " . $docId . " updated\n" );
	} else {
		error_log( "Error: " . mysqli_error($db) . "\n\n" );
	}
}

function insert997($db, $filepath, $EDIKey)
{
	error_log( "\n997 filepath: " . $filepath . "\n" );
	$json = translateToJson( $filepath );
	if (!$json){
		return false;
	}
	$json = trim(str_ireplace(array('\r', '\n', "~"),'', $json));
	$data = json_decode($json);

	$sql = "INSERT INTO EDIDocument (EDIKey, Transaction, ControlNumber, Incoming, DocISAID, DocGSID, Status, X12FilePath, JsonObject, CreateDate, UpdateDate)
	VALUES (
		'$EDIKey',
		'" . getValueByElement($data, 'ST', 1) . "',
		'" . getValueByElement($data, 'ST', 2) . "',
		1,
		'" . getValueByElement($data, 'ISA', 6) . "',
		'" . getValueByElement($data, 'GS', 2) . "',
		'Outbox',
		'" . $filepath . "',
		'" . $db->real_escape_string($json) . "',
		NOW(),
		NOW()
	)";

	error_log( "$sql\n\n" );

	if (mysqli_query($db, $sql)) {
		error_log( "New record created successfully for 997\n" );
	} else {
		error_log( "Error: " . mysqli_error($db) . "\n\n" );
	}

	return $json;
}

function translateNew($db)
{
	global $config;
	$sql = "SELECT * FROM EDIDocument WHERE status = 'New'";
	if ($result = $db->query($sql)){
		// TODO: EDI Document
		$importableEDIDocumentIDs = [
			WAREHOUSE_STOCK_TRANSFER_SHIPMENT_ADVICE,
			WAREHOUSE_STOCK_TRANSFER_SHIPMENT_ADVICE_TO_RETAILER,
			ADVANCE_SHIP_NOTICE_MANIFEST
		];

		foreach($result as $row){
			$success_url = '';

			if (in_array($row['Transaction'], $importableEDIDocumentIDs)) {
				if (file_exists($row['X12FilePath'])){
					// print_r($row);
					$json = translateToJson($row['X12FilePath']);
					$json = trim(str_ireplace(array('\r', '\n', "~"),'', $json));

					if (!$json){
						// Dan: Write database status as 'Error';
						error_log( "Error occurred on DocID: " . $row['DocID'] . " - no json to translate.\n" );
						errorStatus($row['DocID'], $db);
					} else {
						if ($row['JsonObject']){
							error_log("Record " . $row['DocID'] . " already had JSON data, but it is being replaced.\n");
							// TODO: Fail on this when live
						}
						updateDB($row['DocID'], $json, $db);
						// fires off docid
						$success_url = 'edidocument/x12tojson/' . $row['Transaction'] . '/' . $row['EDIKey'] . '/' . $row['DocID'];
						$filepath997 = generate997($json, $row['EDIKey']);

						// Dan: Update database with a new EDI record for 997, outgoing = 1 status = 'Outbox'
						// I've changed the filepath above
						if (! insert997($db, $filepath997, $row['EDIKey']) ) {
							// Dan: Write database status as 'Error';
							error_log( "Error occurred on DocID: " . $row['DocID'] . " - couldn't write 997 back to db.\n" );
							errorStatus($row['DocID'], $db);
						}

						// Dan: For testing, I changed it to not update the status to 'Translated' yet and leave it 'New' to keep rerunning it. See above function.
						// Dan: For testing.

					}
				} else {
					error_log( "Error occurred on DocID: " . $row['DocID'] . " - file does not exist.\n" );
					errorStatus($row['DocID'], $db);
				}
			} else {
				error_log( "Skipping Row: " . $row['DocID'] . " Transaction: " . $row['Transaction'] . "\n" );
			}

			if ( $success_url ) {
				$url = $config[ 'base_url' ] . $success_url;
				error_log( "Calling: $url\n" );
				$agent = curl_init( $url );
				try {
					curl_exec( $agent );
					if ( curl_error( $agent ) ) {
						error_log( "CURL Error in translateNew: " . curl_error( $agent ) );
					}
					curl_close( $agent );
				} catch ( Exception $e ) {
					error_log( "CURL Exception in translateNew: " . $e->getMessage() );
				}
			}
		}

		$result->free_result();
	} else {
		error_log( "DB Error: " . mysqli_error($db) . "\n" );
	}
	mysqli_close($db);

}

translateNew( $db );
