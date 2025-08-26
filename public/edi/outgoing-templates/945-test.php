<?php
error_log( '945' );
$filename = '/tmp/test945.x12';

$data = array(
	'ISACtrlNo' => '000005870',
	'GSCtrlNo' => 5871,
	'STCtrlNo' => 5872,
	// W06
	'VendorOrderNumber' => '0082928581',
	'ShipDate' => '20200522',
	'ShipmentID' => 'OCS323212', // find out what this is
	'AgentShipmentID' => 'OCS323212',
	'CustPONumber' => '0477927891',
	// N1s
	'ShipFromID' => 'PLYMOUTH',
	'ShipToID' => '12345', // from 940
	// N9s
	'MasterBOL' => '0082928581', // from 940 (also order number?)
	'CustOrderNumber' => '0477927891', // same as PO? From 940
	'SealNumber' => '432002',
	// G62
	'DepartureTime' => '130555', // HHMMSS?  use ship date from above
	// W27 
	'SCACCode' => 'ALLV',
	// W03
	'totcases' => 160,
	'totweight' => 720 // LB
);

$lines = array(
	[ 
		'line' => 1,
		// W04 may not be used	
		// W12
		'shipstatus' => 'CC',  // CC = complete  CP = Partial
		'qtyordered' => 160, // CA = case
		'qtyshipped' => 160, // 
		'qtydiff' => 0, // CP if this number is not 0
		'weight' => 720, // G L Gross weight
		'prodnum' => '125874', // PN
		// N9s
		'custlotno' => '000543234', // LT = lot/batch (GL)
		'licenseplate' => '245436978695847', // LV = License plate
		'poline' => '0010'
	] // LI = PO line number 
 );
$data{ 'SECount' } = 9 + (5 * count( $lines ) );

echo "<pre>\n";
include( 'GLC-945-template.php' );
echo "</pre>\n";


