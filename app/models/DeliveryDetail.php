<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class DeliveryDetail extends Model
{
	public const STATUS_PENDING   = '51398435-A4A6-4C41-ACC8-45F6D569057B';
	public $DeliveryDetailID;
	public $DeliveryID;
	public $EDIDocID;
	public $Status;
	public $LineNum;
	public $Qty;
	public $QtyUOM;
	public $PartNum;
	public $CustomerLot;
	public $LicensePlate;
	public $ExpDate;
	public $NetWeight;
	public $WeightUOM;
	public $CreateDate;
	public $UpdateDate;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("DeliveryDetail");
		$this->belongsTo("DeliveryID", "Delivery", "DeliveryID");
		$this->hasOne('EDIDocID', 'EDIDocument', 'EDIDocID');
	}

	public static function getLicensePlate($db, $logger, $deliveryDetailID) {
		$sql = "SELECT LicensePlate FROM DeliveryDetail WHERE DeliveryDetailID = ?";

		try {
			$results = $db->query($sql, [ $deliveryDetailID ]);
			$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
			$results = $results->fetchAll($results);
			if (count($results) == 0) {
				throw new Exception("No Delivery Detail found with ID: '$deliveryDetailID'");
			}
		} catch (\Exception $e) {
			$logger->log($e->getMessage());
			throw $e;
		}

		return $results[0]['LicensePlate'];
	}
}
