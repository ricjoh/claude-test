<?php // GLC_EDIFunctions
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;

class GLC_EDIFunctions {

	private $DC; // reference to calling EDI document controller

	public function getAdjustmentReasonCodes() {
		$ADJUSTMENT_REASON_PRODUCT_PUT_ON_HOLD = '05';
		$ADJUSTMENT_REASON_PRODUCT_DUMPED_OR_DESTROYED = '07';
		$ADJUSTMENT_REASON_RECEIPT_GREATER_THAN_PREVIOUSLY_REPORTED = '54';
		$ADJUSTMENT_REASON_PRODUCT_TAKEN_OFF_HOLD = '55';
		$ADJUSTMENT_REASON_PHYSICAL_COUNT = 'AA';
		$ADJUSTMENT_REASON_TRANSFER = 'AK';
		$ADJUSTMENT_REASON_ORDER_SHIPMENT_ERROR = 'AR';
		$ADJUSTMENT_REASON_RECOUPING = 'AS';
		$ADJUSTMENT_REASON_DAMAGED_IN_FACILITY = 'AU';
		$ADJUSTMENT_REASON_DAMAGED_IN_TRANSIT = 'AV';
		$ADJUSTMENT_REASON_PRODUCT_RECALL = 'AW';
		$ADJUSTMENT_REASON_SHELF_LIFE_OBSOLESCENCE = 'AX';
		$ADJUSTMENT_REASON_OFF_SPECIFICATION = 'BK';
		$ADJUSTMENT_REASON_DOWNGRADE = 'BS';

		return [
			$ADJUSTMENT_REASON_PRODUCT_PUT_ON_HOLD => [
				'name' => "Product Put on Hold",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_PRODUCT_DUMPED_OR_DESTROYED => [
				'name' => "Product Dumped or Destroyed",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_RECEIPT_GREATER_THAN_PREVIOUSLY_REPORTED => [
				'name' => "Receipt Greater Than Previously Reported",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_PRODUCT_TAKEN_OFF_HOLD => [
				'name' => "Product Taken Off Hold",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_PHYSICAL_COUNT => [
				'name' => "Physical Count",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_TRANSFER => [
				'name' => "Transfer",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_ORDER_SHIPMENT_ERROR => [
				'name' => "Order Shipment Error",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_RECOUPING => [
				'name' => "Recouping",
				'prodReadOnly' => 0
			],
			$ADJUSTMENT_REASON_DAMAGED_IN_FACILITY => [
				'name' => "Damaged in Facility",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_DAMAGED_IN_TRANSIT => [
				'name' => "Damaged in Transit",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_PRODUCT_RECALL => [
				'name' => "Product Recall",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_SHELF_LIFE_OBSOLESCENCE => [
				'name' => "Shelf-life Obsolescence",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_OFF_SPECIFICATION => [
				'name' => "Off Specification",
				'prodReadOnly' => 0
			],
			$ADJUSTMENT_REASON_DOWNGRADE => [
				'name' => "Downgrade",
				'prodReadOnly' => 0
			]
		];
	}


	//   .oPYo.    .8  .oPYo.
	//   8'  `8   d'8      `8
	//   8.  .8  d' 8    .oP'
	//   `YooP8 Pooooo    `b.
	//       .P     8      :8
	//   `YooP'     8  `YooP'
	// :::.....:::::..::.....:
	// :::::::::::::::::::::::
	// :::::::::::::::::::::::

	public function processStockTransferShippingAdvice($documentController, $transaction, $jsonEDIArray, $docid) {

		$edikey = 'GLC';
		$this->DC = $documentController;
		$dtm = new TransactionManager();
		$DBTransaction = $dtm->get();
		$this->DC->logger->log("In $edikey transaction: $transaction / $docid");
		$delivery = new Delivery();
		$delivery->EDIDocID = $docid;

		try {
			if ($this->populateDelivery($delivery, $jsonEDIArray) === false) {
				$this->DC->logger->log('Failed to populate delivery.');
				return;
			}
		} catch (\Phalcon\Exception $e) {
			$this->DC->logger->log($e->getMessage());
			$this->DC->logger->log('Catch: Failed to populate delivery.');
			return;
		}
		$this->DC->logger->log('Delivery populated!');

		try {
			if ($delivery->save() == false) {
				$DBTransaction->rollback("Failed to save delivery: $docid");
				$message = "DB error saving customer order details:\n\n" . implode("\n", $delivery->getMessages());
				$this->DC->logger->log("$message\n");
				$this->DC->setEDIError("Error saving delivery.", $docid);
				return;
			}
		} catch (\Phalcon\Exception $e) {
			$this->DC->logger->log($e->getMessage());
			$this->DC->setEDIError("Catch: Error saving delivery: " . $e->getMessage(), $docid);
			return;
		}

		$this->DC->logger->log('Delivery saved!');

		try {
			$deliveryDetailsArray = $this->getDeliveryDetailsArray($delivery, $jsonEDIArray);
		} catch (\Phalcon\Exception $e) {
			$this->DC->logger->log($e->getMessage());
			$this->DC->setEDIError("Error getting delivery details - " . $e->getMessage(), $docid);
		}

		if (empty($deliveryDetailsArray)) {
			$DBTransaction->rollback("Failed to save delivery details: $docid");
			$this->DC->setEDIError("Error getting delivery details: Empty Details", $docid);
			return;
		}

		try {
			foreach ($deliveryDetailsArray as $deliveryDetails) {
				$deliveryDetails->setTransaction($DBTransaction);
				if ($deliveryDetails->save() == false) {
					// If we failed to save, then rollback any changes made.
					$DBTransaction->rollback("Failed to save delivery details: $docid");
					$message = "DB error saving delivery details:\n\n" . implode("\n", $deliveryDetails->getMessages());
					$this->DC->logger->log("$message\n");
					$this->DC->setEDIError("Error saving delivery details - ", $docid);
					return;
				}
			}
			$DBTransaction->commit();
		} catch (\Phalcon\Exception $e) {
			$this->DC->logger->log($e->getMessage());
			$this->DC->setEDIError("Catch: Error saving delivery details: " . $e->getMessage(), $docid);
			return;
		}

		$this->DC->logger->log('Delivery details saved!');
	}


	// .oPYo.    .8     .8
	// 8'  `8   d'8    d'8
	// 8.  .8  d' 8   d' 8
	// `YooP8 Pooooo Pooooo
	//     .P     8      8
	// `YooP'     8      8
	// :.....:::::..:::::..:
	// :::::::::::::::::::::
	// :::::::::::::::::::::

	public function get944X12Data($documentController, $deliveryID, $customer) {

		$this->DC = $documentController;
		//$DeliveryID = $ref; // 328A5737-0797-4535-8A9A-D31C7230B47B
		// http://devtracker.oshkoshcheese.com/edidocument/generatex12/944/GLC/6/61
		$delivery = Delivery::findfirst("DeliveryID = $deliveryID");
		$details =  DeliveryDetail::find("DeliveryID = $deliveryID");
		$lines = array();
		$totPieces = 0;

		foreach ($details as $detail) {

			$receipt =  DeliveryDetailReceipt::findFirst("DeliveryDetailID = " . $detail->DeliveryDetailID);
			$rqty = (isset($receipt)) ?  $receipt->ReceivedQty : 0;

			$line = [
				'line' => $detail->LineNum,
				// W07
				'qtyreceived' => $rqty,
				'prodnum' => $detail->PartNum, // PN
				// N9s
				'custlotno' => $detail->CustomerLot, // LT = lot/batch (GL)
				'licenseplate' => $detail->LicensePlate, // LV = License plate
			];

			array_push($lines, $line);
			$totPieces += $rqty;
		}

		$EDIDocID = $this->DC->dispatcher->getParam("reference2");
		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$data = array(
			'ISAReceiverID' => str_pad($customer->EDIISAID, 15),
			'GSRecCode' => $customer->EDIGSID,
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			'GSCtrlNo' => $control_number,
			'STCtrlNo' => $control_number,
			// W17
			'ReceiveDate' => str_replace('-', '', substr($this->DC->mysqlDate, 0, 10)),
			'ReceiveNumber' => $delivery->ShipmentID,
			'VendorOrderNumber' => $delivery->OrderNum,
			'OrigRefNumber' => $delivery->Reference,
			// N1s
			'ShipToID' => 'PLYMOUTH',
			'ShipFromID' => $delivery->ShipFromID,
			// W14
			'totcases' => $totPieces,
			'lines' => $lines
		);

		$data['SECount'] = 8 + (4 * count($lines)); // 4 if license plate

		return $data;
	}

	// .oPYo.    .8  oooooo
	// 8'  `8   d'8  8
	// 8.  .8  d' 8  8pPYo.
	// `YooP8 Pooooo     `8
	//     .P     8      .P
	// `YooP'     8  `YooP'
	// :.....:::::..::.....:
	// :::::::::::::::::::::
	// :::::::::::::::::::::

	public function getWarehouseShippingAdviceData($documentController, $customer, $bolData, $offer, $CustomerOrder, $CustomerOrderDetail) {
		$this->DC = $documentController;
		$CustomerOrderDetail = $CustomerOrderDetail->toArray();
		$EDIDocID = $CustomerOrder->EDIDocID;
		$details = array();
		$linetotals = array();

		foreach ($CustomerOrderDetail as $line) {
			$prodnum = $line['PartNum'];
			if (!isset($linetotals[$line['POLine']])) $linetotals[$line['POLine']] = 0;

			// save details at prod num level if all else fails.
			$details[$prodnum] = array(
				'qtyordered' => $line['Qty'],
				'poline' => $line['POLine'],
				'detailid' => $line['CustomerOrderDetailID']
			);

			$mapraw = OrderOfferDetailMap::find("CustomerOrderDetailID = " . $line['CustomerOrderDetailID']);
			if ($mapraw) {
				$map = $mapraw->toArray();
				foreach ($map as $mapline) {

					if (isset($mapline['OfferItemID']) and !empty($mapline['OfferItemID'])) {
						$details[$mapline['OfferItemID']] = array(
							'qtyordered' => $line['Qty'],
							'poline' => $line['POLine'],
							'detailid' => $line['CustomerOrderDetailID']
						);

						$shipraw = CustomerShipDetail::findFirst([
							"CustomerOrderDetailID = :codid: AND OfferItemID = :oiid:",
							'bind' => [
								'codid' => $line['CustomerOrderDetailID'],
								'oiid'	=> $mapline['OfferItemID']
							]
						]);
					} else // no offerItemID -- manual offer line
					{
						$details[$prodnum] = array(
							'qtyordered' => $line['Qty'],
							'poline' => $line['POLine'],
							'detailid' => $line['CustomerOrderDetailID']
						);

						$shipraw = CustomerShipDetail::findFirst([ // may return wrong qty
							"CustomerOrderDetailID = :codid: AND OfferItemID IS NULL",
							'bind' => [
								'codid' => $line['CustomerOrderDetailID']
							]
						]);
					}

					if ($shipraw) {
						$CustomerShipDetail = $shipraw->toArray();
						$linetotals[$line['POLine']] += intval($CustomerShipDetail['QtyShipped']);
					}
				}
			}
		}

		$lines = array();
		$tsq = array(); // total shipped so far for each POLine
		$totNetWeight = 0.0;
		$totCases = 0;
		$linecount = 1;

// TODO: Walmart $lps = get LP's for offerid, in array by OfferItemID

		foreach ($offer as $offerLine) {

            //	$this->DC->logger->log("GLC getWarehouseShippingAdviceData offerLine");
			// $this->DC->logger->log($offerLine);

			$prodnum = $offerLine['ProductCode'];

			$mydetail = $details[$offerLine['OfferItemID']] ?? $details[$prodnum];
			$poline = $mydetail['poline'];

			$oq = intval($mydetail['qtyordered']);
			$sq = intval($offerLine['PiecesfromVat']); // per vat
			if (!isset($tsq[$poline])) $tsq[$poline] = 0;
			$tsq[$poline] += $sq; // total shipped so far for this poline

			$ss = ($oq == $linetotals[$poline]) ? 'CC' : 'CP';

			$this->DC->logger->log("SS: $ss POLINE: $poline OQ: $oq  LTPOL: " . $linetotals[$poline]);

			// default for fractional weight
			$weight = $offerLine['WeightfromVat'];

			// get pc weight from descriptions Value2
			$this->DC->logger->log("Getting Piece Weight for: $prodnum / $customer->EDIKey");
			$pcweight = Parameter::getEDIPcWeight($prodnum, $customer->EDIKey);
			$this->DC->logger->log("Piece Weight for: $prodnum / $customer->EDIKey is " . (empty($pcweight) ? 'variable.' : "$pcweight oz."));
			if ($pcweight) {
				$weight = $sq * $pcweight;
			}

			$weight = number_format($weight, 3, '.', ''); // 3 decimal places

			// NOTE: map of OfferItemVatID to LicensePlate not VatID to LicensePlate
			$licensePlates = ShipPallet::getLicensePlates( $offerLine['OfferItemID'] );

			$this->DC->logger->log("GLC getWarehouseShippingAdviceData licensePlates");
			$this->DC->logger->log($licensePlates);

			$line = [
				'line' => $linecount++,
				// W04 may not be used
				// W12
				'shipstatus' => $ss,  // CC = complete  CP = Partial
				'qtyordered' => '', // per Silvia $oq, // Order qty from order
				'qtyshipped' => $sq, // 60 // TODO: test if on this vat
				'qtydiff' => 0, // per silvia $tsq[$poline] - $oq,
				'weight' => $weight, // G L Gross weight
				'prodnum' => $prodnum, // '103400', // ProdNum
				// N9s
				'custlotno' => $offerLine['CustomerLotNumber'], // LT = lot/batch (GLC)
				'licenseplate' => $licensePlates[$offerLine['OfferItemVatID']], // LV = License plate  TODO: Walmart
				'poline' => $poline
			]; // LI = PO line number

			array_push($lines, $line);
			$totNetWeight += $offerLine['OfferItemWeight']; // TODO: on this vat
			$totCases += $offerLine['OfferItemPieces']; // TODO: on this vat
		}

		$shipDate = substr($bolData['BOLDate'], 0, 10);
		$shipDate = str_replace('-', '', $shipDate);
		$departTime = substr($bolData['UpdateDate'], 11, 8);
		$departTime = str_replace($departTime, ':', '');

		$EDIDocID = $this->DC->dispatcher->getParam("reference2");
		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$data = array(
			'ISAReceiverID' => str_pad($customer->EDIISAID, 15),
			'GSRecCode' => $customer->EDIGSID,
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			'GSCtrlNo' => $control_number,
			'STCtrlNo' => $control_number,
			// W06
			'VendorOrderNumber' => $CustomerOrder->CustomerOrderNum,
			'ShipDate' => $shipDate,
			'ShipmentID' => $bolData->ShipperNumber,
			'AgentShipmentID' => $bolData->CarrierNumber ?: '',
			'CustPONumber' => $CustomerOrder->CustPONum, // from 940 W0503
			// N1s
			'ShipFromID' => 'PLYMOUTH',
			'ShipToID' => $CustomerOrder->ShipToID, // from 940 Order->ShipToID
			// N9s
			'MasterBOL' => $CustomerOrder->CustomerOrderNum, // from 940 (also order number?)
			'CustOrderNumber' => $CustomerOrder->ShipToPONum, //  From 940 N902 (CO)
			'SealNumber' => $bolData['SealNumber'],
			// G62
			'DepartureTime' => $departTime, // HHMMSS?  use ship date from above
			// W27
			'SCACCode' => $bolData['CarrierName'],
			// W03
			'totcases' => $totCases, // calc from above
			'totweight' => number_format($totNetWeight, 3, '.', ''), // 3 decimal places // LB, calc from above
			'lines' => $lines, // from above
		);

		$data['SECount'] = 11 + (5 * count($lines)); // 5 if license plate
		return $data;
	}

	private function populateDelivery($delivery, $jsonEDIArray) {

		$this->setCarrierForDelivery($delivery, $jsonEDIArray);  // Not Really Required

		$delivery->Status = 'Pending';
		$delivery->OrderNum = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W06", "W0602")["value"];
		if (!empty($this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W06", "W0603")["value"])) {
			$delivery->ShipDate = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W06", "W0603")["value"];
		} else {
			$delivery->ShipDate = str_replace('-', '', substr($this->DC->mysqlDate, 0, 10));
		}
		$delivery->Sender = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "SF"), "N102")["value"];
		$delivery->ShipFromID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "SF"), "N104")["value"];
		$delivery->Warehouse = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "ST"), "N104")["value"];
		$delivery->Reference = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N9", "F8"), "N902")["value"];
		$delivery->ShipmentID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N9", "SI"), "N902")["value"];
		$delivery->TotalShippedQty = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W03", "W0301")["value"];
		$delivery->TotalShippedWeight = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W03", "W0302")["value"];
		$delivery->ReceivedDate = $this->DC->mysqlDate;
	}

	private function setCarrierForDelivery($delivery, $jsonEDIArray) {
		// Elements in the carrier segment
		$carrierElements = $this->DC->getElementsBySegmentName($jsonEDIArray, "W27");
		$carrierIsOptional = $this->DC->elementsContainValue($carrierElements, "O");
		$carrier = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W27", "W2702");

		// If the carrier is not optional and we fail to get it
		if ($carrierIsOptional == false && $carrier == false) {
			$this->DC->setEDIError("Error - No carrier information for ", $delivery->EDIDocID);
			return false;
		}

		// If the carrier is false, give it a NULL value, else set it to the real value.
		$delivery->Carrier = $carrier == false ? NULL : $carrier["value"];
	}

	private function getDeliveryDetailsArray($delivery, $jsonEDIArray) {
		$deliveryDetailsArray = array();
		$inDeliveryDetail = false;

		$n9HelperArray = array(
			"LI" => "LineNum",
			"LT" => "CustomerLot",
			"LV" => "LicensePlate",
			"ZZ" => "ExpDate"
		);

		foreach ($jsonEDIArray as $segment) {
			if ($segment["segment"] == "W03") {
				break;
			}

			if ($segment["segment"] == "W04") {
				$deliveryDetail = new DeliveryDetail();
				$inDeliveryDetail = true;

				$deliveryDetail->DeliveryID = $delivery->DeliveryID;
				$deliveryDetail->EDIDocID = $delivery->EDIDocID;
				$deliveryDetail->Status = 'Pending';
				$deliveryDetail->Qty = $this->DC->getElementFromSegmentByLabel($segment, "W0401")["value"];
				$deliveryDetail->QtyUOM = $this->DC->getElementFromSegmentByLabel($segment, "W0402")["value"];
				$deliveryDetail->PartNum = $this->DC->getElementFromSegmentByLabel($segment, "W0405")["value"];
			}

			if ($inDeliveryDetail) {
				if ($segment["segment"] == "N9") {
					foreach ($n9HelperArray as $key => $value) {
						$labelReference = $key == "ZZ" ? "N904" : "N902";

						if ($this->DC->elementsContainValue($segment["elements"], $key)) {
							$deliveryDetail->$value = $this->DC->getElementFromSegmentByLabel($segment, $labelReference)["value"];
						}
					}
				}

				// There is only one of these per detail so I should be able to pull the data inside this block
				if ($segment["segment"] == "W20") {
					$inDeliveryDetail = false;

					$deliveryDetail->NetWeight = $this->DC->getElementFromSegmentByLabel($segment, "W2004")["value"];
					$deliveryDetail->WeightUOM = new \Phalcon\Db\RawValue('default');

					// We add to the array in the W20 section because that marks the end of a detail
					array_push($deliveryDetailsArray, $deliveryDetail);
				}
			}
		}

		return $deliveryDetailsArray;
	}

	//  .oPYo.    .8  .oPYo.
	//  8'  `8   d'8  8  .o8
	//  8.  .8  d' 8  8 .P'8
	//  `YooP8 Pooooo 8.d' 8
	//      .P     8  8o'  8
	//  `YooP'     8  `YooP'
	//  :.....:::::..::.....:
	//  :::::::::::::::::::::
	//  :::::::::::::::::::::

	public function processWarehouseShippingOrder($documentController, $transaction, $jsonEDIArray, $docid) {

		$edikey = 'GLC';
		$this->DC = $documentController;

		$dbTM = new TransactionManager();
		$DBTransaction = $dbTM->get();

		$this->DC->logger->log("In $edikey transaction: $transaction");

		$customerOrder = new CustomerOrder();

		$customerOrder->EDIDocID = $docid;
		$this->populateCustomerOrder($customerOrder, $jsonEDIArray);

		$customerOrder->setTransaction($DBTransaction);

		// If the save failed
		try {
			if ($customerOrder->save() == false) {

				// If we failed to save, then rollback any changes made.
				$DBTransaction->rollback("Failed to save customer order: $docid");

				$message = "DB error saving customer order details:\n\n" . implode("\n", $customerOrder->getMessages());
				$this->DC->logger->log("$message\n");

				$this->DC->setEDIError("Error saving customer order -", $docid);

				return;
			}
		} catch (\Phalcon\Exception $e) {
			$this->DC->logger->log($e->getMessage());
			$this->DC->setEDIError("Catch: Error saving customer order: " . $e->getMessage(), $docid);
			return;
		}

		$this->DC->logger->log('Customer order saved!');

		// Get the array for the order details
		$customerOrderDetailsArray = $this->getCustomerOrderDetailsArray($customerOrder, $jsonEDIArray);

		if (empty($customerOrderDetailsArray)) {
			$DBTransaction->rollback("Failed to save customer order details: $docid");
			$this->DC->setEDIError("Error getting order details - ", $docid);
			return;
		}

		try {
			foreach ($customerOrderDetailsArray as $customerOrderDetails) {
				$customerOrderDetails->setTransaction($DBTransaction);

				if ($customerOrderDetails->save() == false) {
					// If we failed to save, then rollback any changes made.
					$DBTransaction->rollback("Failed to save customer order details: $docid");

					$message = "DB error saving customer order details:\n\n" . implode("\n", $customerOrderDetails->getMessages());
					$this->DC->logger->log("$message\n");

					$this->DC->setEDIError("Error saving customer order details. ", $docid);

					return;
				}
			}
		} catch (\Phalcon\Exception $e) {
			$this->DC->logger->log($e->getMessage());
			$this->DC->setEDIError("Catch: Error saving customer order details: " . $e->getMessage(), $docid);
			return;
		}

		$DBTransaction->commit();

		$this->DC->logger->log('Customer order details saved!');
	}

	private function populateCustomerOrder($customerOrder, $jsonDataArray) {

		$customerOrder->Status = CustomerOrder::STATUS_PENDING;
		$customerOrder->CustomerOrderDate = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "GS", "GS04")["value"];
		$customerOrder->CustomerOrderNum = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W05", "W0502")["value"];
		$customerOrder->CustPONum = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W05", "W0503")["value"];
		$customerOrder->ShipToID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N1", "ST"), "N104")["value"];
		$customerOrder->ShipToPONum = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N9", "CO"), "N902")["value"];
		$shipToMasterBOLCheck = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N9", "MB"), "N902");

		if ($shipToMasterBOLCheck != false) {
			$customerOrder->ShipToMasterBOL = $shipToMasterBOLCheck["value"];
		} else {
			$customerOrder->ShipToMasterBOL = NULL;
		}

		$customerOrder->ShipToName = $this->DC->getSegementValueAfterEntity($jsonDataArray, "N1", "ST", 0, "N1", "N102");
		$customerOrder->ShipToAddr1 = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N3", "N301")["value"];
		$shipToAddr2Check = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N3", "N302");

		if ($shipToAddr2Check != false) {
			$customerOrder->ShipToAddr2 = $shipToAddr2Check["value"];
		} else {
			$customerOrder->ShipToAddr2 = NULL;
		}

		$customerOrder->ShipToAddr2 = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N3", "N302")["value"];
		$customerOrder->ShipToCity = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N401")["value"];
		$customerOrder->ShipToState = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N402")["value"];
		$customerOrder->ShipToZIP = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N403")["value"];
		$customerOrder->ShipToCountry = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N404")["value"];
		$customerOrder->ShipByDate = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "G62", "38"), "G6202")["value"];
		$customerOrder->CustomerOrderNotes = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "INT"), "NTE02")["value"];
		$customerOrder->DeliveryNotes = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "DEL"), "NTE02")["value"];
		$customerOrder->Carrier = $this->getCarrierForCustomerOrder($jsonDataArray);
		$customerOrder->TotalCustomerOrderQty = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W76", "W7601")["value"];
		$customerOrder->DistributionCenter = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "GDC"), "NTE02")["value"];
		if ( !empty($customerOrder->DistributionCenter) ) {
			$customerOrder->ProductType = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "TYP"), "NTE02")["value"];
			$customerOrder->ProductDept = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "DPT"), "NTE02")["value"];
			$customerOrder->Walmart = 1;
		}

	}

	private function getCarrierForCustomerOrder($jsonDataArray) {
		$carrier = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W66", "W6601");

		// If the first call was false, try to get it elsewhere
		if ($carrier == false) {
			$carrier = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W66", "W6610");
		}

		// If it is still false, then just return null
		if ($carrier == false) {
			return NULL;
		}

		return $carrier["value"];
	}

	// Customer order is needed to get the id for reference
	function getCustomerOrderDetailsArray($customerOrder, $jsonDataArray) {
		$customerOrderDetailsArray = array();
		$inDetailsCheck = false;

		foreach ($jsonDataArray as $segment) {
			if ($segment["segment"] == "W76") {
				break;
			}

			// If we are in the beginning of a details section
			if ($segment["segment"] == "LX") {
				$customerOrderDetail = new CustomerOrderDetail();
				$inDetailsCheck = true;
				$customerOrderDetail->CustomerOrderID = $customerOrder->CustomerOrderID;
				$customerOrderDetail->EDIDocID = $customerOrder->EDIDocID;
				$customerOrderDetail->QtyUOM = new \Phalcon\Db\RawValue('default');
				$customerOrderDetail->LineNum = $this->DC->getElementFromSegmentByLabel($segment, "LX01")["value"];
			}

			// We should only be checking for these if we are in the details section
			if ($inDetailsCheck) {
				if ($segment["segment"] == "W01") {
					$customerOrderDetail->Qty = $this->DC->getElementFromSegmentByLabel($segment, "W0101")["value"];
					$customerOrderDetail->PartNum = $this->DC->getElementFromSegmentByLabel($segment, "W0105")["value"];
					$shipToPartNum = $this->DC->getElementFromSegmentByLabel($segment, "W0107");

					if ($shipToPartNum == false) {
						// If the value is false, then just set it to false in the array
						$customerOrderDetail->ShipToPartNum = NULL;
					} else {
						$customerOrderDetail->ShipToPartNum = $shipToPartNum["value"];
					}
				}

				// If we are at the end of a details section
				if ($segment["segment"] == "N9") {
					$inDetailsCheck = false;

					if ($this->DC->getElementFromSegmentByLabel($segment, "N901")["value"] == "LI") {
						$customerOrderDetail->POLine = $this->DC->getElementFromSegmentByLabel($segment, "N902")["value"];
					}

					array_push($customerOrderDetailsArray, $customerOrderDetail);
				}
			}
		}

		return $customerOrderDetailsArray;
	}
}
