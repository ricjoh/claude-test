<?php
/*
+--------------------------------------+-----------------------------------+
| ParameterID                          | Value1                            |
+--------------------------------------+-----------------------------------+
| 9F276770-8635-4C1B-BBB2-136A83B7C639 | All Lot Details                   |
| 041DDEEE-75A5-4622-8F4F-99BEB23FFDC2 | All Lot Details with Test Results |
| 7504EE4C-6F75-46BC-9E0C-2CC15AACC060 | No Lot Details                    |
| 0B3FC463-2385-43A9-8D62-4732FB38C4D4 | Full Lot Details                  |
| 65D0A048-DC63-47AA-BBAE-29877E860E54 | Schreiber - All Lot Details       |
| 7C18F8D5-9AE3-4ECC-849F-B751178F8090 | Schreiber - No Lot Details        |
+--------------------------------------+-----------------------------------+
*/

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class BillofladingController extends Controller
{
	// $$\ $$\             $$\
	// $$ |\__|            $$ |
	// $$ |$$\  $$$$$$$\ $$$$$$\
	// $$ |$$ |$$  _____|\_$$  _|
	// $$ |$$ |\$$$$$$\    $$ |
	// $$ |$$ | \____$$\   $$ |$$\
	// $$ |$$ |$$$$$$$  |  \$$$$  |
	// \__|\__|\_______/    \____/

	public function listAction() // pending shipments
	{
		// Number of rows to display on a list page
		$pageDisplayLimit = 50;
		$statusPID = $this->dispatcher->getParam("id") ?? BillOfLading::STATUS_CREATED;
		// Either get the current page number or default to page 1 if not found.
		$pageNumber = $this->dispatcher->getParam("page") ?? 1;
		$this->view->pageNumber = $pageNumber;

		$titleArray = array(
			BillOfLading::STATUS_CREATED => 'Pending Shipments',
			BillOfLading::STATUS_SHIPPED => 'Previous Shipments',
		);

		$readonly = ($statusPID == BillOfLading::STATUS_SHIPPED);
		$this->view->readonly = $readonly;

		// $s = ( $statusPID == BillOfLading::STATUS_CREATED ) ? 'Created ' : '';

		// $this->logger->log( "List ${s}Bills of Lading: status " . $statusPID . "\n");

		// TODO: Add EDI DocID if relevant, change link to see detail on EDI Orders

		$this->view->title = $titleArray[$statusPID] ?: 'Unknown Status Shipments';

		$this->view->headers = ['Ship', 'Shipper Number', 'Create Date', 'Seal Number', 'Cons/Ship To', 'Net Wt', 'Customer Order #', 'EDI#'];

		$fields = [
			'%%CHECKBOX%%',
			'ShipperNumber',
			'CreateDate',
			'SealNumber',
			'ConsignedTo',
			'NetWeight',
			'CustomerOrderNumber',
			'EDIDocID'
		];

		$filterArray = array('conditions' => "StatusPID = '" . $statusPID . "'");

		// If the status is shipped or created
		if ($statusPID === BillOfLading::STATUS_SHIPPED || $statusPID === BillOfLading::STATUS_CREATED) {
			$filterArray['order'] = 'BOLDate DESC';
			$filterArray['limit']['number'] = $pageDisplayLimit;

			// If we get a page number then we need to make the offset based on that
			if (isset($pageNumber)) {
				$filterArray['limit']['offset'] = ($pageNumber - 1) * $pageDisplayLimit;
			}
		}

		$bolData = BillOfLading::find($filterArray)->toArray();

		$this->view->data = array();
		$rows = array();
		$ids = array();

		foreach ($bolData as $key => $data) {
			$row = array();

			$custOrder = CustomerOrder::findFirst("OfferID = '" .  $data['OfferID'] . "'");

			$data['CustomerOrderNumber'] = $custOrder->CustomerOrderNum;

			foreach ($fields as $field) {
				if (false !== strpos($field, 'EDIDocID')) {
					if ($custOrder)
						$data[$field] = $custOrder->EDIDocID;
					else
						$data[$field] = '';
				}

				if (false !== strpos($field, 'ConsignedTo')) {
					$data[$field] = $data['ConsignedTo'];
					if ($data['ConsignedTo'] != $data['ShippedToName']) {
						$data[$field] .= '/' . $data['ShippedToName'];
					}
				}


				if (false !== strpos($field, 'NetWeight')) {
					$data[$field] = '--';
				}

				if (false !== strpos($field, 'CreateDate')) {
					$data[$field] = substr($data[$field], 0, 10);
				}

				if ($field == '%%CHECKBOX%%') {
					if ($custOrder) {
						$data[$field] = 'EDI';
					} elseif ($readonly) {
						$data[$field] = '&#10003;';
					} else {
						$data[$field] = '<input type="checkbox" name="shipped" value="1">';
					}
				}

				array_push($row, $data[$field]);
			}

			array_push($ids, '-' . $data['BOLID']);
			array_push($rows, $row);
		}

		// If the number of rows are less than the limit or no data was retrieved then we are on the last page
		$this->view->isLastPage = (count($rows) < $pageDisplayLimit) || ($bolData == false);
		// Getting the current statusPID for the page to make some link generation easier
		$this->view->statusPID = $statusPID;
		$this->view->data = $rows;
		$this->view->ids = $ids;
	}


//               8 8          o   o
//               8 8          8
// .oPYo. .oPYo. 8 8 .oPYo.  o8P o8 .oooo. .oPYo.
// 8    8 .oooo8 8 8 8oooo8   8   8   .dP  8oooo8
// 8    8 8    8 8 8 8.       8   8  oP'   8.
// 8YooP' `YooP8 8 8 `Yooo'   8   8 `Yooo' `Yooo'
// 8 ....::.....:....:.....:::..::..:.....::.....:
// 8 :::::::::::::::::::::::::::::::::::::::::::::
// ..:::::::::::::::::::::::::::::::::::::::::::::


	public static function getPallets($offerID)
	{
		$pallets = ShipPallet::find("OfferID = '$offerID'");
		$pallets = $pallets->toArray();
		return $pallets;
	}

	public static function autoPalletize($offer, $customerOrder)
	{
		$di = \Phalcon\DI::getDefault();
		$logger = $di->getShared('logger');

		$success = true;

		// take all the offer items and create a pallet for each one mapping customer order detail to offer item by id
		// use OrderOfferDetailMap to get the CustomerOrderDetailID

		// $details = $offer->getOfferItem()->toArray();
		$logger->log("About to palletize offer: {$offer->OfferID}");

		try {
			foreach ($offer as $offerLine) {
				$logger->log("ItemID: " . $offerLine['OfferItemID']);
				$vat2lpmap = ShipPallet::getLicensePlates($offerLine['OfferItemID']);
				$logger->log("VatMap: ");
				$logger->log($vat2lpmap);


				$map = OrderOfferDetailMap::findFirst("OfferItemID = '" . $offerLine['OfferItemID'] . "'");
				// TODO:  if this happens, maybe save it and try to map by SKU?
				// $prodNumberReferenceArray = CustomerOrderDetail::getProdNumberReferenceArray($db);
				// = $prodNumberReferenceArray[$offer["ProductCode"]] ?? '';
				if (!$map) {
					$logger->log("AutoPalletize: No mapping for OfferItemID: " . $offerLine['OfferItemID']);
					$logger->log("It's either not EDI or has been hand-modified?");
					$success = false;
					continue;
				}


				// foreach ($vat2lpmap as $vat => $lp) {
					$lp = $vat2lpmap[ $offerLine['OfferItemVatID'] ];

					$pallet = new ShipPallet();
					$pallet->OfferID = $offerLine['OfferID'];
					$pallet->OfferItemID = $offerLine['OfferItemID'];
					$pallet->CustomerOrderID = $customerOrder->CustomerOrderID;
					$pallet->CustomerOrderDetailID = $map->CustomerOrderDetailID;
					$pallet->LicensePlate = $lp;
					$pallet->OfferItemVatID = $offerLine['OfferItemVatID'];
					$pallet->LotNumber = $offerLine['LotNumber'];
					$pallet->Pieces = $offerLine['PiecesfromVat'];
					$pallet->Weight = $offerLine['WeightfromVat'];
					$pallet->EDIDocIDIn = $customerOrder->EDIDocID;
					$pallet->UpdateDate = date('Y-m-d H:i:s');

					$logger->log("\nSaving pallet for OfferItemID: " . $offerLine['OfferItemID'] . " OIVat: " .  $offerLine['OfferItemVatID'] . " LP: " . $lp . "\n");
					if (!$pallet->save()) {
						$logger->log("Error saving pallet for OfferItemID: " . $offerLine['OfferItemID'] . " LP: " . $lp);
						$success = false;
					} else {
						$logger->log("Palletized OfferItemID: " . $offerLine['OfferItemID']);
					}
				// }
			}
		} catch (\Phalcon\Exception $e) {
			$logger->log('FAIL!');
			$msg = "Exception palletizing Offer:\n\n" . $e->getMessage();
			$logger->log("$msg\n");
			$success = 0;
		}

		return $success;
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
		$offerId = $this->dispatcher->getParam("id");
		$offerData = Offer::findFirst("OfferID = '$offerId'");
		$offerLines = Offer::getOfferInfo($this->db, $offerId);
		$pallets = self::getPallets($offerId);
		$customerOrder = CustomerOrder::findFirst("OfferID = '$offerId'");
		if (!$customerOrder) {
			$this->logger->log("Not EDI: $offerId");
		} else {
			if (count($pallets) <= 0) {
				// Get Walmart flag from CustomerOrder and check palletization
				if ($customerOrder->Walmart) {
					$this->flash->error("Walmart orders must be palletized.");
					$this->response->redirect("offer/edit/" . $offerId);
					return;
				} else {
					self::autoPalletize($offerLines, $customerOrder);
				}
			} else {
				$this->logger->log("Offer {$offerId} already palletized. Walmart: {$customerOrder->Walmart}");
			}
		}
		// $customerOrderDetails = CustomerOrderDetail::find("CustomerOrderID = '$customerOrder->CustomerOrderID'");
		// TODO: Get the customer order information and fill it in if EDI?

		$customerID = $offerData->CustomerID;

		try {
			$shipToAddresses = ShipToAddress::find([
				'conditions' => "CustomerID = '$customerID' AND Active = 1",
				'columns' => 'ID, Name, Address, Address2, City, State, Zip, ConsignedName, Nickname',
				'order' => 'Nickname, ConsignedName'
			])->toArray();
		} catch (\Phalcon\Exception $e) {
			$msg = "Exception getting ShipToAddresses:\n" . $e->getMessage();
			$this->logger->log("$msg\n");
		}
		$bol = $offerData->BillOfLading; // Offer Model rolls in BOL line

		$customerData = Customer::findFirst("CustomerID = '{$customerID}'");

		$BOLID = $bol->BOLID ?? 0;

		// $contact = new Contact();
		$paramModel = new Parameter();

		if ($BOLID) {
			$bolData = $bol->toArray();

			if ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS_AND_TESTS) {
				$bolData['showLotDetails'] = 1;
				$bolData['showTestResults'] = 1;
			} elseif ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS) {
				$bolData['showLotDetails'] = 1;
			} elseif ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS_AND_TESTS_AND_PRODUCT_CODE) {
				$bolData['showLotDetails'] = 1;
				$bolData['showTestResults'] = 1;
				$bolData['showProductCodes'] = 1;
			} elseif ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_PRODUCT_CODE) {
				$bolData['showProductCodes'] = 1;
			} elseif ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS_AND_PRODUCT_CODE) {
				$bolData['showLotDetails'] = 1;
				$bolData['showProductCodes'] = 1;
			}
		} else {
			// data that gets pre-filled on offers
			$bolData = array(
				'BOLDate' => $this->mysqlDate
			);
		}

		$selectPromptElement = array('ParameterID' => '', 'Value1' => 'Select...');

		$instGroups = array('FF0144EB-A6F3-4038-8356-3728773B26AE', '583F7E18-51EA-422A-BB3F-8BF15E7AFEBE', 'FB11DDB9-43C2-474C-8B1E-F5A51056FFD6', 'DE7A4E34-5740-47C6-B69D-758925155E34');
		$instructions = array();

		foreach ($instGroups as $group) {
			$instruction =  $paramModel->getValuesForGroupId(
				$group,
			);
			array_unshift($instruction, $selectPromptElement);

			$instructions[] = $instruction;
		}

		$this->view->customerID = $customerID;
		$this->view->instructions = $instructions;
		$this->view->shipToAddresses = $shipToAddresses; // TODO: Force Address if EDI?
		$this->view->offerID = $offerId;
		$this->view->BOLID = $BOLID;
		$this->view->bolData = $bolData;
		$this->view->EDIKey = isset($customerData->EDIKey) ? $customerData->EDIKey : '';
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
		$offerId = $this->dispatcher->getParam("id");


		if ($this->request->isPost() == true && $offerId) {
			// $this->logger->log( '*********************** Posted *************************' );
			// $this->logger->log( $_REQUEST );

			$offerData = Offer::findFirst("OfferID = '$offerId'");
			$shipped = $offerData->OfferStatusPID == Offer::STATUS_SHIPPED;
			$this->logger->log( "Offer {$offerId} is " . ($shipped ? 'shipped.' : 'not shipped.') );
			$bol = $offerData->BillOfLading; // Offer Model rolls in BOL line

			$BOLID = $bol->BOLID ?? 0;

			$updateFields = array(
				'CarrierName',
				'ConsignedTo',
				'ShippedToName',
				'ShippedToAddress',
				'ShipperNumber',
				'CustomerPO',
				'BOLDate',
				'SealNumber',
				'TotalTareWeight',
				'InstructionOnePID',
				'InstructionTwoPID',
				'InstructionThreePID',
				'InstructionFourPID',
				'InstructionFive',
				'CustomerItemNumber',
				// 'BOLTemplatePID'
			);

			$newBOLID = '';

			if (!$BOLID) { // add new BOL
				$this->logger->log('new BOL');
				$bol = new BillOfLading();

				$newBOLID = $this->utils->UUID(mt_rand(0, 65535));
				$bol->BOLID = $newBOLID;
				$BOLID = $newBOLID;

				$bol->StatusPID = $shipped ? BillOfLading::STATUS_SHIPPED : BillOfLading::STATUS_CREATED;
				$bol->OfferID = $offerId;
				$bol->CreateDate = $this->mysqlDate;
				$bol->CreateID = $this->session->userAuth['UserID']; // id of user who is logged in
				$bol->UserID = $offerData->UserID ?? $this->session->userAuth['UserID'];
			} else { // update existing lot
				$this->logger->log('found BOL');
				$bol->UpdateDate = $this->mysqlDate;
				$bol->UpdateID = $this->session->userAuth['UserID']; // id of user who is logged in
				$bol->UserID = $offerData->UserID ?? $this->session->userAuth['UserID'];
			}

			$this->logger->log('update fields');

			foreach ($updateFields as $f) {
				$postValue = $this->request->getPost($f);

				// $this->logger->log( $f . " = " . $postValue );

				// if ($postValue) {
				if ($f == 'BOLDate' || $f == 'PickupDate') {
					$bol->{$f} = $this->utils->dbDate($postValue);
				} else {
					$bol->{$f} = $postValue;
				}
				// }
			}

			if ($this->request->getPost('showLotDetails')) {
				if ($this->request->getPost('showTestResults')) {
					if ($this->request->getPost('showProductCodes')) {
						$bol->BOLTemplatePID = BillOfLading::SHOW_DETAILS_AND_TESTS_AND_PRODUCT_CODE;
					} else {
						$bol->BOLTemplatePID = BillOfLading::SHOW_DETAILS_AND_TESTS; // All Lot Details with Test Results
					}
				} else {
					if ($this->request->getPost('showProductCodes')) {
						$bol->BOLTemplatePID = BillOfLading::SHOW_DETAILS_AND_PRODUCT_CODE;
					} else {
						$bol->BOLTemplatePID = BillOfLading::SHOW_DETAILS; // All Lot Details
					}
				}
			} else {
				if ($this->request->getPost('showProductCodes')) {
					$bol->BOLTemplatePID = BillOfLading::SHOW_PRODUCT_CODE;
				} else {
					$bol->BOLTemplatePID = BillOfLading::SHOW_NO_LOT_DETAILS; // No Lot Details
				}
			}

			if (isset($bol->UpdateDate)) {
				$this->logger->log("UpdateDate = " . $bol->UpdateDate);
			}

			$this->logger->log("saving...");

			$success = 1;
			try {
				if ($bol->save() == false) {
					$this->logger->log('FAIL!');
					$msg = "Error saving BOL: \n\n" . implode("\n", $bol->getMessages());
					$this->logger->log("$msg\n");
					$success = 0;
				}
			} catch (\Phalcon\Exception $e) {
				$this->logger->log('FAIL!');
				$msg = "Exception saving BOL: \n\n" . $e->getMessage();
				$this->logger->log("$msg\n");
				$success = 0;
			}

			$this->logger->log("save? Success = $success");

			// TODO: Check for Palletization here.
			/*
if EDI and Walmart, and not palletized, then show error.
If EDI and not palletized, then palletize.
if not EDI, then move along.
*/

			$this->view->data = array(
				'success' => $success,
				'status' => ($success ? 'success' : 'error'),
				'msg' => $msg,
				'BOLID' => $BOLID,
				'showProductCodes' => $this->request->getPost('showProductCodes') ?? 0,
				'newBOLID' => $newBOLID
			);
			$this->view->pick("layouts/json");
		}
	}


	//               o         o
	//                         8
	// .oPYo. oPYo. o8 odYo.  o8P
	// 8    8 8  `'  8 8' `8   8
	// 8    8 8      8 8   8   8
	// 8YooP' 8      8 8   8   8
	// 8 ....:..:::::....::..::..:
	// 8 :::::::::::::::::::::::::
	// ..:::::::::::::::::::::::::

	public function printAction()
	{
		$BOLID = $this->dispatcher->getParam("id");

		$bolData = BillOfLading::findFirst("BOLID = '$BOLID'");
		if (! $bolData ) {
			$this->logger->log("Bill of Lading {$BOLID} not found.");
		   	$this->flash->error("Bill of Lading not found.");
			$this->response->redirect("billoflading/list");
			return;
		}
		$bolData = $bolData->toArray();
		$bolData['BOLDate'] = $this->utils->slashDate($bolData['BOLDate']);
		$bolData['BOLTemplate'] = Parameter::getValue($bolData['BOLTemplatePID']);

		$bolData['InstructionOne'] = Parameter::getValue($bolData['InstructionOnePID']);
		$bolData['InstructionTwo'] = Parameter::getValue($bolData['InstructionTwoPID']);
		$bolData['InstructionThree'] = Parameter::getValue($bolData['InstructionThreePID']);
		$bolData['InstructionFour'] = Parameter::getValue($bolData['InstructionFourPID']);

		if ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS_AND_TESTS) {
			$bolData['showLotDetails'] = TRUE;
			$bolData['showTestResults'] = TRUE;
		} else if ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS) {
			$bolData['showLotDetails'] = TRUE;
		} else if ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS_AND_PRODUCT_CODE) {
			$bolData['showLotDetails'] = TRUE;
			$bolData['showProductCodes'] = TRUE;
		} else if ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_DETAILS_AND_TESTS_AND_PRODUCT_CODE) {
			$bolData['showLotDetails'] = TRUE;
			$bolData['showTestResults'] = TRUE;
			$bolData['showProductCodes'] = TRUE;
		} else if ($bolData['BOLTemplatePID'] == BillOfLading::SHOW_PRODUCT_CODE) {
			$bolData['showProductCodes'] = TRUE;
		}

		$offerData = Offer::getOfferInfo($this->db, $bolData['OfferID']);
// $this->logger->log("OfferData: ");
// $this->logger->log($offerData);

		$totNetWeight = 0.0;
		$testData = array();

		$offerItemId = '';
		$prodNumberReferenceArray = CustomerOrderDetail::getProdNumberReferenceArray($this->db);

		$hasShipTo = false;

		$groupItems = FALSE;
		$isEDI = FALSE;

		foreach ($offerData as $key => $offerLine) {
			if (strpos($offerLine["VatNumber"], "EDI") !== FALSE) {
				$isEDI = TRUE;
			}

			if ($offerLine['OfferItemID'] != $offerItemId) {
				$offerItemId = $offerLine['OfferItemID'];

				$totNetWeight += $offerLine['OfferItemWeight'];

				$lotNumber = $offerLine['LotNumber']; // 49000 for example
				$lotId = $offerLine['LotID']; // Lot::findFirst("LotNumber = '$lotNumber'")->LotID;

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

					if ($hasData) $testData[$lotNumber] = $tests;
				}
			}

			$offerData[$key]["ShipToPartNum"] = $prodNumberReferenceArray[$offerLine["ProductCode"]] ?? '';

			if ($offerData[$key]["ShipToPartNum"] != '') {
				$hasShipTo = true;
			}
		}

		if ($hasShipTo) {
			usort($offerData, array($this, "bolShipToSort"));

			if ($isEDI) {
				$offerData = $this->_groupBOLOfferData($offerData);
			}
		}

		$this->view->groupItems = $groupItems;
		$this->view->showProductCodes = $this->dispatcher->getParam("showProductCodes") ?: 0;
		$this->view->bolData = $bolData;
		$this->view->offerData = $offerData;
		$this->view->totNetWeight = $totNetWeight;
		$this->view->testData = $testData;

		if ($isEDI) {
			$this->view->pick("billoflading/printedi");
		}
	}

	private function bolShipToSort($offerOne, $offerTwo)
	{
		$this->logger->log("SORTING\n");

		if ($offerOne["ShipToPartNum"] . $offerOne["LotNumber"] == $offerTwo["ShipToPartNum"] . $offerTwo["LotNumber"]) {
			return 0;
		}

		return ($offerOne["ShipToPartNum"]  . $offerOne["LotNumber"]< $offerTwo["ShipToPartNum"] . $offerTwo["LotNumber"]) ? -1 : 1;
	}

	// This function is for grouping items together as needed on the BOLs in order to help alleviate some confusion.
	private function _groupBOLOfferData($offerData)
	{
		if (count($offerData) == 1) {
			return $offerData;
		}

		$groupingOffer = null;
		$iteration = 1;
		$offerDataLength = count($offerData);
		$newOfferData = array();
		$lotArray = array();

		foreach ($offerData as $key => $offer) {
			// If we are on the first iteration
			if ($groupingOffer == null) {
				$groupingOffer = $offer;
				array_push($lotArray, $offer['LotNumber']);
				$iteration++;

				continue;
			}

			// If the current item is the same as the grouping item
			if ($groupingOffer['ShipToPartNum'] == $offer['ShipToPartNum']) {
				// Add the totals from the current offer to the 'grouped' offer.
				if ($groupingOffer['OfferItemID'] != $offer['OfferItemID']) {
					$groupingOffer['OfferItemPieces'] += $offer['PiecesfromVat'];
					$groupingOffer['OfferItemWeight'] += $offer['WeightfromVat'];
				}

				array_push($lotArray, $offer['LotNumber']);

				// If we are on the last element of the array
				if ($iteration === $offerDataLength) {
					$lotArray = array_unique($lotArray);
					$groupingOffer['LotNumber'] = implode(" / ", $lotArray);

					$lotArray = array();

					array_push($newOfferData, $groupingOffer);
				}
			} else {
				$lotArray = array_unique($lotArray);
				$groupingOffer['LotNumber'] = implode(" / ", $lotArray);
				$lotArray = array();

				array_push($newOfferData, $groupingOffer);

				// If we are on the last element of the array
				if ($iteration === $offerDataLength) {
					array_push($newOfferData, $offer);
				} else {
					array_push($lotArray, $offer['LotNumber']);
					$groupingOffer = $offer;
				}
			}

			$iteration++;
		}

		return $newOfferData;
	}


	//                      d'b  o
	//                      8
	// .oPYo. .oPYo. odYo. o8P  o8 oPYo. ooYoYo.
	// 8    ' 8    8 8' `8  8    8 8  `' 8' 8  8
	// 8    . 8    8 8   8  8    8 8     8  8  8
	// `YooP' `YooP' 8   8  8    8 8     8  8  8
	// :.....::.....:..::..:..:::....::::..:..:..
	// ::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::

	private static function _getInventoryDataForVerification($db, $offerID)
	{
		$getInventorySql = "SELECT oiv.VatID, oiv.VatID, oiv.Pieces AS SoldPieces, oiv.Weight AS SoldWeight,
			ns.Pieces, ns.Weight, ns.InventoryStatusPID
			FROM Offer o
			LEFT JOIN OfferItem oi ON o.OfferID = oi.OfferID
			LEFT JOIN OfferItemVat oiv ON oi.OfferItemID = oiv.OfferItemID
			LEFT JOIN InventoryStatus ns ON oiv.VatID = ns.VatID
			WHERE o.OfferID = '$offerID'
		";

		$data = $db->fetchAll($getInventorySql);

		$dataById = [];
		foreach ($data as $row) {
			if (!array_key_exists($row['VatID'], $dataById)) {
				$dataById[$row['VatID']] = [];
			}

			// NOTE: Set SOLD in this array to oiv.Pieces and oiv.Weight not inv.soldUnshipped
			if ( $row['InventoryStatusPID'] == InventoryStatus::STATUS_SOLDUNSHIPPED ) {
				$dataById[$row['VatID']][InventoryStatus::STATUS_SOLDUNSHIPPED] = [
					'Pieces' => $row['SoldPieces'],
					'Weight' => $row['SoldWeight'],
				];
			}

			$dataById[$row['VatID']][$row['InventoryStatusPID']] = [
				'Pieces' => $row['Pieces'],
				'Weight' => $row['Weight'],
			];
		}

		return $dataById;
	}

	private static function _verifyInventoryDepletion($logger, $beforeData, $afterData)
	{
		$margin = 0.01;
		$success = true;
		foreach ($beforeData as $vatId => $beforeRow) {
			$afterRow = $afterData[$vatId];
			// $logger->log(json_encode(['Before' => $beforeRow, 'After' => $afterRow], JSON_PRETTY_PRINT));
			foreach (['Pieces', 'Weight'] as $field) {
				$soldAmount = isset( $beforeRow[InventoryStatus::STATUS_SOLDUNSHIPPED][$field] ) ? $beforeRow[InventoryStatus::STATUS_SOLDUNSHIPPED][$field] : 0;

				// verify available inventory was depleted
				$availableBefore = $beforeRow[InventoryStatus::STATUS_AVAILABLE][$field];
				$availableAfter = $afterRow[InventoryStatus::STATUS_AVAILABLE][$field];
				if (abs($availableBefore - $soldAmount - $availableAfter) > $margin) {
					$logger->log("Error in verifyInventoryDepletion: $field available was not depleted correctly for vat $vatId\nBefore: {$availableBefore} After: {$availableAfter} Sold: {$soldAmount}");
					$success = false;
				}

				// verify unavailable inventory was increased
				$unavailableBefore = $beforeRow[InventoryStatus::STATUS_UNAVAILABLE][$field];
				$unavailableAfter = $afterRow[InventoryStatus::STATUS_UNAVAILABLE][$field];
				if (abs($unavailableBefore + $soldAmount - $unavailableAfter) > $margin) {
					$logger->log("Error in verifyInventoryDepletion: $field unavailable was not increased correctly for vat {$vatId}\nBefore: {$unavailableBefore} After: {$unavailableAfter} Sold: {$soldAmount}");
					$success = false;
				}
			}
		}

		return $success;
	}

	public function confirmshipmentAction()
	{
		$BOL_ID = $this->dispatcher->getParam("id");
		$bolData = BillOfLading::findFirst("BOLID = '$BOL_ID'");
		$depletedInventory = true;

		if ($bolData) {
			$offerInventoryBeforeShip = $this->_getInventoryDataForVerification($this->db, $bolData->OfferID);

			try {
				// deplete on ship went live night of 7/3/24 -- Ric
				if ( $bolData->CreateDate  >= '2024-07-04' ) {
					$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
					$url = $protocol . $_SERVER['HTTP_HOST'] . "/offer/depleteinventory";
					$this->logger->log("About to deplete inventory.");
					$responseobj = $this->utils->POST($url, ['offerID' => $bolData->OfferID]);
				} else {
					$this->logger->log("BOL is too old to deplete inventory.");
					$depletedInventory = false;
				}

				if ($responseobj['success'] || $depletedInventory == false) {
					$this->logger->log("Updating status on BOLID: $BOL_ID");
					$bolData->StatusPID = BillOfLading::STATUS_SHIPPED;
					// RICDEBUG
					$bolData->save();

					$this->logger->log("Updating status on Offer: {$bolData->OfferID}");
					try{
						$offerData = Offer::findFirst("OfferID = '{$bolData->OfferID}'");
						$offerData->OfferStatusPID = Offer::STATUS_SHIPPED;
						// RICDEBUG
						$offerData->save();
					} catch (\Phalcon\Exception $e) {
						$this->logger->log("Exception in confirmshipmentAction updating status to SHIPPED on Offer: {$bolData->OfferID}");
					}

					// Check inventory before & after ship
					if ( $depletedInventory ) {
						$offerInventoryAfterShip = $this->_getInventoryDataForVerification($this->db, $bolData->OfferID);
						$this->_verifyInventoryDepletion($this->logger, $offerInventoryBeforeShip, $offerInventoryAfterShip);
					}
				}
                else
                {
                    $this->logger->log("Not updating status on Offer {$bolData->OfferID} to SHIPPED due to depletion error. BOL:  {$bolData->BOLID}");
                }
			} catch (\Phalcon\Exception $e) {
				$this->logger->log("Exception in confirmshipmentAction: " . $e->getMessage());
			}

			// send email notification
			$offer = Offer::findFirst("OfferID = '$bolData->OfferID'");
			$userDetails = User::getUserDetail($offer->UserID);
			$to = $offer->OCSContactEmail ?: $userDetails['Email'];
			if ($depletedInventory && $to) {
				$this->logger->log("Sending email for shipped offer");

				$to = $userDetails['FullName'] . '<' . $to . '>';
				if ($this->config->settings['environment'] === "DEVELOPMENT") {
					$to = 'Ric <ric@fai2.com>';
				} else {
                    //					$to .= ', Jordan <jordans@oshkoshcheese.com>';  // removed jordan 6/16/2025
				}

				$baseUrl = $this->config->settings['base_url'];
				$offerLink = "{$baseUrl}offer/edit/{$offer->OfferID}";
				$od = $this->utils->slashDate($offer->OfferDate);

				$cust = Customer::findFirst("CustomerID = '$offer->CustomerID'");
				$subject = "Offer for {$cust->Name} has shipped";
				$headers = 'From: ric@fai2.com';

				$message = <<<EOT
Please note:

  Your offer to {$cust->Name}
	   Offered on $od
	   Has been shipped.

  View offer here: $offerLink

The Cheese Tracker
EOT;

				mail($to, $subject, $message, $headers);
			} // end if to
			$this->view->data = array('success' => 1, 'bolid' => $BOL_ID);
		} else {
			$this->view->data = array('success' => 0, 'msg' => 'Bad BOL ID');
		} // if BOL Data
		$this->view->pick("layouts/json");
	}

	public function unconfirmednotifyAction()
	{
		$this->logger->log('Checking for unconfirmed shipments');
		$bolData = BillOfLading::find("StatusPID = '" . BillOfLading::STATUS_CREATED . "'");
		$this->logger->log('found ' . count($bolData));
		foreach ($bolData as $bol) {
			$offer = Offer::findFirst("OfferID = '$bol->OfferID'");
			$saleDate = strtotime($offer->SaleDate);
			if (time() - $saleDate > 48 * 60 * 60) {
				$this->logger->log("Offer $offer->OfferID has not been shipped in >48 hours, notifying");
				// send email notification
				$this->logger->log("Sending email for unconfirmed offer");
				$userDetails = User::getUserDetail($offer->UserID);
				$to = $offer->OCSContactEmail ?: $userDetails['Email'];
				if ($to) {
					// PUT THESE IN FOR LIVE
					$to = $userDetails['FullName'] . '<' . $to . '>';
					if ($this->config->settings['environment'] === "DEVELOPMENT") {
						$to = 'Ric <ric@fai2.com>';
					} else {
                        //	$to .= ', Jordan <jordans@oshkoshcheese.com>, Bobbie <bobbier@oshkoshcheese.com>'; // removed jordan and bobbie 6/16/2025
					}


					$baseUrl = $this->config->settings['base_url'];
					$offerLink = "$baseUrl/offer/edit/{$offer->OfferID}";

					$cust = Customer::findFirst("CustomerID = '$offer->CustomerID'");
					$od = $this->utils->slashDate($offer->OfferDate);
					$sd = $this->utils->slashDate($offer->SaleDate);

					$subject = 'Sold offer for {$cust->Name} not shipped yet';
					$headers = 'From: ric@fai2.com';
					$message = <<<EOT
Please note:

  Your offer to {$cust->Name}
	   Offered on $od
	   Sold on $sd
	   Has not been shipped yet.

  View offer here: $offerLink

The Cheese Tracker
EOT;

					mail($to, $subject, $message, $headers);
				}
			}
		}
	}


	//        8       o        ooo.            o          o 8
	//        8                8  `8.          8            8
	// .oPYo. 8oPYo. o8 .oPYo. 8   `8 .oPYo.  o8P .oPYo. o8 8
	// Yb..   8    8  8 8    8 8    8 8oooo8   8  .oooo8  8 8
	//   'Yb. 8    8  8 8    8 8   .P 8.       8  8    8  8 8
	// `YooP' 8    8  8 8YooP' 8ooo'  `Yooo'   8  `YooP8  8 8
	// :.....:..:::..:..8 ....:.....:::.....:::..::.....::....
	// :::::::::::::::::8 ::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::..::::::::::::::::::::::::::::::::::::

	// http://devtracker.oshkoshcheese.com/billoflading/shipdetail/03591B6E-B867-4B97-9E70-AD7302491BEE

	public function shipdetailAction()
	{
		$BOLID = $this->dispatcher->getParam("id");
		$this->logger->log('Shipping BOL with ID: ' . $BOLID . "\n\n");

		if (isset($_POST['mode'])) {
			$this->logger->log('POST: Calling _saveShipDetails() with ID: ' . $BOLID);
			$this->_saveShipDetails($BOLID);
			return;
		}

		$bolData = BillOfLading::findFirst("BOLID = '$BOLID'")->toArray();
		$offerData = Offer::getOfferInfo($this->db, $bolData['OfferID']);
		$custOrder =  CustomerOrder::findFirst("OfferID = '" .  $bolData['OfferID'] . "'");

		$ediKey = EDIDocument::getEDIKeyByEDIDocID($custOrder->EDIDocID);
		$customer = Customer::getEDICustomer($ediKey);

		$paramModel = new Parameter();
		$descs = $paramModel->getValuesForGroupId(
			Parameter::PARAMETER_GROUP_DESCS,
			array('orderBy' => 'Value3')
		);

		$description = array();
		$skulookup = array();
		foreach ($descs as $desc) {
			if ($desc['Value4'] !== $ediKey) {
				continue;
			}

			if (intval($desc['Value3']) === 0) {
				continue;
			}

			$description[$desc['Value3']] = $desc['Value1'];
			$skulookup[$desc['ParameterID']] = $desc['Value3'];
		}

$this->logger->log( "skulookup: " . json_encode( $skulookup ) );

		$linecount = 0;

		$this->view->headers = array_filter(
			[
				'Shipped',
				'PO Line',
				'Qty',
				'Prod#',
				'Desc',
				'Batch',
				'Exp Date',
				'Net Wt',
				$ediKey ? 'LicensePlate' : null,
			]
		);
		$this->view->BOLID = $BOLID;

		$fields = array_filter(
			[
				'%%CHECKBOX%%',
				'POLine',
				'PiecesfromVat',
				'PartNum',
				'OfferItemDescription',
				'CustomerLotNumber',
				'MakeDate',
				'WeightfromVat',
				$ediKey ? 'LicensePlate' : null,
			]
		);

		if ($offerData) {
			$readonly = ($bolData['StatusPID'] != BillOfLading::STATUS_CREATED);
			$this->logger->log("DocID: " . $custOrder->EDIDocID . " " . $bolData['OfferID']);
			$this->view->ReadOnly = $readonly;

			$this->view->notes = $offerData->Note; // CustomerOrderNotes + ShipNotes
			// TODO: Walmart?

			$this->view->details = [
				'End Customer' => $custOrder->ShipToName,
				'Address' => $custOrder->ShipToAddr1 . ' ' .
					$custOrder->ShipToAddr2,
				'City' => $custOrder->ShipToCity,
				'State' => $custOrder->ShipToState,
				'ZIP' => $custOrder->ShipToZIP,
				'Cust Order (CO)' => $custOrder->CustomerOrderNum,
				'Order Date' => substr($custOrder->CustomerOrderDate, 0, 10),
				'Ship By' => substr($custOrder->ShipByDate, 0, 10),
				'Total Pieces' => $custOrder->TotalCustomerOrderQty,
				// 'EDI Control#' => $custOrder->ControlNumber,
				'EDI#' => $custOrder->EDIDocID,
				'Customer EDI Key' => $customer->EDIKey,
			];

			$this->view->data = array();
			$rows = array();
			$ids = array();
			$linecount = 0;

			foreach ($offerData as $offerLine) {
				// $this->logger->log("line");
				// $this->logger->log($offerLine);
				$row = array();
				$orderlineraw = false;
				$linecount++;

				$mapraw = OrderOfferDetailMap::findFirst("OfferItemID = '" . $offerLine['OfferItemID'] . "'");

				if ($mapraw) {
					$map = $mapraw->toArray();
					$orderlineraw = CustomerOrderDetail::findFirst("CustomerOrderDetailID = '" . $map['CustomerOrderDetailID'] . "'");
				}

				if ($orderlineraw !== false) {
					$orderline = $orderlineraw->toArray();
				} else {
					$sku = $skulookup[$offerLine['DescriptionPID']];
					$this->logger->log("Can't find OfferItemID: " . $offerLine['OfferItemID'] . " in CustomerOrderDetail making educated guess for product code $sku");
					// Find the customer order line with this SKU. Hopefully this sku doesn't occur on the order twice or we're fucked.
					$orderlineraw = CustomerOrderDetail::findFirst("EDIDocID = '" . $custOrder->EDIDocID . "' AND PartNum = '" . $sku . "'");
					if ($orderlineraw !== false) {
						$orderline = $orderlineraw->toArray();
					} else {
						$orderline = ['PartNum' =>  $sku]; // stub this for sure
						$this->logger->log( "couldn't find $sku for EDIDocID { $custOrder->EDIDocID }\n");
					}
				}
				foreach ($fields as $f) {



					if (false !== strpos($f, 'Date')) { // any date
						$offerLine[$f] = $offerLine[$f] ?: $orderline[$f];
						$offerLine[$f] = substr($offerLine[$f], 0, 10);
					} else if ($f == 'OfferItemDescription' and !$offerLine[$f]) {
						// $this->logger->log( 'OD:' . $offerLine[ $f ] );
						// $this->logger->log( 'PN:' . $orderline[ 'PartNum' ]  );
						$offerLine[$f] = $description[$orderline['PartNum']];
						// $this->logger->log( 'ND:' . $offerLine[ $f ] );
					} else if ($f == '%%CHECKBOX%%') {
						if (!$readonly) {
							$q  = $offerLine['PiecesfromVat'];
							$codid = $orderline['CustomerOrderDetailID'];
							$offerLine[$f] = <<<EOF
	<input type="hidden"   name="codid[]" value="$codid">
	<input type="hidden"   name="oiid[]" value="{$offerLine['OfferItemID']}">
	<input type="hidden"   class="vatqty"   name="vatqty[]"  value="$q">
	<input type="checkbox" class="shipped"  name="shipped[]" value="$codid">
	<input type="text"     class="shipqty"  name="shipqty[]" value="0">
EOF;
						} else {
							$offerLine[$f] = '-';
							if ($offerLine['CustomerOrderDetailID']) {
								$shipped =  CustomerShipDetail::findFirst(
									"CustomerOrderDetailID = " . $offerLine['CustomerOrderDetailID']
								);

								if ($shipped) {
									$offerLine[$f] = (isset($shipped)) ?  $shipped->QtyShipped : 0;
								}
							}
						}
					} else if ($f == 'LicensePlate') {


						$OfferItemVat = OfferItemVat::findFirst([
							'conditions' => 'OfferItemVatID = :id:',
							'bind' => ['id' => $offerLine['OfferItemVatID']]
						]);

						$Vat = Vat::findFirst([
							'conditions' => 'VatID = :id:',
							'bind' => ['id' => $OfferItemVat->VatID]
						]);

						$DeliveryDetail = DeliveryDetail::findFirst([
							'conditions' => 'DeliveryDetailID = :id:',
							'bind' => ['id' => $Vat->DeliveryDetailID]
						]);

						if ($DeliveryDetail) {

							$offerLine[$f] =
								<<<EOF
<input type="text"   name="LicensePlate[]" value="$DeliveryDetail->LicensePlate">
EOF;
						}
					} else {
						$offerLine[$f] = $offerLine[$f] ?: $orderline[$f] ?: '-';
					}


					array_push($row, $offerLine[$f]);

					// $this->logger->log( "$f: " . $offerLine[ $f ] );
				}
				array_push($ids, $orderline['CustomerOrderDetailID']);

				// $this->logger->log( implode( '|', $row ) );
				array_push($rows, $row);
			}

			$this->view->data = $rows;
			$this->view->ids = $ids;
			$this->view->linecount = $linecount;


			$this->view->title = 'Ship Details';
		} else {
			$errorString = 'Bad Ship' . " OfferID: '" . $bolData['OfferID'] . "'";
			$this->logger->log($errorString);

			$this->view->data = array('success' => 0);
			$this->view->data = array('error' => $errorString);

			$this->view->pick("layouts/json");
		}
	}


	//                                  .oPYo. 8       o        ooo.            o
	//                                  8      8                8  `8.          8
	//      .oPYo. .oPYo. o    o .oPYo. `Yooo. 8oPYo. o8 .oPYo. 8   `8 .oPYo.  o8P
	//      Yb..   .oooo8 Y.  .P 8oooo8     `8 8    8  8 8    8 8    8 8oooo8   8
	//        'Yb. 8    8 `b..d' 8.          8 8    8  8 8    8 8   .P 8.       8
	//      `YooP' `YooP8  `YP'  `Yooo' `YooP' 8    8  8 8YooP' 8ooo'  `Yooo'   8
	// oooo :.....::.....:::...:::.....::.....:..:::..:..8 ....:.....:::.....:::..:
	// .....:::::::::::::::::::::::::::::::::::::::::::::8 ::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::::..::::::::::::::::::::::::                                                                        \__|

	private function _saveShipDetails($BOLID)
	{

		$mode = $_POST['mode'];
		$this->logger->log("In _saveShipDetails() in $mode mode with ID: " . $BOLID);
		// $this->logger->log($_POST);

		$qtys = $_POST['shipqty'];
		$oiids = $_POST['oiid'];
		$BOLID = $_POST['bolid'];

		$LicensePlate = $_POST['LicensePlate'];
		// $this->logger->log("LicensePlate");
		// $this->logger->log($LicensePlate);

		$errors = false;
		$edidocid = '';
		$coid = ''; // CustomerOrderID
		$lineqtys = array();

		// $totshipqty = array();
		foreach ($_POST['codid'] as $i => $codid) {
			// $totshipqty[ $codid ] = isset( $totshipqty[ $codid ] ) ? $totshipqty[ $codid ] + $qtys[ $i ] : $qtys[ $i ];
			if (is_array($lineqtys[$codid])) {
				if (isset($lineqtys[$codid][$oiids[$i]])) {
					$lineqtys[$codid][$oiids[$i]] += $qtys[$i];
				} else {
					$lineqtys[$codid] = $lineqtys[$codid] + [$oiids[$i] => $qtys[$i]];
				}
			} else {
				$lineqtys[$codid] = array($oiids[$i] => $qtys[$i]);
			}
		}

		foreach ($_POST['codid'] as $i => $codid) {
			$this->logger->log("Shipping codid: $codid  Qty: " . $qtys[$i] . " OIID: " . $oiids[$i]);

			try {
				$line = CustomerOrderDetail::findFirst([
					'conditions' => "CustomerOrderDetailID = :codid:",
					'bind'       => ['codid' => $codid]
				]);
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log($e->getModel()->getMessages());
				$errors = true;
			}

			$coid = $line->CustomerOrderID;


			if (isset($line) and ($codid = $line->CustomerOrderDetailID)) {
				$edidocid = $line->EDIDocID;
				try {
					$ship = CustomerShipDetail::findFirst([
						'CustomerOrderDetailID = :codid: AND OfferItemID = :oiid:',
						'bind' => [
							'codid' => $codid,
							'oiid'  => $oiids[$i]
						]
					]);
				} catch (\Phalcon\Mvc\Model\Exception $e) {
					$this->logger->log($e->getModel()->getMessages());
					$errors = true;
				}

				if (!$ship) {
					$this->logger->log("Creating ship record for: " . $oiids[$i]);
					$ship = new CustomerShipDetail();
				}

				$ship->LicensePlate = $LicensePlate[$i];
				$ship->CustomerOrderDetailID = $codid;
				$ship->OfferItemID = $oiids[$i];
				$ship->EDIDocID = $edidocid;
				$ship->QtyShipped = isset($lineqtys[$codid][$oiids[$i]]) ? $lineqtys[$codid][$oiids[$i]] : 0;
				$ship->ShippedDate = $this->mysqlDate;
				$ship->CreateDate = $this->mysqlDate;
				$ship->UpdateDate = $this->mysqlDate;

				try {
					if ($ship->save() == false) {
						$msg = "Error saving ship:\n" . implode(
							"\n",
							$ship->getMessages()
						);
						$this->logger->log($msg);
						$errors = true;
					}
				} catch (\Phalcon\Mvc\Model\Exception $e) {
					$this->logger->log($e->getModel()->getMessages());
					$errors = true;
				}
			} else {
				$errors = true;
				$this->logger->log("Shipping Failed - No Matching Detail: CustomerOrderDetailID: $codid Qty: " . $qtys[$i]);
			}
		}

		$stopEDI = false; // stops 945 generation and status update
		$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';

		try {
			$ediKey = EDIDocument::getEDIKeyByEDIDocID($edidocid);
		} catch (\Phalcon\Exception $e) {
			$msg = "Exception getting getEDIKeyByEDIDocID:\n" . $e->getMessage();
			$this->logger->log("$msg\n");
		}

		if (!$stopEDI && !$errors) {
			$this->logger->log("Updating status on CustomerOrder $coid");
			$shipment = CustomerOrder::findFirst($coid);
			if ($shipment) {
				$shipment->StatusPID = CustomerOrder::STATUS_SHIPPED;
				$shipment->save();
			}

			$this->logger->log("Updating status on BOL: $BOLID");
			$bolData = BillOfLading::findFirst("BOLID = '$BOLID'");
			if ($bolData) {
				$this->logger->log("Depleting Inventory Updating status on BOLID: $BOLID");

				$offerInventoryBeforeShip = $this->_getInventoryDataForVerification($this->db, $bolData->OfferID);

				try {
					$url = $protocol . $_SERVER['HTTP_HOST'] . "/offer/depleteinventory";
					$responseobj = $this->utils->POST($url, ['offerID' => $bolData->OfferID]);
				} catch (\Phalcon\Exception $e) {
					$msg = "Exception depleting inventory:\n" . $e->getMessage();
					$this->logger->log("$msg\n");
				}

				if ($responseobj['success']) {
					$this->logger->log("Updating SHIPPED status on BOL: {$BOLID}");
					try {
						$bolData->StatusPID = BillOfLading::STATUS_SHIPPED;
						// RICDEBUG
						$bolData->save();
					} catch (\Phalcon\Exception $e) {
						$this->logger->log("Exception in _saveShipDetails updating status to SHIPPED on BOL: {$BOLID}");
					}

					$this->logger->log("Updating SHIPPED status on Offer: {$bolData->OfferID}");
					try{
						$offerData = Offer::findFirst("OfferID = '{$bolData->OfferID}'");
						$offerData->OfferStatusPID = Offer::STATUS_SHIPPED;
						// RICDEBUG
						$offerData->save();
					} catch (\Phalcon\Exception $e) {
						$this->logger->log("Exception in _saveShipDetails updating status to SHIPPED on Offer: {$bolData->OfferID}");
					}

					$offerInventoryAfterShip = $this->_getInventoryDataForVerification($this->db, $bolData->OfferID);
					$this->_verifyInventoryDepletion($this->logger, $offerInventoryBeforeShip, $offerInventoryAfterShip);

					$this->view->data = array('success' => 1, 'bolid' => $BOLID);
				} else {
                    $this->logger->log("Not updating status on Offer {$bolData->OfferID} to SHIPPED due to depletion error. BOL:  {$bolData->BOLID}");
					$this->view->data = array('success' => 0, 'msg' => 'Failed to deplete inventory.');
				}
			} else {
				$this->logger->log("No BOL Data Found for BOLID: $BOLID");
			}

			// Trigger EDI generation
			$url = $protocol . $_SERVER['HTTP_HOST'] . "/edidocument/generatex12/945/{$ediKey}/{$BOLID}/{$edidocid}";

			$this->logger->log("_saveShipDetails() in BillofladingController.php url");
			$this->logger->log("Creating x12 by url: $url");
			$responseobj = $this->utils->GET($url);
			$edi_status = $responseobj['success'];
			$this->logger->log("x12 response: $edi_status");
		} else {
			$url = $protocol . $_SERVER['HTTP_HOST'] . "/edidocument/generatex12/945/{$ediKey}/{$BOLID}/{$edidocid}";
			$this->logger->log("Error with BOL 945: Run this to generate EDI: $url");
		}

		$this->dispatcher->forward(
			[
				'action' => 'list',
				'params' => [BillOfLading::STATUS_CREATED]
			]
		);
	}
}
