<?php

use Phalcon\Mvc\Model;

/****

OfferItem

Column Name       Type        Size
OfferItemID       varchar     64
OfferID           varchar     64
DescriptionPID    varchar     64
Weight            decimal     18
MakeDate          datetime
Pieces            int         11
Pallets           int         11
Cost              decimal     18
LotID             varchar     64
NoteID            varchar     64
NoteText          varchar     500
Credit            tinyint
CreateId          varchar     64
CreateDate        datetime
UpdateId          varchar     64
UpdateDate        datetime

 ****/

class OfferItem extends Model {

	public function initialize() {
		// set the db table to be used
		$this->setSource("OfferItem");
		$this->belongsTo("OfferID", "Offer", "OfferID");
		$this->hasMany("OfferItemID", "OfferItemVat", "OfferItemID");
		$this->hasOne("LotID", "Lot", "LotID");
	}

	public static function getDb()
	{
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getLogger()
	{
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}

	public function getOfferItemVatWithLP() {
		/* SELECT oiv,*, dd.LicensePlate FROM OfferItemVat AS oiv
		LEFT OUTER JOIN DeliveryDetail ON (dd.OfferItemVatID = oiv.OfferItemVatID) AS dd
		WHERE oiv.OfferItemID = ? */

		$sql = "SELECT oiv.*, dd.LicensePlate
				FROM OfferItemVat AS oiv, Vat AS v
				LEFT OUTER JOIN DeliveryDetail AS dd ON (dd.DeliveryDetailID = v.DeliveryDetailID)
				WHERE oiv.OfferItemID = ?
				AND v.VatID = oiv.VatID
				ORDER BY oiv.Sort, oiv.CreateDate";
		$logger = self::getLogger();
		$logger->log( $sql );
		$params = array($this->OfferItemID);
		$db = self::getDb();
		$db->connect();
		return $db->fetchAll( $sql, Phalcon\Db::FETCH_ASSOC, $params );
	}
}
