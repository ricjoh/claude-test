<?php

use Phalcon\Mvc\Model;

class InventoryInquiry extends Model
{
    public $InventoryInquiryID;
    public $CreateDate;
    public $UpdateDate;

	public function initialize()
	{
		$this->setSource("InventoryInquiry");
	}

	// TODO: Remove if unused
	public function lastID()
	{
		$sql = "SELECT MAX(InventoryInquiryID) AS InventoryInquiryID FROM InventoryInquiry";
		$ID = $this->getDI()->getShared('modelsManager')->executeQuery($sql);

		return $ID[0]->InventoryInquiryID;
	}

	// Checks if we already have an inventory inquiry for a specific date.
	public static function existsForDate($date = null, $dateFormat = "Y-m-d")
	{
		$date = $date ?? date($dateFormat);
		$dateTime = DateTime::createFromFormat($dateFormat, $date);

		// If there was an invalid date
		if (!$dateTime || $dateTime->format($dateFormat) !== $date) {
			throw new Exception("Invalid date used");
		}

		$inventoryInquiry = self::findFirst(
			[
				'conditions' => "DATE(CreateDate) = :date:",
				
				'bind' => [
					'date' => $date
				]
			]
		);

		// Returns true if an entry was found, false if not
		return !empty($inventoryInquiry);
	}
}

/* =====================================================================================================================
Table description
+--------------------+--------------+------+-----+----------------------+--------------------------------+
| Field              | Type         | Null | Key | Default              | Extra                          |
+--------------------+--------------+------+-----+----------------------+--------------------------------+
| InventoryInquiryID | mediumint(9) | NO   | PRI | NULL                 | auto_increment                 |
| EDIDocumentID      | mediumint(9) | NO   | MUL | NULL                 |                                |
| CreateDate         | datetime(6)  | YES  |     | current_timestamp(6) |                                |
| UpdateDate         | datetime(6)  | YES  |     | current_timestamp(6) | on update current_timestamp(6) |
+--------------------+--------------+------+-----+----------------------+--------------------------------+
===================================================================================================================== */