<?php

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Http\Response;
use Phalcon\Logger;

include_once 'logdump.php';

class OfferController extends Controller
{

	public function indexAction()
	{
		$this->view->title = "Offer Index";
	}


	// 8  o          o
	// 8             8
	// 8 o8 .oPYo.  o8P
	// 8  8 Yb..     8
	// 8  8   'Yb.   8
	// 8  8 `YooP'   8
	// ..:..:.....:::..:
	// :::::::::::::::::
	// :::::::::::::::::

	public function listAction()
	{

		$status = $this->dispatcher->getParam("id");
		$s = ($status == Offer::STATUS_EDIPENDING) ? 'Pending ' : '';
		$s = ($status == Offer::STATUS_EXPIRED) ? 'Expired ' : '';

		$fields = ['OfferID', 'EDIDocID', 'CustomerName', 'CustomerOrderNum', 'Status', 'OfferDate', 'Weight', 'Pieces'];

		$offers = Offer::getOffersByStatus($status);
		// $this->logger->log('Got ' . count( $offers ) . ' offer(s).' );
		$rows = array();
		$ids = array();
		foreach ($offers as $offer) {
			$this->logger->log("'" . $offer['EDIDocID'] . "'");
			if (empty($offer['EDIDocID'])) continue;

			$row = array();
			foreach ($fields as $f) {

				if ($f == 'OfferDate' && $status == Offer::STATUS_EXPIRED) {
					$offer[$f] = substr($offer[$f], 0, 10);
				}


				if ($f == 'OfferID') {
					array_push($ids, ':' . $offer[$f]);
				} else {
					array_push($row, $offer[$f]);
				}
			}
			array_push($rows, $row);
		}

		$this->view->headers = ['EDI#', 'Customer', 'PONum', 'Status', 'Offer Date', 'Weight', 'Pieces'];
		$this->view->ids = $ids; // [ 1, 2 ];
		$this->view->data = $rows; /*[
			['CUSTOMERNAME', 'Pending-New', '04-26-2020', '39981.35', 90 ],
			['CUSTOMERNAME', 'Pending-Edited', '04-22-2020', '6981.50', 3933 ],
		];*/
		$this->view->title = "{$s}Offers";
	}

	//             8  o   o
	//             8      8
	// .oPYo. .oPYo8 o8  o8P
	// 8oooo8 8    8  8   8
	// 8.     8    8  8   8
	// `Yooo' `YooP'  8   8
	// :.....::.....::..::..:
	// ::::::::::::::::::::::
	// ::::::::::::::::::::::

	public function editAction()
	{
		$offer = new Offer();
		$contact = new Contact();

		$paramModel = new Parameter();
		$offerID = $this->dispatcher->getParam("id");
		$this->view->title = "Offer";
		$offerData = new stdClass;

		if ($offerID == 'NEW') $offerID = 0;

		if ($offerID) {
			try {
				$offerData = $offer->findFirst("OfferID = '{$offerID}'");
				$bol = $offer->getBillOfLading();
				if ($bol) $this->view->bolID = $bol->BOLID;
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log("Exception in Offer/BOL Edit: " . $e->getModel()->getMessages());
			}
		} else {
			$userID = $this->session->userAuth['UserID'];
			$userDetails = User::getUserDetail($userID);
			// data that gets pre-filled on offers
			$offerData = (object) array(
				'UserID' => $userID, // default the screen to this user
				'OfferStatusPID' => Offer::STATUS_OPEN, // Default to Open
				'OCSContactPhoneNumber' => $userDetails['Phone'],
				'OCSContactFaxNumber' => $userDetails['Fax'],
				'OCSContactEmail' => $userDetails['Email'],
			);
		}

		$customerOrder = new CustomerOrder();
		$customerOrderData = $customerOrder->findFirst("OfferID = '$offerID'");

		$selectPromptElement = array('ParameterID' => '', 'Value1' => 'Select...');

		// Payment Terms
		$terms =  $paramModel->getValuesForGroupId(
			'58DE9214-197F-4867-9910-44FFB312C99E',
		);
		array_unshift($terms, $selectPromptElement);
		$this->view->terms = $terms;
		/***
		// Offer Status
		$status = $paramModel->getValuesForGroupId(
			'2AD6C035-FD2C-4553-AAA8-E2B983DF46C1',
		);
        array_unshift( $status, $selectPromptElement ); Should never be blank.
		$this->view->status = $status;
		 ***/

		// Description
		$descs = $paramModel->getValuesForGroupId(
			'40E74C81-EF36-4700-A38C-F39B64F7E7D1',
		);
		array_unshift($descs, $selectPromptElement);
		$this->view->descs = $descs;


		// will include this customer even if not current if not new offer.
		$this->view->customers = Customer::getActiveCustomers($offerData->CustomerID);

		$transferredToLotId = false;
		$transferredToLotNumber = null;
		if ($offerData->Transferred) {
			$transferredToLot = Lot::findFirst("TransferredFrom = 'O:{$offerData->OfferID}'");
			$transferredToLotId = $transferredToLot->LotID;
			$transferredToLotNumber = $transferredToLot->LotNumber ? '# ' . $transferredToLot->LotNumber : '';
		}

		// NEED TO RETHINK THIS ONE. AJAX?
		// will include this customer even if not current if not new offer.
		// $this->view->contact = Contacts::getActiveContacts( $offerData->CustomerID );

		// will include this customer even if not current if not new offer.
		$this->view->users = User::getActiveUsers($offerData->UserID);
		$this->view->customerOrderID = $customerOrderData ? $customerOrderData->CustomerOrderID : false;
		$this->view->offerID = $offerID;
		$this->view->offerData = $offerData;
		$this->view->logger = $this->logger;
		$this->view->isEDI = CustomerOrder::isEDI($offerID);
		$this->view->isSingleSku = Offer::isSingleSkuOffer($offerID);
		$this->view->transferredTo = $transferredToLotId;
		$this->view->transferredToNum = $transferredToLotNumber;
		$this->view->utils = $this->utils;
	}

	public function issingleskuAction()
	{
		$offerId = $this->dispatcher->getParam("id");

		$isSingleSku = Offer::isSingleSkuOffer($offerId);

		$response = new Response();
		$response->setHeader('Content-Type', 'application/json');
		$response->setStatusCode(200, "OK");
		$response->setContent(json_encode([
			'isSingleSku' => $isSingleSku,
		]));

		$response->send();
	}

	// .oPYo. .oPYo. o    o .oPYo.
	// Yb..   .oooo8 Y.  .P 8oooo8
	//   'Yb. 8    8 `b..d' 8.
	// `YooP' `YooP8  `YP'  `Yooo'
	// :.....::.....:::...:::.....:
	// ::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::

	public function saveAction()
	{

		if ($this->request->isPost() == true) {

			// $this->logger->log('*********************** Posted *************************');
			// $this->logger->log( $_REQUEST );

			$OfferID = $this->request->getPost('OfferID');
			if (!$OfferID) {
				$OfferID = 0;
			}

			// Don't update 'SaleDate' here. Do it on ajax status change
			$updateFields = array(
				'OfferDate',
				'Attention',
				'FOB',
				'OfferExpiration',
				'TermsPID',
				'Note',
				'UserID',
				'CustomerID',
				'ContactID',
				'CustomerPhoneNumber',
				'CustomerFaxNumber',
				'CustomerEmail',
				'OCSContactPhoneNumber',
				'OCSContactFaxNumber',
				'OCSContactEmail'
			);

			$newOfferFlag = 0;
			$offer = Offer::findFirst("OfferID = '$OfferID'");
			if (!$offer) { // add new offer
				$newOfferFlag = 1;
				$this->logger->log('Creating new offer...');
				$offer = new Offer();
				$OfferID = $this->UUID;
				$offer->OfferID = $OfferID;
				$offer->CreateDate = $this->mysqlDate;
				$offer->CreateId = $this->request->getPost('UserID') ?: $this->session->userAuth['UserID'];
				$offer->OfferStatusPID = $this->request->getPost('OfferStatusPID');
			} else { // update existing offer
				$this->logger->log('Saving existing offer...');
				$offer->UpdateDate = $this->mysqlDate;
				$offer->UpdateId = $this->request->getPost('UserID') ?: $this->session->userAuth['UserID']; // id of user who is logged in
				if (
					$offer->OfferStatusPID !== Offer::STATUS_SOLD
					&& $offer->OfferStatusPID !== Offer::STATUS_EXPIRED
					&& $offer->OfferStatusPID !== Offer::STATUS_SHIPPED
				) // can't update status of sold/expired/shipped offer in this routine
				{
					$offer->OfferStatusPID = $this->request->getPost('OfferStatusPID');
				}
			}

			foreach ($updateFields as $fieldName) {
				if ($fieldName == 'OfferDate') {
					$offer->{$fieldName} = $this->utils->dbDate($this->request->getPost($fieldName));
				} else if ($fieldName == 'OfferExpiration') {
					// If the expiration date is not empty
					if (!empty($this->request->getPost($fieldName))) {
						// Only be set this if we actually get a value. Not skipping this results in setting it to today

						// $this->logger->log("Date array: " . $this->request->getPost($fieldName));

						$offer->{$fieldName} = $this->utils->dbDate($this->request->getPost($fieldName));
					}
				} else {
					$offer->{$fieldName} = $this->request->getPost($fieldName);
				}
			}

			$offer->ContactID = $offer->ContactID ?: $offer->UserID;

			$this->logger->log("Saving offer: " . $OfferID . "...");

			$success = 1;
			try {
				if ($offer->save() == false) {
					$this->logger->log('FAIL!');
					$msg = "Error saving offer:\n\n" . implode("\n", $offer->getMessages());
					$this->logger->log("$msg\n");
					$success = 0;
				}
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log('EXCEPTION!');
				$msg = "Exception saving offer:\n" . $e->getMessage();
				$this->logger->log("$msg\n");
				$success = 0;
			}

			$this->logger->log("Did offer save? " . (($success == 1) ? 'Yes!' : 'No.'));

			$this->view->data = array(
				'success' => $success,
				'newOfferFlag' => $newOfferFlag,
				'status' => ($success ? 'success' : 'error'),
				'msg' => $msg,
				'OfferID' => $OfferID
			);

			$this->view->pick("layouts/json");
		}
	}

	//                        o   o
	//                        8   8
	// odYo. .oPYo. o   o   o 8  o8P .oPYo. ooYoYo.
	// 8' `8 8oooo8 Y. .P. .P 8   8  8oooo8 8' 8  8
	// 8   8 8.     `b.d'b.d' 8   8  8.     8  8  8
	// 8   8 `Yooo'  `Y' `Y'  8   8  `Yooo' 8  8  8
	// ..::..:.....:::..::..::..::..::.....:..:..:..
	// :::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::

	public function newitemAction()
	{
		$offerID = $this->dispatcher->getParam("id");
		$lotID = $this->dispatcher->getParam("relid");

		// Check to see if there's an offer item for this offer that corresponds to this lot id
		try {
			$offerItem = OfferItem::findFirst("LotID = '$lotID' AND OfferID = '$offerID'");
		} catch (\Phalcon\Mvc\Model\Exception $e) {
			$this->logger->log('Exception in newitemAction findOfferItem: ' . $e->getMessage());
		}
		//  if so, return the lot id
		if ($offerItem and isset($offerItem->OfferItemID)) {
			$this->logger->log('OfferItem already exists for this lot, returning lot id: ' . $lotID);
			$this->view->data = array('offeritemid' => $offerItem->OfferItemID, 'type' => 'existing');
			$this->view->pick("layouts/json");
		} else {
			$this->logger->log('Creating Offer Item Lot LID: ' . $lotID);
			$this->view->data = array('offeritemid' => '00000000-0000-0000-0000-000000000000', 'type' => 'new');
			$this->view->pick("layouts/json");

			// get lot
			$lot = Lot::findFirst("LotID = '$lotID'");
			$vats = $lot->getVat(array('order' => 'MakeDate, VatNumber'));
			$this->logger->log('Vats: ' . count($vats));

			// create new offeritem record with lot info

			// begin a transaction
			$this->db->begin();
			$offerItemID = $this->utils->UUID(mt_rand(0, 65535));

			$offerItem = new OfferItem();
			$offerItem->OfferItemID = $offerItemID;
			$offerItem->OfferID = $offerID;
			$offerItem->LotID = $lotID;

			// Info from Lot
			$offerItem->DescriptionPID = $lot->DescriptionPID;
			$offerItem->MakeDate = $vats[0]->MakeDate;

			$this->logger->log('Make Date: ' . $offerItem->MakeDate);

			// Initialize
			$offerItem->Weight = 0;
			$offerItem->Pieces = 0; // update this on save itemvat?
			// $offerItem->Pallets = 0;
			$offerItem->NoteText = '';

			// Unknowns
			// $offerItem->NoteID = 0; // not used
			$offerItem->Cost = 0; // default to 0 per diane Sept 4, 2015
			$offerItem->Credit = 0; // ???

			// std stuff
			$offerItem->CreateId = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in
			$offerItem->CreateDate = $this->mysqlDate;

			$this->logger->log('About to save OfferItem...');

			$msg = '';
			try {
				if ($offerItem->save() == false) {
					$msg = "Error saving offerItem:\n" . implode("\n", $offerItem->getMessages());
					$this->logger->log($msg);
					$this->db->rollback();
					$this->view->data = array('success' => 0, 'msg' => $msg, 'type' => 'new');
					$this->view->pick("layouts/json");
					return;
				}
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log('Exception in newitemAction saveOfferItem: ' . $e->getMessage());
			}
			$this->logger->log('Saved OfferItem! Creating Vats.');


			// create offeritemvat records to match vats in lot

			$sort = 0;

			$vatmap = array();

			try {
				foreach ($vats as $v) {
					// create new offerItemVat record
					$offerItemVat = new OfferItemVat();
					$offerItemVat->OfferItemVatID  = $this->utils->UUID(mt_rand(0, 65535));
					$offerItemVat->OfferItemID = $offerItemID;
					$offerItemVat->OfferID = $offerID;
					$offerItemVat->VatID = $v->VatID;
					// $offerItemVat->MakeDate = $v->MakeDate; // column doesn't exist in table - Ric & David 2015-09-11
					$offerItemVat->Pieces = 0;
					$offerItemVat->Weight = 0;
					// $offerItemVat->EstPallets = 0;
					$offerItemVat->Price = 0;
					$offerItemVat->Sort = $sort;
					$offerItemVat->CreateId = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in
					$offerItemVat->CreateDate = $this->mysqlDate;

					$vatmap[$v->VatID] = $offerItemVat->OfferItemVatID;

					// save
					$msg = '';
					try {
						if ($offerItemVat->save() == false) {
							$this->logger->log('PANIC NOW!');
							$msg = "Error saving offerItemVat:\n" . implode("\n", $offerItemVat->getMessages());
							$this->logger->log($msg);
							$this->db->rollback();
							$this->view->data = array('success' => 0, 'msg' => $msg, 'type' => 'new');
							$this->view->pick("layouts/json");
							return;
						}
					} catch (\Phalcon\Mvc\Model\Exception $e) {
						$this->logger->log('Exception in newitemAction saveVat: ' . $e->getMessage());
					}

					$sort++;
				}
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log('REALLY PANIC NOW!');
				$this->logger->log('Big Exception in newitemAction saveVat: ' . $e->getModel()->getMessages());
				$this->logger->log($msg);
				$this->db->rollback();
				$this->view->data = array('success' => 0, 'msg' => $msg, 'type' => 'new');
				$this->view->pick("layouts/json");
				return;
			}

			// save changes to the db
			$this->db->commit();
			$this->view->data = array(
				'offeritemid' => $offerItemID,
				'vatmap' => $vatmap,
				'type' => 'new',
				'success' => 1,
				'msg' => $msg
			);
			$this->view->pick("layouts/json");
		}
	}


	//                 o  o   o
	//                 8  8   8
	// .oPYo. .oPYo.  o8P 8  o8P .oPYo. ooYoYo. .oPYo.
	// 8    8 8oooo8   8  8   8  8oooo8 8' 8  8 Yb..
	// 8    8 8.       8  8   8  8.     8  8  8   'Yb.
	// `YooP8 `Yooo'   8  8   8  `Yooo' 8  8  8 `YooP'
	// :....8 :.....:::..:..::..::.....:..:..:..:.....:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::::

	public function getitemsAction()
	{
		$offerID = $this->dispatcher->getParam("id");
		$totals = (object) array(
			'totPieces' => 0,
			'totWeight' => 0,
			'totPriceOut' => 0,
			'totPallets'  => 0
		);

		if ($offerID == '00000000-0000-0000-0000-000000000000') {
			$this->view->items = array();
			$offerID = 'NEW';
			$isSoldOrExpired = false;
		} else {
			try {
				$offerData = Offer::findFirst("OfferID = '$offerID'");
			} catch (\Exception $e) {
				$this->logger->log('Exception in getitemsAction Existing: ' . $e->getMessage());
			}
			$this->view->items = $offerData->getOfferItem();

			$isSoldOrExpired = ($offerData->OfferStatusPID == Offer::STATUS_SOLD
				|| $offerData->OfferStatusPID == Offer::STATUS_EXPIRED
				|| $offerData->OfferStatusPID == Offer::STATUS_SHIPPED) ? true : false;

			$offerUsesPallets = false;

			// Entered Pieces, Available Pieces, Entered Weight, Available Weight
			foreach ($this->view->items as $item) {
				$lot = Lot::findFirst("LotID = '$item->LotID'");

				if ($lot->StorageUnit == Lot::UNIT_PALLET && $lot->HandlingUnit == Lot::UNIT_PALLET && $lot->Pallets > 0) {
					$offerUsesPallets = true;
				}

				$totals->totPieces  += $item->Pieces;
				$totals->totWeight  += $item->Weight;
				$totals->totCost    += $item->Weight * $item->Cost;
				$totals->totPallets += $item->Pallets ?? 0;
			}
		}

		$this->view->offerUsesPallets = $offerUsesPallets;
		$this->view->offerID = $offerID;
		$this->view->isSoldOrExpired = $isSoldOrExpired;
		$this->view->offerTotals = $totals;
		$this->view->utils = $this->utils;
		$this->view->isEDI = CustomerOrder::isEDI($offerID);
		$this->view->pick("offer/ajax/getitems");
	}


	//                 o  o   o                 o     o          o
	//                 8  8   8                 8     8          8
	// .oPYo. .oPYo.  o8P 8  o8P .oPYo. ooYoYo. 8     8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8   8  8   8  8oooo8 8' 8  8 `b   d' .oooo8   8  Yb..
	// 8    8 8.       8  8   8  8.     8  8  8  `b d'  8    8   8    'Yb.
	// `YooP8 `Yooo'   8  8   8  `Yooo' 8  8  8   `8'   `YooP8   8  `YooP'
	// :....8 :.....:::..:..::..::.....:..:..:..:::..::::.....:::..::.....:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function getitemvatsAction()
	{

		$offerItemID = $this->dispatcher->getParam("id");

		// $this->logger->log( 'getitemvatsAction ID: ' . $offerItemID );


		$vatStats = new stdClass;
		$totals = (object) array(
			'totCostOut' => 0,
			'totWeight' => 0,
			'totPieces' => 0,
		);
		$availablePallets = 0;

		if ($offerItemID == '00000000-0000-0000-0000-000000000000') {
			$this->view->vats = array();
			$offerItemID = 'NEW';
		} else {
			$itemData = OfferItem::findFirst("OfferItemID = '$offerItemID'");

			try {
				$this->view->vats = $itemData->getOfferItemVat(array('order' => 'Sort, CreateDate'));
			} catch (\Exception $e) {
				$this->logger->log('Exception in getitemvatsAction Existing: ' . $e->getMessage());
			}

			$offer = $itemData->getOffer();
			$isSold = $offer->OfferStatusPID == Offer::STATUS_SOLD;
			$isShipped = $offer->OfferStatusPID == Offer::STATUS_SHIPPED;
			try {
				// Entered Pieces, Available Pieces, Entered Weight, Available Weight
				foreach ($this->view->vats as $v) {
					$vat = $v->getVat();
					$vatStats->{$v->VatID} = new stdClass;
					if ($vat) {
						$dd = false;
						if ( !empty($vat->DeliveryDetailID ) ) {
							$dd = DeliveryDetail::findFirst( $vat->DeliveryDetailID );
							log_dump( $dd->toArray());
						}

						$status = $vat->getOffered();
						$vatStats->{$v->VatID}->LicensePlate = $dd ? substr( $dd->LicensePlate, -8) : '';
						// error_log("LP:" . $vatStats->{$v->VatID}->LicensePlate );
						$vatStats->{$v->VatID}->OffPieces = $status['Pieces'];
						$vatStats->{$v->VatID}->OffWeight = $status['Weight'];

						// $availstatus = $vat->getAvailable(!isSold); // NOTE: allows editing of a sold order -- Oops bad idea: SUPPORT-2715
						$availstatus = $vat->getAvailable();
						$vatStats->{$v->VatID}->InvPieces = $availstatus[0]->InvPieces;
						$vatStats->{$v->VatID}->InvWeight = $availstatus[0]->InvWeight;
						$vatStats->{$v->VatID}->Pieces = $vat->Pieces;
						$vatStats->{$v->VatID}->Weight = $vat->Weight;

						if ( $isShipped ) { // allows editing of a shipped order
							$vatStats->{$v->VatID}->MaxPieces = $vat->Pieces;
							$vatStats->{$v->VatID}->MaxWeight = $vat->Weight;
						} else {
							$vatStats->{$v->VatID}->MaxPieces = $availstatus[0]->InvPieces;
							$vatStats->{$v->VatID}->MaxWeight = $availstatus[0]->InvWeight;
						}

						$vatStats->{$v->VatID}->isOffered = $vat->isOffered();
						$vatStats->{$v->VatID}->VatNumber = $vat->VatNumber;
						$vatStats->{$v->VatID}->CustomerLotNumber = $vat->CustomerLotNumber;
						$vatStats->{$v->VatID}->Moisture = $vat->Moisture;
						$vatStats->{$v->VatID}->PH = $vat->PH;
						$vatStats->{$v->VatID}->FDB = $vat->FDB;
						$vatStats->{$v->VatID}->Salt = $vat->Salt;
						$vatStats->{$v->VatID}->MakeDate = $vat->MakeDate;
					} else {
						$this->logger->log('getItemVats: No vat found (probably deleted) for VatID: ' . $v->VatID);
					}
					$totals->totPieces += $v->Pieces;
					$totals->totWeight += $v->Weight;
					$totals->totCostOut += $v->Price;
				}
			} catch (\Exception $e) {
				$this->logger->log('Exception in getitemvatsAction: ' . $e->getMessage());
			}

			try {

				$lot = $itemData->getLot();

				$availablePallets = $lot->getAvailablePallets();
			} catch (\Exception $e) {
				$this->logger->log('Exception in getAvailablePallets: ' . $e->getMessage());
			}

			$this->view->maxPallets = $availablePallets + $itemData->Pallets;
			$this->view->availablePallets = $availablePallets;
			$this->view->lotNumber = $lot->LotNumber;
			$this->view->lotDescription = Parameter::getValue($lot->DescriptionPID, 'Description');
			$this->view->lotUsesPallets = !empty($lot->Pallets); // $lot->HandlingUnit === 'pallet';
		}


		$this->view->modifiedOn = '';
		$this->view->modifiedBy = '';
		if ($itemData->UpdateId) {
			$userDetails = User::getUserDetail($itemData->UpdateId);
			// $this->view->modifiedOn = $this->utils->slashDate( $itemData->UpdateDate );
			$this->view->modifiedOn = substr($itemData->UpdateDate, 0, 19);
			$this->view->modifiedBy = $userDetails['FullName'];
		}

		$userDetails = User::getUserDetail($this->session->userAuth['UserID']);

		$this->view->currentUser = $userDetails['FullName'];
		$this->view->currentTime =  substr($this->mysqlDate, 0, 19);
		$this->view->offerItemID = $offerItemID;
		$this->view->item = $itemData;
		$this->view->logger = $this->logger;
		$this->view->vatStats = $vatStats;
		$this->view->itemTotals = $totals;
		$this->view->utils = $this->utils;
		$this->view->pick("offer/ajax/getitemvats");
	}

	//      8               8          o           o
	//      8               8          8           8
	// .oPYo8 .oPYo. .oPYo. 8 .oPYo.  o8P .oPYo.   8 odYo. o    o
	// 8    8 8oooo8 8    8 8 8oooo8   8  8oooo8   8 8' `8 Y.  .P
	// 8    8 8.     8    8 8 8.       8  8.       8 8   8 `b..d'
	// `YooP' `Yooo' 8YooP' 8 `Yooo'   8  `Yooo'   8 8   8  `YP'
	// :.....::.....:8 ....:..:.....:::..::.....:::....::..::...::
	// ::::::::::::::8 :::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::..:::::::::::::::::::::::::::::::::::::::::::

	public function depleteinventoryAction()
	{
		$success = 1;
		$offerID = $this->dispatcher->getParam("id");

		if ($this->request->isPost() == true && ! $offerId) {
			$offerID = $this->request->getPost('offerID');
		}

		$this->logger->log("depleteInventory OfferID: " . $offerID);


		$offer = Offer::findFirst([
			'conditions' => "OfferID = :offerID:",
			'bind'       => ['offerID' => $offerID]
		]);

		if ($offer === false) {
			$success = 0;
			$message = "Failed to get offer using offerID: $offerID";
		}

		if ($success) {
			$itemData = $offer->getOfferItem("OfferID = '$offer->OfferID' AND (Weight > 0 OR Pieces > 0)");

			foreach ($itemData as $item) {
				$itemVatData = $item->getOfferItemVat("OfferItemID = '$item->OfferItemID' AND (Weight > 0 OR Pieces > 0)");

				foreach ($itemVatData as $vat) {
					if (!$this->depleteVat($vat->VatID, $vat->Pieces, $vat->Weight)) {
						$success = 0;
						$message = "Error in depleteInventory: Failed to update Available Inventory";
						$this->logger->log("$message\n");
					}
				}
			}

			$message = 'Inventory successfully updated.';
		}

		return json_encode(['success' => $success, 'msg' => $message]);
	}


	//      8               8          o         o     o          o
	//      8               8          8         8     8          8
	// .oPYo8 .oPYo. .oPYo. 8 .oPYo.  o8P .oPYo. 8     8 .oPYo.  o8P
	// 8    8 8oooo8 8    8 8 8oooo8   8  8oooo8 `b   d' .oooo8   8
	// 8    8 8.     8    8 8 8.       8  8.      `b d'  8    8   8
	// `YooP' `Yooo' 8YooP' 8 `Yooo'   8  `Yooo'   `8'   `YooP8   8
	// :.....::.....:8 ....:..:.....:::..::.....::::..::::.....:::..:
	// ::::::::::::::8 ::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::..::::::::::::::::::::::::::::::::::::::::::::::

	public function depleteVat($vatID, $pieces, $weight, $adjustSoldUnshipped = true)
	{

		/*****
		-- Available
		UPDATE InventoryStatus SET Weight = Weight - $weight, Pieces = Pieces - $pieces
		WHERE VatID = '$vatID' AND InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'
		 *****/

		$this->logger->log("depleteVat vatID: {$vatID} Pieces: {$pieces} Weight: {$weight}");
		$transactionManager = new TransactionManager();
		$transaction = $transactionManager->get();
		$thisvat = Vat::findFirst("VatID = '{$vatID}'");


		$date = $this->mysqlDate;
		$userid = $this->session->userAuth['UserID'];

		// InventoryStatus::STATUS_AVAILABLE
		$avail = InventoryStatus::findFirst(
			"VatID = '$vatID' AND InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'"
		);

		try {
			// TODO: if net is < 0, then we have a problem.
			if ($avail && $avail->Pieces >= $pieces && $avail->Weight >= $weight) { // don't take below 0
				$avail->setTransaction($transaction);
				$avail->Weight = $avail->Weight - $weight;
				$avail->Pieces = $avail->Pieces - $pieces;
				$avail->UpdateDate = $date;
				$avail->UpdateID =   $userid;
				if (!$avail->save()) {
					$transaction->rollback("Can't save available for Vat: $vatID");
				}
			} else {
				$this->logger->log("depleteVat: Failed to update Available Inventory for VatID: {$vatID}");
				$this->logger->log("Attempt: AP: {$avail->Pieces} >= P: {$pieces}?   AW: {$avail->Weight} >= W: {$weight}?");
				return false;
			}
		} catch (\Exception $e) {
			$this->logger->log('Exception: depleteVat available Failed, reason: ');
			$this->logger->log($e->getMessage());
			return false;
		}


		if ($adjustSoldUnshipped) {
			// InventoryStatus::STATUS_SOLDUNSHIPPED
			$soldUnshipped = InventoryStatus::findFirst(
				"VatID = '$vatID' AND InventoryStatusPID = '" . InventoryStatus::STATUS_SOLDUNSHIPPED . "'"
			);

			try {
				if (!$soldUnshipped) {
					$this->logger->log('	 ' . $vatID);
					$UUID = $this->utils->UUID(mt_rand(0, 65535));
					$soldUnshipped = new InventoryStatus();
					$soldUnshipped->InventoryStatusID = $UUID;
					$soldUnshipped->VatID = $vatID;
					$soldUnshipped->InventoryStatusPID = InventoryStatus::STATUS_SOLDUNSHIPPED;
					$soldUnshipped->Weight = 0; // no point in subtracting from sold/unshipped
					$soldUnshipped->Pieces = 0; // no point in subtracting from sold/unshipped
					$soldUnshipped->CreateDate = $date;
					$soldUnshipped->CreateID = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in
				} else {
					$this->logger->log("depleteVat: SoldUnshipped Inventory (Incoming) -- Pcs: {$soldUnshipped->Pieces} Wgt: {$soldUnshipped->Weight}");
					$w = floatval($soldUnshipped->Weight - $weight);
					$p = intval($soldUnshipped->Pieces - $pieces);
					if ( $w < 0 || $p < 0 ) {
						$this->logger->log("WARNING: depleteVat: SoldUnshipped Inventory would go negative -- Pcs: {$p} Wgt: {$w}");
					}
					$soldUnshipped->Weight = max($w, 0.0); // don't take below 0;
					$soldUnshipped->Pieces = max($p, 0); // don't take below 0;
					$this->logger->log('Updating soldUnshipped record for vat: ' . $vatID . ' Pieces: ' . $soldUnshipped->Pieces . ' Weight: ' . $soldUnshipped->Weight);
				}
				$soldUnshipped->setTransaction($transaction);
				$soldUnshipped->UpdateDate = $date;
				$soldUnshipped->UpdateID =   $userid;
				if (!$soldUnshipped->save()) {
					$transaction->rollback("Can't save soldUnshipped for Vat: $vatID");
				}
			} catch (\Exception $e) {
				$this->logger->log('Exception: depleteVat soldUnshipped Failed, reason: ');
				$this->logger->log($e->getMessage());
				return false;
			}
		}

		/*****
		-- Unavailable
		UPDATE InventoryStatus SET Weight = Weight + $weight, Pieces = Pieces + $pieces
		WHERE VatID = '$vatID' AND InventoryStatusPID = InventoryStatus::STATUS_UNAVAILABLE
		 *****/

		$unavail = InventoryStatus::findFirst(
			"VatID = '$vatID' AND InventoryStatusPID = '" . InventoryStatus::STATUS_UNAVAILABLE . "'"
		);

		try {
			// TODO: if net is > vat->Pieces, then we have a problem.
			if ($unavail) {
				$unavail->setTransaction($transaction);
				$unavail->Weight = $unavail->Weight + $weight;
				$unavail->Pieces = $unavail->Pieces + $pieces;
				$unavail->UpdateDate = $date;
				$unavail->UpdateID =   $userid;
				if (!$unavail->save()) {
					$transaction->rollback("Can't save unavailable for Vat: $vatID");
				}
			} else {
				$this->logger->log('depleteVat unavailable Failed');
				$this->logger->log('Failed to update Unavailable Inventory');
				return false;
			}
		} catch (\Exception $e) {
			$this->logger->log('Exception: depleteVat unavailable Failed, reason: ');
			$this->logger->log($e->getMessage());
			return false;
		}
		$transaction->commit();

		// TODO: costly, but necessary for now.  Fix this. This is a hack to fix the offered count.
		$this->logger->log("depleteVat fixing offered");
		$thisvat->updateOffered($thisvat->getOffered());

		$this->logger->log("depleteVat Returning True for VAT: $vatID, PCS: $pieces, WGT: $weight");
		return true;
	}

	//  o                     8  o      8          o
	//                        8         8          8
	// o8 odYo. o    o .oPYo. 8 o8 .oPYo8 .oPYo.  o8P .oPYo.
	//  8 8' `8 Y.  .P .oooo8 8  8 8    8 .oooo8   8  8oooo8
	//  8 8   8 `b..d' 8    8 8  8 8    8 8    8   8  8.
	//  8 8   8  `YP'  `YooP8 8  8 `YooP' `YooP8   8  `Yooo'
	// :....::..::...:::.....:..:..:.....::.....:::..::.....:
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::
	private function invalidateOtherOffers($vatID)
	{

		$this->logger->log("Invalidating vat: $vatID");

		$date = substr($this->mysqlDate, 0, 19);
		$vat = Vat::findFirst("VatID = '$vatID'");
		try {
			$avail = $vat->getAvailable()[0]; // Automatically removes sold. Use getAvailable( false ) to get raw value.
		} catch (\Exception $e) {
			$this->logger->log('invalidateOtherOffers: Failed to get Available Inventory');
		}


		// get VatNumber
		$vatNumber = $vat->VatNumber;
		$lotID = $vat->LotID;

		// get LotNumber
		$lot = Lot::findFirst("LotID = '$lotID'");
		$lotNumber = $lot->LotNumber;

		$invalidatedOffers = []; // prevent multiple emails and attempts to invalidate offer.

		$netAvailableInventoryPieces = $avail->InvPieces;
		$netAvailableInventoryWeight = $avail->InvWeight;

		$this->logger->log('invalidateOtherOffers: net available pieces: ' . $netAvailableInventoryPieces);
		$this->logger->log('invalidateOtherOffers: net available weight: ' . $netAvailableInventoryWeight);

		$offerItems = OfferItem::find("LotID = '$lotID'");

		foreach ($offerItems as $offerItem) {
			if ($invalidatedOffers[$offerItem->OfferID]) continue;
			$invalidatedOffers[$offerItem->OfferID] = 1; // prevent multiple emails and attempts to invalidate offer for SAME vat. (not likely)

			$offer = Offer::findFirst("OfferID = '$offerItem->OfferID'");
			if ($offer->OfferStatusPID == Offer::STATUS_SOLD) continue; // Don't touch sold orders
			if ($offer->OfferStatusPID == Offer::STATUS_SHIPPED) continue; // Don't touch shipped orders
			if ($offer->OfferStatusPID == Offer::STATUS_EXPIRED) continue; // prevents another email on new vat (more likely)

			// $offerItemVat = $offerItem->getOfferItemVat( "VatID = '$vatID'" )[0];

			$offerItemVats = $offerItem->getOfferItemVat("VatID = '$vatID'");
			if (!$offerItemVats) continue; // no vat for this offer item
			$offerItemVats = $offerItemVats->toArray();
			if (empty($offerItemVats)) continue; // no vat for this offer item
			$offerItemVat = $offerItemVats[0];

			if ((int)$offerItemVat['Pieces'] + (float)$offerItemVat['Weight'] == 0) continue; // no pieces or weight for this vat

			$this->logger->log("invalidateOtherOffers: OfferItemVat needs " . $offerItemVat['Pieces'] . ' pieces, weight: ' . $offerItemVat['Weight']);

			if (
				(int)$offerItemVat['Pieces'] > (int)$netAvailableInventoryPieces ||
				round($offerItemVat['Weight'], 2) > round($netAvailableInventoryWeight, 2)
			) {
				$this->logger->log('invalidateOtherOffers: Not enough pieces/weight left for offer: ' . $offer->OfferID);

				$offer->OfferStatusPID = Offer::STATUS_EXPIRED;

				$note = "Invalidated by sale of Lot# " . $lotNumber . ",  Vat# " . $vatNumber . " on " . $date;
				$offer->Note = empty($offer->Note) ? $note : "{$offer->Note}\n\n$note";

				$offer->UpdateDate = $this->mysqlDate;
				$offer->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in

				if ($offer->save() == false) {
					$this->logger->log('FAIL!');
					$msg = "Error invalidating offer:\n\n" . implode("\n", $offer->getMessages());
					$this->logger->log("$msg\n");
				} else {
					$this->logger->log('invalidateOtherOffers: Need to send email for invalidated offer');
					if ($offer->UserID) {
						// $this->logger->log("UserID exists");
						// get email and name of sales guy
						$userDetails = User::getUserDetail($offer->UserID);
						$to = $offer->OCSContactEmail ?: $userDetails['Email'];
						if ($to) {
							// $this->logger->log('"$to" exists');
							// PUT THESE IN FOR LIVE
							$to = $userDetails['FullName'] . '<' . $to . '>';
							if ($this->config->settings['environment'] === "DEVELOPMENT") {
								$to = 'Ric <ric@fai2.com>';
							} else {
								$to .= ', Jordan <jordans@oshkoshcheese.com>'; // Jordan still wants these 6/15/2025
							}

							$cust = Customer::findFirst("CustomerID = '$offer->CustomerID'");
							$subject = "Offer for {$cust->Name} invalidated by sale";
							$headers = 'From: ric@fai2.com';

							$baseUrl = $this->config->settings['base_url'];
							$offerLink = "{$baseUrl}offer/edit/{$offer->OfferID}";

							$od = $this->utils->slashDate($offer->OfferDate);
							$ed = $this->utils->slashDate($offer->OfferExpiration);
							$message = <<<EOT
Please note:

  Your offer to {$cust->Name}
	   Offered on $od
	   Expiring $ed
	   Has been $note.

  View offer here: $offerLink

The Cheese Tracker
EOT;
							$this->logger->log('sending mail');
							mail($to, $subject, $message, $headers);
						}
					}
				}
			} // end if for pieces and weight check
			else {
				$this->logger->log('invalidateOtherOffers: ENOUGH pieces/weight left for offer: ' . $offer->OfferID);
			}
		}
	}


	//               8 8
	//               8 8
	// .oPYo. .oPYo. 8 8
	// Yb..   8oooo8 8 8
	//   'Yb. 8.     8 8
	// `YooP' `Yooo' 8 8
	// :.....::.....:....
	// ::::::::::::::::::
	// ::::::::::::::::::

	// Handle "SOLD" Status
	public function sellAction()
	{
		/********************
		! Status drop-down triggers server-side behavior (Button?)
		[-] Update Status of Offer: Contract, Expired, Open, Sold
		[-] Modify offers when converting to sold status
			[-] SaleDate
			[-] Decrement Available (Inventory)
			[-] Increment Unavailable (Inventory)
		[-] Selling an offer invalidates all offers against that sold inventory [Expire?]
		 ****************/
		global $lastOrderLineNum;
		$lastOrderLineNum = 0;

		function buildOfferLine($orderLineTemplate, $offerLine, $customerOrderID) {
			global $lastOrderLineNum;
			/**************
			orderLine= Array(
				[DocID] => 49552
				[EDIKey] => GLC
				[Qty] => 70
				[PartNum] => 103210
				[POLine] => 000010
				[LineNum] => 1
				[Description] => CHSE C PRD 12/2 LUCE
				[ShipToPartNum] => 04365
			)

			 */
			log_dump( $orderLineTemplate, 'Order Item Template' );
			$orderLine = $orderLineTemplate;
			$orderLine['EDIDocID'] = $orderLine['DocID'];
			$orderLine['CustomerOrderID'] = $customerOrderID;
			unset( $orderLine['DocID'] );
			$orderLine['Description'] = Parameter::getValue( $offerLine['DescriptionPID'], 'Value1' );
			unset( $orderLine['EDIKey'] );
			$orderLine['Qty'] = intval($offerLine['PiecesfromVat']);
			if ( $lastOrderLineNum == 0 ) {
				$lastOrderLineNum = intval($orderLine['LineNum'])+1;
			} else {
				$lastOrderLineNum++;
			}
			$orderLine['LineNum'] = intval($lastOrderLineNum);
			if ( strlen(trim($orderLineTemplate['POLine'])) > 3 ) { // zero padded x 10
				$orderLine['POLine'] = sprintf( '%06d', $lastOrderLineNum * 10 );
			} else {
				$orderLine['POLine'] = $lastOrderLineNum;
			}

			$orderLine['PartNum'] = $offerLine['ProductCode'];
			$orderLine['ShipToPartNum'] = $offerLine['ProductCode'];
			log_dump( $orderLine, 'Order Item' );


			return $orderLine;
		}

		$success = 1;
		$saleDate = $this->mysqlDate;

		$offerID = $this->dispatcher->getParam("id");

		$offer = Offer::findFirst("OfferID = '$offerID'");
		if ($offer) {
			if (
				$offer->OfferStatusPID != Offer::STATUS_SOLD
				&& $offer->OfferStatusPID != Offer::STATUS_EXPIRED
				&& $offer->OfferStatusPID != Offer::STATUS_SHIPPED
			) {
				// NOTE: if offer is EDI, check customer order to see if the items match
				$itemsMatch = true;
				if ( CustomerOrder::isEDI($offerID) ) {
					$customerOrder = CustomerOrder::findFirst("OfferID = '$offerID'");
					$detailItems = $customerOrder->getDetails($customerOrder->CustomerOrderID)->toArray();
					// $this->logger->log('Detail Items: ' . print_r($detailItems, true));
					$orderItems = [];
					foreach ($detailItems as $detailItem) {
						$key = $detailItem['PartNum'];
						$orderItems[$key] = $detailItem;
					}

					$offerItemData = Offer::getOfferInfo($this->db, $offerID);
					$offerItems = [];
					foreach ($offerItemData as $item) {
						$offerItems[$item['ProductCode']] = $item;
					}

					// $this->logger->log('Order Items: ' . print_r(array_keys($orderItems), true));
					// $this->logger->log('Offer Items: ' . print_r(array_keys($offerItems), true));

					// check which items from the order don't have the right amount offerred
					$mismatches = [];
					foreach ($offerItems as $key => $offerLine) {
						if ( ! isset( $orderItems[$key] )) {
							$mismatches[$key] = buildOfferLine(end($detailItems), $offerLine, $customerOrder->CustomerOrderID);
							$itemsMatch = false;
						}
					}
				}

				if (!$itemsMatch) {
					$this->logger->log('Sale Offer: ' . $offerID . ' Items do not match customer order! Showing warning message.');
					$success = 0;
					$message = "<p><strong>Warning!</strong></p><p>Some items on this offer do not exist on the EDI order.</p><br>";
					foreach ($mismatches as $key => $value) {
						$message .= "<p>Product <strong>$key</strong> " .
						$value['Description'] .
						"</p>";
					}
				} else {
					$this->logger->log('Sale Offer: ' . $offerID . ' Date: ' . $saleDate);
					$offer->UpdateDate = $saleDate;
					$offer->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
					$offer->SaleDate = $saleDate;
					$offer->OfferStatusPID = Offer::STATUS_SOLD;
					if ($offer->save() == false) {
						$message = "DB error updating offer to SOLD:\n\n" . implode("\n", $offer->getMessages());
						$this->logger->log("$message\n");
						$success = 0;
					} else {
						$itemData = $offer->getOfferItem("OfferID = '$offer->OfferID' AND (Weight > 0 OR Pieces > 0)");
						$this->logger->log('sellAction: Sale Items:');
						foreach ($itemData as $item) {
							$this->logger->log('sellAction:   Get vats for ItemID: ' . $item->OfferItemID);
							$itemVatData = $item->getOfferItemVat("OfferItemID = '$item->OfferItemID' AND (Weight > 0 OR Pieces > 0)");
							foreach ($itemVatData as $vat) {
								$vatID = $vat->VatID;
								$success = $this->_updateSoldUnshipped($vatID);

								if ($success) {
									$this->logger->log("sellAction: invalidateOtherOffers({$vatID})");
									$this->invalidateOtherOffers($vatID);
								}

								// TODO: costly, but necessary for now.  Fix this. This is a hack to fix the offered count.
								$this->logger->log("sellAction fixing offered");
								$thisvat = Vat::findFirst("VatID = '{$vatID}'");
								$thisvat->updateOffered($thisvat->getOffered());
							}
						}

					}
				}
			} else {
				$this->logger->log("Offer was sold or expired, but we'll act like everything went okay.");
			}
		} else {
			$success = 0;
			$message = "Update to SOLD Failed.\nOfferID '$offerID' not found.";
			$this->logger->log("$message\n");
		}

		$this->logger->log("Success sale: " . $success);

		$data = [
			'success' => $success,
			'status' => $success ? 'success' : 'error',
			'msg' => $message,
		];

		if ($mismatches) {
			$data['mismatches'] = $mismatches;
		}

		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	private function _updateSoldUnshipped($vatID)
	{
		$soldItems = Offer::computeTotalSoldItemsForVat($vatID);
		// $this->logger->log( "_updateSoldUnshipped: VatID: {$vatID}\nsoldItems:" );
		// $this->logger->log( $soldItems );
		$date = $this->mysqlDate;
		$userID = $this->session->userAuth['UserID'];

		try {
			// InventoryStatus::STATUS_SOLDUNSHIPPED
			$soldUnshipped = InventoryStatus::findFirst(
				"VatID = '$vatID' AND InventoryStatusPID = '" . InventoryStatus::STATUS_SOLDUNSHIPPED . "'"
			);

			if (!$soldUnshipped) {
				$this->logger->log('sellAction: no soldUnshipped record, creating one');
				$UUID = $this->utils->UUID(mt_rand(0, 65535));
				$soldUnshipped = new InventoryStatus();
				$soldUnshipped->InventoryStatusID = $UUID;
				$soldUnshipped->VatID = $vatID;
				$soldUnshipped->InventoryStatusPID = InventoryStatus::STATUS_SOLDUNSHIPPED;
				$soldUnshipped->CreateDate = $date;
				$soldUnshipped->CreateID = $userID ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in
			}
			$soldUnshipped->Weight = max(floatval($soldItems['Weight']), 0.0); // these were computed from scratch, so no increment
			$soldUnshipped->Pieces = max(intval($soldItems['Pieces']), 0); // these were computed from scratch, so no increment
			$soldUnshipped->UpdateDate = $date;
			$soldUnshipped->UpdateID =   $userID;
			if (!$soldUnshipped->save()) {
				$message = "Error: Can't save soldUnshipped for Vat: $vatID";
				$this->logger->log("$message\n");
				return 0;
			}
		} catch (\Exception $e) {
			$this->logger->log('Exception: soldAction soldUnshipped Failed, reason: ');
			$this->logger->log($e->getMessage());
			return 0;
		}
		return 1; // success value
	}

	//                o        8      .oPYo. 8       o
	//                         8      8      8
	// .oPYo. o    o o8 .oPYo. 8  .o  `Yooo. 8oPYo. o8 .oPYo.
	// 8    8 8    8  8 8    ' 8oP'       `8 8    8  8 8    8
	// 8    8 8    8  8 8    . 8 `b.       8 8    8  8 8    8
	// `YooP8 `YooP'  8 `YooP' 8  `o. `YooP' 8    8  8 8YooP'
	// :....8 :.....::..:.....:..::...:.....:..:::..:..8 ....:
	// :::::8 :::::::::::::::::::::::::::::::::::::::::8 :::::
	// :::::..:::::::::::::::::::::::::::::::::::::::::..:::::

	public function quickshipAction() {

		$offerID = $this->dispatcher->getParam("id");

		if ( CustomerOrder::isEDI($offerID) ) {
			$this->logger->log("quickshipAction: EDI offer, not allowed.");
			$this->view->data = array(
				'success' => 0,
				'status' => 'error',
				'msg' => 'EDI customer, not allowed.'
			);
			$this->view->pick("layouts/json");
			return;
		}
		$this->logger->log( "Selling $offerID");
		$responseobj = $this->dispatcher->callActionMethod(
			$this, // 'OfferContoller'?
			'sellAction',
			['offerID' => $offerID]
		);
		$this->logger->log( $responseobj );

		$this->logger->log( "Shipping $offerID");
		$responseobj = $this->dispatcher->callActionMethod(
			$this, // 'OfferContoller'?
			'depleteinventoryAction',
			['offerID' => $offerID]
		);

		if (json_decode( $responseobj )->success ) {
			$this->logger->log("Updating status to SHIPPED on Offer: {$offerID}");
			try{
				$offerData = Offer::findFirst("OfferID = '{$offerID}'");
				$offerData->OfferStatusPID = Offer::STATUS_SHIPPED;
				// RICDEBUG
				$offerData->save();
			} catch (\Phalcon\Exception $e) {
				$this->logger->log("Exception in confirmshipmentAction updating status to SHIPPED on Offer: {$offerID}");
			}
		}
	}

	// re-open an expired offer

	public function reopenAction() {
		$security = $this->getDI()['securityPlugin'];
		if ( !$security->hasRole(User::SU_ROLE_PID) ) {
			$this->logger->log("Access Denied: User tried to re-open offer without SU role!");
			$response = new Response();
			$response->setStatusCode(403, "Forbidden");
			$response->send();
			return;
		}

		$offerId = $this->dispatcher->getParam("id");
		$success = true;
		$msg = '';

		$desiredStatus = CustomerOrder::isEDI($offerId) ? Offer::STATUS_EDIPENDING : Offer::STATUS_OPEN;
		$desiredStatusName = $desiredStatus == Offer::STATUS_EDIPENDING ? 'EDI PENDING' : 'OPEN';

		//$this->logger->log("Re-opening offer: $offerId as $desiredStatusName");

		try {
			$offerData = Offer::findFirst("OfferID = '{$offerId}'");
			// if offer is already EDI PENDING, do nothing and return success
			if ( $offerData->OfferStatusPID != $desiredStatus ) {
				if ( $offerData->OfferStatusPID != Offer::STATUS_EXPIRED) {
					$this->logger->log("Error: Offer $offerId is not EXPIRED, cannot mark as $desiredStatusName");
					$success = false;
					$msg = "Cannot mark offer as $desiredStatusName, it is not EXPIRED";
				}
				$offerData->OfferStatusPID = $desiredStatus;
				$offerData->save();
			}
		} catch (\Phalcon\Exception $e) {
			$this->logger->log("Exception in setedipendingAction updating status to $desiredStatusName on Offer: $offerId");
			$success = false;
			$msg = "Exception marking offer as $desiredStatusName: " . $e->getMessage();
		}

		$this->view->data = [
			'success' => $success,
			'msg' => $msg,
			'OfferID' => $offerId
		];

		$this->view->pick("layouts/json");
	}

	//                           ooooo o                 o     o          o
	//                             8   8                 8     8          8
	// .oPYo. .oPYo. o    o .oPYo. 8  o8P .oPYo. ooYoYo. 8     8 .oPYo.  o8P .oPYo.
	// Yb..   .oooo8 Y.  .P 8oooo8 8   8  8oooo8 8' 8  8 `b   d' .oooo8   8  Yb..
	//   'Yb. 8    8 `b..d' 8.     8   8  8.     8  8  8  `b d'  8    8   8    'Yb.
	// `YooP' `YooP8  `YP'  `Yooooo8oo 8  `Yooo' 8  8  8   `8'   `YooP8   8  `YooP'
	// :.....::.....:::...:::.....:..::..::.....:..:..:..:::..::::.....:::..::.....:
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	// save changes on offer form
	public function saveitemvatsAction()
	{
		// update OIitemVat, OItem, and Inventory
		// TODO: If update and SOLD, update sold values.

		$this->logger->log('saveitemvatsAction');

		$success = 1;
		$jsonData = $this->request->getPost('jsondata');

		// If the pallets data is set in the json and it's a number
		if ($jsonData['Pallets'] && is_numeric($jsonData['Pallets'])) {
			$pallets = $jsonData['Pallets'];
		} else {
			// If nothing was entered, then just set it to 0
			$pallets = 0;
		}

		// Need OI ID, OIV ID and CostOut

		$offerID = '';
		$itemData = OfferItem::findFirst("OfferItemID = '" . $jsonData['OfferItemID'] . "'");
		if ($itemData) {
			$offer = $itemData->getOffer();
			$offerID = $offer->OfferID;
			$EDIKey = CustomerOrder::isEDI($offerID); // returns the EDIKey or false
		} else {
			$this->view->data = array(
				'success' => 0,
				'status' => 'error',
				'msg' => 'bad offeritemid: ' . $jsonData['OfferItemID']
			);
			$this->logger->log('bad offeritemid: ' . $jsonData['OfferItemID']);
			$this->view->pick("layouts/json");
			return;
		}

		$isSold = ($offer->OfferStatusPID == Offer::STATUS_SOLD);
		$isShipped = ($offer->OfferStatusPID == Offer::STATUS_SHIPPED);
		$lot = Lot::findFirst("LotID = '$itemData->LotID'");
		$availablePallets = $lot->getAvailablePallets(); // TODO: only sell pallets on ship.
		$maxPallets = $itemData->Pallets + $availablePallets; // TODO: only sell pallets on ship.

		// If the number of pallets being added to the OfferItem would put the pallets for the lot into the negative
		if ($pallets > $maxPallets) {
			$message = "The total number of offered pallets must be less than or equal to the number of lot pallets. Available Pallets: $maxPallets";

			$this->view->data = array(
				'success' => 0,
				'status' => 'error',
				'msg' => $message
			);

			$this->logger->log($message);
			$this->view->pick("layouts/json");

			return;
		}

		$itemData->Pallets = $pallets;

		$totals = array('Pieces' => 0, 'Weight' => 0); // , 'Pallets' => 0 );
		$makeDate = null;

		foreach ($jsonData['lines'] as $vat) {
			try {
				$itemVatData = $itemData->getOfferItemVat("OfferItemVatID = '" . $vat['OfferItemVatID'] . "'");
			} catch (\Exception $e) {
				$this->logger->log('Exception for jsondatalinevat:' . $e->getMessage());
			}

			// $itemVatData is historical data, find out how pieces and weight change to adjust inventory records.
			if ($isShipped) {
				$pchange = $vat['pieces'] - $itemVatData[0]->Pieces;
				$wchange = $vat['weight'] - $itemVatData[0]->Weight;

				$this->logger->log("[Shipped Status] VatID: {$itemVatData[0]->VatID} Pieces Change: $pchange, Weight Change: $wchange");

				if ($pchange || $wchange) {
					$success = $this->depleteVat($itemVatData[0]->VatID, $pchange, $wchange, false); // false means don't adjust soldunshipped
				}
			}

			$updateArray = array(
				"UpdateDate" => $this->mysqlDate,
				"UpdateId" => $this->session->userAuth['UserID'], // id of user who is logged in,
				"Pieces" => $vat['pieces'],
				"Weight" => $vat['weight'],
				"Price" => $vat['price']
			);

			try {
				$good = $itemVatData[0]->save($updateArray);

				if (!$good) {
					$this->logger->log('Failure saving itemvats! Messages Follow?');
					foreach ($itemVatData[0]->getMessages() as $message) {
						$this->logger->log('Error saving itemvats - ' . $message);
					}
					$success = 0;
					$message = 'Error saving itemvats. See error log.';
				} else {
					$success = 1;
					$message = 'Success saving itemvats!';
				}

				if ($isSold) {  // Not for Shipped Status, shipped offers don't affect this.
					$this->_updateSoldUnshipped($vat['VatID']);
				}

				$thisvat = Vat::findFirst("VatID = '" . $vat['VatID'] . "'");
				$thisvat->updateOffered($thisvat->getOffered());

				$totals['Pieces'] += $vat['pieces'];
				$totals['Weight'] += $vat['weight'];
				// $totals[ 'Pallets' ] += $vat[ 'estPallets' ];

				if (!$makeDate && $vat['pieces']) $makeDate = $lotvat->MakeDate;
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$message = 'Exception for itemVatData->save:' . $e->getMessage();
				$this->logger->log($message);
				$this->logger->log('Failed Update Array [Offer Item Vat]: ');
				$this->logger->log($updateArray);
				$success = 0;
			}
		}

		error_log( "Pieces: " . $totals[ 'Pieces' ] . " Weight: " . $totals[ 'Weight' ] );

		if ($success == 1) {

			$updateArray = array(
				"UpdateDate" => $this->mysqlDate,
				"UpdateId" => $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000', // id of user who is logged in,
				"Pieces" => $totals['Pieces'],
				"Weight" => $totals['Weight'],
				// "Pallets" => $totals[ 'Pallets' ],
				"Cost" => $jsonData['CostOut'],
				"NoteText" => $jsonData['NoteText']
			);

			if ($makeDate) $updateArray['MakeDate'] = $makeDate;

			try {
				$good = $itemData->save($updateArray);
			} catch (\Exception $e) {
				$this->logger->log('Exception for itemData->save:' . $e->getMessage());
				$this->logger->log('Failed Update Array [Offer Item]: ');
				$this->logger->log($updateArray);
				$good = 0;
			}


			if (!$good) {
				$this->logger->log('Failure saving itemtotals! Messages Follow?');
				foreach ($itemData->getMessages() as $message) {
					$this->logger->log('Error saving itemtotals - ' . $message);
				}
				$success = 0;
				$message = 'Error saving itemtotals. See error log.';
			} else {
				$success = 1;
				$message = 'Success saving itemtotals!';
			}

			if ( $EDIKey && $success ) {
				$partNum = Parameter::getValue( $lot->DescriptionPID, 'Value3') | '';
				Lot::setLastPicked($lot->LotID, $partNum, $lot->CustomerID, $lot->LotNumber);
				$this->logger->log("Lot LastPicked set for $partNum on $lot->LotNumber");
			}
		}

		$this->view->data = array(
			'success' => $success,
			'status' => ($success ? 'success' : 'error'),
			'msg' => $message
		);
		$this->view->pick("layouts/json");
	}




	//                    8          o         o     o          o
	//                    8          8         8     8          8
	// o    o .oPYo. .oPYo8 .oPYo.  o8P .oPYo. 8     8 .oPYo.  o8P
	// 8    8 8    8 8    8 .oooo8   8  8oooo8 `b   d' .oooo8   8
	// 8    8 8    8 8    8 8    8   8  8.      `b d'  8    8   8
	// `YooP' 8YooP' `YooP' `YooP8   8  `Yooo'   `8'   `YooP8   8
	// :.....:8 ....::.....::.....:::..::.....::::..::::.....:::..:
	// :::::::8 :::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::..:::::::::::::::::::::::::::::::::::::::::::::::::::

	public function updatevatAction()
	{
		/* NOTE:
			Some stuff in here with computing price
			Some stuff in here about updating inventory records
			Some stuff in here to update OfferItem to reflect proper totals (piece? Weight? Cost?)
		*/
		$this->logger->log('updatevatAction');

		// TODO: If update and SOLD, update sold values.

		$vatID = $this->dispatcher->getParam("id"); // vatid
		$offerItemID = $_POST["pk"];

		$itemvat = OfferItemVat::findFirst("OfferItemID = '$offerItemID' AND VatID = '$vatID'");
		$success = 0;
		$message = '';

		if ($itemvat) {
			$this->logger->log('Updating itemvat ' . $vatID . "\nNext message should be success or fail.");
			$this->logger->log('  (updating itemvat pk ' . $_POST["pk"] . ')');
			$this->logger->log('  (updating itemvat name ' . $_POST["name"] . ')');
			$this->logger->log('  (updating itemvat value ' . $_POST["value"] . ')');

			$val = $_POST["value"];
			// if ( $_POST["name"] == 'MakeDate' )
			// {
			// 	$val = $this->utils->dbDate( $val );
			// }

			try {
				$good = $itemvat->save(array(
					"UpdateDate" => $this->mysqlDate,
					"UpdateId" => $this->session->userAuth['UserID'], // id of user who is logged in,
					$_POST["name"] => $val
				));
			} catch (\Exception $e) {
				$this->logger->log('Exception saving itemvat: ' . $e->getMessage());
				$good = false;
			}

			$this->logger->log('And the message is: ' . $good ? 'success' : 'fail');

			if (!$good) {
				$this->logger->log('Failure saving itemvat! Messages Follow?');
				foreach ($itemvat->getMessages() as $message) {
					$this->logger->log('Error saving itemvat: ' . $message);
				}
				$success = 0;
				$message = 'Error saving itemvat. See error log.';
			} else {
				$success = 1;
				$message = 'Success saving itemvat!';
			}
		} else {
			$success = 0;
			$message = 'Error saving itemvat: offerItemID: "' . $offerItemID . '" or vatID: "' . $vatID . '" is not valid.';
			$this->logger->log($message);
		}

		$success = 1;
		$message = '';

		$this->view->data = array('success' => $success, 'status' => ($success ? 'success' : 'error'), 'msg' => $message);
		$this->view->pick("layouts/json");
	}



	// 					  8          o         o   o
	// 				  	  8          8         8   8
	// o    o .oPYo. .oPYo8 .oPYo.  o8P .oPYo. 8  o8P .oPYo. ooYoYo.
	// 8    8 8    8 8    8 .oooo8   8  8oooo8 8   8  8oooo8 8' 8  8
	// 8    8 8    8 8    8 8    8   8  8.     8   8  8.     8  8  8
	// `YooP' 8YooP' `YooP' `YooP8   8  `Yooo' 8   8  `Yooo' 8  8  8
	// :.....:8 ....::.....::.....:::..::.....:..::..::.....:..:..:..
	// :::::::8 :::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::..:::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function updateitemAction()
	{
		$this->logger->log('updateitemAction');

		// NOTE: Only currently used for updating item notes.

		$itemID = $this->dispatcher->getParam("id");
		$item = OfferItem::findFirst("OfferItemID = '$itemID'");
		$success = 0;
		$message = '';

		if ($item) {
			$val = $_POST["value"];

			$good = $item->save(array(
				"UpdateDate" => $this->mysqlDate,
				"UpdateId" => $this->session->userAuth['UserID'], // id of user who is logged in,
				$_POST["name"] => $val
			));

			if (!$good) {
				$this->logger->log('Failure saving Item! Messages Follow?');
				foreach ($item->getMessages() as $message) {
					$this->logger->log('Error saving Item - ' . $message);
				}
				$success = 0;
				$message = 'Error saving Item. See error log.';
			} else {
				$success = 1;
				$message = 'Success saving Item!';
			}
		} else {
			$success = 0;
			$message = 'Error saving Item: Item ID: "' . $itemID . '" is not valid.';
			$this->logger->log($message);
		}

		$success = 1;
		$message = '';

		$this->view->data = array('success' => $success, 'status' => ($success ? 'success' : 'error'), 'msg' => $message);
		$this->view->pick("layouts/json");
	}



	//      8        8          o         o   o
	//      8        8          8         8   8
	// .oPYo8 .oPYo. 8 .oPYo.  o8P .oPYo. 8  o8P .oPYo. ooYoYo.
	// 8    8 8oooo8 8 8oooo8   8  8oooo8 8   8  8oooo8 8' 8  8
	// 8    8 8.     8 8.       8  8.     8   8  8.     8  8  8
	// `YooP' `Yooo' 8 `Yooo'   8  `Yooo' 8   8  `Yooo' 8  8  8
	// :.....::.....:..:.....:::..::.....:..::..::.....:..:..:..
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function deleteitemAction()
	{

		// NOTE: This is only for deleting offer lines that have zeros that are created by accident.
		// NOTE: No inventory adjustment needed.
		$this->logger->log('deleteitemAction');


		if ($this->request->isPost() == true) {

			$OfferItemID = $this->request->getPost('OfferItemID');
			$this->logger->log('delete item id ' . $OfferItemID);

			$msg = '';
			$success = 1;

			try {
				$offerItem = OfferItem::findFirst("OfferItemID = '$OfferItemID'");
			} catch (\Exception $e) {
				$msg = 'Exception finding OfferItem: ' . $e->getMessage();
				$this->logger->log($msg);
				$success = 0;
			}


			if ($offerItem) {
				// begin a transaction
				$this->db->begin();

				try {
					$vats = $offerItem->getOfferItemVat();
				} catch (\Exception $e) {
					$msg = 'Exception finding OfferItemVat: ' . $e->getMessage();
					$this->logger->log($msg);
					$success = 0;
				}

				if ($vats) {
					try {
						foreach ($vats as $vat) {
							if ($vat->delete() == false) {
								$msg = "Error deleting offer item vat:\n\n" . implode("\n", $vat->getMessages());
								$this->logger->log("error deleting offer item vat: \n\n");
								$this->logger->log($msg);
								$this->db->rollback();
								$this->view->data = array('success' => 0, 'msg' => $msg);
								$this->view->pick("layouts/json");
								return;
							}
						}

						if ($offerItem->delete() == false) {
							$msg = "Error deleting offer item:\n\n" . implode("\n", $offerItem->getMessages());
							$this->logger->log("error deleting offer item: \n\n");
							$this->logger->log($msg);
							$this->db->rollback();
							$this->view->data = array('success' => 0, 'msg' => $msg);
							$this->view->pick("layouts/json");
							return;
						}
					} catch (\Exception $e) {
						$msg = 'Exception deleting offer items: ' . $e->getMessage();
						$this->logger->log($msg);
						$success = 0;
					}

					// save changes to the db
					$this->db->commit();
				} else {
					$success = 0;
					$msg = "ERROR: No Offer Item Vats Found.";
				}
			}


			$this->view->data = array('success' => $success, 'msg' => $msg);
			$this->view->pick("layouts/json");
		}
	}



	//                       o
	//
	// .oPYo. `o  o' .oPYo. o8 oPYo. .oPYo.
	// 8oooo8  `bd'  8    8  8 8  `' 8oooo8
	// 8.      d'`b  8    8  8 8     8.
	// `Yooo' o'  `o 8YooP'  8 8     `Yooo'
	// :.....:..:::..8 ....::....:::::.....:
	// ::::::::::::::8 :::::::::::::::::::::
	// ::::::::::::::..:::::::::::::::::::::

	public function expireAction()
	{

		$log = '';

		$success = 1;
		$msg = '';

		$offerData = Offer::find("OfferExpiration <= NOW() AND OfferStatusPID = '" . Offer::STATUS_OPEN . "'");
		$log .= 'Found ' . count($offerData->toArray()) . ' orders to expire.' . "\n";

		$this->db->begin();

		foreach ($offerData as $offer) {
			//      set offer to expired
			$offer->OfferStatusPID = Offer::STATUS_EXPIRED;
			$log .= 'Expiring Offer: ' . $offer->OfferID . "\n";
			if ($offer->save() == false) {
				$message = "DB error updating offer to EXPIRED:\n\n" . implode("\n", $offer->getMessages());
				$log .= "$message\n";
				$success = 0;
			} else {

				$items = $offer->getOfferItem();
				foreach ($items as $item) {
					$log .= 'Offer Item: ' . $item->OfferItemID . "\n";
					$vats = $item->getOfferItemVat()->toArray();
					foreach ($vats as $vat) {
						if ($vat['Pieces'] > 0) // if this offer actually affected this vat
						{
							$lotvat = Vat::findFirst("VatID = '" . $vat['VatID'] . "'");
							$log .= 'Reconciling Vat: ' . $lotvat->VatNumber . "\n";
							$lotvat->updateOffered($lotvat->getOffered());
						}
					}
				}
			}
			continue;
		}


		if ($success == 1) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}

		$this->logger->log($log);

		$this->view->data = array(
			'success' => $success,
			'status' => ($success ? 'success' : 'error'),
			'msg' => $msg,
			'log' => $log
		);
		$this->view->pick("layouts/json");
	}


	//        o     o               o  d'b                 o
	//        8b   d8                  8                   8
	// .oPYo. 8`b d'8 .oPYo. odYo. o8 o8P  .oPYo. .oPYo.  o8P
	// 8    8 8 `o' 8 .oooo8 8' `8  8  8   8oooo8 Yb..     8
	// 8    8 8     8 8    8 8   8  8  8   8.       'Yb.   8
	// 8YooP' 8     8 `YooP8 8   8  8  8   `Yooo' `YooP'   8
	// 8 ....:..::::..:.....:..::..:..:..:::.....::.....:::..:
	// 8 :::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ..:::::::::::::::::::::::::::::::::::::::::::::::::::::


	public function printmanifestAction()
	{
		$offerId = $this->dispatcher->getParam("id");

		$offerData = Offer::getOfferInfo($this->db, $offerId);
		$tryUseLP = $offerData[0]['CustomerID'] == '80C1E632-0EB3-4613-89FA-B96ED59AEB4D'; // we will try to use LPs for Saputo orders, if they are set
		$this->logger->log('Offer Manifest - Try to use LPs? ' . ($tryUseLP ? 'YES' : 'NO') . "\n");

		$testData = array();

		$userId = $offerData[0]['UserID'];
		$salesRep = User::findFirst("UserID = '$userId'");

		$locations = []; // lot number => location string
		$itemIdentifierColumns = []; // lot number => list of which 2 columns to display from (LicensePlate, CustomerLotNumber, VatNumber)
		$totPieces = 0;
		$totWeight = 0.0;
		$totPallets = 0;

		$offerItemId = '';
		$hasProductCode = false;

		foreach ($offerData as $offer) {
			if ($offer['OfferItemID'] != $offerItemId) {
				$offerItemId = $offer['OfferItemID'];

				$totPieces += $offer['OfferItemPieces'];
				$totWeight += $offer['OfferItemWeight'];
				$totPallets += $offer['OfferItemPallets'];

				// $lotNumber = $offer[LotNumber]; // 49000 for example
				// $lotId = Lot::findFirst("LotNumber = $lotNumber")->LotID;

				$lotId = $offer['LotID'];
				$locations[$offer['LotNumber']] = Lot::getLocation( $lotId );
				$hasData = FALSE;

				if (empty($itemIdentifierColumns[$offer['LotNumber']])) {
					$itemIdentifierColumns[$offer['LotNumber']] = ['LicensePlate', 'CustomerLotNumber'];
				}

				if (!$tryUseLP || empty($offer['LicensePlate'])) {
					$itemIdentifierColumns[$offer['LotNumber']] = ['CustomerLotNumber', 'VatNumber'];
				}

				$tests = array();

				$testGroup = ContaminentTestGroup::findFirst("LotID = '$lotId'");

				if ($offer['ProductCode']) {
					$hasProductCode = true;
					// $this->logger->log($offer['ProductCode']);
				}

				if ($testGroup) {
					$testGroupTests = $testGroup->getContaminentTest(array('order' => 'TestPerformedPID'));
					foreach ($testGroupTests as $testGroupTest) {
						$test = array(
							"TestPerformed" => Parameter::getValue($testGroupTest->TestPerformedPID),
							"TestResults" => Parameter::getValue($testGroupTest->TestResultsPID)
						);

						if ($test['TestResults']) $hasData = TRUE;

						array_push($tests, $test);
					}

					if ($hasData) $testData[$lotId] = $tests;
				}
			}
		}

		// $this->logger->log('ITEM IDENTIFIER COLUMNS:');
		// $this->logger->log($itemIdentifierColumns);
		// $this->logger->log(array_map(function ($v) { return array_map(function ($vv) use ($v) { return $v[$vv]; }, ['LicensePlate', 'CustomerLotNumber', 'VatNumber']); }, $offerData));
		// $this->logger->log("LOCATIONS:");
		// $this->logger->log($locations);
		$this->view->locations = $locations;
		$this->view->itemIdentifierColumns = $itemIdentifierColumns;
		$this->view->hasProductCode = $hasProductCode;
		$this->view->offerData = $offerData;
		$this->view->salesRep = $salesRep ? $salesRep->toArray() : [];
		$this->view->totPieces = $totPieces;
		$this->view->totPallets = $totPallets;
		$this->view->totWeight = number_format($totWeight, 2);
		$this->view->testData = $testData;
	}


	//        .oPYo.  d'b  d'b
	//        8    8  8    8
	// .oPYo. 8    8 o8P  o8P  .oPYo. oPYo.
	// 8    8 8    8  8    8   8oooo8 8  `'
	// 8    8 8    8  8    8   8.     8
	// 8YooP' `YooP'  8    8   `Yooo' 8
	// 8 ....::.....::..:::..:::.....:..::::
	// 8 :::::::::::::::::::::::::::::::::::
	// ..:::::::::::::::::::::::::::::::::::

	public function printofferAction()
	{
		$offerId = $this->dispatcher->getParam("id");

		$this->logger->log("Print " . $offerId);

		$offerData = Offer::getOfferInfo($this->db, $offerId);

		$testData = array();

		$userId = $offerData[0]['UserID'];
		$salesRep = User::findFirst("UserID = '$userId'");

		$totPieces = 0;
		$totWeight = 0.0;

		$offerItemId = '';
		foreach ($offerData as $offer) {
			if ($offer['OfferItemID'] != $offerItemId) {
				$offerItemId = $offer['OfferItemID'];

				$totPieces += $offer['OfferItemPieces'];
				$totWeight += $offer['OfferItemWeight'];
				$lotId = $offer['LotID'];

				$hasData = FALSE;

				$tests = array();

				$testGroup = ContaminentTestGroup::findFirst("LotID = '$lotId'");
				if ($testGroup) {
					$testGroupTests = $testGroup->getContaminentTest(array('order' => 'TestPerformedPID'));
					foreach ($testGroupTests as $testGroupTest) {
						$test = array(
							"TestPerformed" => Parameter::getValue($testGroupTest->TestPerformedPID),
							"TestResults" => Parameter::getValue($testGroupTest->TestResultsPID)
						);

						if ($test['TestResults']) $hasData = TRUE;

						array_push($tests, $test);
					}

					if ($hasData) $testData[$lotId] = $tests;
				}
			}
		}


		$this->logger->log("Offer Data to Print.");
		$this->logger->log($offerData);


		$this->view->offerData = $offerData;
		$this->view->pick("offer/printoffer");
		$this->view->salesRep = $salesRep ? $salesRep->toArray() : array();
		$this->view->totPieces = $totPieces;
		$this->view->totWeight = number_format($totWeight, 2);
		$this->view->testData = $testData;
	}


	// .oPYo.               o
	// 8    8
	// 8      o    o oPYo. o8 odYo. .oPYo.
	// 8      8    8 8  `'  8 8' `8 8    8
	// 8    8 8    8 8      8 8   8 8    8
	// `YooP' `YooP' 8      8 8   8 `YooP8
	// :.....::.....:..:::::....::..:....8
	// :::::::::::::::::::::::::::::::ooP'.
	// :::::::::::::::::::::::::::::::...::


	public function curinginvoiceAction()
	{
		$offerId = $this->dispatcher->getParam("id");

		if ($offerId) $offer = Offer::findFirst("OfferID = '$offerId'");

		if (!$offer) {
			$this->logger->log("No Offer found.");

			$response = new Response();
			$response->setStatusCode(404, "Not Found");
			$response->send();

			return;
		}

		$bol = $offer->getBillOfLading();

		require_once(dirname(__FILE__) . "/../../lib/PHPExcel/PHPExcel.php");

		$phpxl = PHPExcel_IOFactory::load(dirname(__FILE__) . "/../../assets/curing-invoice-template.xlsx");

		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$offerItems = $offer->getOfferItem();

		if (empty($offerItems)) {
		}

		$offerItemCount = count($offerItems);
		$rowsPerSheet = 18;
		$maxSheets = 20;


		$sheet = $phpxl->getSheet(0);


		if ($offerItemCount > 1) {
			$sums = ['H15'];

			$greyRowFill = [
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'startcolor' => ['rgb' => '969696']
			];

			for ($i = 1; $i < $offerItemCount; $i++) {
				$off = $i * $rowsPerSheet;

				for ($j = 1; $j <= $rowsPerSheet; $j++) {
					$this->utils->copyXLRow($sheet, $sheet, $j, $off + $j);
				}

				$sheet->mergeCellsByColumnAndRow(1, $off + 3, 2, $off + 3);
				$sheet->mergeCellsByColumnAndRow(1, $off + 9, 2, $off + 9);
				$sheet->mergeCellsByColumnAndRow(1, $off + 10, 2, $off + 10);
				$sheet->mergeCellsByColumnAndRow(6, $off + 9, 7, $off + 9);
				$sheet->mergeCellsByColumnAndRow(6, $off + 10, 7, $off + 10);

				$greyRow = $off - 1;

				$sheet->getStyle("A$greyRow:I$greyRow")->getFill()->applyFromArray($greyRowFill);
				$sheet->getRowDimension($greyRow)->setRowHeight(6);

				$sumRow = $off + 15;
				$startSum = $off + 9;
				$endSum = $off + 13;

				// $this->logger->log( PHPExcel_Calculation::getInstance()->parseFormula("=SUM(G$startSum:H$endSum)") );

				// $sheet->getCellByColumnAndRow(7, $sumRow)->setValue("=SUM(G$startSum:H$endSum)");

				$sums[] = "H$sumRow";
			}

			$off = $offerItemCount * $rowsPerSheet;

			$sheet->getCellByColumnAndRow(3, $off)->setValue("TOTAL");
			$sheet->getCellByColumnAndRow(4, $off)->setValue("=SUM(" . implode(',', $sums) . ")");
			$sheet->getStyleByColumnAndRow(4, $off)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE); // alternatively: _("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)

			$sheet->getStyle("D$off:E$off")->applyFromArray([
				'font' => [
					'bold' => true,
					'size' => 12
				],
				'alignment' => [
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
				]
			]);
		}


		$sheet->setTitle('INVOICING');

		$off = 0; // row offset

		foreach ($offerItems as $i => $offerItem) {

			$this->logger->log("Item $i");

			$off = $i * $rowsPerSheet;

			$lot = $offerItem->getLot();

			// $sheet->getCellByColumnAndRow(1, 3)->setValue($bol->CustomerPO ?: $lot->CustomerPONumber);
			$sheet->getCellByColumnAndRow(4, $off + 3)->setValue($lot->LotNumber);
			$sheet->getCellByColumnAndRow(7, $off + 3)->setValue($bol->ShipperNumber);

			$sheet->getCellByColumnAndRow(1, $off + 5)->setValue($lot->Cost);
			$sheet->getCellByColumnAndRow(4, $off + 5)->setValue($lot->FirstMonthRate);
			$sheet->getCellByColumnAndRow(7, $off + 5)->setValue($lot->AdditionalMonthRate);

			$sheet->getCellByColumnAndRow(1, $off + 7)->setValue(substr($lot->DateIn, 0, 10));
			$sheet->getCellByColumnAndRow(4, $off + 7)->setValue(substr($offer->OfferDate, 0, 10));

			$sheet->getCellByColumnAndRow(1, $off + 9)->setValue($offerItem->Weight);
			$sheet->getCellByColumnAndRow(1, $off + 10)->setValue($offerItem->Weight);


			$sheet->getCellByColumnAndRow(1, $off + 12)->setValue($offerItem->Pieces);
			$sheet->getCellByColumnAndRow(1, $off + 13)->setValue(Parameter::getValue($lot->InventoryTypePID));
			$sheet->getCellByColumnAndRow(1, $off + 14)->setValue($lot->getOwnedBy()->Name);
			$sheet->getCellByColumnAndRow(1, $off + 15)->setValue($lot->getCustomer()->Name);

			// $sheet->getCellByColumnAndRow(5, $off + 12)->setValue($lot->Pallets);


			$sheet->getStyleByColumnAndRow(1, $off + 7)->getNumberFormat()->setFormatCode('mm/dd/yyyy');
			$sheet->getStyleByColumnAndRow(4, $off + 7)->getNumberFormat()->setFormatCode('mm/dd/yyyy');
			$sheet->getStyleByColumnAndRow(1, $off + 9)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$sheet->getStyleByColumnAndRow(1, $off + 10)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$sheet->getStyleByColumnAndRow(1, $off + 12)->getNumberFormat()->setFormatCode('#,##0');
		}

		// capture the output of the save method and put it into $content as a string
		ob_start();
		$objWriter = PHPExcel_IOFactory::createWriter($phpxl, "Excel2007");
		$objWriter->save("php://output");

		$content = ob_get_contents();
		ob_end_clean();

		// Getting a response instance
		$response = new Response();

		// $response->setHeader("Content-Type", "application/vnd.ms-excel");
		$response->setHeader("Content-Type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"); // for Excel2007
		$response->setHeader('Content-Disposition', 'attachment; filename="curing_invoice.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}

	public function transfertolotAction() {
		$this->logger->log("Transfer initiated: Offer -> Lot");
		$offerId = $this->dispatcher->getParam("id");

		if ($offerId) {
			$offer = Offer::findFirst("OfferID = '$offerId'");
		}

		$response = new Response();
		$response->setHeader('Content-Type', 'application/json');

		if (!$offer) {
			$this->logger->log("Error in OfferController::transfertolotAction: No Offer found.");
			$response->setStatusCode(404, "Not Found");
			$response->setContent(json_encode([
				'success' => 0,
				'msg' => 'Offer not found.'
			]));
			$response->send();
			return;
		}

		if (!Offer::isSingleSkuOffer($offerId)) {
			$this->logger->log("Error in OfferController::transfertolotAction: Offer is not a single-SKU offer.");
			$response->setStatusCode(400, "Bad Request");
			$response->setContent(json_encode([
				'success' => 0,
				'msg' => 'Offer items must all be the same product.'
			]));
			$response->send();
			return;
		}

		$errorResponse = function($msg) use ($response) {
			$this->db->rollback();
			$this->logger->log($msg);
			$response->setStatusCode(500, "Internal Server Error");
			$response->setContent(json_encode([
				'success' => 0,
				'msg' => 'Internal Server Error'
			]));
			$response->send();
			return;
		};

		$currentTime = $this->mysqlDate;
		$currentUser = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000';
		$inventoryStatusParameters = Parameter::getValuesForGroupId('8E321110-EA4D-4FF1-A70A-5E7AC496DDA8');

		$offerData = Offer::getOfferInfo($this->db, $offer->OfferID);
		$originalLot = Lot::findFirst("LotID = '{$offerData[0]['LotID']}'");

		$this->db->begin();

		try {
			// create draft lot
			$this->logger->log("Creating draft lot...");
			$lot = new Lot();
			$lotId = $this->utils->UUID(mt_rand(0, 65535));
			$lot->LotID = $lotId;
			$lot->DateIn = $offer->OfferDate;
			$lot->StatusPID = Lot::STATUS_DRAFT;
			$lot->TransferredFrom = 'O:' . $offer->OfferID;
			$lot->DescriptionPID = $originalLot->DescriptionPID;
			$lot->InventoryTypePID = $originalLot->InventoryTypePID; //? do we need to do this?
			$lot->WarehouseID = $originalLot->WarehouseID;
			$lot->NoteText = 'TRANSFERRED FROM OFFER ON ' . date('m/d/Y');
			$lot->CreateDate = $currentTime;
			$lot->CreateId = $currentUser;
			$this->logger->log("Saving draft lot " . $lot->LotID);
			if ($lot->save() == false) {
				$errorResponse('Error saving lot: ' . print_r($lot->getMessages(), true));
				return;
			}

			// create copies of the vats on the offer
			$this->logger->log("Creating vats...");
			foreach ($offerData as $offerLine) {
				$vat = Vat::findFirst("VatID = '{$offerLine['VatID']}'");
				$this->logger->log("Cloning vat " . $vat->VatID);
				$newVat = $vat->clone($lotId);
				$newVat->Pieces = $offerLine['PiecesfromVat'];
				$newVat->Weight = $offerLine['WeightfromVat'];
				$this->logger->log("Saving new vat " . $newVat->VatID);
				if ($newVat->save() == false) {
					$errorResponse('Error saving vat: ' . print_r($newVat->getMessages(), true));
					return;
				}

				// create InventoryStatus records for the vat
				foreach ($inventoryStatusParameters as $inventoryStatusParameter) {
					$newInventoryStatusRecord = new InventoryStatus();
					$newInventoryStatusRecord->InventoryStatusID = $this->utils->UUID(mt_rand(0, 65535));
					$newInventoryStatusRecord->VatID = $newVat->VatID;
					$newInventoryStatusRecord->Pieces = 0;
					$newInventoryStatusRecord->Weight = 0;
					$newInventoryStatusRecord->InventoryStatusPID = $inventoryStatusParameter['ParameterID'];
					$newInventoryStatusRecord->CreateDate = $currentTime;
					$newInventoryStatusRecord->CreateID = $currentUser;

					if ($inventoryStatusParameter['ParameterID'] == InventoryStatus::STATUS_AVAILABLE) {
						$newInventoryStatusRecord->Pieces = $offerLine['PiecesfromVat'];
						$newInventoryStatusRecord->Weight = $offerLine['WeightfromVat'];
					}

					$this->logger->log("Saving new inventory status record " . $newInventoryStatusRecord->InventoryStatusID);
					if ($newInventoryStatusRecord->save() == false) {
						$errorResponse('Error saving inventory status: ' . print_r($newInventoryStatusRecord->getMessages(), true));
						return;
					}
				}
			}

			// update the offer status
			$this->logger->log("Updating offer status...");
			$offer->Transferred = 1;
			$offer->OfferStatusPID = Offer::STATUS_SHIPPED;
			$offer->UpdateDate = $currentTime;
			$offer->UpdateId = $currentUser;
			if ($offer->save() == false) {
				$errorResponse('Error saving offer: ' . print_r($offer->getMessages(), true));
				return;
			}
			$this->logger->log("Done.");
		} catch (\Exception $e) {
			$errorResponse('Exception in OfferController::transfertolotAction: ' . $e->getMessage());
			return;
		}

		//$errorResponse('rolling back for dev');
		//return;

		$this->db->commit();

		$response->setStatusCode(200, "OK");
		$response->setContent(json_encode([
			'success' => 1,
			'lotId' => $lotId,
		]));
		$response->send();
	}
}
