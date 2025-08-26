<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class DeliveryDetailReceipt extends Model
{

	public $ReceiptID;
	public $LicensePlate;
	public $DeliveryDetailID;
	public $ReceivedQty;
	public $LotID;
	public $EDISent;
	public $CreateDate;
	public $UpdateDate;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("DeliveryDetailReceipt");
		$this->belongsTo("LicensePlate", "DeliveryDetail", "LicensePlate");
		$this->hasOne('LicensePlate', 'DeliveryDetail', 'LicensePlate');
	}

	public function getDeliveryID( $lotid )
	{
		$sql = "SELECT DISTINCT dd.DeliveryID 
				FROM Delivery d, DeliveryDetail dd, DeliveryDetailReceipt ddr
				WHERE ddr.DeliveryDetailID = dd.DeliveryDetailID 
				AND  ddr.LotID = '$lotid'";
	
		// $this->getDI()->getShared('logger')->log( $sql . ";" );
	
		try {
			$answer = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		} catch (Exception $e) {
			$this->getDI()->getShared('logger')->log( 'Error' );
			$this->getDI()->getShared('logger')->log( $e->getMessage() );
		}

		// $this->getDI()->getShared('logger')->log( $answer );

		if ( $answer ) {
			$answerarray = $answer->toArray();
			if ( isset( $answerarray[0] ) )	return $answerarray[0]->DeliveryID;
		}
		return false;
	}

}