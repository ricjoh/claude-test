<?php

use Phalcon\Mvc\Model;
// use Phalcon\Mvc\Model\Query;

class CustomerShipPallet extends Model
{
	public $CustomerShipPalletID;
	public $CustomerOrderDetailID;
	public $LicensePlate;
	public $ChepPallet;
	public $CreateDate;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource('CustomerShipPallet');
		$this->hasMany('CustomerOrderDetailID', 'CustomerOrderDetail', 'CustomerOrderDetailID');
	}
}
