<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class Delivery extends Model {
	public const STATUS_GROUP 	  = 'E7D27442-F7B9-4ADD-84FC-B56D32AADBE6';
	public const STATUS_PENDING   = '51398435-A4A6-4C41-ACC8-45F6D569057B';
	public const STATUS_RECEIVED  = '0DE180F5-FA4B-4DE7-A6F5-715FF4BE3836';
	public const STATUS_CONVERTED = '25AB7862-7669-4F74-A7F6-92A240F1A898';

	public $DeliveryID;
	public $EDIDocID;
	public $StatusPID;
	public $OrderNum;
	public $ShipDate;
	public $Sender;
	public $Warehouse;
	public $Reference;
	public $ShipmentID;
	public $Carrier;
	public $TotalShippedQty;
	public $TotalShippedWeight;
	public $CreateDate;
	public $UpdateDate;

	public function initialize() {
		// set the db table to be used
		$this->setSource("Delivery");
		$this->hasMany("DeliveryID", "DeliveryDetail", "DeliveryID");
		$this->hasOne('EDIDocID', 'EDIDocument', 'EDIDocID');
	}

	public function getHeader($id) {
		$sql = <<<EOF
SELECT
	ed.DocID,
	ed.ControlNumber,
	ed.EDIKey,
	ed.CreateDate,
	c.Name,
	c.CustomerID,
	d.Sender,
	d.DeliveryID,
	d.ShipDate,
	d.OrderNum,
	d.TotalShippedQty,
	d.TotalShippedWeight,
	d.Carrier,
	d.ShipmentID,
	d.StatusPID,
	d.EDIDocID,
	d.Warehouse
FROM
	Delivery AS d,
	EDIDocument AS ed,
	Customer AS c
WHERE
	ed.EDIKey = c.EDIKey
	AND d.EDIDocID = ed.DocId
	AND d.DeliveryID = $id
EOF;

		$header = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $header[0];
	}

	// TODO: Search for references to this function and adjust them to pass in the $ediKey
	public function getDetails($id, $ediKey) {

		$sql = <<<EOF
SELECT DISTINCT
	ed.DocID,
	ed.EDIKey,
	dd.DeliveryDetailID,
	dd.LicensePlate,
	p.Value1 AS PartDescription,
	p.Value2 AS PieceWeight,
	p.ParameterID AS DescriptionPID,
	dd.PartNum,
	dd.CustomerLot,
	dd.ExpDate,
	dd.LineNum,
	dd.Qty,
	dd.NetWeight
FROM
	EDIDocument AS ed
	JOIN DeliveryDetail AS dd
	LEFT JOIN Parameter AS p ON p.Value3 = dd.PartNum AND p.Value4 = '$ediKey'
WHERE
	dd.EDIDocID = ed.DocId
	AND dd.DeliveryID = $id
ORDER BY
	dd.LineNum,
	dd.DeliveryDetailID
EOF;

		return $this->getDI()->getShared('modelsManager')->executeQuery($sql);
	}

	public function lastID() {
		$sql = "SELECT MAX( DeliveryID ) AS DeliveryID FROM Delivery";
		$ID = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $ID[0]->DeliveryID;
	}
}
