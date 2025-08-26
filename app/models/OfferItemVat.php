<?php

use Phalcon\Mvc\Model;

class OfferItemVat extends Model
{

	public $OfferItemVatID;
	public $OfferItemID;
	public $VatID;
	public $Pieces;
	public $Weight;
	public $EstPallets;
	public $Price;
	public $CreateId;
	public $CreateDate;
	public $UpdateId;
	public $UpdateDate;
	public $Sort;

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("OfferItemVat");
        $this->hasOne("VatID", "Vat", "VatID");
        $this->belongsTo("OfferItemID", "OfferItem", "OfferItemID");
    }

}
