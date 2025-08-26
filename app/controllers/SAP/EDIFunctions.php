<?php // LOL EDIFunctions
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;

class SAP_EDIFunctions {

	private $DC; // reference to calling EDI document controller
	private const EDI_KEY = 'SAP';
	private const DEV_ISA_ID = '006253835DFQ';
	private const DEV_GS_ID = '006253835DFQ';

	public function getAdjustmentReasonCodes() {
		// Define the adjustment reason codes based on the provided instructions
		$ADJUSTMENT_REASON_RETURNED_INVENTORY = 'NO CODE';
		$ADJUSTMENT_REASON_ADJUSTMENT_DECREASE = '06';
		$ADJUSTMENT_REASON_DUMPED_OR_DESTROYED = '07';
		$ADJUSTMENT_REASON_ADJUSTMENT_INCREASE = '56';
		$ADJUSTMENT_REASON_PHYSICAL_COUNT = 'AA';
		$ADJUSTMENT_REASON_DISASTER = 'AI';
		$ADJUSTMENT_REASON_DAMAGED_IN_FACILITY = 'AU';
		$ADJUSTMENT_REASON_DAMAGED_IN_TRANSIT = 'AV';
		$ADJUSTMENT_REASON_PRODUCT_RECALL = 'AW';

		return [
			$ADJUSTMENT_REASON_RETURNED_INVENTORY => [
				'name' => "Returned Inventory",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_ADJUSTMENT_DECREASE => [
				'name' => "Adjustment Decrease",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_DUMPED_OR_DESTROYED => [
				'name' => "Dumped or Destroyed",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_ADJUSTMENT_INCREASE => [
				'name' => "Adjustment Increase",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_PHYSICAL_COUNT => [
				'name' => "Physical Count",
				'prodReadOnly' => 1
			],
			$ADJUSTMENT_REASON_DISASTER => [
				'name' => "Disaster",
				'prodReadOnly' => 1
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
			]
		];
	}

	//  .PY.     .8  .pPYo.
	//  8  8    d'8  8
	// .oPYo.  d' 8  8oPYo.
	// 8'  `8 Pooooo 8'  `8
	// 8.  .P     8  8.  .P
	// `YooP'     8  `YooP'
	// :.....:::::..::.....:
	// :::::::::::::::::::::
	// :::::::::::::::::::::

	public function getInventoryInquiryData($docController, $customer, $inventoryInquiry) {
		$this->DC = $docController;

		$this->DC->logger->log('EDI: Getting INVENTORY_INQUIRY_ADVICE data');

		$customerInventory = $customer->getInventory();

		$EDIDocID = $this->DC->dispatcher->getParam("reference2");
		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$data = [
			// ISA08 (Interchange Receiver ID)
			'ISAReceiverID' => str_pad($this->DC->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIISAID : '006253835DFQ', 15),
			// ISA13 (Interchange Control Number), IEA02 (Interchange Control Number)
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			// GS03 (Applications Receiver's Code)
			'GSID' => $this->DC->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIGSID : '006253835DFQ',
			// GS06 (Group Control Number) &  GE02 (Group control number)
			'GSCtrlNo' => $control_number,

			// TODO: Deterimine IF there's a difference between these two
			// ST02 (Transaction Set Control Number), SE02 (Transaction Set Control Number)
			'STCtrlNo' => $control_number,

			// Array to hold the looped data
			'lines' => array()
		];

		foreach ($customerInventory as $inventoryItem) {
			array_push(
				$data['lines'],
				[
					// LIN03 (Product / Service ID)
					'productID' => $inventoryItem['ProductCode'],
					// LIN05 (Product/Service ID)
					'lotNumber' => $inventoryItem['LotNumber'],
					// PID03 (Agency Qualifier Code)
					'agencyQualifierCode' => 'SW',
					// PID04 (Product Description Code)
					'productDescriptionCode' => $inventoryItem['productDescriptionCode'],
					// PID05 (Description)
					'productDescription' => $inventoryItem['LotDescription'],
					// PID08 (Yes/No Condition or Response Code): N = Off Hold | Y = On Hold
					'responseCode' => 'Y',
					// DTM02 (Date)
					'expirationDate' => $inventoryItem['ExpirationDate'],
					// QTY02 (Quantity)
					'quantityOfProduct' => $inventoryItem['Weight'],
					// QTY03 (Composite Unit of Measure)
					'unitOfMeasure' => 'LB',
					// MEA03 (Measurement Value)
					'netWeight' => $inventoryItem['Weight']
				]
			);
		}

		// 4 if license plate
		// SE01 (Number of Included Segments)
		$data['SECount'] = 5 + (5 * count($data['lines']));

		return $data;
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
		$this->DC = $documentController;

		$dtm = new TransactionManager();

		$DBTransaction = $dtm->get();

		$this->DC->logger->log("testing");

		$this->DC->logger->log("In " . self::EDI_KEY .  " transaction: $transaction / $docid");

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

		$this->DC->logger->log("delivery->Warehouse");
		$this->DC->logger->log($delivery->Warehouse);

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

		$delivery = Delivery::findfirst("DeliveryID = $deliveryID");

		$details =  DeliveryDetail::find("DeliveryID = $deliveryID"); // Doesn't have lot number? Can't send it without...

		$EDIDocID = $this->DC->dispatcher->getParam("reference2");

		$EDIDocument = EDIDocument::findfirst("DocID = $EDIDocID");

		$jsonEDIArray = json_decode($EDIDocument->JsonObject, true);

		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$data = array(
			// ISA08 (Interchange Receiver ID)
			'ISAReceiverID' => str_pad($customer->EDIISAID, 15),
			// ISA13 (Interchange Control Number), IEA02 (Interchange Control Number)
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			// ISA15 (Usage Indicator)
			'usageIndicator' => 'P', // TODO: restore $documentController->config->settings['environment'] == 'PRODUCTION' ? 'P' : 'T',
			// GS03 (Application Receiver's Code)
			'GSRecCode' => $customer->EDIISAID,
			// GS06 (Group Control Number), GE02 (Group Control Number)
			'GSCtrlNo' => $control_number,
			// ST02 (Transaction Set Control Number), SE02 (Transaction Set Control Number)
			'STCtrlNo' => $control_number,

			// TODO? Is this just today's date?
			// W1702 (Receipt of a delivery date)
			'ReceiveDate' => str_replace('-', '', substr($this->DC->mysqlDate, 0, 10)),

			// W1703 (Warehouse Receipt Number)
			'ReceiveNumber' => $delivery->ShipmentID,
			// W1704 (Depositor Order Number) - LOL Order number from W0602 of 943
			'VendorOrderNumber' => $delivery->OrderNum,
			// N902
			'OrigRefNumber' => $delivery->Reference,
			// N1s // Getting the data from the incoming 943 and using it here
			'ShipToID' => $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "ST"), "N104")["value"],

			// Getting the data from the incoming 943 and using it here
			'ShipToName' => $this->DC->getElementFromSegmentByLabel(
				$this->DC->getSegmentWithElementValue(
					$jsonEDIArray,
					"N1",
					"ST"
				),
				"N102"
			)["value"],

			// Getting the data from the incoming 943 and using it here
			'ShipToAddress' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N1', "ST", 1, "N3", "N301"),

			// Getting the data from the incoming 943 and using it here
			'ShipToCity' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N1', "ST", 2, "N4", "N401"),

			// Getting the data from the incoming 943 and using it here
			'ShipToState' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N1', "ST", 1, "N4", "N402"),

			// Getting the data from the incoming 943 and using it here
			'ShipFromID' => $delivery->ShipFromID,

			// Getting the data from the incoming 943 and using it here
			'ShipFromName' => $this->DC->getElementFromSegmentByLabel(
				$this->DC->getSegmentWithElementValue(
					$jsonEDIArray,
					"N1",
					"SF"
				),
				"N102"
			)["value"],

			// Getting the data from the incoming 943 and using it here
			'ShipFromAddress' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N1', "SF", 1, "N3", "N301"),

			// Getting the data from the incoming 943 and using it here
			'ShipFromCity' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N1', "SF", 2, "N4", "N401"),

			// Getting the data from the incoming 943 and using it here
			'ShipFromState' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N1', "SF", 1, "N4", "N402"),

			// Getting the data from the incoming 943 and using it here
			'ShipDate' => str_replace('-', '', substr($delivery->ShipDate, 0, 10)),

			// Getting the data from the incoming 943 and using it here
			'CarrierSCAC' => $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W27", "W2703")["value"],

			// W1401 (Quantity Received)
			'totcases' => 0,
			'palletCount' => 0,
			'materialNumber' => 4005385,
			'lines' => array()
		);

		foreach ($details as $detail) {

			$receipt = DeliveryDetailReceipt::findFirst("DeliveryDetailID = " . $detail->DeliveryDetailID);
			$rqty = (isset($receipt)) ? $receipt->ReceivedQty : 0;

			// Just using 'SW' for now
			// W0704 (Product Service ID Qualifier)
			$upcQualifier = 'SW';

			// upcCaseCode is not needed if the qualifier equals SW
			if ($upcQualifier === 'SW') {
				$upcCaseCode = '';
			} else {
				$upcCaseCode = '??';
			}

			// This section is only used if something is off in the quantities
			$showW13Section = $detail->Qty != $receipt->ReceivedQty;
			// If we received less than expected, then this is 02, else it's 03
			$detailExceptionConditionCode = $receipt->ReceivedQty < $detail->Qty ? '02' : '03';

			$line = [
				'line' => $detail->LineNum,
				// W0701 (Quantity Received)
				'qtyreceived' => $rqty,
				// W0702 (Unit for Measurement Code)
				'qtyMeasurementCode' => $detail->QtyUOM,
				// W0703 (UPC Case Code)
				'upcCaseCode' => $upcCaseCode,
				// W0704 (Product Service ID Qualifier)
				'upcQualifier' => $upcQualifier,
				// W0705 (Product/Service ID)
				'prodnum' => $detail->PartNum,
				// W0707 (LOL lot number)
				'custlotno' => $detail->CustomerLot,
				// N904 (Expiration Date)
				'lotExpDate' => str_replace('-', '', substr($detail->ExpDate, 0, 10)),
				// W2004 (Weight)
				'weight' => $detail->NetWeight,
				// W13-Flag
				'showW13Section' => $showW13Section,
				// W1301 (Quantity)
				'detailExceptionQuantity' => abs($receipt->ReceivedQty - $detail->Qty),
				// W1302 (Unit for Measurement Code)
				'detailExceptionMeasurementUnit' => $detail->QtyUOM,
				// W1303 (Receiving Condition Code)
				'detailExceptionConditionCode' => $detailExceptionConditionCode,
			];

			array_push($data['lines'], $line);

			$data['totcases'] += $rqty;

			$data['palletCount'] += $detail->PalletCount;
		}

		// TODO? What is considered a segment? -- A: A segment is a line of data in the file.
		// SE01 (Number of included segments) including ST and SE
		$data['SECount'] = 16 + (4 * count($data['lines'])); // 7 segments + 4 segments per line (optional segments are calculated in the template)



		return $data;
	}


	public function getUPCCodeAfterLXSegment($jsonEDIArray, $currentLine) {
		// Find the LX segment for the current line
		foreach ($jsonEDIArray as $segment) {
			if ($segment['segment'] == 'LX' && $segment['elements'][0]['value'] == strval($currentLine)) {
				// Found the LX segment for this line, now look for the W01 segment
				$foundLX = true;
				continue;  // Move to the next segment
			}

			if (isset($foundLX) && $foundLX && $segment['segment'] == 'W01') {
				// Found the W01 segment, now retrieve the W0103 value
				foreach ($segment['elements'] as $element) {
					if ($element['label'] == 'W0103') {
						$upcCaseCode = $element['value'];

						// Clean the UPC code if it's 14 characters long
						if (strlen($upcCaseCode) == 14) {
							$upcCaseCode = substr($upcCaseCode, 1, -1);
						}

						return $upcCaseCode;  // Return the cleaned UPC code
					}
				}
			}

			// If we found another LX segment before finding W01, stop searching
			if (isset($foundLX) && $foundLX && $segment['segment'] == 'LX') {
				break;
			}
		}

		// If the method hasn't returned by now, it didn't find the UPC code
		return null;
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

	// Warehouse Shipping Advice [945]
	public function getWarehouseShippingAdviceData($documentController, $customer, $bolData, $offer, $CustomerOrder, $CustomerOrderDetail) {
		$this->DC = $documentController;
		$data = array();
		$CustomerOrderDetail = $CustomerOrderDetail->toArray();
		$details = array();
		$linetotals = array();
		$EDIDocID = $CustomerOrder->EDIDocID;
		$EDIDocument = EDIDocument::findfirst("DocID = $EDIDocID");
		$jsonEDIArray = json_decode($EDIDocument->JsonObject, true);

		foreach ($CustomerOrderDetail as $line) {
			$prodnum = $line['PartNum'];

			if (!isset($linetotals[$line['POLine']])) {
				$linetotals[$line['POLine']] = 0;
			}

			// save detailsw at prod num level if all else fails.
			$details[$prodnum] = array(
				'qtyordered' => $line['Qty'],
				'poline' => $line['POLine'],
				'detailid' => $line['CustomerOrderDetailID']
			);


			$mapraw = OrderOfferDetailMap::find("CustomerOrderDetailID = " . $line['CustomerOrderDetailID']);
			if ($mapraw) {
				$map = $mapraw->toArray();

				foreach ($map as $mapline) {
					if (isset($mapline['OfferItemID']) && !empty($mapline['OfferItemID'])) {
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
					} else {
						// no offerItemID -- manual offer line
						$details[$prodnum] = array(
							'qtyordered' => $line['Qty'],
							'poline' => $line['POLine'],
							'detailid' => $line['CustomerOrderDetailID']
						);

						// may return wrong qty
						$shipraw = CustomerShipDetail::findFirst([
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
		$lineCount = 1;

		$currentline = 1;
		$prodnumUPCCodes = [];

		foreach ($offer as $offerLine) {

			$this->DC->logger->log("SAP getWarehouseShippingAdviceData offer");
			$this->DC->logger->log($offerLine);

			$prodnum = $offerLine['ProductCode'];

			if (isset($prodnumUPCCodes[$prodnum])) {
				$upcCaseCode = $prodnumUPCCodes[$prodnum];
			} else {
				$upcCaseCode = $this->getUPCCodeAfterLXSegment($jsonEDIArray, $currentline++);
				$prodnumUPCCodes[$prodnum] = $upcCaseCode;
			}

			$mydetail = isset($details[$offerLine['OfferItemID']]) ? $details[$offerLine['OfferItemID']] : $details[$prodnum];

			$poline = $mydetail['poline'];

			// TODO: find line by sku + qty?
			$qtyOrdered = intval($mydetail['qtyordered']);
			$qtyShipped = intval($offerLine['PiecesfromVat']); // per vat

			if (!isset($tsq[$poline])) {
				$tsq[$poline] = 0;
			}

			$tsq[$poline] += $qtyShipped; // total shipped so far for this poline

			$qtyDiff = $tsq[$poline] - $qtyOrdered;

			// Which of these is right?!
			$shipStatus = ($qtyOrdered == $linetotals[$poline]) ? 'CC' : 'CP';
			$shipStatus = $qtyDiff != 0 ? 'CP' : 'CC';

			// default for fractional weight
			$weight = $offerLine['WeightfromVat'];

			// get pc weight from descriptions Value2
			$pcweight = Parameter::getEDIPcWeight($prodnum, $customer->EDIKey);

			if ($pcweight) {
				$weight = $qtyShipped * $pcweight;
			}

			$weight = number_format($weight, 3, '.', ''); // 3 decimal places

			$licensePlates = ShipPallet::getLicensePlates( $offerLine['OfferItemID'] );

			$this->DC->logger->log("SAP getWarehouseShippingAdviceData licensePlates");
			$this->DC->logger->log($licensePlates);

			$line = [
				// LX01 (Asigned Number)
				'line' => $lineCount++,
				// W04 may not be used
				// W1201 (Shipment/Order Status Code)
				'shipstatus' => $shipStatus,  // CC = complete  CP = Partial
				// W1202 (Quantity Ordered)
				'qtyordered' => $qtyOrdered, // Order qty from order
				// W1203 (Number of Units Shipped)
				'qtyshipped' => $qtyShipped, // 60 // TODO: on this vat
				// W1204 (Quantity Difference)
				'qtydiff' => $qtyDiff,
				// W1206 (UPC Case Code)
				'upcCaseCode' => $upcCaseCode,
				// W1208 (Product/Service ID)
				'prodnum' => $prodnum, // '103400', // ProdNum
				// W1210 (Weight)
				'weight' => $weight, // G L Gross weight
				// N902 (Reference Identification)
				'poline' => $poline,
				'licenseplate' => $licensePlates[$offerLine['OfferItemVatID']],
				'CustomerLotNumber' => $offerLine['CustomerLotNumber'],
				'OfferItemDescription' => $offerLine['OfferItemDescription'],
			];

			array_push($lines, $line);
			$totNetWeight += $offerLine['OfferItemWeight'];
			$totCases += $offerLine['OfferItemPieces'];
		}

		$shipDate = substr($bolData['BOLDate'], 0, 10);
		$shipDate = str_replace('-', '', $shipDate);
		$departTime = substr($bolData['UpdateDate'], 11, 8);
		$departTime = str_replace($departTime, ':', '');
		// This is always 'M'. If that changes, then adjust it <here class=""></here>
		$transportMethodCode = 'M';
		$equipmentDescriptionCode = 'RT';

		$EDIDocID = $this->DC->dispatcher->getParam("reference2");
		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);


		$data = [
			// ISA08 (Interchange Receiver ID)
			'ISAReceiverID' => str_pad($documentController->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIISAID : self::DEV_ISA_ID, 15),
			// ISA13 (Interchange Control Number), IEA02 (Interchange Control Number)
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			// GS03 (Applications Receiver's Code)
			'GSID' => $this->DC->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIGSID : '006253835DFQ', // TODO: right pad to 15
			// ISA15 (Usage Indicator)
			'UsageIndicator' => 'P', //TODO: changes this back on live // $this->DC->config->settings['environment'] == 'PRODUCTION' ? 'P' : 'T',
			// GS06 (Group Control Number), GE02 (Group Control Number)
			'GSCtrlNo' => $control_number,
			// ST02 (Transaction set control number), SE02 (Transaction set control number)
			'STCtrlNo' => $control_number,
			// W0602 (Depositor Order Number)
			'VendorOrderNumber' => $CustomerOrder->CustomerOrderNum,
			// W0603 (Date)
			'ShipDate' => $shipDate,
			// W0606 (Purchase Order Number)
			'CustPONumber' => $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W05", "W0502")['value'], // from 940 W0502
			// N104 (Identification Code Qualifier)
			'ShipFromID' => 'PLYMOUTH',
			// N104 (Identification Code) - 3-Digit Warehouse number
			'ShipToID' => $CustomerOrder->ShipToID, // from 940 Order->ShipToID
			// N902 (Reference Identification Qualifier): Bill of Lading Number
			'MasterBOL' => $CustomerOrder->CustomerOrderNum, // from 940 (also order number?)
			// N902 (Reference Identification Qualifier): Load Planning number
			'CustOrderNumber' => $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W05", "W0503")['value'], //  From 940 N902 (CO)
			// N902 (Reference Identification Qualifier): Stop Sequence Number
			'StopSequenceNumber' => $CustomerOrder->LoadSequenceStopNumber,
			// N902 (Reference Identification Qualifier): Stop Sequence Number
			'SealNumber' => isset($bolData['SealNumber']) ? $bolData['SealNumber'] : 'NA',
			// G62 (Optional)
			'DepartureTime' => $departTime, // HHMMSS?  use ship date from above
			// W2701 (Transportation Method/Type Code)
			'transportMethodCode' => $transportMethodCode,
			// W2702 (Standard Carrier Alpha Code)
			'SCACCode' => $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W66", "W6601")["value"],
			'routingDescription' => 'Ship via ' . $bolData['CarrierName'],

			// W2705 (Equipment Description Code)
			'equipmentDescriptionCode' => $equipmentDescriptionCode,
			// W2706 (Equipment Initial) [Optional]
			'equipmentInitial' => '',
			// W2707 (Equipment Number) - BoL SealNumber
			'equipmentNumber' => $bolData['SealNumber'],
			// W0301 (Number of Units shipped)
			'totcases' => $totCases, // calc from above
			// W0302 (Weight): 3 decimal places - LB, calc from above
			'totweight' => number_format($totNetWeight, 3, '.', ''),
			'ShipToName' => $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "ST"), "N102")["value"],
			'ShipToAddress1' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N3", "N301"),
			'ShipToAddress2' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N3", "N302"),
			'ShipToCity' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N4", "N401"),
			'ShipToState' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N4",	"N402"),
			'ShipToZipCode' => $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N4", "N403"),
			'MasterReferenceLinkNumber' => $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W05", "W0505")['value'],
			// SE01 (Number of Included Segments)
			'SECount' => 9 + (7 * count($lines)),
			// Loop Data
			'Lines' => $lines
		];


		return $data;
	}


	// .oPYo.    .8  oooooo
	// 8'  `8   d'8     .o'
	// 8.  .8  d' 8    .o'
	// `YooP8 Pooooo  .o'
	//     .P     8  .o'
	// `YooP'     8  o'
	// :.....:::::..:..:::::
	// :::::::::::::::::::::
	// :::::::::::::::::::::

	// ST*947*837460001~
	// W15*20230217*1391265893*1391265893~
	// N1*WH*WAREHOUSE NAME*9*1234567890802~
	// N1*DE*SAPUTO*9*7964262860000~
	// W19*06*-13.000*CA*007015788277*VN*1009332***BS13026001*58.50*N******PJ*20230126~
	// SE*6*837460001~

	public function get947X12Data($documentController, $customer, $warehouseAdjustmentList, $lot, $vat) {
		$this->DC = $documentController;

		$EDIDocID = $this->DC->dispatcher->getParam("edidocumentid");

		// $this->DC->logger->log("EDIDocID");
		// $this->DC->logger->log($EDIDocID);

		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$isaReceiverID = $this->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIISAID : '006253835DFQ';

		// $this->DC->logger->log("customer->EDIISAID");
		// $this->DC->logger->log($customer->EDIISAID);

		$data = [
			// ISA08 (Interchange Receiver ID)
			'ISAReceiverID' => $isaReceiverID,
			// ISA13 (Interchange Control Number), IEA02 (Interchange Control Number)
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			// GS06 (Group Control Number), GE02 (Group Control Number)
			'GSCtrlNo' => $control_number,
			// ST02, SE02
			'STCtrlNo' => $control_number,
			// N902, W1908
			'referenceIdentification' => $vat->VatNumber,
			// W1502
			'WarehouseAdjustmentNumber' => $control_number,
			// W1503
			'DepositorAdjustmentNumber' => $control_number,
			// Loop
			'lines' => array(),
		];



		// Each transaction has its own list of details (W19 section)
		foreach ($warehouseAdjustmentList as $warehouseAdjustment) {

			// TODO? EDI - Is this just SW all the time? - Depends on if we have UPC codes...

			$productIDQualifier = 'SW';
			$upcCaseCode = $productIDQualifier === "SW" ? '' : '???';

			$line = [
				// W1901
				'adjustmentReasonCode' => $warehouseAdjustment->AdjustmentReason,
				// W1902
				'CreditDebitQuantity' => $warehouseAdjustment->CreditDebitQuantity,
				// W1903
				'measurementUnit' => $warehouseAdjustment->ValueTypeChanged,
				// W1904
				'upcCaseCode' => $upcCaseCode,
				// W1905
				'productIDQualifier' => $productIDQualifier,
				// W1906
				'productID' => $warehouseAdjustment->ProductID,
				// W1909 [Warehouse Lot Number]
				'OshLotNumber' => $lot->LotNumber,
				// W1910
				'netWeight' => $warehouseAdjustment->NetWeight,
				// W1916
				'inventoryTransactionType' => $warehouseAdjustment->InventoryTransactionTypeCode,
				// W1918
				'MDProductID' => $warehouseAdjustment->ServiceID,
			];

			array_push($data['lines'], $line);
		}

		// SE01
		$data['SECount'] = 7 + (count($data['lines']));

		// $this->DC->logger->log("data");
		// $this->DC->logger->log($data);

		return $data;
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

	private function populateDelivery($delivery, $jsonEDIArray) {

		$delivery->Status = 'Pending';
		$delivery->OrderNum = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W06", "W0602")["value"];
		$delivery->ShipDate = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "G62", "11"), "G6202")["value"];

		$delivery->Sender = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "DE"), "N102")["value"];
		$delivery->ShipFromID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "SF"), "N104")["value"]; // may be blank

		$delivery->Warehouse = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 2, "N4", "N401");

		$delivery->Reference = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N9", "LO"), "N902")["value"];
		$delivery->ShipmentID = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W06", "W0604")["value"];
		$delivery->Carrier = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W27", "W2703")["value"];
		$delivery->TotalShippedQty = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W03", "W0301")["value"];
		$delivery->TotalShippedWeight = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W03", "W0302")["value"];

		$delivery->ReceivedDate = $this->DC->mysqlDate;
	}

	private function isValueInParameterTable($w0405_value) {
		// Query the "parameter" table for the existence of $w0405_value in the Value3 column
		$parameter = Parameter::findFirst(
			array(
				'conditions' => 'ParameterGroupID = :PGID: AND Value3 = :V3: AND Value4 = :V4:',
				'bind' => array(
					'PGID' => Parameter::PARAMETER_GROUP_DESCS,
					'V3' => $w0405_value,
					'V4' => 'SAP'  // Since Value4 seems to be hardcoded to 'SAP' in POST data
				)
			)
		);

		// If a record is found, return true, else return false
		return ($parameter !== false);
	}

	private function getDeliveryDetailsArray($delivery, $jsonEDIArray) {
		$deliveryDetailsArray = array();
		$inDeliveryDetail = false;
		$processedProdnums = array(); // To keep track W0405

		$n9HelperArray = array(
			"LI" => "LineNum",
			"LT" => "CustomerLot",
			"LV" => "LicensePlate",
			"ZZ" => "ExpDate"
		);

		$lineNum = 1;

		foreach ($jsonEDIArray as $segment) {

			if ($segment["segment"] == "W03") {
				break; // we hit a total ine, bail out.
			}

			if ($segment["segment"] == "W04") {
				$deliveryDetail = new DeliveryDetail();
				$inDeliveryDetail = true;

				$deliveryDetail->DeliveryID = $delivery->DeliveryID;
				$deliveryDetail->EDIDocID = $delivery->EDIDocID;
				// TODO: Assign to constant that references the UUID
				$deliveryDetail->Status = 'Pending';
				$deliveryDetail->Qty = $this->DC->getElementFromSegmentByLabel($segment, "W0401")["value"];
				$deliveryDetail->QtyUOM = $this->DC->getElementFromSegmentByLabel($segment, "W0402")["value"];
				$deliveryDetail->PartNum = $this->DC->getElementFromSegmentByLabel($segment, "W0405")["value"];
				$deliveryDetail->LineNum = $lineNum++;
				$deliveryDetail->CustomerLot = $this->DC->getElementFromSegmentByLabel($segment, "W0407")["value"]; // may not be set?

				// $deliveryDetail->LicensePlate = set in n9HelperArray below

				$deliveryDetail->ExpDate = $this->DC->getElementFromSegmentByLabel(
					$this->DC->getSegmentWithElementValue(
						$jsonEDIArray,
						"N9",
						"MAKE DATE"
					),
					"N904"
				)["value"];



				$w0405_value = $this->DC->getElementFromSegmentByLabel($segment, "W0405")["value"];
			}
			// $this->DC->logger->log("deliveryDetail->ExpDate");
			// $this->DC->logger->log($deliveryDetail->ExpDate);

			if ($segment["segment"] == "G69") {

				$g69_value = $segment["elements"][0]; // Accessing the first element directly

				// Check for duplicates
				if (!empty($g69_value) && !empty($w0405_value) && !in_array($w0405_value, $processedProdnums) && !$this->isValueInParameterTable($w0405_value)) {

					array_push($processedProdnums, $w0405_value); // Store the w0405_value for future checks

					try {
						$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
						$url = $protocol . $_SERVER['HTTP_HOST'] . "/parameter/save";
						$this->DC->utils->POST(
							$url,
							[
								"ParameterGroupID" => Parameter::PARAMETER_GROUP_DESCS,
								"Value1" => $g69_value,
								"Value3" => $w0405_value,
								"Value4" => 'SAP',
								"Description" => $g69_value,
							]
						);
					} catch (\Phalcon\Exception $e) {
						$this->DC->logger->log("Exception saving parameter: " . $e->getMessage());
						return;
					}

					$this->DC->logger->log("Record Added in the DB Parameter");
				} else {

					$this->DC->logger->log("Record Found in the DB Parameter");
				}
			}

			if ($inDeliveryDetail) {

				if ($segment["segment"] == "N9") {
					foreach ($n9HelperArray as $key => $value) {
						$labelReference = $key == "ZZ" ? "N904" : "N902";

						if ($this->DC->elementsContainValue($segment["elements"], $key)) {

							// If the key is "ZZ", check if N903 is "MAKE DATE"
							$n903_element = $this->DC->getElementFromSegmentByLabel($segment, "N903");

							if ($key == "ZZ" && ($n903_element === false || $n903_element["value"] !== "MAKE DATE")) {
								continue;
							}

							$deliveryDetail->$value = $this->DC->getElementFromSegmentByLabel($segment, $labelReference)["value"];
						}
					}
					$this->DC->logger->log("deliveryDetail->value");
					$this->DC->logger->log($deliveryDetail->$value);
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

	public function send940ConditionalEmail($jsonEDIArray, $documentController) {

		$this->DC = $documentController;
		// Extract endCustomer
		$endCustomer = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 0, "N1", "N102");

		$warehouseName = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "WH", 1, "N4", "N401");

		// Extract other data for email segments
		$w0501 = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W05", "W0501")['value'];

		$address = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N3", "N301");

		$city = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N4", "N401");

		$state = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N4", "N402");

		$zip = $this->DC->getSegementValueAfterEntity($jsonEDIArray, "N1", "ST", 1, "N4", "N403");

		$custOrder = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W05", "W0502")['value'];

		$requestedShipDate = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, 'G62', '10'), 'G6202')['value'];

		$deliveryRequestedDate = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, 'G62', '02'), 'G6202')['value'];

		$validLXIndices = [];

		// Step 1: Identify valid LX segments
		foreach ($jsonEDIArray as $index => $segment) {
			if (
				$segment['segment'] === 'LX' &&
				isset($jsonEDIArray[$index + 1], $jsonEDIArray[$index + 2], $jsonEDIArray[$index + 3]) &&
				$jsonEDIArray[$index + 1]['segment'] === 'W01' &&
				$jsonEDIArray[$index + 2]['segment'] === 'G69' &&
				$jsonEDIArray[$index + 3]['segment'] === 'N9'
			) {
				$validLXIndices[] = $index;
			}
		}

		$lines = [];

		// Step 2: Retrieve and construct line data using getSegementValueAfterEntity
		foreach ($validLXIndices as $lxIndex) {

			$assignedNumber = $jsonEDIArray[$lxIndex]['elements'][0]['value'];

			$quantityOrdered = $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'W01', '1', $lxIndex, 'W01', 'W0101');

			$upcCaseCode = $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'W01', '1', $lxIndex, 'W01', 'W0103');

			$prodNum = $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'W01', '1', $lxIndex, 'W01', 'W0105');

			$productDescription = $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'G69', '1', $lxIndex, 'G69', 'G6901');

			$poline = $this->DC->getSegementValueAfterEntity($jsonEDIArray, 'N9', '1', $lxIndex, 'N9', 'N902');

			$lines[] = [
				"Assigned Number: $assignedNumber",
				"Quantity Ordered: $quantityOrdered",
				"UPC Case Code: $upcCaseCode",
				"Product Number: $prodNum",
				"Product Description: $productDescription",
				"Po Line: $poline"

			];
		}

		// Construct email body
		$emailSegments = [
			"\n",
			"A 940{$w0501} Warehouse Shipping Order has been processed. Below are the detailed segments of the order:\n\n",
			"End Customer: $endCustomer\n",
			"Address: $address, $city, $state, $zip\n",
			"Customer Order Number: $custOrder\n",
			"Order Date: $deliveryRequestedDate\n",
			"Ship By: $requestedShipDate\n",
			"Warehouse Name: $warehouseName\n\n",
		];

		foreach ($lines as $line) {
			foreach ($line as $lineItem) {
				$emailSegments[] = "$lineItem\n";
			}
			$emailSegments[] = "\n";
			// Add an extra newline between line groups for readability
		}

		$message = implode("", $emailSegments);

		// Constructing email headers and subject
		$headers = 'From: edi-notifications@fai2.com';

		$subject = "Detailed 940{$w0501} Warehouse Shipping Order has been Processed";

		// Sending the email
		return mail("ric@fai2.com", $subject, $message, $headers);
	}

	public function processWarehouseShippingOrder($documentController, $transaction, $jsonEDIArray, $docid) {

		$edikey = 'SAP';

		$this->DC = $documentController;

		$dbTM = new TransactionManager();

		$DBTransaction = $dbTM->get();

		$this->DC->logger->log("In {$edikey} transaction: {$transaction}");

		$customerOrder = new CustomerOrder();

		$customerOrder->EDIDocID = $docid;

		$is_940c = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W05", "W0501")['value'] == "C";

		if (!$is_940c) {
			return $this->send940ConditionalEmail($jsonEDIArray, $documentController);
		}

		$this->populateCustomerOrder($customerOrder, $jsonEDIArray);

		$customerOrder->setTransaction($DBTransaction);

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

		$w0503 = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W05", "W0503");

		$w0505 = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W05", "W0505");

		$customerOrder->CustPONum = !empty($w0503["value"]) ? $w0503["value"] : $w0505["value"];

		$customerOrder->ShipToID =
			$this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N1", "CN"), "N104")["value"] ??
			$this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N1", "ST"), "N104")["value"];

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

		$customerOrder->ShipToState = $this->DC->getSegementValueAfterEntity($jsonDataArray, 'N1', "ST", 1, "N4", "N402");

		$customerOrder->ShipToZIP = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N403")["value"];

		$customerOrder->ShipToCountry = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N404")["value"];

		$customerOrder->ShipByDate = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "G62", "G6202")["value"];

		$customerOrder->CustomerOrderNotes = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "INT"), "NTE02")["value"];

		$customerOrder->DeliveryNotes = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "DEL"), "NTE02")["value"];

		$customerOrder->Carrier = $this->getCarrierForCustomerOrder($jsonDataArray);

		$customerOrder->TotalCustomerOrderQty = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W76", "W7601")["value"];

		$customerOrder->LoadSequenceStopNumber = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N9", "QN"), "N902")["value"];
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
