<?php

use Phalcon\Mvc\Model;

class Vat extends Model {

	public $VatID;
	public $LotID;
	public $VatNumber;
	public $MakeDate;
	public $Pieces;
	public $Moisture;
	public $FDB;
	public $PH;
	public $Salt;
	public $Weight;
	public $NoteText;
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;
	public $CustomerLotNumber;
	public $DeliveryDetailID;

	public function initialize() {

		Phalcon\Mvc\Model::setup(['exceptionOnFailedSave' => true]);
		// set the db table to be used
		$this->setSource("Vat");
		$this->belongsTo("LotID", "Lot", "LotID");
		$this->hasMany("VatID", "InventoryStatus", "VatID");
		$this->belongsTo("VatID", "OfferItemVat", "VatID");
	}

	public function clone($lotId = null) {
		// clone Vat, set new Lot ID if passed
		$newVat = new Vat();
		$di = \Phalcon\DI::getDefault();
		$newVat->VatID = $di->getShared('utils')->UUID(mt_rand(0, 65535));
		$newVat->LotID = $lotId ?: $this->LotID;
		$newVat->VatNumber = $this->VatNumber;
		$newVat->MakeDate = $this->MakeDate;
		$newVat->Pieces = $this->Pieces;
		$newVat->Moisture = $this->Moisture;
		$newVat->FDB = $this->FDB;
		$newVat->PH = $this->PH;
		$newVat->Salt = $this->Salt;
		$newVat->Weight = $this->Weight;
		$newVat->NoteText = $this->NoteText;
		$newVat->CreateDate = date('Y-m-d H:i:s');
		$newVat->CreateId = $di->getShared('session')->userAuth['UserID'];
		$newVat->UpdateDate = null;
		$newVat->UpdateId = null;
		$newVat->CustomerLotNumber = $this->CustomerLotNumber;
		$newVat->DeliveryDetailID = $this->DeliveryDetailID;

		return $newVat;
	}

	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getLogger() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}

	public function getAvailable( $removeSold = true ) {
		// INVENTORY: By default this WILL remove sold items from the available inventory

		// $logger = self::getLogger();
		$sql = "SELECT i.Pieces AS InvPieces, i.Weight AS InvWeight
				FROM InventoryStatus AS i
				WHERE i.InventoryStatusPID  = '" . InventoryStatus::STATUS_AVAILABLE . "'" .
				" AND i.vatid = '$this->VatID' ";

		$inv = $this->getDI()->getShared('modelsManager')->executeQuery($sql);

		if ( $removeSold ) {

			$sold = $this->getSoldUnshipped();

			$inv[0]->InvPieces -= $sold[0]['SoldPieces'];
			$inv[0]->InvWeight -= $sold[0]['SoldWeight'];
		}
		return $inv;
	}


	public static function getVatSoldUnshipped( $vatID ) {
		// $logger = self::getLogger();
		$sql = "SELECT COALESCE( i.Pieces, 0 ) AS SoldPieces,
					   COALESCE( i.Weight, 0 ) AS SoldWeight
				FROM InventoryStatus AS i
				WHERE i.InventoryStatusPID  = '" . InventoryStatus::STATUS_SOLDUNSHIPPED . "'" .
				" AND i.vatid = '$vatID'";

		$sold = self::getDb()->query($sql);
		$sold = $sold->fetchAll($sold);

		if ( is_null($sold) || empty($sold) ) {
			$sold = [['SoldPieces' => 0, 'SoldWeight' => 0]];
		}
		return $sold;
	}

	public function getSoldUnshipped() {
		return self::getVatSoldUnshipped( $this->VatID );
	}


	public function getOffered( $addSold = false ) {
		// INVENTORY: By default this will NOT add sold items to the offered inventory

		/***********
			This only uses data from all offers that are open with this vat id and add up weights and pieces.
			Turns out "Offered" in inventory reflects all offers ever. Not very useful.
		 ************/

		$logger = self::getLogger();
		// $logger->log("Vat::getOffered() VatID: {$this->VatID}");

		$STATUS_OPEN = '9A085965-75B4-4EE2-85A8-02D61924DCC8';
		$STATUS_EDIPENDING = 'C478D7C5-25FC-439B-A5A4-A155493ABC08';

		$results = array('Pieces' => 0, 'Weight' => 0);

		$ovats = OfferItemVat::find("VatID = '" . $this->VatID . "'")->toArray();

		foreach ($ovats as $ov) {
			$oi = OfferItem::findFirst("OfferItemID = '" . $ov['OfferItemID'] . "'");

			if ($oi) {
				$o = $oi->getOffer();
				if ($o->OfferStatusPID  == $STATUS_OPEN or $o->OfferStatusPID  == $STATUS_EDIPENDING) // OPEN and EDIPENDING offers only
				{
					$results['Pieces'] += $ov['Pieces'];
					$results['Weight'] += $ov['Weight'];
				}
			}
		}

		if ( $addSold ) {
			$sold = $this->getSoldUnshipped();
			$results['Pieces'] += $sold[0]['SoldPieces'];
			$results['Weight'] += $sold[0]['SoldWeight'];
		}

		return $results;
	}

	public function isOffered() {
		$sql = "SELECT i.Pieces AS InvPieces
				FROM
				   InventoryStatus AS i,
				   Parameter AS p
				WHERE i.InventoryStatusPID = p.ParameterID
				  AND i.vatid = '$this->VatID'
				  AND p.Value1 = 'Offered'
				ORDER BY p.Value1 LIMIT 1";

		$isOffered = false;
		$data = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		if ($data && $data[0]->InvPieces) {
			$isOffered = true;
		}

		return $isOffered;
	}

	public function updateOffered($params) {
		$logger = self::getLogger();
		$logger->log("Vat::updateOffered() VatID: {$this->VatID}");
		$OFFERED = InventoryStatus::STATUS_OFFERED;
		$sql = "UPDATE InventoryStatus " .
			"SET Pieces = " . $params['Pieces'] . ", " .
			"Weight = " . $params['Weight'] . ", " .
			"UpdateDate = '" . $this->getDI()->getShared('mysqlDate') . "', " .
			"UpdateID = '" . $this->getDI()->getShared('session')->userAuth['UserID'] . "' " .
			"WHERE InventoryStatusPID = '{$OFFERED}' " .
			"AND VatID = '{$this->VatID}'";

		$this->getDI()->getShared('modelsManager')->executeQuery($sql);
	}


	public static function ediCustomerLotAutoFill($db, $customerLotNumber, $customerID) {
		$db->connect();

		$sql = "SELECT
					distinct v.CustomerLotNumber
				FROM
					Vat v
					LEFT JOIN Lot l ON l.LotID = v.LotID
					LEFT JOIN InventoryStatus i ON i.VatID = v.VatID
				WHERE
					l.CustomerID = '$customerID'
					AND i.Pieces > 0
					AND i.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'
					AND v.CustomerLotNumber LIKE '%$customerLotNumber%'";

		$results = self::getDb()->query($sql);
		$results = $results->fetchAll($results);

		return $results;
	}
}
