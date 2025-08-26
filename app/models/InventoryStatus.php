<?php

use Phalcon\Mvc\Model;

class InventoryStatus extends Model
{
	public $InventoryStatusID;
	public $VatID;
	public $Pieces;
	public $Weight;
	public $InventoryStatusPID;
	public $CreateDate;
	public $CreateID;
	public $UpdateDate;
	public $UpdateID;

	public const STATUS_UNAVAILABLE = '235E42CD-31BE-42F0-983A-24675305ED04';
	public const STATUS_OFFERED = 'C67C99CD-492D-4227-92E3-0A3B8DF6EEC8';
	public const STATUS_AVAILABLE = 'D99FC80E-52BC-4AD0-9B10-3E5A5F07EAE0';
	public const STATUS_SOLDUNSHIPPED = 'D6BB15FC-BA12-46A2-A5EE-9CCCB5BCAC5E';

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("InventoryStatus");
		$this->belongsTo("VatID", "Vat", "VatID");
	}


}
