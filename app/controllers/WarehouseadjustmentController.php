<?php

use Phalcon\Mvc\Controller;

class WarehouseAdjustmentController extends Controller {
	// $$\ $$\             $$\
	// $$ |\__|            $$ |
	// $$ |$$\  $$$$$$$\ $$$$$$\
	// $$ |$$ |$$  _____|\_$$  _|
	// $$ |$$ |\$$$$$$\    $$ |
	// $$ |$$ | \____$$\   $$ |$$\
	// $$ |$$ |$$$$$$$  |  \$$$$  |
	// \__|\__|\_______/    \____/

	/**
	 * Gets the warehouse adjustments for a given Vat
	 *
	 * @param string $vatID ID used to get the vat and the corresponding data
	 *
	 * @author Robert Kinney
	 *
	 * @access public
	 */



	public function listAction() {
		$vatID = $this->dispatcher->getParam("id");
		$vat = Vat::findFirst("VatID = '$vatID'");
		$lot = Lot::findFirst("LotID = '" . $vat->LotID . "'");
		$customer = Customer::findFirst("CustomerID = '" . $lot->CustomerID . "'");

		$warehouseAdjustmentList = WarehouseAdjustment::find([
			'conditions' => "LotID = :LotID: AND VatID = :VatID:",
			'bind' => [
				'LotID' => $lot->LotID,
				'VatID' => $vat->VatID
			],
			'order' => 'WarehouseAdjustmentID ASC'
		]);

		// Change this to be an array so we can keep altered values for the view
		$warehouseAdjustmentList = iterator_to_array($warehouseAdjustmentList);

		$this->alterValuesForProductCodes($warehouseAdjustmentList);

		$this->view->warehouseAdjustmentList = $warehouseAdjustmentList;
		$this->view->customer = $customer;

		$this->view->pick("edidocument/ajax/pastAdjustments");
	}

	//                  $$\       $$\
	//                  $$ |      $$ |
	//   $$$$$$\   $$$$$$$ | $$$$$$$ |
	//   \____$$\ $$  __$$ |$$  __$$ |
	//   $$$$$$$ |$$ /  $$ |$$ /  $$ |
	//  $$  __$$ |$$ |  $$ |$$ |  $$ |
	//  \$$$$$$$ |\$$$$$$$ |\$$$$$$$ |
	//   \_______| \_______| \_______|

	/**
	 * Adds new Warehouse Adjustment records
	 *
	 * Adds new Warehouse Adjustment records and will generate the edi document records if needed
	 *
	 * @param array $_POST POST data from the inventory adjustment
	 *
	 * @author Robert Kinney
	 *
	 * @access public
	 */
	public function addAction() {

		$this->logger->log("Starting addAction function.");

		$lot = Lot::findFirst("LotID = '" . $_POST['lotID'] . "'");

		$vat = Vat::findFirst(
			"VatID = '" . $_POST['vatID'] . "'"
		);

		$customer = Customer::findFirst(
			"CustomerID = '" . $_POST['customerID'] . "'"
		);

		$warehouseAdjustmentArray = array();

		$serviceID = $_POST['adjustmentReasonSelect'] == '05' ? 'HOLD' : '0';

		$this->logger->log("serviceID: " . $serviceID);


		$this->logger->log("lot === false || vat === false || customer === false");
		$this->logger->log($lot === false || $vat === false || $customer === false);
		$this->logger->log("END: lot === false || vat === false || customer === false");

		if ($lot === false || $vat === false || $customer === false) {
			return json_encode(
				[
					"success" => 0,
					"message" => 'Invalid data sent to request'
				]
			);
		}


		// If the using weight and the weight has changed
		if ($lot->StorageUnit == Lot::UNIT_LB && $vat->Weight != $_POST['storageAmount']) {

			$weightAdjustment = new WarehouseAdjustment();

			$weightAdjustment->VatID = $vat->VatID;
			$weightAdjustment->LotID = $lot->LotID;
			$weightAdjustment->PreviousValue = $vat->Weight;
			$weightAdjustment->NewValue = $_POST['storageAmount'];
			$weightAdjustment->ValueTypeChanged = $lot->StorageUnit;
			$weightAdjustment->ValueTypeChangedPlainText = WarehouseAdjustment::CHANGED_WEIGHT;
			$weightAdjustment->AdjustmentReason = $_POST['adjustmentReasonSelect'];
			$weightAdjustment->NetWeight = $_POST['storageAmount'] - $vat->Weight;
			$weightAdjustment->CreditDebitQuantity = $_POST['storageAmount'] - $vat->Weight;
			$weightAdjustment->MeasurementCode = Lot::UNIT_LB;
			$weightAdjustment->ProductID = $_POST['productCode'];
			$weightAdjustment->InventoryTransactionTypeCode = WarehouseAdjustment::INVENTORY_TRANSACTION_INVENTORY_ADJUSTMENT_DECREASE;
			$weightAdjustment->ServiceID = $serviceID;
			$weightAdjustment->CreateDate = $this->mysqlDate;

			array_push($warehouseAdjustmentArray, $weightAdjustment);

			$vat->Weight = $_POST['storageAmount'];
		}

		if ($_POST['inventoryPieces'] != $vat->Pieces) {
			$piecesAdjustment = new WarehouseAdjustment();

			$piecesAdjustment->VatID = $vat->VatID;
			$piecesAdjustment->LotID = $lot->LotID;
			$piecesAdjustment->PreviousValue = $vat->Pieces;
			$piecesAdjustment->NewValue = $_POST['inventoryPieces'];

			$piecesAdjustment->ValueTypeChanged = 'EA';

			$piecesAdjustment->ValueTypeChangedPlainText = WarehouseAdjustment::CHANGED_PIECES;
			$piecesAdjustment->AdjustmentReason = $_POST['adjustmentReasonSelect'];
			$piecesAdjustment->NetWeight = ($vat->Pieces - $_POST['inventoryPieces']) * ($vat->Weight / $vat->Pieces);
			$piecesAdjustment->CreditDebitQuantity = $_POST['storageAmount'] - $vat->Weight;

			$piecesAdjustment->MeasurementCode = 'EA';
			$piecesAdjustment->ProductID = $_POST['productCode'];
			$piecesAdjustment->InventoryTransactionTypeCode = WarehouseAdjustment::INVENTORY_TRANSACTION_INVENTORY_ADJUSTMENT_DECREASE;
			$piecesAdjustment->ServiceID = $serviceID;
			$piecesAdjustment->CreateDate = $this->mysqlDate;

			array_push($warehouseAdjustmentArray, $piecesAdjustment);

			$vat->Pieces = $_POST['inventoryPieces'];
		}

		// If the product classification has changed


		if ($_POST['productCode'] != $lot->ProductCode) {

			$this->logger->log("this->allowProductCodeUpdate(_POST['adjustmentReasonSelect']) === false");

			$this->logger->log($this->allowProductCodeUpdate($_POST['adjustmentReasonSelect'], $customer->EDIKey) === false);

			// Not every reason should allow you to change the product code so we need to double check
			if ($this->allowProductCodeUpdate($_POST['adjustmentReasonSelect'], $customer->EDIKey) === false) {
				return json_encode([
					"success" => 0,
					"message" => 'You cannot change a product code for that adjustment reason.'
				]);
			}

			// This is set to 0 here for a check that's done later to assign the same id to each member of the group
			$warehouseAdjustmentGroupFiller = 0;

			// DECREASE
			$productCodeDecreaseAdjustment = new WarehouseAdjustment();

			$productCodeDecreaseAdjustment->WarehouseAdjustmentGroup = $warehouseAdjustmentGroupFiller;
			$productCodeDecreaseAdjustment->VatID = $vat->VatID;
			$productCodeDecreaseAdjustment->LotID = $lot->LotID;
			$productCodeDecreaseAdjustment->PreviousValue = $vat->Weight;
			$productCodeDecreaseAdjustment->NewValue = 0;
			$productCodeDecreaseAdjustment->ValueTypeChanged = Lot::UNIT_LB;
			$productCodeDecreaseAdjustment->ValueTypeChangedPlainText = WarehouseAdjustment::CHANGED_PRODUCT_CODE;
			$productCodeDecreaseAdjustment->AdjustmentReason = $_POST['adjustmentReasonSelect'];


			$productCodeDecreaseAdjustment->NetWeight = -1 * abs($vat->Weight);
			$productCodeDecreaseAdjustment->CreditDebitQuantity = -1 * abs($vat->Weight);

			$productCodeDecreaseAdjustment->MeasurementCode = Lot::UNIT_LB;
			$productCodeDecreaseAdjustment->ProductID = $lot->ProductCode;
			$productCodeDecreaseAdjustment->InventoryTransactionTypeCode = WarehouseAdjustment::INVENTORY_TRANSACTION_INVENTORY_ADJUSTMENT_DECREASE;
			$productCodeDecreaseAdjustment->ServiceID = $serviceID;
			$productCodeDecreaseAdjustment->CreateDate = $this->mysqlDate;

			// INCREASE
			$productCodeIncreaseAdjustment = new WarehouseAdjustment();

			$productCodeIncreaseAdjustment->WarehouseAdjustmentGroup = $warehouseAdjustmentGroupFiller;
			$productCodeIncreaseAdjustment->VatID = $vat->VatID;
			$productCodeIncreaseAdjustment->LotID = $lot->LotID;
			$productCodeIncreaseAdjustment->PreviousValue = 0;
			$productCodeIncreaseAdjustment->NewValue = $vat->Weight;
			$productCodeIncreaseAdjustment->ValueTypeChanged = Lot::UNIT_LB;
			$productCodeIncreaseAdjustment->ValueTypeChangedPlainText = WarehouseAdjustment::CHANGED_PRODUCT_CODE;
			$productCodeIncreaseAdjustment->AdjustmentReason = $_POST['adjustmentReasonSelect'];


			$productCodeIncreaseAdjustment->NetWeight = abs($vat->Weight);
			$productCodeIncreaseAdjustment->CreditDebitQuantity = abs($vat->Weight);


			$productCodeIncreaseAdjustment->MeasurementCode = Lot::UNIT_LB;
			$productCodeIncreaseAdjustment->ProductID = $_POST['productCode'];
			// TODO? is this the right transaction type for increasing inventory?
			$productCodeIncreaseAdjustment->InventoryTransactionTypeCode = WarehouseAdjustment::INVENTORY_TRANSACTION_SALEABLE_INVENTORY;
			$productCodeIncreaseAdjustment->ServiceID = $serviceID;
			$productCodeIncreaseAdjustment->CreateDate = $this->mysqlDate;

			// We only need to add this to the array once
			array_push($warehouseAdjustmentArray, $productCodeDecreaseAdjustment, $productCodeIncreaseAdjustment);

			$lot->ProductCode = $_POST['productCode'];
		}

		// If nothing was updated, then we can just return
		if (empty($warehouseAdjustmentArray)) {
			return json_encode(["success" => 0, "message" => 'No changes were made']);
		}

		$this->db->begin();

		$saveResponse = $this->saveNewAdjustmentData($lot, $vat, $warehouseAdjustmentArray);

		if ($saveResponse['success'] === 0) {
			$this->db->rollback();

			return json_encode(['success' => 0, 'message' => $saveResponse['message']]);
		}

		// Saving the changes to the Lot/Vat
		$this->db->commit();

		// If they have chosen to send an EDI Document when saving changes to inventory
		if ($_POST['sendEDI'] == 'true') {

			// need to post to
			// http://devtracker.oshkoshcheese.com/edidocument/generatex12/947/SAP/10
			foreach ($warehouseAdjustmentArray as $warehouseAdjustment) {
				$ediX12Response = $this->sendEDIAndMakeX12($customer->EDIKey, $warehouseAdjustment->WarehouseAdjustmentGroup);

				if ($ediX12Response['success'] !== 1) {

					return json_encode(['success' => 0, 'message' => 'Failed to generate an EDI Document and the X12']);
				}
			}
		}

		$response = [
			'success' => 1,
			'message' => 'Successfully updated inventory'
		];

		return json_encode($response);
	}



	private function allowProductCodeUpdate($adjustmentReason, $ediKey) {
		$this->logger->log("adjustmentReason");
		$this->logger->log($adjustmentReason);

		require_once(dirname(__FILE__) . "/EdidocumentController.php");

		$processor = EDIDocumentController::getProcessor($ediKey);

		$this->logger->log("processor->getAdjustmentReasonCodes()[adjustmentReason]['prodReadOnly'] == 0");
		$this->logger->log($processor->getAdjustmentReasonCodes()[$adjustmentReason]['prodReadOnly'] == 0);

		return $processor->getAdjustmentReasonCodes()[$adjustmentReason]['prodReadOnly'] == 0;
	}

	//                 $$\ $$\   $$\
	//                 $$ |\__|  $$ |
	//  $$$$$$\   $$$$$$$ |$$\ $$$$$$\
	// $$  __$$\ $$  __$$ |$$ |\_$$  _|
	// $$$$$$$$ |$$ /  $$ |$$ |  $$ |
	// $$   ____|$$ |  $$ |$$ |  $$ |$$\
	// \$$$$$$$\ \$$$$$$$ |$$ |  \$$$$  |
	//  \_______| \_______|\__|   \____/

	/**
	 * Gets the details needed to edit a Lot/Vat inventory
	 *
	 * @param string $id Get parameter for the VatID
	 *
	 * @author Robert Kinney
	 *
	 * @access public
	 */
	public function editAction() {
		$vatID = $this->dispatcher->getParam("id");

		$this->view->pick("edidocument/ajax/getinventorydetail");

		$vat = Vat::findFirst("VatID = '$vatID'");

		$lot = Lot::findFirst("LotID = '" . $vat->LotID . "'");

		$customer = Customer::findFirst("CustomerID = '" . $lot->CustomerID . "'");

		require_once(dirname(__FILE__) . "/EdidocumentController.php");

		$processor = EDIDocumentController::getProcessor($customer->EDIKey);

		// Get a record of all the edi adjustments
		$warehouseAdjustmentList = WarehouseAdjustment::find([
			'conditions' => "LotID = :LotID: AND VatID = :VatID:",
			'bind' => [
				'LotID' => $lot->LotID,
				'VatID' => $vat->VatID
			]
		]);

		$this->view->serviceIDArray = WarehouseAdjustment::PRODUCT_SERVICE_ID_ARRAY;
		$this->view->ediInventoryAdjustmentReasonArray = $processor->getAdjustmentReasonCodes();
		$this->view->warehouseAdjustmentList = $warehouseAdjustmentList;
		$this->view->utils = $this->utils;
		$this->view->vat = $vat;
		$this->view->customer = $customer;
		$this->view->lot = $lot;
		$this->view->lotDescription = Parameter::getValue($lot->DescriptionPID);
	}

	//                                     $$\                 $$\ $$\
	//                                     $$ |                $$ |\__|
	//  $$$$$$$\  $$$$$$\  $$$$$$$\   $$$$$$$ | $$$$$$\   $$$$$$$ |$$\
	// $$  _____|$$  __$$\ $$  __$$\ $$  __$$ |$$  __$$\ $$  __$$ |$$ |
	// \$$$$$$\  $$$$$$$$ |$$ |  $$ |$$ /  $$ |$$$$$$$$ |$$ /  $$ |$$ |
	//  \____$$\ $$   ____|$$ |  $$ |$$ |  $$ |$$   ____|$$ |  $$ |$$ |
	// $$$$$$$  |\$$$$$$$\ $$ |  $$ |\$$$$$$$ |\$$$$$$$\ \$$$$$$$ |$$ |
	// \_______/  \_______|\__|  \__| \_______| \_______| \_______|\__|

	/**
	 * Sends an edi document for a past adjustment
	 *
	 * @param String edikey EDI key for a given customer
	 * @param String warehouseadjustmentgroup WarehouseAdjustment group to get related adjustments
	 *
	 * @author Robert Kinney
	 *
	 * @access public
	 *
	 * @return mixed $responseObject Response from the internal curl request to edidocument/generatex12/
	 */
	public function sendediAction() {
		$this->logger->log('Sending WarehouseAdjustment EDI');

		$ediKey = $_POST['edikey'];
		$warehouseAdjustmentGroup = $_POST['warehouseAdjustmentGroup'];

		$sendEDIResponse = $this->sendEDIAndMakeX12($ediKey, $warehouseAdjustmentGroup);

		return json_encode($sendEDIResponse);
	}


	//        8   o               o     o        8                       ooooo               .oPYo.                   8                 o  .oPYo.             8
	//        8   8               8     8        8                       8                   8    8                   8                 8  8    8             8
	// .oPYo. 8  o8P .oPYo. oPYo. 8     8 .oPYo. 8 o    o .oPYo. .oPYo. o8oo   .oPYo. oPYo. o8YooP' oPYo. .oPYo. .oPYo8 o    o .oPYo.  o8P 8      .oPYo. .oPYo8 .oPYo. .oPYo.
	// .oooo8 8   8  8oooo8 8  `' `b   d' .oooo8 8 8    8 8oooo8 Yb..    8     8    8 8  `'  8      8  `' 8    8 8    8 8    8 8    '   8  8      8    8 8    8 8oooo8 Yb..
	// 8    8 8   8  8.     8      `b d'  8    8 8 8    8 8.       'Yb.  8     8    8 8      8      8     8    8 8    8 8    8 8    .   8  8    8 8    8 8    8 8.       'Yb.
	// `YooP8 8   8  `Yooo' 8       `8'   `YooP8 8 `YooP' `Yooo' `YooP'  8     `YooP' 8      8      8     `YooP' `YooP' `YooP' `YooP'   8  `YooP' `YooP' `YooP' `Yooo' `YooP'
	// :.....:..::..::.....:..:::::::..::::.....:..:.....::.....::.....::..:::::.....:..:::::..:::::..:::::.....::.....::.....::.....:::..::.....::.....::.....::.....::.....:
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	/**
	 * Alters the array of warehouse adjustments to show the product code change instead of weight.
	 *
	 * @param Array Array of warehouse adjustments
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 */
	private function alterValuesForProductCodes(&$warehouseAdjustmentList) {
		$groupingCode = '';
		$oldValue = '';
		$previousKey = '';

		// Loop through each of the changes
		foreach ($warehouseAdjustmentList as $key => $warehouseAdjustment) {
			// If we come across a product code, then we know there will be two entries
			if ($warehouseAdjustment->ValueTypeChangedPlainText == WarehouseAdjustment::CHANGED_PRODUCT_CODE) {
				// If the current grouping code is different
				if ($groupingCode != $warehouseAdjustment->WarehouseAdjustmentGroup) {
					$oldValue = $warehouseAdjustment->ProductID;
					$groupingCode = $warehouseAdjustment->WarehouseAdjustmentGroup;
				} else {
					// We are on the same grouped entries
					$warehouseAdjustmentList[$key]->NewValue = $warehouseAdjustment->ProductID;
					$warehouseAdjustmentList[$key]->PreviousValue = $oldValue;
					$oldValue = '';

					unset($warehouseAdjustmentList[$previousKey]);
				}
			}

			$previousKey = $key;
		}
	}

	//                                     $$\ $$$$$$$$\ $$$$$$$\  $$$$$$\  $$$$$$\                  $$\ $$\      $$\           $$\                 $$\   $$\   $$\    $$$$$$\
	//                                     $$ |$$  _____|$$  __$$\ \_$$  _|$$  __$$\                 $$ |$$$\    $$$ |          $$ |                $$ |  $$ |$$$$ |  $$  __$$\
	//  $$$$$$$\  $$$$$$\  $$$$$$$\   $$$$$$$ |$$ |      $$ |  $$ |  $$ |  $$ /  $$ |$$$$$$$\   $$$$$$$ |$$$$\  $$$$ | $$$$$$\  $$ |  $$\  $$$$$$\  \$$\ $$  |\_$$ |  \__/  $$ |
	// $$  _____|$$  __$$\ $$  __$$\ $$  __$$ |$$$$$\    $$ |  $$ |  $$ |  $$$$$$$$ |$$  __$$\ $$  __$$ |$$\$$\$$ $$ | \____$$\ $$ | $$  |$$  __$$\  \$$$$  /   $$ |   $$$$$$  |
	// \$$$$$$\  $$$$$$$$ |$$ |  $$ |$$ /  $$ |$$  __|   $$ |  $$ |  $$ |  $$  __$$ |$$ |  $$ |$$ /  $$ |$$ \$$$  $$ | $$$$$$$ |$$$$$$  / $$$$$$$$ | $$  $$<    $$ |  $$  ____/
	//  \____$$\ $$   ____|$$ |  $$ |$$ |  $$ |$$ |      $$ |  $$ |  $$ |  $$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |\$  /$$ |$$  __$$ |$$  _$$<  $$   ____|$$  /\$$\   $$ |  $$ |
	// $$$$$$$  |\$$$$$$$\ $$ |  $$ |\$$$$$$$ |$$$$$$$$\ $$$$$$$  |$$$$$$\ $$ |  $$ |$$ |  $$ |\$$$$$$$ |$$ | \_/ $$ |\$$$$$$$ |$$ | \$$\ \$$$$$$$\ $$ /  $$ |$$$$$$\ $$$$$$$$\
	// \_______/  \_______|\__|  \__| \_______|\________|\_______/ \______|\__|  \__|\__|  \__| \_______|\__|     \__| \_______|\__|  \__| \_______|\__|  \__|\______|\________|

	/**
	 * Saves data surrounding the WarehouseAdjustment
	 *
	 * @param String $ediKey Edi key to show what customer is using this method
	 * @param Int $warehouseAdjustmentGroup Number used to reference warehouse adjustments since some can have 2 entries
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 *
	 * @return mixed EDIDocument object or false if it fails
	 */
	private function sendEDIAndMakeX12($ediKey, $warehouseAdjustmentGroup) {
		$response = ['success' => 0];

		$warehouseAdjustmentList = WarehouseAdjustment::getWarehouseAdjustmentsByGroup($warehouseAdjustmentGroup);

		if (count($warehouseAdjustmentList) < 1) {
			return $response;
		}

		// We only need to generate one EDI Document, so just send one of the adjustments
		$ediDocument = $this->generateEDIDocument($ediKey, $warehouseAdjustmentList[0]);

		if ($ediDocument === false) {
			return $response;
		}

		foreach ($warehouseAdjustmentList as $warehouseAdjustment) {
			$warehouseAdjustment->EDIDocumentID = $ediDocument->DocID;

			if ($warehouseAdjustment->save() === false) {
				$this->logSaveFail($warehouseAdjustment, 'sendEDIAndMakeX12');

				return $response;
			}
		}

		$generateX12Response = $this->generateX12(($ediDocument));

		if ($generateX12Response === false) {
			$this->logger->log("Something went wrong when trying to generate the X12 document.");
		}

		$response['success'] = (int)($generateX12Response);

		return $response;
	}

	//                                                             $$\               $$$$$$$$\ $$$$$$$\  $$$$$$\ $$$$$$$\                                                                   $$\
	//                                                             $$ |              $$  _____|$$  __$$\ \_$$  _|$$  __$$\                                                                  $$ |
	//  $$$$$$\   $$$$$$\  $$$$$$$\   $$$$$$\   $$$$$$\  $$$$$$\ $$$$$$\    $$$$$$\  $$ |      $$ |  $$ |  $$ |  $$ |  $$ | $$$$$$\   $$$$$$$\ $$\   $$\ $$$$$$\$$$$\   $$$$$$\  $$$$$$$\ $$$$$$\
	// $$  __$$\ $$  __$$\ $$  __$$\ $$  __$$\ $$  __$$\ \____$$\\_$$  _|  $$  __$$\ $$$$$\    $$ |  $$ |  $$ |  $$ |  $$ |$$  __$$\ $$  _____|$$ |  $$ |$$  _$$  _$$\ $$  __$$\ $$  __$$\\_$$  _|
	// $$ /  $$ |$$$$$$$$ |$$ |  $$ |$$$$$$$$ |$$ |  \__|$$$$$$$ | $$ |    $$$$$$$$ |$$  __|   $$ |  $$ |  $$ |  $$ |  $$ |$$ /  $$ |$$ /      $$ |  $$ |$$ / $$ / $$ |$$$$$$$$ |$$ |  $$ | $$ |
	// $$ |  $$ |$$   ____|$$ |  $$ |$$   ____|$$ |     $$  __$$ | $$ |$$\ $$   ____|$$ |      $$ |  $$ |  $$ |  $$ |  $$ |$$ |  $$ |$$ |      $$ |  $$ |$$ | $$ | $$ |$$   ____|$$ |  $$ | $$ |$$\
	// \$$$$$$$ |\$$$$$$$\ $$ |  $$ |\$$$$$$$\ $$ |     \$$$$$$$ | \$$$$  |\$$$$$$$\ $$$$$$$$\ $$$$$$$  |$$$$$$\ $$$$$$$  |\$$$$$$  |\$$$$$$$\ \$$$$$$  |$$ | $$ | $$ |\$$$$$$$\ $$ |  $$ | \$$$$  |
	//  \____$$ | \_______|\__|  \__| \_______|\__|      \_______|  \____/  \_______|\________|\_______/ \______|\_______/  \______/  \_______| \______/ \__| \__| \__| \_______|\__|  \__|  \____/
	// $$\   $$ |
	// \$$$$$$  |
	//  \______/

	/**
	 * Saves data surrounding the WarehouseAdjustment
	 *
	 * @param String $ediKey Edi key to show what customer is using this method
	 * @param WarehouseAdjustment $WarehouseAdjustment object
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 *
	 * @return mixed EDIDocument object or false if it fails
	 */
	private function generateEDIDocument($ediKey, $warehouseAdjustment) {
		$argumentArray = [
			EDIDocument::WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE,
			$ediKey,
			$warehouseAdjustment->WarehouseAdjustmentGroup
		];

		$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
		$argumentsString = implode("/", $argumentArray);
		$url = $protocol . $_SERVER['HTTP_HOST'] . "/edidocument/add/$argumentsString";

		// Need to wrap in try/catch in order to catch errors from the internal call
		try {
			$httpResponse = $this->utils->GET($url);
		} catch (\Phalcon\Mvc\Model\Exception $e) {
			$this->logger->log('Exception thrown in generateEDIDocument');
			$this->logger->log($e->getModel()->getMessages());

			return false;
		}

		if ($httpResponse['success'] !== 1) {
			$this->logger->log("http response");
			$this->logger->log($httpResponse);

			return false;
		}

		$ediDocument = EDIDocument::findFirst('DocID = ' . $httpResponse['EDIDocumentID']);

		return $ediDocument;
	}

	//                                         $$\   $$\                          $$$$$$\        $$\                           $$\                                        $$\     $$$$$$$\             $$\
	//                                         $$$\  $$ |                        $$  __$$\       $$ |                          $$ |                                       $$ |    $$  __$$\            $$ |
	//  $$$$$$$\  $$$$$$\ $$\    $$\  $$$$$$\  $$$$\ $$ | $$$$$$\  $$\  $$\  $$\ $$ /  $$ | $$$$$$$ |$$\ $$\   $$\  $$$$$$$\ $$$$$$\   $$$$$$\$$$$\   $$$$$$\  $$$$$$$\ $$$$$$\   $$ |  $$ | $$$$$$\ $$$$$$\    $$$$$$\
	// $$  _____| \____$$\\$$\  $$  |$$  __$$\ $$ $$\$$ |$$  __$$\ $$ | $$ | $$ |$$$$$$$$ |$$  __$$ |\__|$$ |  $$ |$$  _____|\_$$  _|  $$  _$$  _$$\ $$  __$$\ $$  __$$\\_$$  _|  $$ |  $$ | \____$$\\_$$  _|   \____$$\
	// \$$$$$$\   $$$$$$$ |\$$\$$  / $$$$$$$$ |$$ \$$$$ |$$$$$$$$ |$$ | $$ | $$ |$$  __$$ |$$ /  $$ |$$\ $$ |  $$ |\$$$$$$\    $$ |    $$ / $$ / $$ |$$$$$$$$ |$$ |  $$ | $$ |    $$ |  $$ | $$$$$$$ | $$ |     $$$$$$$ |
	//  \____$$\ $$  __$$ | \$$$  /  $$   ____|$$ |\$$$ |$$   ____|$$ | $$ | $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |  $$ | \____$$\   $$ |$$\ $$ | $$ | $$ |$$   ____|$$ |  $$ | $$ |$$\ $$ |  $$ |$$  __$$ | $$ |$$\ $$  __$$ |
	// $$$$$$$  |\$$$$$$$ |  \$  /   \$$$$$$$\ $$ | \$$ |\$$$$$$$\ \$$$$$\$$$$  |$$ |  $$ |\$$$$$$$ |$$ |\$$$$$$  |$$$$$$$  |  \$$$$  |$$ | $$ | $$ |\$$$$$$$\ $$ |  $$ | \$$$$  |$$$$$$$  |\$$$$$$$ | \$$$$  |\$$$$$$$ |
	// \_______/  \_______|   \_/     \_______|\__|  \__| \_______| \_____\____/ \__|  \__| \_______|$$ | \______/ \_______/    \____/ \__| \__| \__| \_______|\__|  \__|  \____/ \_______/  \_______|  \____/  \_______|
	//                                                                                         $$\   $$ |
	//                                                                                         \$$$$$$  |
	//                                                                                          \______/

	/**
	 * Saves data surrounding the WarehouseAdjustment
	 *
	 * @param Lot $lot Lot object
	 * @param Vat $vat Vat object
	 * @param Array $warehouseAdjustmentArray Array of warehouseAdjustment objects
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 *
	 * @return Array $response A response composed of a success/fail flag and a message on what went wrong
	 */
	private function saveNewAdjustmentData($lot, $vat, $warehouseAdjustmentArray) {
		$errorMessage = '';

		try {
			if ($lot->save() == false) {
				$this->logSaveFail($lot);
				$errorMessage .= 'Failed to save the Lot data';
			}

			if ($vat->save() == false) {
				$this->logSaveFail($vat);
				$errorMessage .= 'Failed to save the Vat data';
			}

			if ($this->saveWarehouseAdjustmentArray($warehouseAdjustmentArray) === false) {
				$errorMessage .= 'Failed to save warehouse adjustments';
			}
		} catch (\Phalcon\Mvc\Model\Exception $e) {
			$this->logger->log('Exception thrown');
			$this->logger->log($e->getModel()->getMessages());

			$errorMessage .= $e->getModel()->getMessages();
		}

		return [
			'success' => (int)($errorMessage === ''),
			'message' => $errorMessage
		];
	}

	//                                         $$\      $$\                               $$\                                                $$$$$$\        $$\                           $$\                                        $$\      $$$$$$\
	//                                         $$ | $\  $$ |                              $$ |                                              $$  __$$\       $$ |                          $$ |                                       $$ |    $$  __$$\
	//  $$$$$$$\  $$$$$$\ $$\    $$\  $$$$$$\  $$ |$$$\ $$ | $$$$$$\   $$$$$$\   $$$$$$\  $$$$$$$\   $$$$$$\  $$\   $$\  $$$$$$$\  $$$$$$\  $$ /  $$ | $$$$$$$ |$$\ $$\   $$\  $$$$$$$\ $$$$$$\   $$$$$$\$$$$\   $$$$$$\  $$$$$$$\ $$$$$$\   $$ /  $$ | $$$$$$\   $$$$$$\  $$$$$$\  $$\   $$\
	// $$  _____| \____$$\\$$\  $$  |$$  __$$\ $$ $$ $$\$$ | \____$$\ $$  __$$\ $$  __$$\ $$  __$$\ $$  __$$\ $$ |  $$ |$$  _____|$$  __$$\ $$$$$$$$ |$$  __$$ |\__|$$ |  $$ |$$  _____|\_$$  _|  $$  _$$  _$$\ $$  __$$\ $$  __$$\\_$$  _|  $$$$$$$$ |$$  __$$\ $$  __$$\ \____$$\ $$ |  $$ |
	// \$$$$$$\   $$$$$$$ |\$$\$$  / $$$$$$$$ |$$$$  _$$$$ | $$$$$$$ |$$ |  \__|$$$$$$$$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |\$$$$$$\  $$$$$$$$ |$$  __$$ |$$ /  $$ |$$\ $$ |  $$ |\$$$$$$\    $$ |    $$ / $$ / $$ |$$$$$$$$ |$$ |  $$ | $$ |    $$  __$$ |$$ |  \__|$$ |  \__|$$$$$$$ |$$ |  $$ |
	//  \____$$\ $$  __$$ | \$$$  /  $$   ____|$$$  / \$$$ |$$  __$$ |$$ |      $$   ____|$$ |  $$ |$$ |  $$ |$$ |  $$ | \____$$\ $$   ____|$$ |  $$ |$$ |  $$ |$$ |$$ |  $$ | \____$$\   $$ |$$\ $$ | $$ | $$ |$$   ____|$$ |  $$ | $$ |$$\ $$ |  $$ |$$ |      $$ |     $$  __$$ |$$ |  $$ |
	// $$$$$$$  |\$$$$$$$ |  \$  /   \$$$$$$$\ $$  /   \$$ |\$$$$$$$ |$$ |      \$$$$$$$\ $$ |  $$ |\$$$$$$  |\$$$$$$  |$$$$$$$  |\$$$$$$$\ $$ |  $$ |\$$$$$$$ |$$ |\$$$$$$  |$$$$$$$  |  \$$$$  |$$ | $$ | $$ |\$$$$$$$\ $$ |  $$ | \$$$$  |$$ |  $$ |$$ |      $$ |     \$$$$$$$ |\$$$$$$$ |
	// \_______/  \_______|   \_/     \_______|\__/     \__| \_______|\__|       \_______|\__|  \__| \______/  \______/ \_______/  \_______|\__|  \__| \_______|$$ | \______/ \_______/    \____/ \__| \__| \__| \_______|\__|  \__|  \____/ \__|  \__|\__|      \__|      \_______| \____$$ |
	//                                                                                                                                                    $$\   $$ |                                                                                                                $$\   $$ |
	//                                                                                                                                                    \$$$$$$  |                                                                                                                \$$$$$$  |
	//                                                                                                                                                     \______/                                                                                                                  \______/

	/**
	 * Saves the warehouse adjustments that are in an array
	 *
	 * @param Array $warehouseAdjustmentArray Array of warehouseAdjustment objects
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 *
	 * @return Mixed Nothing on success, false on failure
	 */
	private function saveWarehouseAdjustmentArray($warehouseAdjustmentArray) {
		$duplicateID = '';

		// Saving each fo the adjustments that were made
		foreach ($warehouseAdjustmentArray as $warehouseAdjustment) {
			// Need to save here in order get an ID record into the database
			if ($warehouseAdjustment->save() === false) {
				$this->logSaveFail($warehouseAdjustment);

				return false;
			}

			// If we are dealing with a record that needs a duplicate
			if ($warehouseAdjustment->WarehouseAdjustmentGroup === 0) {
				// If we have not set the duplicate ID yet
				if ($duplicateID === '') {
					// Set the duplicateID to the AdjustmentID
					$duplicateID = $warehouseAdjustment->WarehouseAdjustmentID;
				}

				// Set the group ID to the duplicateID
				$warehouseAdjustment->WarehouseAdjustmentGroup = $duplicateID;
			} else {
				// If the record doesn't require duplicate entries, then just set it to its own ID
				$warehouseAdjustment->WarehouseAdjustmentGroup = $warehouseAdjustment->WarehouseAdjustmentID;
			}

			// Need to save again in order to store the groupID which is dependent on the recordID
			if ($warehouseAdjustment->save() === false) {
				$this->logSaveFail($warehouseAdjustment);

				return false;
			}
		}
	}

	//                                                             $$\               $$\   $$\   $$\    $$$$$$\
	//                                                             $$ |              $$ |  $$ |$$$$ |  $$  __$$\
	//  $$$$$$\   $$$$$$\  $$$$$$$\   $$$$$$\   $$$$$$\  $$$$$$\ $$$$$$\    $$$$$$\  \$$\ $$  |\_$$ |  \__/  $$ |
	// $$  __$$\ $$  __$$\ $$  __$$\ $$  __$$\ $$  __$$\ \____$$\\_$$  _|  $$  __$$\  \$$$$  /   $$ |   $$$$$$  |
	// $$ /  $$ |$$$$$$$$ |$$ |  $$ |$$$$$$$$ |$$ |  \__|$$$$$$$ | $$ |    $$$$$$$$ | $$  $$<    $$ |  $$  ____/
	// $$ |  $$ |$$   ____|$$ |  $$ |$$   ____|$$ |     $$  __$$ | $$ |$$\ $$   ____|$$  /\$$\   $$ |  $$ |
	// \$$$$$$$ |\$$$$$$$\ $$ |  $$ |\$$$$$$$\ $$ |     \$$$$$$$ | \$$$$  |\$$$$$$$\ $$ /  $$ |$$$$$$\ $$$$$$$$\
	//  \____$$ | \_______|\__|  \__| \_______|\__|      \_______|  \____/  \_______|\__|  \__|\______|\________|
	// $$\   $$ |
	// \$$$$$$  |
	//  \______/

	/**
	 * Does an http request to generate an x12 document
	 *
	 * @param EDIDocument $ediDocument This should contain all of the information needed to generate an x12 document
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 *
	 * @return Boolean True/False based on success/fail respectively
	 */
	private function generateX12($ediDocument) {
		$argumentArray = [
			EDIDocument::WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE,
			$ediDocument->DocID
		];

		$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
		$argumentsString = implode("/", $argumentArray);
		$url = $protocol . $_SERVER['HTTP_HOST'] . "/edidocument/makex12/$argumentsString";

		// Need to wrap in try/catch in order to catch errors from the internal call
		try {
			$httpResponse = $this->utils->GET($url);
		} catch (\Phalcon\Mvc\Model\Exception $e) {
			$this->logger->log('Exception thrown in generateX12:');
			$this->logger->log($e->getModel()->getMessages());
		}

		if ($httpResponse['success'] !== 1) {
			$this->logger->log("makex12 Response");
			$this->logger->log(json_encode($httpResponse, JSON_PRETTY_PRINT));
		}

		return $httpResponse['success'] === 1;
	}

	// $$\                      $$$$$$\                                $$$$$$$$\       $$\ $$\
	// $$ |                    $$  __$$\                               $$  _____|      \__|$$ |
	// $$ | $$$$$$\   $$$$$$\  $$ /  \__| $$$$$$\ $$\    $$\  $$$$$$\  $$ |   $$$$$$\  $$\ $$ |
	// $$ |$$  __$$\ $$  __$$\ \$$$$$$\   \____$$\\$$\  $$  |$$  __$$\ $$$$$\ \____$$\ $$ |$$ |
	// $$ |$$ /  $$ |$$ /  $$ | \____$$\  $$$$$$$ |\$$\$$  / $$$$$$$$ |$$  __|$$$$$$$ |$$ |$$ |
	// $$ |$$ |  $$ |$$ |  $$ |$$\   $$ |$$  __$$ | \$$$  /  $$   ____|$$ |  $$  __$$ |$$ |$$ |
	// $$ |\$$$$$$  |\$$$$$$$ |\$$$$$$  |\$$$$$$$ |  \$  /   \$$$$$$$\ $$ |  \$$$$$$$ |$$ |$$ |
	// \__| \______/  \____$$ | \______/  \_______|   \_/     \_______|\__|   \_______|\__|\__|
	//               $$\   $$ |
	//               \$$$$$$  |
	//                \______/

	/**
	 * Gives a standardized response for when a save fails
	 *
	 * @param Object $modelObject Object for the model being used to handle data
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function logSaveFail($modelObject, $methodName = '') {
		$msg = "Error saving " . get_class($modelObject) . ":\n\n" . implode("\n", $modelObject->getMessages());

		if ($methodName !== '') {
			$this->logger->log("Method: $methodName");
		}

		$this->logger->log("error saving " . get_class($modelObject) . ": \n\n");
		$this->logger->log($msg);
	}
}
