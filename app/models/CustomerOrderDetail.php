<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class CustomerOrderDetail extends Model
{
	public $CustomerOrderDetailID;
	public $CustomerOrderID;
	public $EDIDocID;
	public $LineNum;
	public $Qty;
	public $QtyUOM;
	public $PartNum;
	public $ShipToPartNum;
	public $POLine;
	public $CreateDate;
	public $UpdateDate;
	public $OfferItemID;
	public $MetaData;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("CustomerOrderDetail");
		$this->belongsTo("CustomerOrderID", "CustomerOrder", "CustomerOrderID");
		$this->hasOne('EDIDocID', 'EDIDocument', 'EDIDocID');
	}

	public function onConstruct()
	{
		$this->MetaData = [];
	}

	public static function getProdNumberReferenceArray($databaseConnection)
	{
		$sql = "SELECT DISTINCT PartNum, ShipToPartNum FROM CustomerOrderDetail ORDER BY PartNum";

		$databaseConnection->connect();

		$result_set = $databaseConnection->query($sql);
		$result_set->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result_set = $result_set->fetchAll($result_set);

		$referenceArray = array();

		if (is_array($result_set) && count($result_set) > 0) {
			foreach ($result_set as $result) {
				$referenceArray[$result["PartNum"]] = $result["ShipToPartNum"];
			}
		}

		return $referenceArray;
	}

	// encode/decode metadata
	public function beforeSave() {
		$this->MetaData = json_encode( $this->MetaData );
	}

	public function afterSave() {
		$this->MetaData = json_decode( $this->MetaData );
	}

	public function afterFetch() {
		$this->MetaData = json_decode( $this->MetaData );
	}
}
