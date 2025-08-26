<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class ShipToAddress extends Model
{
	public $ID;
	public $CustomerID;
	public $Name;
	public $Address;
	public $Address2;
	public $City;
	public $State;
	public $Zip;
	public $ConsignedName;
	public $Nickname;
	public $Active;
	public $CreateDate;
	public $CreateID;
  	public $UpdateDate;
	public $UpdateID;

	public function initialize()
	{
		$this->setSource("ShipToAddress");
		$this->belongsTo("CustomerID", "Customer", "CustomerID");
	}
}
