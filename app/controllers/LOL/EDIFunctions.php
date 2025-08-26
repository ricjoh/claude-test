<?php // LOL EDIFunctions
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;

class LOL_EDIFunctions {

	private $DC; // reference to calling EDI document controller
	private const EDI_KEY = 'LOL';
	private const DEV_ISA_ID = '006253835DFQ';
	private const DEV_GS_ID = '006253835DFQ';

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
		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$data = array(
			// ISA08 (Interchange Receiver ID)
			'ISAReceiverID' => str_pad($customer->EDIISAID, 15),
			// ISA13 (Interchange Control Number), IEA02 (Interchange Control Number)
			'ISACtrlNo' => str_pad($control_number, 9, '0', STR_PAD_LEFT),
			// ISA15 (Usage Indicator)
			'usageIndicator' => $documentController->config->settings['environment'] == 'PRODUCTION' ? 'P' : 'T',
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
			// N1s
			'ShipToID' => 'PLYMOUTH',

			// TODO? Not used... Cut it??
			'ShipFromID' => $delivery->ShipFromID,

			// W1401 (Quantity Received)
			'totcases' => 0,

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

			$this->DC->logger->log('LINE');
			$this->DC->logger->log($line);

			array_push($data['lines'], $line);

			$data['totcases'] += $rqty;
		}

		// TODO? What is considered a segment? -- A: A segment is a line of data in the file.
		// SE01 (Number of included segments) including ST and SE
		$data['SECount'] = 7 + (2 * count($data['lines'])); // 7 segments + 2 segments per line (optional segments are calculated in the template)

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

	// Warehouse Shipping Advice [945]
	public function getWarehouseShippingAdviceData($documentController, $customer, $bolData, $offerData, $CustomerOrder, $CustomerOrderDetail) {
		$this->DC = $documentController; // parent controller (EDIDocumentController.php)

		$data = array();

		$CustomerOrderDetail = $CustomerOrderDetail->toArray();

		$details = array();
		$linetotals = array();

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

		$this->DC->logger->log($details);

		$lines = array();
		$tsq = array(); // total shipped so far for each POLine
		$totNetWeight = 0.0;
		$totCases = 0;
		$lineCount = 1;

		foreach ($offerData as $offer) {
			$prodnum = $offer['ProductCode'];

			$mydetail = isset($details[$offer['OfferItemID']]) ? $details[$offer['OfferItemID']] : $details[$prodnum];
			$poline = $mydetail['poline'];

			// TODO: find line by sku + qty?
			$qtyOrdered = intval($mydetail['qtyordered']);
			$qtyShipped = intval($offer['PiecesfromVat']); // per vat

			if (!isset($tsq[$poline])) {
				$tsq[$poline] = 0;
			}

			$tsq[$poline] += $qtyShipped; // total shipped so far for this poline

			$qtyDiff = $tsq[$poline] - $qtyOrdered;

			// Which of these is right?!
			$shipStatus = ($qtyOrdered == $linetotals[$poline]) ? 'CC' : 'CP';
			$shipStatus = $qtyDiff != 0 ? 'CP' : 'CC';

			// default for fractional weight
			$weight = $offer['WeightfromVat'];

			// get pc weight from descriptions Value2
			$pcweight = Parameter::getEDIPcWeight($prodnum, $customer->EDIKey);

			if ($pcweight) {
				$weight = $qtyShipped * $pcweight;
			}

			$weight = number_format($weight, 3, '.', ''); // 3 decimal places

			// Need logic because this isn't always used
			$upcCaseCode = '';

			$subLotID = 0;

			$this->DC->logger->log('Offer Data');
			$this->DC->logger->log($offerData);

			$line = [
				// LX01 (Asigned Number)
				'line' => $lineCount++,
				// W04 may not be used
				// W1201 (Shipment/Order Status Code)
				'shipstatus' => $shipStatus,  // CC = complete  CP = Partial
				// W1202 (Quantity Ordered)
				'qtyordered' => $qtyOrdered, // Order qty from order
				// W1203 (Number of Units Shipped)
				'qtyshipped' => $qtyShipped, // 60
				// W1204 (Quantity Difference)
				'qtydiff' => $qtyDiff,
				// W1206 (UPC Case Code)
				'upcCaseCode' => $upcCaseCode,
				// W1208 (Product/Service ID)
				'prodnum' => $prodnum, // '103400', // ProdNum
				// W1210 (Weight)
				'weight' => $weight, // G L Gross weight
				// W1218 (Product/Service ID)
				// TODO: LOL EDI: Is this the right data? My notes from 12/29 said to use BoL->ProductNumber
				'productID' => $prodnum,
				// W1222 (Product/Service ID): SubLot
				'subLotID' => $subLotID,
				// N902 (Reference Identification)
				'poline' => $poline
			];

			array_push($lines, $line);

			$totNetWeight += $offer['OfferItemWeight'];
			$totCases += $offer['OfferItemPieces'];
		}

		$shipDate = substr($bolData['BOLDate'], 0, 10);
		$shipDate = str_replace('-', '', $shipDate);
		$departTime = substr($bolData['UpdateDate'], 11, 8);
		$departTime = str_replace($departTime, ':', '');
		// This is always 'M'. If that changes, then adjust it here.
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
			'UsageIndicator' => $this->DC->config->settings['environment'] == 'PRODUCTION' ? 'P' : 'T',
			// GS03 (Applications Receiver's Code)
			'GSID' => $this->DC->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIGSID : '006253835DFQ', // TODO: right pad to 15
			// GS06 (Group Control Number), GE02 (Group Control Number)
			'GSCtrlNo' => $control_number,
			// ST02 (Transaction set control number), SE02 (Transaction set control number)
			'STCtrlNo' => $control_number,
			// W0602 (Depositor Order Number)
			'VendorOrderNumber' => $CustomerOrder->CustomerOrderNum,
			// W0603 (Date)
			'ShipDate' => $shipDate,
			// W0606 (Purchase Order Number)
			'CustPONumber' => $CustomerOrder->CustPONum, // from 940 W0503
			// N104 (Identification Code Qualifier)
			'ShipFromID' => 'PLYMOUTH',
			// N104 (Identification Code) - 3-Digit Warehouse number
			'ShipToID' => $CustomerOrder->ShipToID, // from 940 Order->ShipToID
			// N902 (Reference Identification Qualifier): Bill of Lading Number
			'MasterBOL' => $CustomerOrder->CustomerOrderNum, // from 940 (also order number?)
			// N902 (Reference Identification Qualifier): Load Planning number
			'CustOrderNumber' => $CustomerOrder->CustPONum, //  From 940 N902 (CO)
			// N902 (Reference Identification Qualifier): Stop Sequence Number
			'StopSequenceNumber' => $CustomerOrder->LoadSequenceStopNumber,
			// N902 (Reference Identification Qualifier): Stop Sequence Number
			'SealNumber' => isset($bolData['SealNumber']) ? $bolData['SealNumber'] : 'NA',
			// G62 (Optional)
			'DepartureTime' => $departTime, // HHMMSS?  use ship date from above
			// W2701 (Transportation Method/Type Code)
			'transportMethodCode' => $transportMethodCode,
			// W2702 (Standard Carrier Alpha Code)
			'SCACCode' => $bolData['CarrierName'],

			// TODO: LOL EDI: We need to ask them about this.
			// W2703 (Routing)
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
			// SE01 (Number of Included Segments)
			'SECount' => 11 + (3 * count($lines)),
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

	public function get947X12Data($documentController, $customer, $warehouseAdjustmentList, $lot, $vat) {
		$this->DC = $documentController;

		$EDIDocID = $this->DC->dispatcher->getParam("reference2");
		$control_number = str_pad($EDIDocID, 4, '0', STR_PAD_LEFT);

		$isaReceiverID = $this->config->settings['environment'] == 'PRODUCTION' ? $customer->EDIISAID : '006253835DFQ';

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
			// NTE
			'noteSpecialInstructions' => '',
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
		$data['SECount'] = 7 + (4 * count($data['lines']));

		return $data;
	}

	private function populateDelivery($delivery, $jsonEDIArray) {
		$this->setCarrierForDelivery($delivery, $jsonEDIArray);  // Not Really Required

		$delivery->Status = 'Pending';
		$delivery->OrderNum = $this->DC->getElementBySegmentNameAndLabel($jsonEDIArray, "W06", "W0602")["value"];
		$delivery->ShipDate = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "G62", "69"), "G6202")["value"];

		$delivery->Sender = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "DE"), "N102")["value"];
		$delivery->ShipFromID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "SF"), "N104")["value"]; // may be blank

		$delivery->Warehouse = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N1", "CN"), "N104")["value"];
		$delivery->Reference = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N9", "LO"), "N902")["value"];
		$delivery->ShipmentID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonEDIArray, "N9", "LO"), "N902")["value"];
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
			$this->DC->setEDIError("Error: No mandatory carrier information in 943/W2702.", $delivery->EDIDocID);
			return false;
		}

		// If the carrier is false, give it a NULL value, else set it to the real value.
		$delivery->Carrier = $carrier == false ? NULL : $carrier["value"];
	}

	private function getDeliveryDetailsArray($delivery, $jsonEDIArray) {
		$deliveryDetailsArray = array();
		$inDeliveryDetail = false;

		// $n9HelperArray = array(
		// 	"LI" => "LineNum",
		// 	"LT" => "CustomerLot",
		// 	"LV" => "LicensePlate",
		// 	"ZZ" => "ExpDate"
		// );


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
				// $deliveryDetail->LicensePlate = ??
				// $deliveryDetail->ExpDate = ??
			}

			if ($inDeliveryDetail) {
				// if ($segment["segment"] == "N9") {
				// 	foreach ($n9HelperArray as $key => $value) {
				// 		$labelReference = $key == "ZZ" ? "N904" : "N902";

				// 		if ($this->DC->elementsContainValue($segment["elements"], $key)) {
				// 			$deliveryDetail->$value = $this->DC->getElementFromSegmentByLabel($segment, $labelReference)["value"];
				// 		}
				// 	}
				// }

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
		$edikey = 'LOL';
		$this->DC = $documentController;

		$dbTM = new TransactionManager();
		$DBTransaction = $dbTM->get();

		$this->DC->logger->log("In $edikey transaction: $transaction");

		$customerOrder = new CustomerOrder();

		$customerOrder->EDIDocID = $docid;
		$this->populateCustomerOrder($customerOrder, $jsonEDIArray);

		$customerOrder->setTransaction($DBTransaction);

		$this->DC->logger->log($customerOrder->toArray());

		// If the save failed
		if ($customerOrder->save() == false) {
			// If we failed to save, then rollback any changes made.
			$DBTransaction->rollback("Failed to save customer order: $docid");

			$message = "DB error saving customer order details:\n\n" . implode("\n", $customerOrder->getMessages());
			$this->DC->logger->log("$message\n");

			$this->DC->setEDIError("Error saving customer order -", $docid);

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
		$customerOrder->ShipToID = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N1", "CN"), "N104")["value"];
		$customerOrder->ShipToPONum = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N9", "CO"), "N902")["value"];

		$shipToMasterBOLCheck = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N9", "MB"), "N902");

		if ($shipToMasterBOLCheck != false) {
			$customerOrder->ShipToMasterBOL = $shipToMasterBOLCheck["value"];
		} else {
			$customerOrder->ShipToMasterBOL = NULL;
		}

		$customerOrder->ShipToName = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "N2", "N201")["value"];
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
		$customerOrder->ShipByDate = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "G62", "G6202")["value"];
		$customerOrder->CustomerOrderNotes = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "INT"), "NTE02")["value"];
		$customerOrder->DeliveryNotes = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "NTE", "DEL"), "NTE02")["value"];
		$customerOrder->Carrier = $this->getCarrierForCustomerOrder($jsonDataArray);
		$customerOrder->TotalCustomerOrderQty = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W76", "W7601")["value"];
		$customerOrder->LoadSequenceStopNumber = $this->DC->getElementFromSegmentByLabel($this->DC->getSegmentWithElementValue($jsonDataArray, "N9", "QN"), "N902")["value"];
	}

	private function getCarrierForCustomerOrder($jsonDataArray) {
		$carrier = $this->DC->getElementBySegmentNameAndLabel($jsonDataArray, "W66", "W6605");

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
