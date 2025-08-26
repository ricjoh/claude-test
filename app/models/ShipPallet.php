<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class ShipPallet extends Model
{
	public $ShipPalletID;
	public $CustomerOrderID;
	public $CustomerOrderDetailID;
	public $OfferID;
	public $OfferItemID;
	public $OfferItemVatID;
	public $EDIDocIDIn;
	public $EDIDocIDOut;
	public $LicensePlate;
	public $ChepPallet;
	public $PalletSKU;
	public $CreateDate;
	public $UpdateDate;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource('ShipPallet');
		$this->hasMany('CustomerOrderDetailID', 'CustomerOrderDetail', 'CustomerOrderDetailID');
		$this->hasMany('OfferItemID', 'OfferItem', 'OfferItemID');
	}


	// public function getPallets( $OfferID ) {
	// 	$pallets = ShipPallet::find( "OfferID = $OfferID" );
	// 	$pallets = $pallets->toArray();
	// 	return $pallets;
	// }

	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getLogger() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}

	public static function getLicensePlates( $OfferItemID ) {
		$db = self::getDb();
		$db->connect();
		$logger = self::getLogger();

		$sql = "SELECT COALESCE( dd.LicensePlate, '' ) AS LicensePlate, oiv.OfferItemVatID
				FROM OfferItemVat AS oiv
				LEFT OUTER JOIN Vat as v ON oiv.VatID = v.VatID
				LEFT OUTER JOIN DeliveryDetail AS dd ON v.DeliveryDetailID = dd.DeliveryDetailID
				WHERE oiv.Pieces > 0
				AND oiv.OfferItemID = ?";
// $logger->log( $sql );
// $logger->log( "LPS oiid: $OfferItemID" );
		$params = array($OfferItemID);
		$lps = $db->query($sql, $params);
		$lps->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$lps = $lps->fetchAll($lps);

// $logger->log( $lps );
		$licenseplates = array();
		foreach ($lps as $lp) {
			$licenseplates[$lp['OfferItemVatID']] = $lp['LicensePlate'];
		}
		return $licenseplates;
	}

}
