<?php

use Phalcon\Mvc\Model;

/*
+---------------+---------------+------+-----+---------+-------+
| Field         | Type          | Null | Key | Default | Extra |
+---------------+---------------+------+-----+---------+-------+
| ReceiptID     | varchar(64)   | NO   | PRI | NULL    |       |
| ReceiptNumber | varchar(64)   | NO   | UNI | NULL    |       |
| ReceiptDate   | datetime(6)   | NO   | MUL | NULL    |       |
| CustomerID    | varchar(64)   | YES  |     | NULL    |       |
| CreateDate    | datetime(6)   | YES  |     | NULL    |       |
| CreateId      | varchar(64)   | NO   |     | NULL    |       |
| UpdateDate    | datetime(6)   | YES  |     | NULL    |       |
| UpdateId      | varchar(64)   | YES  |     | NULL    |       |
| Handling      | decimal(18,4) | YES  |     | NULL    |       |
| HandlingUnit  | varchar(10)   | YES  |     | NULL    |       |
| Storage       | decimal(18,4) | YES  |     | NULL    |       |
| StorageUnit   | varchar(10)   | YES  |     | NULL    |       |
+---------------+---------------+------+-----+---------+-------+
*/

class WarehouseReceipt extends Model {

	public function initialize() {

		//set the db table to be used
		$this->setSource("WarehouseReceipt");
		$this->hasMany("ReceiptID", "WarehouseReceiptItem", "ReceiptID");
		$this->hasManyToMany("ReceiptID", "WarehouseReceiptItem", "ReceiptID", "LotID", "Lot", "LotID");
		$this->hasOne('CustomerID', 'Customer', 'CustomerID');
	}

	public function getCustomer() {
		if (!$this->CustomerID) {
			//error_log('saving customer id');
			$this->CustomerID = $this->getLot()[0]->getCustomer()->CustomerID;

			$this->save();
		}

		return Customer::findFirst("CustomerID = '{$this->CustomerID}'");
	}

	public function beforeValidationOnCreate() {
		if (!isset($this->ReceiptNumber) || !$this->ReceiptNumber) {
			$this->ReceiptNumber = self::getNextReceiptNumber();
		}
	}

	public function getPossibleLots() {
		$currentLots = $this->lot->toArray();


		if (count($currentLots) == 0) return false;


		$customerId = $this->CustomerID; // $currentLots[0]['CustomerID'];
		// $descriptionPid = $currentLots[0]['DescriptionPID'];


		$currentLotIDs = array_map(function ($lot) {

			return $lot['LotID'];
		}, $currentLots);

		$notIn = "NOT IN ('" . implode("', '", $currentLotIDs) . "')";

		$possibleLots = Lot::find([
			"CustomerID = '$customerId' AND InventoryTypePID = '85102C3F-33C1-482A-9750-4FB4A5EA7A7B' AND LotID $notIn", // AND DescriptionPID = '$descriptionPid'
			"order" => "DateIn DESC", //, DescriptionPID <> '$descriptionPid'",
			"limit" => 20
		]);

		return array_merge($currentLots, $possibleLots->toArray());
	}


	public static function getNextReceiptNumber() {

		$currentMax = self::findFirst([
			'columns' => ['maxNumber' => 'MAX(CAST(ReceiptNumber AS UNSIGNED))'],
		]);

		if ($currentMax && isset($currentMax->maxNumber)) {
			return $currentMax->maxNumber + 1;
		}

		return 10001;
	}
}

// ALTER TABLE WarehouseReceiptItem ADD Pieces int(11);
// ALTER TABLE WarehouseReceiptItem ADD Weight decimal(18,2);
// ALTER Table WarehouseReceipt ADD CustomerID varchar(64) AFTER ReceiptDate;
// ALTER TABLE WarehouseReceipt ADD Handling decimal(18,4);
// ALTER TABLE WarehouseReceipt ADD HandlingUnit varchar(10);
// ALTER TABLE WarehouseReceipt ADD Storage decimal(18,4);
// ALTER TABLE WarehouseReceipt ADD StorageUnit varchar(10);
