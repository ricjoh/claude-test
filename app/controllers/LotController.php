<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class LotController extends Controller
{
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
		$s = ($status == Lot::STATUS_DRAFT) ? 'Draft ' : '';
		// $this->logger->log( "List ${s}Lots" );

		$fields = ['LotID', 'EDIDocID', 'LotNumber', 'CustomerName', 'CustomerPONumber', 'ProductCode', 'Description', 'DateIn', 'Vats', 'Pieces', 'Weight'];

		$lots = Lot::getLotsByStatus($status);
		// $this->logger->log('Got ' . count( $lots ) . ' lot(s).' );
		$rows = array();
		$ids = array();
		foreach ($lots as $lot) {

			// $this->logger->log( $lot[ 'LotID' ] );
			$row = array();
			foreach ($fields as $f) {

				if (false !== strpos($f, 'EDIDocID')) { // any date
					if (!empty($lot['TransferredFrom'])) {
						$isTransferredFromOffer = substr($lot['TransferredFrom'], 0, 1) == 'O';
						$transferredFromID = substr($lot['TransferredFrom'], 2);
						$lot[$f] = '<a href="/' . ($isTransferredFromOffer ? 'offer' : 'lot') . '/edit/' . $transferredFromID . '" class="transfer-link">FROM ' . ($isTransferredFromOffer ? 'OFFER' : 'LOT') . '</a>';
					}
				}

				if (false !== strpos($f, 'Date')) { // any date
					$lot[$f] = substr($lot[$f], 0, 10);
				}

				if ($f == 'CustomerName') {
					$lot[$f] = substr($lot[$f], 0, 11);
				}

				if ($f == 'LotID') {
					array_push($ids, ':' . $lot[$f]);
				} else {
					array_push($row, $lot[$f]);
				}
			}

			array_push($row, '<a href="#" class="deleteLot" data-lotid="' . $lot['LotID'] . '">Delete</a>');
			array_push($rows, $row);
		}

		$this->view->headers = ['EDI#', 'Lot#', 'Customer', 'PO#', 'Prod', 'Description', 'Date In', 'Vats', 'Pcs', 'Weight', ''];
		$this->view->ids = $ids;
		$this->view->data = $rows;

		$this->view->title = "{$s} Lots";
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
		$lot = new Lot();
		$paramModel = new Parameter();

		$lotId = $this->dispatcher->getParam("id");
		$isJustTransferred = ($this->request->getQuery('transferred', 'int', 0)) > 0;
		$this->logger->log('isJustTransferred: ' . $isJustTransferred);
		if ($isJustTransferred) {
			$this->flash->success("Successfully Created Draft Lot.");
		}

		if ($lotId == 'NEW') {
			$lotId = 0;
		}

		$this->view->LotID = $lotId;
		$vatStats = new stdClass;
		$totals = (object) array(
			'totPieces' => 0,
			'totWeight' => 0,
			'availPieces' => 0,
			'availWeight' => 0
		);

		$this->view->billingFrequencyArray = Lot::getBillingFrequencyArray();

		if ($lotId) {
			$lotData = $lot->findFirst("LotID = '$lotId'");

			$this->view->title = "Lot " . $lotData->LotNumber;
			$this->view->vats = $lotData->getVat(array('order' => 'MakeDate, VatNumber'));

			// Entered Pieces, Available Pieces, Entered Weight, Available Weight

			// Note: 1 Query Here Per Iteration of the Loop.
			foreach ($this->view->vats as $v) {
				$status = $v->getAvailable();
				$vatStats->{$v->VatID} = new stdClass;
				$vatStats->{$v->VatID}->InvPieces = $status[0]->InvPieces;
				$vatStats->{$v->VatID}->InvWeight = $status[0]->InvWeight;
				$totals->totPieces += $v->Pieces;
				$totals->totWeight += $v->Weight;
				$totals->availPieces += $status[0]->InvPieces;
				$totals->availWeight += $status[0]->InvWeight;
			}

			$this->view->availablePallets = $lotData->getAvailablePallets();
			$this->view->minimumPallets = $lotData->getUsedPallets();
		} else {
			$this->view->title = "New Lot";
			$lotData = (object) array(
				'LotNumber' => '',
				'CustomerID' => NULL,
				'FactoryID' => NULL,
				'VendorID' => NULL,
				'StatusPID' => Lot::STATUS_EXISTING
			);
			$this->view->vats = array();
		}

		// "c" prefix is "computed" or "constructed", i.e. added to object

		// $this->logger->log( 'Spot 1');



		$selectPromptElement = array('ParameterID' => '', 'Value1' => 'Select...');

		$rooms =  $paramModel->getValuesForGroupId(
			'46AE19E9-0230-4306-AF82-D15C763C6673',
		);
		array_unshift($rooms, $selectPromptElement);
		$this->view->rooms = $rooms;

		$itypes = $paramModel->getValuesForGroupId(
			'13BB5520-1B36-4783-823B-F4C1A114BCB9',
		);
		array_unshift($itypes, $selectPromptElement);
		$this->view->itypes = $itypes;

		$descs = $paramModel->getValuesForGroupId(
			Parameter::PARAMETER_GROUP_DESCS,
		);
		array_unshift($descs, $selectPromptElement);
		$this->view->descs = $descs;

		$statii = $paramModel->getValuesForGroupId(
			'5F7730A1-AF76-4E3D-9B62-35872908944E',
		);
		$this->view->statii = $statii;

		$lotRooms = array();

		for ($i = 1; $i <= 3; $i++) {
			$Num	= ($i != 1) ? $i : ""; // e.x. RoomPID, RoomPID2, RoomPID3
			$RoomEl	= "RoomPID$Num";
			$TempEl	= "RoomTemp$Num";
			$SiteEl	= "Site$Num";

			if ($lotData->$RoomEl || $lotData->$SiteEl) {
				array_push(
					$lotRooms,
					array(
						'RoomPID'	=> $lotData->$RoomEl,
						'RoomTemp'	=> $lotData->$TempEl,
						'Site'		=> $lotData->$SiteEl
					)
				);
			}
		}

		if (count($lotRooms) == 0)
			$lotRooms = array(array('RoomPID' => '', 'Site' => ''));

		$transferredToLotId = false;
		if ($lotData->Transferred) {
			$transferredToLot = Lot::findFirst("TransferredFrom = 'L:{$lotData->LotID}'");
			$transferredToLotId = $transferredToLot->LotID;
		}

		$transferredFromLink = false;
		if ($lotData->TransferredFrom) {
			$isTransferredFromOffer = substr($lotData->TransferredFrom, 0, 1) == 'O';
			$transferredFromID = substr($lotData->TransferredFrom, 2);
			$transferredFromLink = '<a href="/' . ($isTransferredFromOffer ? 'offer' : 'lot') . '/edit/' . $transferredFromID . '" class="transfer-link">' . ($isTransferredFromOffer ? 'OFFER' : 'LOT') . '</a>';
		}

		$security = $this->getDI()['securityPlugin'];
		$this->view->creator = '';
		if ( $security->hasRole(User::SU_ROLE_PID) ) {
			$user = User::getUserDetail($lotData->CreateId);
			$this->view->creator = "{$user['FirstName']} {$user['LastName']}";
		}

		$this->view->DRAFTMODE = false;
		if ($lotData->StatusPID == Lot::STATUS_DRAFT and isset($lotData->LotID)) {
			$this->view->DRAFTMODE = true;
			$ddr = new DeliveryDetailReceipt();
			$did = $ddr->getDeliveryID($lotData->LotID);
			if ($did)	$this->view->deliveryurl = '/delivery/incomingdetail/' . $did;
		}


		// will include this customer even if not current if not new Lot.
		$this->view->customers = Customer::getActiveCustomers($lotData->CustomerID);

		// will include this factory even if not current if not new Lot.
		$this->view->factories = Factory::getActiveFactories($lotData->FactoryID);

		// will include this Vendor even if not current if not new Lot.
		$this->view->vendors = Vendor::getActiveVendors($lotData->VendorID);

		$this->view->warehouses = Warehouse::getActiveWarehouses();

		$this->view->lotId = $lotId;
		$this->view->lotData = $lotData;
		$this->view->lotRooms = $lotRooms;
		$this->view->vatStats = $vatStats;
		$this->view->lotTotals = $totals;
		$this->view->transferredTo = $transferredToLotId;
		$this->view->transferredFromLink = $transferredFromLink;
		$this->view->logger = $this->logger;
		$this->view->utils = $this->utils;
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
			// $this->logger->log( '*********************** Posted *************************' );
			// $this->logger->log( $_REQUEST );

			$LotID = $this->request->getPost('LotID');
			if (!$LotID) {
				$LotID = 0;
			}

			// $this->logger->log( $this->utils->dbDate( $this->request->getPost('DateIn') ) );

			$updateFields = array(
				'LotNumber',
				'DescriptionPID',
				'Cost',
				'DateIn',
				'ProductCode',
				'InventoryTypePID',
				'CustomerID',
				'CustomerPONumber',
				'VendorID',
				'FactoryID',
				'RoomPID',
				'FirstMonthRate',
				'AdditionalMonthRate',
				'Handling',
				'HandlingUnit',
				'Storage',
				'StorageUnit',
				'Pallets',
				'BillingFrequency',
				'RoomTemp',
				'Site',
				'RoomPID2',
				'RoomTemp2',
				'Site2',
				'RoomPID3',
				'RoomTemp3',
				'Site3',
				'NoteText',
				'WarehouseID',
				'OwnedBy',
				'TempChangeDate',
			);

			$newLotID = '';
			$lot = Lot::findFirst("LotID = '$LotID'");
			if (!$lot) { // add new lot

				$LotNumber = $this->request->getPost('LotNumber');
				$exists = Lot::findFirst("LotNumber = '$LotNumber'");
				if ($exists) {
					$this->view->data = array('success' => 0, 'msg' => "That lot number already exists.\nPlease pick another one.");
					$this->view->pick("layouts/json");
					return;
				}

				// $this->logger->log( 'new lot' );
				$lot = new Lot();
				$newLotID = $this->utils->UUID(mt_rand(0, 65535));
				$lot->LotID = $newLotID;
				$lot->CreateDate = $this->mysqlDate;
				$lot->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
			} else { // update existing lot
				// $this->logger->log( 'found lot' );
				$lot->UpdateDate = $this->mysqlDate;
				$lot->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}

			$usedPallets = $lot->getUsedPallets();

			if ($this->request->getPost('Pallets') && $this->request->getPost('Pallets') < $usedPallets) {
				$this->view->data = array(
					'success' => 0,
					'msg' => "The number of lot pallets must be greater than or equal to the number of offered pallets. Offered Pallets: " . $usedPallets
				);

				$this->view->pick("layouts/json");
				return;
			}

			// $this->logger->log( 'update fields' );

			foreach ($updateFields as $f) {
				if ($f == 'DateIn') {
					$lot->DateIn = $this->utils->dbDate($this->request->getPost('DateIn'));
				} elseif ($f == 'TempChangeDate') {
					$tmpchgDate = $this->request->getPost('TempChangeDate');
					$lot->TempChangeDate = $tmpchgDate ? $this->utils->dbDate($tmpchgDate) : null;
				} elseif ($f == 'Pallets') {
					$lot->{$f} = $this->request->getPost($f) ?: new \Phalcon\Db\RawValue('default');
				} elseif ($f == 'OwnedBy') {
					$lot->{$f} = $this->request->getPost($f) ?: null;
				} else {
					$lot->{$f} = $this->request->getPost($f);
				}
			}

			$saveFromDraft = false;
			if ($lot->StatusPID == Lot::STATUS_DRAFT) {
				$lot->StatusPID = Lot::STATUS_EXISTING;
				$saveFromDraft = true;
			}

			$this->db->begin();

			try {
				$this->logger->log("Saving lot:{$lot->LotID}.");
				$success = 1;
				if ($lot->save() == false) {
					$msg = "Error saving lot: {$lot->LotID}\n\n" . implode("\n", $lot->getMessages());
					$this->logger->log("$msg\n");
					$success = 0;
				}
			} catch (\Exception $e) {
				$msg = "Exception saving lot: {$lot->LotID}\n\n" . implode("\n", $lot->getMessages());
				$this->logger->log("$msg\n");
				$success = 0;
			}

			if ($success == 1) {
				$this->db->commit();
			} else {
				$this->db->rollback();
			}

			$this->logger->log('Lot save? ' . (($success == 1) ? 'Yes!' : 'No.'));

			if ($success == 1 && $lot->TransferredFrom && $saveFromDraft) {
				// deplete original vats
				$this->logger->log("Lot created from a transfer. Depleting original vats.");
				$transferredFromID = substr($lot->TransferredFrom, 2);
				$isTransferredFromOffer = substr($lot->TransferredFrom, 0, 1) == 'O';
				$this->logger->log("Transferred from: {$lot->TransferredFrom}");
				$depleteVatIds = [];
				if ($isTransferredFromOffer) {
					$offerData = Offer::getOfferInfo($this->db, $transferredFromID);
					foreach($offerData as $offerLine) {
						$depleteVatIds[$offerLine['VatID']] = [
							'Pieces' => $offerLine['PiecesfromVat'],
							'Weight' => $offerLine['WeightfromVat'],
						];
					}
				} else { // transferred from lot
					$vats = Vat::find("LotID = '$transferredFromID'");
					foreach($vats as $vat) {
						$avail = $vat->getAvailable()[0];
						$depleteVatIds[$vat->VatID] = [
							'Pieces' => $avail->InvPieces,
							'Weight' => $avail->InvWeight,
						];
					}
				}

				$this->logger->log("Vat depletion data: " . print_r($depleteVatIds, true));

				$offerController = new OfferController();
				foreach ($depleteVatIds as $vatId => ['Pieces' => $pieces, 'Weight' => $weight]) {
					$this->logger->log("Depleting $pieces Pieces ($weight#) from vat $vatId");
					$offerController->depleteVat($vatId, $pieces, $weight, false);
				}
			}

			$this->view->data = array(
				'success' => $success,
				'status' => ($success ? 'success' : 'error'),
				'msg' => $msg,
				'newLotID' => $newLotID,
				'reload' => $saveFromDraft
			);
			$this->view->pick("layouts/json");
		}
	}


	//      8        8          o
	//      8        8          8
	// .oPYo8 .oPYo. 8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8 8 8oooo8   8  8oooo8
	// 8    8 8.     8 8.       8  8.
	// `YooP' `Yooo' 8 `Yooo'   8  `Yooo'
	// :.....::.....:..:.....:::..::.....:
	// :::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::

	public function deleteAction() {
		if ($this->request->isPost() == true) {

			$LotID = $this->request->getPost('LotID');
			$userDetails = User::getUserDetail($this->session->userAuth['UserID']);
			$this->logger->log("Delete Lot Action | ID: $LotID | User: {$userDetails['LoginID']} | Time: " . date('Y-m-d H:i:s'));

			$msg = '';
			$success = 1;

			$lot = Lot::findFirst("LotID = '$LotID'");

			if ($lot != false && $lot->StatusPID == Lot::STATUS_DRAFT) {
				try {
					// begin a transaction
					$this->db->begin();

					// if it's a transfer, set offer status back to open and delete the vats and inventory status records associated with the lot
					if ($lot->TransferredFrom) {
						$transferredFromID = substr($lot->TransferredFrom, 2);
						$isTransferredFromOffer = substr($lot->TransferredFrom, 0, 1) == 'O';
						$this->logger->log("Transferred from " . ($isTransferredFromOffer ? 'Offer' : 'Lot') . ": {$transferredFromID}\nResetting status...");
						if ($isTransferredFromOffer) {
							$offer = Offer::findFirst("OfferID = '$transferredFromID'");
							$offer->Transferred = 0;
							$offer->OfferStatusPID = Offer::STATUS_OPEN;
							if ($offer->save() == false) {
								$msg = "Error saving offer:\n\n" . implode("\n", $offer->getMessages());
								$success = 0;
								$this->logger->log($msg);
								$this->db->rollback();
							}
						} else { // transferred from lot
							$originalLot = Lot::findFirst("LotID = '$transferredFromID'");
							$originalLot->Transferred = 0;
							$originalLot->StatusPID = Lot::STATUS_EXISTING;
							if ($originalLot->save() == false) {
								$msg = "Error saving lot:\n\n" . implode("\n", $originalLot->getMessages());
								$success = 0;
								$this->logger->log($msg);
								$this->db->rollback();
							}
						}

						if ($success == 1) {
							$this->logger->log("Deleting vats and inventory status records from Lot...");
							$vats = Vat::find("LotID = '$LotID'");
							foreach ($vats as $vat) {
								if ($success == 0) break;

								if ($vat->delete() == false) {
									$msg = "Error deleting vat:\n\n" . implode("\n", $vat->getMessages());
									$success = 0;
									$this->logger->log($msg);
									$this->db->rollback();
									break;
								} else {
									$inventoryStatuses = InventoryStatus::find("VatID = '$vat->VatID'");
									foreach ($inventoryStatuses as $inventoryStatus) {
										if ($inventoryStatus->delete() == false) {
											$msg = "Error deleting inventory status:\n\n" . implode("\n", $inventoryStatus->getMessages());
											$success = 0;
											$this->logger->log($msg);
											$this->db->rollback();
											break;
										}
									}
								}
							}
						}
					}

					if ($success == 1) {
						if ($lot->delete() == false) {
							$msg = "Error deleting lot:\n\n" . implode("\n", $lot->getMessages());
							$success = 0;
							$this->logger->log($msg);
							$this->db->rollback();
						} else {
							// save changes to the db
							$this->db->commit();
						}
					}
				} catch (\Exception $e) {
					$msg = "Error deleting lot: \n\n" . $e->getMessage();
					$success = 0;
					$this->logger->log($msg);
				}
			}

			$this->view->data = array('success' => $success, 'msg' => $msg);
			$this->view->pick("layouts/json");
		}
	}

	//                              o         ooo.                 d'b   o
	//                              8         8  `8.               8     8
	// .oPYo. oPYo. .oPYo. .oPYo.  o8P .oPYo. 8   `8 oPYo. .oPYo. o8P   o8P
	// 8    ' 8  `' 8oooo8 .oooo8   8  8oooo8 8    8 8  `' .oooo8  8     8
	// 8    . 8     8.     8    8   8  8.     8   .P 8     8    8  8     8
	// `YooP' 8     `Yooo' `YooP8   8  `Yooo' 8ooo'  8     `YooP8  8     8
	// :.....:..:::::.....::.....:::..::.....:.....::..:::::.....::..::::..:
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	// http://devtracker.oshkoshcheese.com/lot/createdraft/12/24
	public function createdraftAction()
	{
		$DeliveryID = $this->dispatcher->getParam("id");
		$this->logger->log("DeliveryID: $ref");
		$EDIDocID = $this->dispatcher->getParam("relid");
		$this->logger->log("EDIDocID: $EDIDocID");

		// get delivery header
		$delivery = Delivery::findfirst("DeliveryID = $DeliveryID")->toArray();

		$ediKey = EDIDocument::getEDIKeyByEDIDocID($EDIDocID);



		$customer = Customer::getEDICustomer($ediKey);
		$vendor = Vendor::findFirst("EDIKey = '$ediKey'");
		$factory = Factory::findFirst("EDIKey = '$ediKey'");

		// get delivery details
		$details =  DeliveryDetail::find("DeliveryID = $DeliveryID")->toArray();
		// group by part number
		$parts = array();
		$partspallets = array();
		foreach ($details as $detail) {

			// get receive detail
			$receipt = DeliveryDetailReceipt::findFirst("DeliveryDetailID = " . $detail['DeliveryDetailID'])->toArray();

			if (!isset($parts[$detail['PartNum']]))
				$parts[$detail['PartNum']] = array();

			// each detail will become a vat
			// only create a vat for qty > 0
			if ($receipt['ReceivedQty'] > 0) {
				array_push(
					$parts[$detail['PartNum']],
					array(
						'DeliveryDetailID' => $detail['DeliveryDetailID'],
						'LineNum' => $detail['LineNum'],
						'CustomerLot' => $detail['CustomerLot'],
						'ExpDate' => $detail['ExpDate'],
						'NetWeight' => $detail['NetWeight'],
						'RecdQty' => $receipt['ReceivedQty'],
						'ShipQty' => $detail['Qty'],
						'ReceivedDate' => $receipt['ReceivedDate'],
						'ReceiptID' => $receipt['ReceiptID']
					)
				);
				if (!isset($partspallets[$detail['PartNum']]))  $partspallets[$detail['PartNum']] = 0;
				$partspallets[$detail['PartNum']] += $detail['PalletCount'];

				$this->logger->log("createdraftAction part: {$detail['PartNum']} with {$partspallets[$detail['PartNum']]} pallets so far.");
			}
		}

		$this->logger->log('Draft Lot Parts:');
		$this->logger->log($parts);

		// each partnum becomes a lot
		foreach ($parts as $partnum => $lines) {


			$newLotID = '';
			$lot = false;
			$lot = Lot::findFirst("DeliveryID = $DeliveryID AND ProductCode = '$partnum'");
			if (!$lot) { // add new lot
				$lot = new Lot();
				$newLotID = $this->utils->UUID(mt_rand(0, 65535));
				$this->logger->log("New draft lot ----------------- $newLotID");
				$lot->LotID = $newLotID;
				$lot->CreateDate = $this->mysqlDate;
				$lot->CreateId = '00000000-0000-0000-0000-000000000000';
			} else {
				// update existing lot
				$this->logger->log('found lot'); // TODO: Scream and Bail if Status not Draft
				$lot->UpdateDate = $this->mysqlDate;
				$lot->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}

			$this->logger->log('update fields');

			$proddata = Parameter::findFirst("ParameterGroupID = '" . Parameter::PARAMETER_GROUP_DESCS . "' AND Value4 = '$ediKey' AND Value3 = '$partnum'");

			if ($proddata) {
				$proddesc = $proddata->toArray();
				$this->logger->log("PRODDATA:");
				$this->logger->log($proddesc);
			} else {
				$this->logger->log('FAIL!');
				$msg = "No Description Parameter set for PART# $partnum";
				$this->logger->log("$msg\n");
				$success = 0;
			}

			$lot->DescriptionPID = $proddesc['ParameterID'];
			$lot->DateIn = $this->utils->dbDate($delivery['ReceivedDate']);
			$lot->ProductCode = $partnum;
			$lot->Pallets = $partspallets[$partnum];
			// SET-ASIDE: 61D4197D-22EB-4B50-8D5F-B94C07CB6509
			$lot->InventoryTypePID = '85102C3F-33C1-482A-9750-4FB4A5EA7A7B'; // AGING
			$lot->CustomerID = $customer->CustomerID;
			$lot->CustomerPONumber = $delivery['OrderNum'];

			$lot->VendorID = $ediKey == "SAP" ? "5A3C8EBC-18C8-4A07-A197-F791B3D740EE" : "test";

			// If we didn't get this, then we need to skip setting it
			if ($factory !== false) {
				$lot->FactoryID = $factory->FactoryID;
			}
			$wh = Warehouse::findFirst("Name like '%" . $delivery['Warehouse'] . "%'");
			$warehouseID = $wh ? $wh->WarehouseID : 2; // Plymouth default
			$lot->WarehouseID = $warehouseID;
			$lot->OwnedBy = 2;
			$lot->StatusPID = Lot::STATUS_DRAFT;
			$lot->DeliveryID = $DeliveryID;

			// $ediKey = EDIDocument::getEDIKeyByEDIDocID($delivery->EDIDocID);

			// $this->logger->log("ediKey");
			// $this->logger->log($ediKey);

			$lot->NoteText = "EDI #{$EDIDocID}";
			$lot->NoteText .= ($ediKey == "GLC" ? ". MAKE DATE IS REALLY EXP DATE." : '');

			// Map LP -> Pallet Count per lot not overal pallets for shipment. This is done above.
			// $totalPalletCount = array_sum(array_column($details, 'PalletCount'));
			// $lot->Pallets = $totalPalletCount;



			$this->logger->log("saving lot ...");
			// $this->logger->log( $lot->toArray() );

			$success = 1;
			try {
				if ($lot->save() == false) {
					$this->logger->log('FAIL!');
					$msg = "Error saving draft lot:\n\n" . implode("\n", $lot->getMessages());
					$this->logger->log("$msg\n");
					$success = 0;
				}
			} catch (\Exception $e) {
				$this->logger->log("Error saving lot: " . $e->getMessage());
			}

			$vat = 1;
			foreach ($lines as $line) {
				// add vat for item, qty, makedate
				// POST to /vat/addvat  $this->util->POST( $url, $data );
				// Trigger add vat

				$pieceweight = !empty($proddesc['Value2']) ? floatval($proddesc['Value2']) : false;

				$lineweight = 0; // init
				if ($pieceweight) {
					$this->logger->log("pieceweight found: $pieceweight");
					$lineweight = $line['ShipQty'] * $pieceweight;
				} else {
					$weight_percent = 1;
					if ($line['ShipQty'] > 0) {
						$weight_percent =  $line['RecdQty'] /  $line['ShipQty']; // Recompute Weight if partial Receive
						$lineweight = $line['NetWeight'] * $weight_percent;
					}
				}
				$this->logger->log("lineweight: $lineweight");

				$this->logger->log("createdraftAction() line[ 'CustomerLot' ]" . $line['CustomerLot']);

				$postdata = array(
					'DeliveryDetailID' => $line['DeliveryDetailID'],
					'LotID' => $lot->LotID,
					'MakeDate' => $line['ExpDate'],
					'Pieces' => $line['RecdQty'],
					'Weight' => $lineweight,
					'VatNumber' => 'EDI ' . $vat++,
					'CustomerLotNumber' => $line['CustomerLot'],
					'NoteText' => $ediKey == "GLC" ? "EDI LOT/VAT. MAKE DATE IS REALLY EXP DATE. PO " . $delivery['OrderNum'] . " LINE " . $line['LineNum'] : "",
				);

				$this->logger->log('Create Vat: postdata Array');
				$this->logger->log($postdata);


				$this->logger->log('New Vat Data:');
				$this->logger->log($postdata);

				try {
					$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
					$url = $protocol . $_SERVER['HTTP_HOST'] . "/vat/addvat";
					$this->logger->log("Creating Vat by url: $url");
					$responseobj = $this->utils->POST($url, $postdata);
					$status = $responseobj['success'];
				} catch (\Phalcon\Exception $e) {
					$msg = "Exception adding vat in createDraftAction:\n" . $e->getMessage();
					$this->logger->log("$msg\n");
				}

				$this->logger->log("Add Vat success: $status");
				if (!$status) {
					$this->logger->log($responseobj['msg']);
					$success = 0;
					$msg = $responseobj['msg']; // TODO: FUCKING BAIL OUT!
				}

				$receiptrec = DeliveryDetailReceipt::findFirst($line['ReceiptID']);
				if ($receiptrec) {
					$receiptrec->LotID = $lot->LotID;
					$receiptrec->EDISent = 1;

					try {
						$receiptrec->save();
					} catch (\Phalcon\Exception $e) {
						$this->DC->logger->log($e->getMessage());
						return;
					}
				} else {
					$this->logger->log('Receipt ' . $line['ReceiptID'] . ' not found.');
				}
			}
		} // next part number

		$this->logger->log("Vats saved? Success = $success");

		$this->view->data = array(
			'success' => $success,
			'status' => ($success ? 'success' : 'error'),
			'msg' => $msg,
			'newLotID' => $newLotID
		);
		$this->view->pick("layouts/json");
	}

	//                 o  o     o          o
	//                 8  8     8          8
	// .oPYo. .oPYo.  o8P 8     8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8   8  `b   d' .oooo8   8  Yb..
	// 8    8 8.       8   `b d'  8    8   8    'Yb.
	// `YooP8 `Yooo'   8    `8'   `YooP8   8  `YooP'
	// :....8 :.....:::..::::..::::.....:::..::.....:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::

	public function getvatsAction()
	{
		$lotId = $this->dispatcher->getParam("id");

		$vatStats = new stdClass;
		$totals = (object) array(
			'lotPieces' => 0,
			'lotWeight' => 0,
			'lotAvailPieces' => 0,
			'lotAvailWeight' => 0
		);

		$vatLps = [];
		if ($lotId == '00000000-0000-0000-0000-000000000000') {
			$this->view->vats = array();
			$lotId = 'NEW';
		} else {
			$lotData = Lot::findFirst("LotID = '$lotId'");
			$this->view->vats = $lotData->getVat(array('order' => 'MakeDate, 0+VatNumber, VatNumber, CustomerLotNumber'));

			// Entered Pieces, Available Pieces, Entered Weight, Available Weight
			foreach ($this->view->vats as $v) {
				$status = $v->getAvailable();
				$vatStats->{$v->VatID} = new stdClass;
				$vatStats->{$v->VatID}->InvPieces = $status[0]->InvPieces;
				$vatStats->{$v->VatID}->InvWeight = $status[0]->InvWeight;
				$vatStats->{$v->VatID}->isOffered = $v->isOffered();
				$totals->lotPieces += $v->Pieces;
				$totals->lotWeight += $v->Weight;
				$totals->lotAvailPieces += $status[0]->InvPieces;
				$totals->lotAvailWeight += $status[0]->InvWeight;
				if (!empty($v->DeliveryDetailID)) {
					$vatLps[$v->VatID] = DeliveryDetail::getLicensePlate($this->db, $this->logger, $v->DeliveryDetailID);
				}
			}
		}

		$this->view->lotId = $lotId;
		$this->view->vatStats = $vatStats;
		$this->view->lotTotals = $totals;
		$this->view->vatLps = $vatLps;
		$this->view->utils = $this->utils;
		$this->view->logger = $this->logger;
		$this->view->pick("lot/ajax/getvats");
	}


	// 8          o  ooooo          o         8
	// 8          8    8            8         8
	// 8 .oPYo.  o8P   8   .oPYo.  o8P .oPYo. 8 .oPYo.
	// 8 8    8   8    8   8    8   8  .oooo8 8 Yb..
	// 8 8    8   8    8   8    8   8  8    8 8   'Yb.
	// 8 `YooP'   8    8   `YooP'   8  `YooP8 8 `YooP'
	// ..:.....:::..:::..:::.....:::..::.....:..:.....:
	// ::::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::

	public function getlottotalsAction()
	{
		$lotId = $this->dispatcher->getParam("id");

		$vatStats = new stdClass;
		$totals = (object) array(
			'lotPieces' => 0,
			'lotWeight' => 0,
			'lotAvailPieces' => 0,
			'lotAvailWeight' => 0
		);

		if ($lotId == '00000000-0000-0000-0000-000000000000') {
			$this->view->vats = array();
			$lotId = 'NEW';
		} else {
			$lotData = Lot::findFirst("LotID = '$lotId'");
			$this->view->vats = $lotData->getVat(array('order' => 'MakeDate, VatNumber'));

			// Entered Pieces, Available Pieces, Entered Weight, Available Weight
			foreach ($this->view->vats as $v) {
				$status = $v->getAvailable();
				$vatStats->{$v->VatID} = new stdClass;
				$vatStats->{$v->VatID}->InvPieces = $status[0]->InvPieces;
				$vatStats->{$v->VatID}->InvWeight = $status[0]->InvWeight;
				$vatStats->{$v->VatID}->isOffered = $v->isOffered();
				$totals->lotPieces += $v->Pieces;
				$totals->lotWeight += $v->Weight;
				$totals->lotAvailPieces += $status[0]->InvPieces;
				$totals->lotAvailWeight += $status[0]->InvWeight;
			}
		}

		$this->view->data = array(
			'lotPieces' => $totals->lotPieces,
			'lotWeight' => $totals->lotWeight,
			'lotAvailPieces' => $totals->lotAvailPieces,
			'lotAvailWeight' => $totals->lotAvailWeight
		);
		$this->view->pick("layouts/json");
	}


	//                 o       .oo          o   o         o   o
	//                 8      .P 8          8                 8
	// .oPYo. .oPYo.  o8P    .P  8 .oPYo.  o8P o8 o    o o8  o8P o    o
	// 8    8 8oooo8   8    oPooo8 8    '   8   8 Y.  .P  8   8  8    8
	// 8    8 8.       8   .P    8 8    .   8   8 `b..d'  8   8  8    8
	// `YooP8 `Yooo'   8  .P     8 `YooP'   8   8  `YP'   8   8  `YooP8
	// :....8 :.....:::..:..:::::..:.....:::..::..::...:::..::..::....8
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::...::

	public function getactivityAction()
	{
		$this->view->pick("lot/ajax/getactivity");

		$lotId = $this->dispatcher->getParam("id");
		$this->view->lotId = $lotId;

		$activity = array();
		if ($lotId == '00000000-0000-0000-0000-000000000000' || !$lotId) {
			$lotId = 'NEW';
		} else {
			$lotData = Lot::findFirst("LotID = '$lotId'");
			$activity = $lotData->getLotActivity();

			$totals = (object) array(
				'totPieces' => 0,
				'totWeight' => 0.0,
				'totPallets' => $lotData->Pallets,
			);

			$vats = $lotData->getVat(array('order' => 'MakeDate, VatNumber'));

			// Entered Pieces, Available Pieces, Entered Weight, Available Weight
			foreach ($vats as $v) {
				// $status = $v->getAvailable();
				$totals->totPieces += $v->Pieces;
				$totals->totWeight += $v->Weight;
				// $totals->availPieces += $status[0]->InvPieces;
				// $totals->availWeight += $status[0]->InvWeight;
			}

			$this->view->activity = $activity;
			$this->view->totals = $totals;
		}
	}


//               o         o       .oo          o   o         o   o
//                         8      .P 8          8                 8
// .oPYo. oPYo. o8 odYo.  o8P    .P  8 .oPYo.  o8P o8 o    o o8  o8P o    o
// 8    8 8  `'  8 8' `8   8    oPooo8 8    '   8   8 Y.  .P  8   8  8    8
// 8    8 8      8 8   8   8   .P    8 8    .   8   8 `b..d'  8   8  8    8
// 8YooP' 8      8 8   8   8  .P     8 `YooP'   8   8  `YP'   8   8  `YooP8
// 8 ....:..:::::....::..::..:..:::::..:.....:::..::..::...:::..::..::....8
// 8 ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
// ..::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::...::

	public function printactivityAction()
	{
		$lotId = $this->dispatcher->getParam("id");
		$this->view->lotId = $lotId;

		if ($lotId == '00000000-0000-0000-0000-000000000000' || !$lotId) {
			$this->view->activity = array();
			$lotId = 'NEW';
		} else {
			$lotData = Lot::findFirst("LotID = '$lotId'");
			$this->view->activity = $lotData->getLotActivity();

			$totals = (object) array(
				'totPieces' => 0,
				'totWeight' => 0.0,
				'availPieces' => 0,
				'availWeight' => 0.0,
				'totPallets' => $lotData->Pallets,
			);

			$vats = $lotData->getVat(array('order' => 'MakeDate, VatNumber'));

			// Entered Pieces, Available Pieces, Entered Weight, Available Weight
			foreach ($vats as $v) {
				$status = $v->getAvailable();
				$totals->totPieces += $v->Pieces;
				$totals->totWeight += $v->Weight;
				$totals->availPieces += $status[0]->InvPieces;
				$totals->availWeight += $status[0]->InvWeight;
			}

			$this->view->totals = $totals;
		}
	}


//                                   8      .oPYo.  .oPYo.        o         o
//                                   8          `8  8    8                  8
// .oPYo. .oPYo. .oPYo. oPYo. .oPYo. 8oPYo.    oP' o8YooP' oPYo. o8 odYo.  o8P
// Yb..   8oooo8 .oooo8 8  `' 8    ' 8    8 .oP'    8      8  `'  8 8' `8   8
//   'Yb. 8.     8    8 8     8    . 8    8 8'      8      8      8 8   8   8
// `YooP' `Yooo' `YooP8 8     `YooP' 8    8 8ooooo  8      8      8 8   8   8
// :.....::.....::.....:..:::::.....:..:::.........:..:::::..:::::....::..::..:
// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function printsearchAction()
	{

		$searchController = new SearchController();
		$lots = $searchController->lotsSearch();
		$searchFeedback = $this->getSearchFeedback('html');
		$printCost = $this->request->getPost('PrintSummaryWCost');
		$printTemp = $this->request->getPost('PrintSummaryWTemp');
		$printPallets = $this->request->getPost('PrintSummaryWPallets');

		if ($printPallets) {
			foreach ($lots as $key => $lot) {
				$lots[$key]['availablePallets'] = Lot::getAvailablePalletsByLotID($lot['LotID']);
				// TODO: does math need to be done for palette results in search?
				// $usedPallets = Lot::getUsedPalletsByLotID($lot->LotID);
				// $lots[$key]['availablePallets'] = $lot['Pallets'] - $usedPallets;
			}
		}

		$this->view->printPallets = $printPallets;
		$this->view->printCost = $printCost;
		$this->view->printTemp = $printTemp;
		$this->view->lots = $lots;
		$this->view->sortList = $this->request->getPost('sortList');
		$this->view->searchFeedback = $searchFeedback['searchFeedback'];
		$this->view->fieldsHtml = $searchFeedback['fieldsHtml'];
	}

	public function getSearchFeedback($type)
	{
		$fields = array(
			'LotNumber' => array('label' => 'Lot #'),
			'WarehouseID' => array(
				'label' => 'Warehouse',
				'function' => function ($WarehouseID) {
					$whnames = ''; // default for none or all selected
					if ($WarehouseID && ($count = count($WarehouseID))) {
						$lotModel = new Lot();
						$lotWarehouse = $lotModel->getLotWarehouseLookup();
						if ($count != count($lotWarehouse)) {
							$whnames = '';
							foreach ($WarehouseID as $id) $whnames .= $lotWarehouse[$id] . ',';
							$whnames = rtrim($whnames, ','); // strip trailing comma
						} // else all are selected
					}
					return $whnames;
				}
			),
			'OwnedBy' => array(
				'label' => 'Owned By',
				'function' => function ($OwnedBy) {
					$whnames = ''; // default for none or all selected
					if ($OwnedBy && ($count = count($OwnedBy))) {
						$lotModel = new Lot();
						$lotWarehouse = $lotModel->getLotWarehouseLookup();
						if ($count != count($lotWarehouse)) {
							$obnames = '';
							foreach ($OwnedBy as $id) $obnames .= $lotWarehouse[$id] . ',';
							$obnames = rtrim($obnames, ','); // strip trailing comma
						} // else all are selected
					}
					return $obnames;
				}
			),
			'CustomerID' => array(
				'label' => 'Customer',
				'function' => function ($customerId) {
					$customerData = Customer::findFirst(
						array(
							'columns' => 'Name',
							'conditions' => 'CustomerID = :CustomerID:',
							'bind' => array('CustomerID' => $customerId)
						)
					);
					return $customerData->Name;
				}
			),
			'DescriptionPID' => array(
				'label' => 'Description',
				'function' => function ($val) {
					return Parameter::getValue($val);
				}
			),
			'RoomPID' => array(
				'label' => 'Room #',
				'function' => function ($val) {
					return Parameter::getValue($val);
				}
			),
			'VendorID' => array(
				'label' => 'Vendor',
				'function' => function ($VendorID) {
					$vendor = Vendor::findFirst(
						array(
							'columns' => 'Name',
							'conditions' => 'VendorID = :VendorID:',
							'bind' => array('VendorID' => $VendorID)
						)
					);
					return $vendor->Name;
				}
			),
			'FactoryID' => array(
				'label' => 'Factory',
				'function' => function ($FactoryID) {
					$factory = Factory::findFirst(
						array(
							'columns' => 'Name',
							'conditions' => 'FactoryID = :FactoryID:',
							'bind' => array('FactoryID' => $FactoryID)
						)
					);
					return $factory->Name;
				}
			),
			'InventoryTypePID' => array(
				'label' => 'Inventory Type',
				'function' => function ($val) {
					return Parameter::getValue($val);
				}
			),
			'OnlyAvailable' => array(
				'label' => 'Inventory Status',
				'function' => function ($val) {
					$returnVal = 'All (Includes zero qty)';
					if ($val == 'available') {
						$returnVal = 'Only Available';
					}
					return $returnVal;
				}
			),
			'IncludeSoldUnshipped' => array(
				'label' => 'Include Sold',
				'function' => function ($val) {
					$returnVal = 'No';
					if ($val == 'on') {
						$returnVal = 'Yes';
					}
					return $returnVal;
				}
			),
			'MakeDateFrom' => array('label' => 'Make Date From'),
			'MakeDateTo' => array('label' => 'Make Date To'),
			'DateInFrom' => array('label' => 'Date In From'),
			'DateInTo' => array('label' => 'Date In To'),
			'ProductCode' => array('label' => 'Product Code'),
			'CustomerPONumber' => array('label' => 'PO #'),
			'MaxItems' => array('label' => 'Max Items'),
			'RoomTemperature' => array('label' => 'Temperature')
		);
		$criteriaFeedback = array();
		$returnArr = array();
		$fieldsHtml = '';
		foreach ($fields as $fieldName => $data) {
			$val = $this->request->getPost($fieldName);
			if ($fieldName == 'WarehouseID' || $fieldName == 'OwnedBy') {
				// "function" returns '' on all or no warehouse set. Both return data for all warehouses.
				if (isset($data['function']) && $label = $data['function']($val)) {
					array_push($criteriaFeedback, '<b>' . $data['label'] . ':</b> ' . $label);
					// this is in the if because if all are selected, we dont' need to do anything
					//   as "none" selects the same data.
					foreach ($val as $v) {
						$fieldsHtml .= '<input type="hidden" name="' . $fieldName . '[]" value="' . $v . '" />';
						echo '<input type="hidden" name="' . $fieldName . '[]" value="' . $v . '" />';
					}
				}
			} else if ($val || $fieldName == 'OnlyAvailable') {
				$fieldsHtml .= '<input type="hidden" name="' . $fieldName . '" value="' . $val . '" />';
				echo '<input type="hidden" name="' . $fieldName . '" value="' . $val . '" />';
				if (isset($data['function'])) {
					$val = $data['function']($val);
				}
				array_push($criteriaFeedback, '<b>' . $data['label'] . ':</b> ' . $val);
			}
		}

		$separator = '&nbsp;&nbsp;';
		if ($type == 'xls') {
			// have to trick PHPExcel_Helper_HTML into honoring spaces
			// extra whitespace is ignored by default
			$separator = '<font> </font><font> </font><font> </font>';
		}
		$returnArr['fieldsHtml'] = $fieldsHtml;
		$searchFeedback = implode($separator, $criteriaFeedback);
		$returnArr['searchFeedback'] = $searchFeedback;

		return $returnArr;
	}


	//                                   8      .oPYo.  o    o  o     .oPYo.
	//                                   8          `8  `b  d'  8     8
	// .oPYo. .oPYo. .oPYo. oPYo. .oPYo. 8oPYo.    oP'   `bd'   8     `Yooo.
	// Yb..   8oooo8 .oooo8 8  `' 8    ' 8    8 .oP'     .PY.   8         `8
	//   'Yb. 8.     8    8 8     8    . 8    8 8'      .P  Y.  8          8
	// `YooP' `Yooo' `YooP8 8     `YooP' 8    8 8ooooo .P    Y. 8oooo `YooP'
	// :.....::.....::.....:..:::::.....:..:::...........::::..:......:.....:
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	// INVENTORY: Report


	public function searchtoxlsAction()
	{

		require_once(dirname(__FILE__) . "/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Inventory');

		// get mast with company name/address/contact info
		$mast = $this->utils->getExcelMast(array('sheet' => $sheet, 'datePrintedColumn' => 'K'));
		$rows = $mast['rows'];
		$sheet = $mast['sheet'];

		$printCost = $this->request->getPost('printCost');
		$printTemp = $this->request->getPost('printTemp');
		$printPallets = $this->request->getPost('printPallets');

		$rowStart = 6;
		$afterRowStart = $rowStart + 1;
		$dateInFieldRange = 'H' . $afterRowStart . ':H';
		$piecesFieldRange = 'F' . $afterRowStart . ':F';
		$weightFieldRange = 'G' . $afterRowStart . ':G';
		$lastColumn = 'M';
		$piecesColumn = 'F';
		$wtColumn = 'G';
		$dateInColumn = 'H';

		if ($printCost) {
			$dateInFieldRange = 'I' . $afterRowStart . ':I';
			$piecesFieldRange = 'G' . $afterRowStart . ':G';
			$weightFieldRange = 'H' . $afterRowStart . ':H';
			$lastColumn = 'N';
			$piecesColumn = 'G';
			$wtColumn = 'H';
			$dateInColumn = 'I';
		}

		if ($printPallets) {
			$palletsFieldRange = 'F' . $afterRowStart . ':F';
			$piecesFieldRange = 'G' . $afterRowStart . ':G';
			$weightFieldRange = 'H' . $afterRowStart . ':H';
			$dateInFieldRange = 'I' . $afterRowStart . ':I';
			$lastColumn = 'N';
			$piecesColumn = 'G';
			$wtColumn = 'H';
			$dateInColumn = 'I';
		}

		if ($printCost) {
			$lastColumn = 'N';
		}

		// feedback of what form fields were selected to generate the report
		$searchFeedback = $this->getSearchFeedback('xls');
		$wizard = new PHPExcel_Helper_HTML;
		$richText = $wizard->toRichTextObject($searchFeedback['searchFeedback']);
		$xlRow = array($richText);
		array_push($rows, $xlRow);

		// header columns
		$columns = array();
		array_push($columns, 'Lot #');
		array_push($columns, 'Customer');
		array_push($columns, 'Description');
		array_push($columns, 'Make Date');
		if ($printCost) {
			array_push($columns, 'Cost');
		}
		array_push($columns, 'Factory');
		$printPallets && array_push($columns, 'Pallets');
		array_push($columns, 'Pieces');
		array_push($columns, 'Net Weight');
		array_push($columns, 'Date In');
		array_push($columns, 'Product Code');
		array_push($columns, 'Room #');
		if ($printTemp) {
			array_push($columns, 'Temp');
		}
		array_push($columns, 'Site');
		array_push($columns, 'Inv Type');
		array_push($columns, 'PO #');
		array_push($rows, $columns);

		$style = array(
			'font' => array(
				'bold' => true,
			)
		);
		$sheet->getStyle($rowStart)->applyFromArray($style);

		// get lot data
		$searchController = new SearchController();
		$lots = $searchController->lotsSearch();
		$lotCount = count($lots);

		if ($printPallets) {
			foreach ($lots as $key => $lot) {
				$lots[$key]['availablePallets'] = Lot::getAvailablePalletsByLotID($lot['LotID']);
				// TODO: does math need to be done for palette results in xls?
				// $usedPallets = Lot::getUsedPalletsByLotID($lot->LotID);
				// $lots[$key]['availablePallets'] = $lot['Pallets'] - $usedPallets;
			}
		}

		// add lot data to sheet
		$totalPieces = 0;
		$totalWeight = 0;
		$totalPallets = 0;
		$rowEnd = $rowStart;

		if ($lotCount) {
			foreach ($lots as $l) {
				$makeDate = strtotime($l['MakeDate']);
				$makeDate = PHPExcel_Shared_Date::PHPToExcel($makeDate);

				$dateIn = strtotime($l['DateIn']);
				$dateIn = PHPExcel_Shared_Date::PHPToExcel($dateIn);

				$xlRow = array();
				array_push($xlRow, $l['LotNumber']);
				array_push($xlRow, $l['CustomerName']);
				array_push($xlRow, $l['Description']);
				array_push($xlRow, $makeDate);

				if ($printCost) {
					array_push($xlRow, $l['Cost']);
				}

				array_push($xlRow, $l['FactoryName']);

				if ($printPallets) {
					array_push($xlRow, $l['availablePallets']);
				}

				array_push($xlRow, $l['AvailablePieces']);
				array_push($xlRow, $l['AvailableWeight']);
				array_push($xlRow, $dateIn);
				array_push($xlRow, $l['ProductCode']);
				array_push($xlRow, $l['RoomNumber']);

				if ($printTemp) {
					array_push($xlRow, $l['RoomTemp']);
				}

				array_push($xlRow, $l['Site']);
				array_push($xlRow, $l['InventoryType']);
				array_push($xlRow, $l['CustomerPONumber']);
				array_push($rows, $xlRow);

				$totalPieces += $l['AvailablePieces'];
				$totalWeight += $l['AvailableWeight'];
				$totalPallets += $l['availablePallets'] ?? 0;

				$rowEnd++;

				if ($l['RoomNumber2'] || $l['Site2'] || $l['RoomNumber3'] || $l['Site3']) {
					$rowEnd++;
					$cellRange = 'A' . $rowEnd . ':' . $lastColumn . $rowEnd;
					// $this->logger->log($cellRange);
					$additionalLocations = array();
					if ($l['RoomNumber2'])	array_push($additionalLocations, 'Room: ' . $l['RoomNumber2']);
					if ($l['RoomTemp2'])		array_push($additionalLocations, $l['RoomTemp2']);
					if ($l['Site2'])			array_push($additionalLocations, 'Site: ' . $l['Site2']);
					if ($l['RoomNumber3'])	array_push($additionalLocations, 'Room: ' . $l['RoomNumber3']);
					if ($l['RoomTemp3'])		array_push($additionalLocations, $l['RoomTemp3']);
					if ($l['Site3'])			array_push($additionalLocations, 'Site: ' . $l['Site3']);

					$commaSeparated = implode(", ", $additionalLocations);
					$xlRow = array('Additional Locations: ' . $commaSeparated);
					array_push($rows, $xlRow);
					$sheet->mergeCells($cellRange);
				}
			}

			// add totals to sheet
			$columns = array();
			array_push($columns, '');
			array_push($columns, '');
			array_push($columns, '');
			array_push($columns, '');
			if ($printCost) {
				array_push($columns, '');
			}
			array_push($columns, 'Total:');
			// array_push($columns, $totalPieces);
			// array_push($columns, number_format($totalWeight, 2, '.', ','));
			if ($printPallets) {
				array_push($columns, "=SUM($palletsFieldRange$rowEnd)");
			}
			array_push($columns, "=SUM($piecesFieldRange$rowEnd)"); // total pieces formula
			array_push($columns, "=SUM($weightFieldRange$rowEnd)"); // total pieces formula

			array_push($rows, $columns);
		}

		$sheet->fromArray($rows);

		// add border and center align all lot data
		$range = 'A' . $rowStart . ':' . $lastColumn . $rowEnd;
		$styleArray = array(
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'font' => array(
				'size' => 10
			)
		);
		$sheet->getStyle($range)->applyFromArray($styleArray);

		// Commenting this out centers ALL of the data
		// align fields right
		// $styleArray = array(
		// 	'alignment' => array(
		// 		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
		// 	)
		// );

		$colRange = 'D' . $rowStart . ':D' . $rowEnd;
		$sheet->getStyle($colRange)->applyFromArray($styleArray);
		if ($printCost || $printPallets) {
			$colRange = 'E' . $rowStart . ':E' . $rowEnd;
			$sheet->getStyle($colRange)->applyFromArray($styleArray);
		}

		$colRange = $piecesColumn . $rowStart . ':' . $piecesColumn . $rowEnd;
		$sheet->getStyle($colRange)->applyFromArray($styleArray);
		$colRange = $wtColumn . $rowStart . ':' . $wtColumn . $rowEnd;
		$sheet->getStyle($colRange)->applyFromArray($styleArray);
		$colRange = $dateInColumn . $rowStart . ':' . $dateInColumn . $rowEnd;
		$sheet->getStyle($colRange)->applyFromArray($styleArray);

		// add border and align center total cells
		$rowEnd++;
		$totalsFieldRange = 'E' . $rowEnd . ':G' . $rowEnd;

		if ($printCost) {
			$totalsFieldRange = 'F' . $rowEnd . ':H' . $rowEnd;
		} else if ($printPallets) {
			$totalsFieldRange = 'E' . $rowEnd . ':H' . $rowEnd;
		} else {
			$totalsFieldRange = 'E' . $rowEnd . ':G' . $rowEnd;
		}

		$styleArray = array(
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			),
			'font' => array(
				'bold' => true
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
			),
		);

		$sheet->getStyle($totalsFieldRange)->applyFromArray($styleArray);

		$sheet->getColumnDimension('B')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE); // setWidth(45);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('H')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE); // setWidth(22);
		$sheet->getColumnDimension('J')->setAutoSize(TRUE); // setWidth(15);
		$sheet->getColumnDimension('K')->setAutoSize(TRUE); // setWidth(15);
		$sheet->getColumnDimension('L')->setAutoSize(TRUE); // setWidth(15);
		$sheet->getColumnDimension('M')->setAutoSize(TRUE); // setWidth(15);

		if ($printCost || $printPallets) {
			$sheet->getColumnDimension('N')->setAutoSize(TRUE); // setWidth(15);
		}

		// $this->logger->log('rowEnd = ' . $rowEnd . ' lotCount = ' . $lotCount);

		// set "make date" and "date in" formats
		$sheet->getStyle('D2:D' . ($rowEnd - 1))->getNumberFormat()->setFormatCode('mm/dd/y');
		$sheet->getStyle($dateInFieldRange . ($rowEnd - 1))->getNumberFormat()->setFormatCode('mm/dd/yy');

		// set "pieces" and "weight" formats
		$sheet->getStyle($piecesFieldRange . $rowEnd)->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle($weightFieldRange . $rowEnd)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

		// capture the output of the save method and put it into $content as a string
		ob_start();
		$objWriter = PHPExcel_IOFactory::createWriter($phpxl, "Excel2007");
		$objWriter->save("php://output");

		$content = ob_get_contents();
		ob_end_clean();

		// Getting a response instance
		$response = new Response();

		$response->setHeader("Content-Type", "application/vnd.ms-excel");
		$fileName = 'inventory-report-' . date('m-d-Y-his') . '.xlsx';
		$response->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
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
		$manifestType = $this->dispatcher->getParam("type");
		$lotId = $this->dispatcher->getParam("id");

		try {
			$userList = User::getActiveUsers($this->session->userAuth['UserID']);
		} catch (Exception $e) {
			$this->logger->log('Exception getting user list in printmanifestAction: ' . $e->getMessage());
			$userList = array();
		}
		array_shift($userList);

		$this->view->userList = $userList;

		// $vatStats = new stdClass;
		$totals = (object) array(
			'totPieces' => 0,
			'totWeight' => 0,
			'availPieces' => 0,
			'availWeight' => 0
		);

		$lotData = Lot::findFirst("LotID = '$lotId'");

		if (!$lotData) {
			$this->logger->log('No lot found for LotID: ' . $lotId);
			return false;
		}

		$this->view->lotData = $lotData;
		$this->view->vats = $lotData->getAvailableVats();

		// $this->logger->log($this->view->vats);

		$vatLps = [];
		// Entered Pieces, Available Pieces, Entered Weight, Available Weight
		foreach ($this->view->vats as $v) {
			$totals->totPieces += $v['Pieces'];
			$totals->totWeight += $v['Weight'];
			if (!empty($v['DeliveryDetailID'])) {
				$vatLps[$v['VatID']] = DeliveryDetail::getLicensePlate($this->db, $this->logger, $v['DeliveryDetailID']);
			}
		}

		$lotRooms = array();

		if ($lotData->RoomPID || $lotData->Site) {
			array_push($lotRooms, array(
				'RoomPID' => $lotData->RoomPID,
				'Room' => Parameter::getValue($lotData->RoomPID),
				'Site' => $lotData->Site
			));
		}

		if ($lotData->RoomPID2 || $lotData->Site2) {
			array_push($lotRooms, array(
				'RoomPID' => $lotData->RoomPID2,
				'Room' => Parameter::getValue($lotData->RoomPID2),
				'Site' => $lotData->Site2
			));
		}

		if ($lotData->RoomPID3 || $lotData->Site3) {
			array_push($lotRooms, array(
				'RoomPID' => $lotData->RoomPID3,
				'Room' => Parameter::getValue($lotData->RoomPID3),
				'Site' => $lotData->Site3
			));
		}

		$this->view->lotRooms = $lotRooms;

		$factory = Factory::findFirst(
			array(
				'columns' => 'Name',
				'conditions' => 'FactoryID = :FactoryID:',
				'bind' => array('FactoryID' => $lotData->FactoryID)
			)
		);

		$this->view->lotFactory = $factory->Name;

		$this->view->lotDescription = Parameter::getValue($lotData->DescriptionPID);

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
		}
		if ($manifestType == 'pick' || $manifestType == 'move') {
			$this->view->pick("lot/printpickmove");
		} else {
			$this->view->pick("lot/printmanifest");
		}


		$this->view->tests = $tests;
		$this->view->manifestType = $manifestType;
		$this->view->lotId = $lotId;
		$this->view->vatStats = $vatStats;
		$this->view->lotTotals = $totals;
		$this->view->vatLps = $vatLps;
		$this->view->utils = $this->utils;
	}


	//        8                    8      o    o                8
	//        8                    8      8b   8                8
	// .oPYo. 8oPYo. .oPYo. .oPYo. 8  .o  8`b  8 o    o ooYoYo. 8oPYo. .oPYo. oPYo.
	// 8    ' 8    8 8oooo8 8    ' 8oP'   8 `b 8 8    8 8' 8  8 8    8 8oooo8 8  `'
	// 8    . 8    8 8.     8    . 8 `b.  8  `b8 8    8 8  8  8 8    8 8.     8
	// `YooP' 8    8 `Yooo' `YooP' 8  `o. 8   `8 `YooP' 8  8  8 `YooP' `Yooo' 8
	// :.....:..:::..:.....::.....:..::.....:::..:.....:..:..:..:.....::.....:..::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function checknumberAction()
	{
		$this->view->pick("layouts/json");
		$json = array(
			'value' => '',
			'valid' => false
		);

		$lotId = $this->dispatcher->getParam("id");
		$lotNumber = $this->request->get('value');
		$json['value'] = $lotNumber;

		// $this->logger->log(print_r($this->request->get(), true));

		if ($lotNumber) {
			$exists = Lot::findFirst("LotNumber = '$lotNumber' AND LotID <> '$lotId'");
			if ($exists) {
				// $this->logger->log("$lotNumber already exists");
				$json['valid'] = false;
				$json['message'] = "Lot number already exists";
			} else {
				// $this->logger->log("nah, $lotNumber is fine");
				$json['valid'] = true;
			}
		}

		// $this->er->log(print_r($json, true));

		$this->view->data = $json;
	}

	public function archiveAction()
	{
		Lot::archive();
	}

	public function transfertolotAction()
	{
		//! LOT TRANSFER DISABLED UNTIL CLIENT APPROVAL
		return;

		$this->logger->log("Transfer initiated: Lot -> Lot");
		$id = $this->dispatcher->getParam("id");

		if ($id) {
			$originalLot = Lot::findFirst("LotID = '$id'");
		}

		$response = new Response();
		$response->setHeader('Content-Type', 'application/json');

		if (!$originalLot) {
			$this->logger->log("Error in LotController::transfertolotAction: No Lot found.");
			$response->setStatusCode(404, "Not Found");
			$response->setContent(json_encode([
				'success' => 0,
				'msg' => 'Lot not found.'
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

		$this->db->begin();

		try {
			// create draft lot
			$this->logger->log("Creating draft lot...");
			$lot = new Lot();
			$lotId = $this->utils->UUID(mt_rand(0, 65535));
			$lot->LotID = $lotId;
			$lot->LotNumber = $originalLot->LotNumber . '-T';
			$lot->DateIn = $originalLot->DateIn;
			$lot->DescriptionPID = $originalLot->DescriptionPID;
			$lot->VendorID = $originalLot->VendorID;
			$lot->FactoryID = $originalLot->FactoryID;
			$lot->InventoryTypePID = $originalLot->InventoryTypePID; //? do we need to do this?
			$lot->WarehouseID = $originalLot->WarehouseID;
			$lot->RoomPID = $originalLot->RoomPID;
			$lot->RoomPID2 = $originalLot->RoomPID2;
			$lot->RoomPID3 = $originalLot->RoomPID3;
			$lot->RoomTemp = $originalLot->RoomTemp;
			$lot->RoomTemp2 = $originalLot->RoomTemp2;
			$lot->RoomTemp3 = $originalLot->RoomTemp3;
			$lot->Site = $originalLot->Site;
			$lot->Site2 = $originalLot->Site2;
			$lot->Site3 = $originalLot->Site3;
			$lot->StatusPID = Lot::STATUS_DRAFT;
			$lot->TransferredFrom = 'L:' . $id;
			$lot->DescriptionPID = $originalLot->DescriptionPID;
			$lot->NoteText = $originalLot->NoteText . ($originalLot->NoteText ? "\n\n" : '') . 'TRANSFERRED FROM LOT# ' . $originalLot->LotNumber . ' ON ' . date('m/d/Y');
			$lot->CreateDate = $currentTime;
			$lot->CreateId = $currentUser;
			$this->logger->log("Saving draft lot " . $lot->LotID);
			if ($lot->save() == false) {
				$errorResponse('Error saving lot: ' . print_r($lot->getMessages(), true));
				return;
			}

			// create copies of the vats on the lot
			$this->logger->log("Creating vats...");
			$vats = Vat::find("LotID = '$id'");
			foreach ($vats as $vat) {
				// get available inventory from original vat
				$avail = $vat->getAvailable()[0];
				if ($avail->InvPieces == 0) continue;

				$this->logger->log("Cloning vat " . $vat->VatID);
				$newVat = $vat->clone($lotId);
				$newVat->Pieces = $avail->InvPieces;
				$newVat->Weight = $avail->InvWeight;
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
						$newInventoryStatusRecord->Pieces = $avail->InvPieces;
						$newInventoryStatusRecord->Weight = $avail->InvWeight;
					}

					$this->logger->log("Saving new inventory status record " . $newInventoryStatusRecord->InventoryStatusID);
					if ($newInventoryStatusRecord->save() == false) {
						$errorResponse('Error saving inventory status: ' . print_r($newInventoryStatusRecord->getMessages(), true));
						return;
					}
				}
			}

			// update the original lot status
			$this->logger->log("Updating original lot status...");
			$originalLot->Transferred = 1;
			$originalLot->StatusPID = Lot::STATUS_TRANSFERRED;
			$originalLot->UpdateDate = $currentTime;
			$originalLot->UpdateId = $currentUser;
			if ($originalLot->save() == false) {
				$errorResponse('Error saving lot: ' . print_r($originalLot->getMessages(), true));
				return;
			}
			$this->logger->log("Done.");
		} catch (\Exception $e) {
			$errorResponse('Exception in LotController::transfertolotAction: ' . $e->getMessage());
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
