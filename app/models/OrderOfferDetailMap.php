<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

/***
OrderOfferDetailMap;
+-----------------------+--------------+------+-----+----------------------+----------------+
| Field                 | Type         | Null | Key | Default              | Extra          |
+-----------------------+--------------+------+-----+----------------------+----------------+
| OODMapID              | mediumint(9) | NO   | PRI | NULL                 | auto_increment |
| CustomerOrderDetailID | mediumint(9) | NO   | MUL | NULL                 |                |
| EDIDocID              | mediumint(9) | NO   |     | NULL                 |                |
| OfferItemID           | varchar(64)  | YES  | UNI | NULL                 |                |
| CreateDate            | datetime(6)  | NO   |     | current_timestamp(6) |                |
| UpdateDate            | datetime(6)  | YES  |     | NULL                 |                |
+-----------------------+--------------+------+-----+----------------------+----------------+
***/

class OrderOfferDetailMap extends Model
{
	public $OODMapID;
	public $CustomerOrderDetailID;
	public $EDIDocID;
	public $OfferItemID;
	public $CreateDate;
	public $UpdateDate;


	public function initialize()
	{
		// set the db table to be used
		$this->setSource("OrderOfferDetailMap");
		// $this->belongsTo("CustomerOrderDetailID", "CustomerOrderDetail", "CustomerOrderDetailID");
		$this->hasOne('EDIDocID', 'EDIDocument', 'EDIDocID');
		$this->hasMany('CustomerOrderDetailID', 'CustomerOrderDetail', 'CustomerOrderDetailID');
	}
}
