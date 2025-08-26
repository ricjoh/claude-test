<?php

use Phalcon\Mvc\Model;
// use Phalcon\Mvc\Model\Query;

class CustomerShipDetail extends Model
{
	public $CustomerOrderDetailID;
	public $CustomerOrderID;
	public $QtyShipped;
	public $LicensePlate;
	public $ShippedLotID;
	public $ShippedVatID;
	public $ShippedDate;
	public $CreateDate;
	public $UpdateDate;
	public $EDIDocID;


	public function initialize()
	{
		// set the db table to be used
		$this->setSource("CustomerShipDetail");
	}
}
