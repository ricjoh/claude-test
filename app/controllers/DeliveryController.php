<?php

use Phalcon\Mvc\Controller;

class DeliveryController extends Controller {
	public function incomingAction() {
		$pageTitleArray = array(
			Delivery::STATUS_PENDING => 'Incoming Deliveries',
			Delivery::STATUS_CONVERTED => 'Converted Deliveries'
		);

		$this->view->headers = ['EDI#', 'Vendor', 'Ship Date', 'PO Number', 'Pieces', 'Gross Wt', 'Carrier', 'Ship ID'];

		$fields = [
			'EDIDocID',
			'DeliveryID',
			'Sender',
			'ShipDate',
			'OrderNum',
			'TotalShippedQty',
			'TotalShippedWeight',
			'Carrier',
			'ShipmentID'
		];

		$statusPID = $this->dispatcher->getParam("id") ?? Delivery::STATUS_PENDING;
		$lineData = Delivery::find(array("StatusPID = '" . $statusPID . "'", "order" => "EDIDocID DESC"));
		$this->view->data = array();

		$rows = array();
		$ids = array();

		foreach ($lineData as $line) {
			$row = array();

			foreach ($fields as $f) {
				if (false !== strpos($f, 'Date')) { // any date
					$line->{$f} = substr($line->{$f}, 0, 10);
				}

				if ($f == 'DeliveryID') {
					array_push($ids, $line->{$f});
				} else {
					array_push($row, $line->{$f});
				}
			}

			array_push($rows, $row);
		}

		$this->view->data = $rows;
		$this->view->ids = $ids;

		$this->view->title = $pageTitleArray[$statusPID];
	}

	public function incomingdetailAction() {
		$error = '';
		$deliveryID = $this->dispatcher->getParam("id");
		$this->logger->log('incoming detail with ID: ' . $deliveryID);

		if (isset($_POST['mode'])) {

			$this->logger->log('POST: Calling _saveDeliveryDetails() with ID: ' . $deliveryID);
			$this->_saveDeliveryDetails($deliveryID);
			return;
		}

		$deliveryModel = new Delivery();

		$headerData = $deliveryModel->getHeader($deliveryID);

		$linecount = 0;

		$this->view->headers = array_filter(
			[
				"Rec'd",
				// 'License Plate',
				'Prod Num',
				'Desc',
				'Batch',
				'Make Date',
				'PO Line',
				'Qty',
				'Weight',
				'Pallets'
			]
		);

		$this->view->deliveryID = $deliveryID;

		$fields = array_filter(
			[
				'%%CHECKBOX%%',
				'PartNum', // Part No
				'PartDescription',
				'CustomerLot', // Batch? Need to verify
				'ExpDate',
				'LineNum', // PO Line
				'Qty',
				'NetWeight',
				'PalletCount'
			]
		);

		if ($headerData) {
			$this->logger->log("StatusPID: " . $headerData->StatusPID);
			$readonly = ($headerData->StatusPID != Delivery::STATUS_PENDING);
			$this->logger->log("Readonly: $readonly");
			$this->view->ReadOnly = $readonly;

			$this->view->notes = ''; // CustomerOrderNotes + DeliveryNotes

			// $this->view->data = array();
			$rows = array();
			$ids = array();

			$detailData = $deliveryModel->getDetails($deliveryID, $headerData->EDIKey);

			$netWeightTotal = 0;

			foreach ($detailData as $line) {
				$row = array();
				$linecount++;

				foreach ($fields as $f) {
					if (false !== strpos($f, 'Date')) { // any date
						$line->{$f} = substr($line->{$f}, 0, 10);
					}

					if ($f == '%%CHECKBOX%%') {
						if (!$readonly) {
							$q  = $line->Qty;
							$ddid = $line->DeliveryDetailID;
							$line->{$f} = <<<EOF
	<input type="hidden"   name="ddid[]" value="$ddid">
	<input type="hidden"   class="shipqty"  name="shipqty[]"  value="$q">
	<input type="checkbox" class="received" name="received[]" value="$ddid">
	<input type="text"     class="recdqty"  name="recdqty[]"  value="0">
EOF;
						} else {
							$receipt =  DeliveryDetailReceipt::findFirst("DeliveryDetailID = " . $line->DeliveryDetailID);
							$line->{$f} = (isset($receipt)) ?  $receipt->ReceivedQty : 0;
						}
					}

					if ($f == 'PartDescription') {
						$this->logger->log("Field: $f >" . $line->{$f} . "<");
						// If desc or descPID missing flag to not allow save
						if ( empty( $line->{$f} )|| empty( $line->DescriptionPID ) ) {
							$error = 'Missing Part Description for ' . $line->{'PartNum'} . ' -- cannot proceed.';
						}
						$line->{$f} .= "<br><small>$line->LicensePlate</small>";


					}

					// put input box here
					if ($f == 'CustomerLot') {
						$line->{$f} = <<<EOF
							<input type="text" class="customerlot validateInput" placeholder="Customer Lot#" name="customerlot[]" value="{$line->{$f}}">
EOF;
					}

					// put input box here
					if ($f == 'ExpDate') {
						$line->{$f} = <<<EOF
							<input type="text" class="expdate datepicker validateInput" autocomplete="off" name="expdate[]" value="{$line->{$f}}">
EOF;
					}

					if ($f == 'PalletCount') {

						if (!empty($line->LicensePlate)) {
							array_push($ids, $line->LicensePlate);
						}
						$lp = $line->LicensePlate ?? '';

 						$line->{$f} = <<<EOF
 							<input data-license-plate='{$lp}' type="number" min="0" name="palletCount[]" class="palletCount" value="0">
 							<input type="hidden" value="$lp" class="licenseplate" name="licenseplate[]" value="0">
EOF;
 					}


// 					if ($f == 'LicensePlate') {

// 						if (!empty($line->{$f})) {
// 							array_push($ids, $line->{$f});
// 						}

// 						$line->{$f} = <<<EOF
// 							<input type="hidden" class="licenseplate lp-input"  name="licenseplate[]" value="{$line->{$f}}">
// EOF;
// 					}


					if ($f == 'NetWeight') {

						// First, calculate the $actualNetWeight
						$actualNetWeight = $line->{$f}; // default / variable weight item if pc weight is zero

						if (!is_null($line->{'PieceWeight'})) {
							$actualNetWeight = $line->{'PieceWeight'} * $line->{'Qty'};
							$line->{$f} .= '/' . $actualNetWeight; // slashing it for the screen need to reassign for print.
						}

						$netWeightTotal += $actualNetWeight;


						$line->{$f} = <<<EOF
								<input type="text" class="" autocomplete="off" name="netWeight[]" value="{$actualNetWeight}">
EOF;
					}

					array_push($row, $line->{$f});
				}

				// $this->logger->log( implode( '|', $row ) );
				array_push($rows, $row);
			}

			// $this->logger->log("headerData");
			// $this->logger->log($headerData);

			$this->view->details = array_filter(
				[
					'Vendor' => $headerData->Sender,
					'Vendor PO' => $headerData->OrderNum,
					'Ship Date' => substr($headerData->ShipDate, 0, 10),
					'Carrier' => $headerData->Carrier ?: '&nbsp;',
					'Shipment ID' => $headerData->ShipmentID,
					'Total Pieces' => $headerData->TotalShippedQty,
					'Gross Wt' => $headerData->TotalShippedWeight,
					'EDI Date' => substr($headerData->CreateDate, 0, 10),
					'Ship To Warehouse' => $headerData->EDIKey !== 'GLC' ? $headerData->Warehouse : '',
					'EDI#' => $headerData->EDIDocID,
					'EDI Control#' => $headerData->EDIKey !== 'GLC' ? '' : $headerData->ControlNumber,
					'Net Wt' => $netWeightTotal,
					'Pallet Count' => $headerData->EDIKey !== 'GLC' ? count(array_unique($ids)) : ""
				]
			);
			$this->logger->log("Error: $error");
			$this->view->error = $error;
			$this->view->data = $rows;
			$this->view->ids = $ids;
			$this->view->linecount = $linecount;


			$this->view->title = 'Delivery Details';
		} else {
			$errorString = 'Bad Delivery' . "ID: '$deliveryID'";
			$this->logger->log($errorString);

			$this->view->data = array('success' => 0);
			$this->view->data = array('error' => $errorString);

			$this->view->pick("layouts/json");
		}
	}

	private function _saveDeliveryDetails($deliveryID) {

		$mode = $_POST['mode'];
		$this->logger->log("In _saveDeliveryDetails() in $mode mode with ID: " . $deliveryID);

		$palletCount = $_POST['palletCount'];

		$netWeight = $_POST['netWeight'];

		$qtys = $_POST['recdqty'];
		$errors = false;
		$edidocid = '';
		$deliveryid = '';

		$this->logger->log("_POST");
		$this->logger->log($_POST);

		foreach ($_POST['ddid'] as $i => $post_ddid) {
			$this->logger->log("Receiving DeliveryDetailID: $post_ddid  Qty: " . $qtys[$i]);

			$line = DeliveryDetail::findFirst([
				'conditions' => "DeliveryDetailID = :deliverydetailID:",
				'bind'       => ['deliverydetailID' => $post_ddid]
			]);

			if (isset($line) and ($ddid = $line->DeliveryDetailID)) {
				$edidocid =   $line->EDIDocID;
				$deliveryid = $line->DeliveryID;

				$receipt = DeliveryDetailReceipt::findFirst([
					'DeliveryDetailID = :dd:',
					'bind' => ['dd' => $ddid]
				]);

				$deliveryDetail = DeliveryDetail::findFirst([
					'DeliveryDetailID = :dd:',
					'bind' => ['dd' => $ddid]
				]);

				$this->logger->log('exp date:');
				$this->logger->log($_POST['expdate'][$i]);
				$this->logger->log($this->utils->dbDate($_POST['expdate'][$i]));

				$deliveryDetail->CustomerLot = $_POST['customerlot'][$i];
				$deliveryDetail->ExpDate = $this->utils->dbDate($_POST['expdate'][$i]);

				if (!$receipt) {
					$receipt = new DeliveryDetailReceipt();
				}

				$licenseplate = $_POST['licenseplate'][$i];
$this->logger->log("License Plate: $licenseplate");
				if (!empty($licenseplate)) {
					$receipt->LicensePlate = $licenseplate;
					$deliveryDetail->LicensePlate = $licenseplate;
				}

				$deliveryDetail->NetWeight = $netWeight[$i];

				$deliveryDetail->PalletCount = $palletCount[$i];

				$deliveryDetail->Qty = $qtys[$i];

				// if ($licenseplate != null && $this->licensePlateExists($licenseplate)) {
				// 	$errors = true;
				// 	$msg = "License plate: $licenseplate already exists.";
				// }

				$receipt->DeliveryDetailID = $ddid;
				$receipt->ReceivedQty = $qtys[$i];
				$receipt->ReceivedDate = $this->mysqlDate;
				$receipt->UpdateDate = $this->mysqlDate;


				if ($errors === false) {
					try {
						if ($receipt->save() == false) {
							$msg = "Error saving receipt:\n" . implode(
								"\n",
								$receipt->getMessages()
							);
							$this->logger->log($msg);
							$errors = true;
						}
					} catch (\Phalcon\Exception $e) {
						$this->logger->log($e->getMessage());
						return;
					}

					try {
						if ($deliveryDetail->save() == false) {
							$msg = "Error saving delivery detail:\n" . implode(
								"\n",
								$deliveryDetail->getMessages()
							);

							$this->logger->log($msg);

							$errors = true;
						}
					} catch (\Phalcon\Exception $e) {
						$this->logger->log($e->getMessage());
						return;
					}
				} else {
					$errors = true;
					$this->logger->log($msg);
				}
			} else {
				$errors = true;
				$this->logger->log("Receipt Failed - No Matching Detail: DeliveryDetailID: {$post_ddid} Qty: " . $qtys[$i]);
			}
		}

		if (!$errors) {
			$this->logger->log("Updating status on DeliveryID: {$deliveryid}");
			$delivery = Delivery::findFirst($deliveryid);
			$delivery->StatusPID = Delivery::STATUS_RECEIVED;

			try {
				$delivery->save();
			} catch (\Phalcon\Exception $e) {
				$this->logger->log('Error: Save Draft Lot');
				$this->logger->log($e->getMessage());
				return;
			}

			// Trigger Draft Lots
			$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
			$url = $protocol . $_SERVER['HTTP_HOST'] . "/lot/createdraft/$deliveryid/$edidocid";
			$this->logger->log("Creating Draft Lots by url: $url");

			try {
				$responseobj = $this->utils->GET($url);
			} catch (\Phalcon\Exception $e) {
				$this->logger->log("Exception Creating Draft Lot: {$url}");
				$this->logger->log($e->getMessage());
				return;
			}

			$lot_status = $responseobj['success'];
			$this->logger->log("Draft Lots response: $status");

			// Trigger EDI generation
			$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';

			$deliveryHeader = $delivery->getHeader($delivery->DeliveryID);
			$customer = Customer::findFirst("CustomerID = '" . $deliveryHeader['CustomerID'] . "'");

			$ediKey = $customer->EDIKey;
			$url = $protocol . $_SERVER['HTTP_HOST'] . "/edidocument/generatex12/944/$ediKey/$deliveryid/$edidocid";
			$this->logger->log("Creating x12 by url: $url");

			try {
				$responseobj = $this->utils->GET($url);
			} catch (\Phalcon\Exception $e) {
				$this->logger->log('Exception while creating x12 by url: ' . $url);
				$this->logger->log($e->getMessage());
				return;
			}
			$edi_status = $responseobj['success'];
			$this->logger->log("x12 response: $edi_status");

			if ($lot_status) {
				// Update EDIDoc Status on Draft Lot to 'Converted'
				$edi = EDIDocument::findFirst("DocID = $edidocid");
				$edi->Status = 'Converted';

				try {
					$edi->save();
				} catch (\Phalcon\Exception $e) {
					$this->logger->log('Exception: EDIsave: ' . $e->getMessage());
					return;
				}


				// Update Delivery on Draft Lot to Delivery::STATUS_CONVERTED
				$delivery = Delivery::findFirst($deliveryid);
				$delivery->StatusPID = Delivery::STATUS_CONVERTED;

				try {
					$delivery->save();
				} catch (\Phalcon\Exception $e) {
					$this->logger->log($e->getMessage());
					$this->logger->log('Error: delivery save');
					return;
				}
			}
		}

		$this->dispatcher->forward(
			[
				'action' => 'incoming',
				'params' => [$deliveryID]
			]
		);
	}

	public function printincomingdetailAction() {
		$deliveryID = $this->dispatcher->getParam("id");

		$fields = [
			'%%CHECKBOX%%',
			'Qty',
			'LicensePlate',
			'PartNum', // Part No
			'CustomerLot', // Batch? Need to verify
			'ExpDate',
			'LineNum', // PO Line
			'NetWeight'
		];

		$deliveryModel = new Delivery();

		$headerData = $deliveryModel->getHeader($deliveryID, Delivery::STATUS_PENDING);

		if ($headerData->TotalShippedQty) {
			$this->view->notes = ''; // CustomerOrderNotes + DeliveryNotes


			$this->view->data = array();
			$rows = array();
			$ids = array();

			$detailData = $deliveryModel->getDetails($deliveryID, $headerData->EDIKey);

			$netWeightTotal = 0;

			foreach ($detailData as $detail) {
				$row = array();

				foreach ($fields as $field) {
					if (false !== strpos($field, 'Date')) { // any date
						$detail->{$field} = substr($detail->{$field}, 0, 10);
					}

					if ($field == '%%CHECKBOX%%') {
						$detail->{$field} = '________';  // blank for them to write stuff in here -- Ric
					}

					if ($field == 'NetWeight') {
						// default / variable weight item if pc weight is zero
						$actualNetWeight = $detail->{$field};

						if (!is_null($detail->{'PieceWeight'})) {
							$actualNetWeight = $detail->{'PieceWeight'} * $detail->{'Qty'};
							$detail->{$field} = $actualNetWeight;
						}

						$netWeightTotal += $actualNetWeight;
					}

					if ($field == 'Qty') {
						array_push($ids, $detail->{$field});
					}

					array_push($row, $detail->{$field});
				}

				$keyedRow = array(
					"DeliveryDetailID" => $detail["DeliveryDetailID"],
					"%%CHECKBOX%%" => $row[0],
					"Qty" => $row[1],
					"LicensePlate" => $row[2],
					"PartNum" => $row[3],
					"CustomerLot" => $row[4],
					"ExpDate" => $row[5],
					"LineNum" => $row[6],
					"NetWeight" => $row[7]
				);

				// $this->logger->log( implode( '|', $row ) );
				array_push($rows, $keyedRow);
			}

			$this->view->details = [
				'Vendor' => $headerData->Sender,
				'Vendor PO' => $headerData->OrderNum,
				'EDI Date' => substr($headerData->CreateDate, 0, 10),
				'Ship Date' => substr($headerData->ShipDate, 0, 10),
				'EDI Control Number' => $headerData->ControlNumber,
				'Carrier' => $headerData->Carrier,
				'Shipment ID' => $headerData->ShipmentID,
				'Total Pieces' => $headerData->TotalShippedQty,
				'Gross Wt' => $headerData->TotalShippedWeight,
				'Net Wt' => $netWeightTotal
			];

			$this->view->detailData = $detailData;
			$this->view->data = $rows;

			// $this->logger->log('Controller data: ' . print_r($this->view->data));

			$this->view->ids = $ids;

			$this->view->title = 'Delivery Details';
		} else {
			$errorString = 'Bad Delivery' . "ID: '$deliveryID'";
			$this->logger->log($errorString);

			$this->view->data = array('success' => 0);
			$this->view->data = array('error' => $errorString);

			$this->view->pick("layouts/json");
		}
	}

	public function checklicenseplateAction() {
		// $this->logger->log($_POST);
		// $plates = [];

		// if (isset($_POST['licensePlateArray'])) {
		// 	$plates = $_POST['licensePlateArray'];
		// } else {
		// 	$plates = [$this->dispatcher->getParam("licensePlate")];
		// }

		// foreach ($plates as $licensePlate) {
		// 	// If we find that a plate exists
		// 	$licensePlateExists = $this->licensePlateExists($licensePlate);

		// 	if ($licensePlateExists) {
		// 		$this->logger->log("Duplicate License Plate: $licensePlate");

		// 		return json_encode([
		// 			'success' => 0,
		// 			'licensePlateExists' => (int)$licensePlateExists,
		// 			'message' => "License plate: '$licensePlate' is already in use."
		// 		]);
		// 	}
		// }

		return json_encode([
			'success' => 1,
			'licensePlateExists' => 0,
			'message' => ""
		]);
	}

	private function licensePlateExists($licensePlate) {
		if (empty($licensePlate)) {
			return false;
		}

		$deliveryDetail = DeliveryDetail::findFirst([
			'LicensePlate = :licensePlate:',
			'bind' => ['licensePlate' => $licensePlate]
		]);

		$deliveryDetailReceipt = DeliveryDetailReceipt::findFirst([
			'LicensePlate = :licensePlate:',
			'bind' => ['licensePlate' => $licensePlate]
		]);

		// Return if we have either of these, then we have a duplicate License Plate
		return $deliveryDetail !== false || $deliveryDetailReceipt !== false;
	}
}
