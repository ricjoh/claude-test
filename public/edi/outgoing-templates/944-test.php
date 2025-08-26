<?php
error_log( "944\n" );
$filename = '/tmp/test944.x12';

$data = array(
	'ISACtrlNo' => '000005875',
	'GSCtrlNo' => 5876,
	'STCtrlNo' => 5877,
	// W17
	'ReceiveDate' => '20200522',
	'ReceiveNumber' => '20200522-001',
	'VendorOrderNumber' => '0082928581',
	'OrigRefNumber' => '5501354539',
	// N1s
	'ShipToID' => 'PLYMOUTH',
	'ShipFromID' => '12345', // from 940
	// N9s
	'MasterBOL' => '0082928581', // from 940 (also order number?)
	'CustOrderNumber' => '0477927891', // same as PO? From 940
	'SealNumber' => '432002',
	// W14
	'totcases' => 160,

);

$lines = array(
	[ 
		'line' => 1,
		// W07
		'qtyreceived' => 160,
		'shipstatus' => 'CC',  // CC = complete  CP = Partial
		'prodnum' => '125874', // PN
		// N9s
		'custlotno' => '000543234', // LT = lot/batch (GL)
		'licenseplate' => '245436978695847', // LV = License plate
		'poline' => '0010'
	] // LI = PO line number 
 );

$data{ 'SECount' } = 5 + (4 * count( $lines ) );

echo "<pre>\n";
include( 'GLC-944-template.php' );
echo "</pre>\n";


