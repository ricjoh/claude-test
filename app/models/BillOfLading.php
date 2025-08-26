<?php

use Phalcon\Mvc\Model;

class BillOfLading extends Model
{

	public $BOLID;
	public $OfferID;
	public $CarrierName;
	public $CarrierNumber;
	public $ConsignedTo;
	public $ShippedToName;
	public $ShippedToAddress;
	public $ShipperNumber;
	public $CustomerPO;
	public $BOLDate;
	public $SealNumber;
	public $TotalTareWeight;
	public $InstructionOnePID;
	public $InstructionTwoPID;
	public $InstructionThreePID;
	public $InstructionFourPID;
	public $InstructionFive;
	public $InstructionSix;
	public $CustomerItemNumber;
	public $PickupDate;
	public $PrintManifest;
	public $AttachManifest;
	public $PrintTestResults;
	public $UserID;
	public $ManifestDetailTemplatePID;
	public $BOLTemplatePID;
	public $CreateID;
	public $CreateDate;
	public $UpdateID;
	public $UpdateDate;
	public $StatusPID;

	public const STATUS_GROUP   = 'EFFCB53D-3929-4E0D-BD40-5E0ECC45D251';
	public const STATUS_CREATED = '1E732327-AD3E-42C1-9602-F505B3A75E7E';
	public const STATUS_SHIPPED = '124B3F46-9F63-4256-AC3A-B8CB00656CC1';

	public const SHOW_DETAILS_AND_TESTS = '041DDEEE-75A5-4622-8F4F-99BEB23FFDC2';
	public const SHOW_DETAILS = '9F276770-8635-4C1B-BBB2-136A83B7C639';

	public const SHOW_PRODUCT_CODE = 'E09B73D7-9738-4536-BD44-FD999EDFF88B';
	public const SHOW_DETAILS_AND_PRODUCT_CODE = '6E67A7DD-3BA2-436B-862F-9D21DA935293';
	public const SHOW_DETAILS_AND_TESTS_AND_PRODUCT_CODE = '3AB61D59-3D21-4CB5-83C9-98516C124F21';

	public const SHOW_NO_LOT_DETAILS = '7504EE4C-6F75-46BC-9E0C-2CC15AACC060';

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("BillOfLading");
        $this->belongsTo("OfferID", "Offer", "OfferID");

    }

}
