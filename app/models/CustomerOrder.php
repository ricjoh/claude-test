<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class CustomerOrder extends Model
{

	public const STATUS_GROUP = '9173DEE9-5907-4211-85CC-7CE51FF170F7';
	public const STATUS_PENDING = '05FF809C-B079-45F0-8BE5-49AD7B149B43';
	public const STATUS_OFFERPENDING = 'DC9F8F03-A05B-4466-B135-9CA4E83606AA';
	public const STATUS_SHIPPED = 'B9A7285B-CB33-4C99-8D13-8E9325C6811E';

	public $CustomerOrderID;
	public $EDIDocID;
	public $Status;
	public $CustomerOrderDate;
	public $CustomerOrderNum;
	public $CustPONum;
	public $ShipToID;
	public $ShipToPONum;
	public $ShipToMasterBOL;
	public $ShipToName;
	public $ShipToAddr1;
	public $ShipToAddr2;
	public $ShipToCity;
	public $ShipToState;
	public $ShipToZIP;
	public $ShipToCountry;
	public $ShipByDate;
	public $CustomerOrderNotes;
	public $DeliveryNotes;
	public $Carrier;
	public $TotalCustomerOrderQty;
	public $LoadSequenceStopNumber;
	public $OfferID;
	public $CreateDate;
	public $UpdateDate;
	public $MetaData;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("CustomerOrder");
		$this->hasMany("CustomerOrderID", "CustomerOrderDetail", "CustomerOrderID");
		$this->hasOne('EDIDocID', 'EDIDocument', 'EDIDocID');
		$this->hasOne('OfferID', 'Offer', 'OfferID');
	}

	public function onConstruct()
	{
		$this->MetaData = [];
	}

	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getLogger() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}


    // .oPYo.  o      8          .oPYo.  d'b  d'b                 o    o      o                     o
    // 8              8          8.      8    8                   8    8      8
    // `Yooo. o8 .oPYo8 .oPYo.   `boo   o8P  o8P  .oPYo. .oPYo.  o8P   8      8 .oPYo. oPYo. odYo. o8 odYo. .oPYo.
    //     `8  8 8    8 8oooo8   .P      8    8   8oooo8 8    '   8    8  db  8 .oooo8 8  `' 8' `8  8 8' `8 8    8
    //      8  8 8    8 8.       8       8    8   8.     8    .   8    `b.PY.d' 8    8 8     8   8  8 8   8 8    8
    // `YooP'  8 `YooP' `Yooo'   `YooP'  8    8   `Yooo' `YooP'   8     `8  8'  `YooP8 8     8   8  8 8   8 `YooP8
    // :.....::..:.....::.....::::.....::..:::..:::.....::.....:::..:::::..:..:::.....:..::::..::..:....::..:....8
    // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
    // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::...::
	/***
	* This function will return the EDIKey to be interpreted as TRUE.
	* Do *not* evaluate the return value as a boolean literal.
	* (i.e. if ( isEDI($offerID) === true ) { ... } )
	* Just use it as a truthy value. `(isEDI($offerID) !== false) is fine`
	***/
	public static function isEDI( $offerID, $ediKey = null ) {
		$db = self::getDb();
		$log = self::getLogger();
		$db->connect();
		$params = array($offerID);
		if (!empty($ediKey)) {
			$sql = "SELECT COUNT(*) FROM CustomerOrder JOIN EDIDocument ON CustomerOrder.EDIDocID = EDIDocument.DocID WHERE CustomerOrder.OfferID = ? AND EDIDocument.EDIKey = ?";
			$params = array($offerID, $ediKey);
			$result = $db->query($sql, $params);
			$count = $result->fetch();
			if ( $count[0] > 0 ) {
				return $ediKey; // also truthy
			}
		}
		else
		{
			$sql = "SELECT DISTINCT EDIDocument.EDIKey FROM CustomerOrder JOIN EDIDocument ON CustomerOrder.EDIDocID = EDIDocument.DocID WHERE CustomerOrder.OfferID = ? LIMIT 1";
			$params = array($offerID);
			$result = $db->query($sql, $params);
			$result->setFetchMode(Phalcon\Db::FETCH_OBJ);
			$singleResult = $result->fetch();
			if ( $singleResult ) {
				return $singleResult->EDIKey;
			}
		}
		return false;
	}


	public function getHeader( $id ) {
		$sql = <<<EOF
SELECT ed.DocID, ed.ControlNumber, ed.EDIKey, c.Name, c.CustomerID, c.EDIKey, co.ShipToName,
		co.ShipToAddr1, co.ShipToAddr2, co.ShipToCity, co.ShipToState,
		co.ShipToZIP, co.CustomerOrderNum, co.CustomerOrderDate, co.ShipByDate, co.CustomerOrderNotes,
		co.DeliveryNotes, co.TotalCustomerOrderQty, co.EDIDocID, co.Status
FROM CustomerOrder AS co, EDIDocument AS ed, Customer AS c
WHERE ed.EDIKey = c.EDIKey
	AND co.EDIDocID = ed.DocId
	AND co.CustomerOrderID = $id
EOF;
// $this->getDI()->getShared('logger')->log( $sql . ";\n" );
		$header = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $header[0];
	}


	public function getDetails( $id ) {
		$sql = <<<EOF
SELECT ed.DocID, ed.EDIKey, cod.Qty, cod.PartNum, cod.POLine, cod.LineNum,
	Value1 as Description, cod.ShipToPartNum
FROM EDIDocument AS ed
	JOIN CustomerOrderDetail AS cod
	LEFT OUTER JOIN
		Parameter AS p ON (cod.PartNum = Value3 AND Value4 = ed.EDIKey)
WHERE cod.EDIDocID = ed.DocId
	AND cod.CustomerOrderID = $id
ORDER BY cod.LineNum, cod.CustomerOrderDetailID
EOF;

		return $this->getDI()->getShared('modelsManager')->executeQuery($sql);
	}


	public function lastID() {
		$sql = "SELECT MAX( CustomerOrderID ) AS CustomerOrderID FROM CustomerOrder";
		$ID = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $ID[0]->CustomerOrderID;
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
