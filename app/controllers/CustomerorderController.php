<?php

use Phalcon\Mvc\Controller;

class CustomerOrderController extends Controller {

	// $$\ $$\             $$\
	// $$ |\__|            $$ |
	// $$ |$$\  $$$$$$$\ $$$$$$\
	// $$ |$$ |$$  _____|\_$$  _|
	// $$ |$$ |\$$$$$$\    $$ |
	// $$ |$$ | \____$$\   $$ |$$\
	// $$ |$$ |$$$$$$$  |  \$$$$  |
	// \__|\__|\_______/    \____/

	public function listAction() {

		$colorsArray = [
			'#0090e0',
			'#34c240',
			'#fa9f47',
			'#d64242',
		];

		$statusPID = $this->dispatcher->getParam("id") ?? CustomerOrder::STATUS_PENDING;

		$pageTitleArray = array(
			CustomerOrder::STATUS_PENDING => 'Incoming Orders',
			CustomerOrder::STATUS_OFFERPENDING => 'Converted Orders',
		);

		$this->view->headers = ['EDI#', 'Customer', 'Order Date', 'Cust Order', 'Pieces', 'Carrier', 'State', 'Zip'];

		$fields = [
			'CustomerOrderID',
			'EDIDocID',
			'ShipToName',
			'CustomerOrderDate',
			'CustomerOrderNum',
			'TotalCustomerOrderQty',
			'Carrier',
			'ShipToState',
			'ShipToZIP'
		];

		$linedata = CustomerOrder::find(array("Status = '" . $statusPID . "'", "order" => "EDIDocID DESC"));

		$this->view->data = array();

		$rows = array();
		$ids = array();

		$duplicateCustOrderNumbers = [];

		foreach ($linedata as $line) {
			$row = array();
			foreach ($fields as $f) {
				if (false !== strpos($f, 'Date')) { // any date
					$line->{$f} = substr($line->{$f}, 0, 10);
				}

				if ($f == 'CustomerOrderNum') {
					// Make an array of all the order numbers
					array_push($duplicateCustOrderNumbers, $line->{$f});
				}

				if ($f == 'CustomerOrderID') {
					array_push($ids, $line->{$f});
				} else {
					array_push($row, $line->{$f});
				}
			}

			array_push($rows, $row);
		}

		// Get an array of all values that are going to have duplicates
		$duplicateCustOrderNumbers = array_diff_assoc($duplicateCustOrderNumbers, array_unique($duplicateCustOrderNumbers));
		// Trim the array down to be unique
		$duplicateCustOrderNumbers = array_unique($duplicateCustOrderNumbers);
		// Re-index the array so the keys are sequential and we won't go out of bounds
		$duplicateCustOrderNumbers = array_values($duplicateCustOrderNumbers);

		$highlightingArray = [];

		foreach ($duplicateCustOrderNumbers as $index => $duplicateCustOrderNumber) {
			$highlightingArray[$duplicateCustOrderNumber] = $colorsArray[($index % count($colorsArray))];
		}

		$this->view->data = $rows;
		$this->view->highlightingArray = $highlightingArray;
		$this->view->highlightIndex = 3;
		$this->view->ids = $ids;

		$this->view->title = $pageTitleArray[$statusPID];
	}



	//             8      8 o      o
	//             8      8 8
	// .oPYo. .oPYo8 .oPYo8 8     o8 odYo. .oPYo.
	// .oooo8 8    8 8    8 8      8 8' `8 8oooo8
	// 8    8 8    8 8    8 8      8 8   8 8.
	// `YooP8 `YooP' `YooP' 8oooo  8 8   8 `Yooo'
	// :.....::.....::.....:......:....::..:.....:
	// :::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::

	/**********************************************************
	* Add a line to the customer order because it was added to
	* offer and EDI will fail otherwise.
	**********************************************************/
	public function addlineAction()
	{
		$this->logger->log('addlineAction');
		if ($this->request->isPost() == false) {
			$this->logger->log('Not a POST');
			return;
		}
		$result = array('success' => 0, 'msg' => 'Nothing to do.');


		$customerOrderDetail = new CustomerOrderDetail();
		$customerOrderDetail->CustomerOrderID = $_POST['CustomerOrderID'];
		$customerOrderDetail->Qty = $_POST['Qty'];
		$customerOrderDetail->PartNum = $_POST['PartNum'];
		$customerOrderDetail->POLine = $_POST['POLine'];
		$customerOrderDetail->LineNum = $_POST['LineNum'];
		$customerOrderDetail->ShipToPartNum = $_POST['ShipToPartNum'];
		$customerOrderDetail->EDIDocID = $_POST['EDIDocID'];
		$this->logger->log('addlineAction Adding: ');
		$this->logger->log($customerOrderDetail->toArray());

		$result['success'] = 1;
		$result['msg'] = 'Success';

		try {
			if ($customerOrderDetail->save() == false) {
				$result['success'] = 0;
				$result['msg'] = "Error saving customer order detail:\n\n" . implode("\n", $customerOrderDetail->getMessages());
				$this->logger->log("$msg\n");
			}
		}
		catch (\Phalcon\Exception $e) {
			$result['success'] = 0;
			$result['msg'] = "Catch: Error saving customer order detail: " . $e->getMessage();
			$this->logger->log("$msg\n");
		}

		return json_encode($result);
	}


	//      8          o          o 8
	//      8          8            8
	// .oPYo8 .oPYo.  o8P .oPYo. o8 8
	// 8    8 8oooo8   8  .oooo8  8 8
	// 8    8 8.       8  8    8  8 8
	// `YooP' `Yooo'   8  `YooP8  8 8
	// :.....::.....:::..::.....::....
	// :::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::

	public function incomingdetailAction() {

		$orderID = $this->dispatcher->getParam("id");

		$this->view->headers = ['Line', 'CO Line', 'Qty', 'Prod No', 'Desc', 'Cust Prod#/UPC'];
		$fields = [
			'LineNum',
			'POLine',
			'Qty',
			'PartNum',
			'Description',
			'ShipToPartNum'
		];

		try {
			$coModel = new CustomerOrder();
			$headerData = $coModel->getHeader($orderID);
			$this->view->OrderID  = $orderID;
			$this->view->EDIDocID = $headerData->EDIDocID;
		} catch (\Phalcon\Exception $e) {
			$this->logger->log($e->getMessage());
		}

		if (isset($headerData->TotalCustomerOrderQty)) {

			$readonly = ($headerData->Status !== CustomerOrder::STATUS_PENDING);
			$this->view->ReadOnly = $readonly;

			$status = Parameter::findFirst("ParameterID = '" . $headerData->Status . "'");


			$this->view->notes = ''; // CustomerOrderNotes + ShipNotes
			$this->view->details = [
				'Status' => $status->Value1,
				'OCS Customer' => $headerData->Name,
				'End Customer' => $headerData->ShipToName,
				'Address' => $headerData->ShipToAddr1 . ' ' .
					$headerData->ShipToAddr2,
				'City' => $headerData->ShipToCity,
				'State' => $headerData->ShipToState,
				'ZIP' => $headerData->ShipToZIP,
				'Cust Order (CO)' => $headerData->CustomerOrderNum,
				'Order Date' => substr($headerData->CustomerOrderDate, 0, 10),
				'Ship By' => substr($headerData->ShipByDate, 0, 10),
				'Total Pieces' => $headerData->TotalCustomerOrderQty,
				'EDI Control#' => $headerData->ControlNumber,
				'EDI#' => $headerData->EDIDocID,
			];

			$this->view->data = array();
			$rows = array();
			$ids = array();

			// $ediKey = EDIDocument::getEDIKeyByEDIDocID($headerData->EDIDocID);

			$detailData = $coModel->getDetails($orderID);

			foreach ($detailData as $line) {
				$row = array();
				foreach ($fields as $f) {
					if (false !== strpos($f, 'Date')) { // any date
						$line->{$f} = substr($line->{$f}, 0, 10);
					}

					if ($f == 'CustomerOrderID') {
						array_push($ids, $line->{$f});
					} else {
						array_push($row, $line->{$f});
					}
					$this->logger->log( "$f: " . $line->{ $f } );
				}
				// $this->logger->log( implode( '|', $row ) );
				array_push($rows, $row);
			}
			$this->view->data = $rows;

			$this->view->title = 'EDI Order Detail';
		} else {
			$errorString = "Bad CustomerOrder - ID: '$orderID'";
			$this->logger->log($errorString);

			$this->view->data = array('success' => 0);
			$this->view->data = array('error' => $errorString);

			$this->view->pick("layouts/json");
		}
	}

	//                                          $$\               $$$$$$$\                            $$\ $$\
	//                                          $$ |              $$  __$$\                           $$ |\__|
	//  $$$$$$$\  $$$$$$\   $$$$$$\   $$$$$$\ $$$$$$\    $$$$$$\  $$ |  $$ | $$$$$$\  $$$$$$$\   $$$$$$$ |$$\ $$$$$$$\   $$$$$$\
	// $$  _____|$$  __$$\ $$  __$$\  \____$$\\_$$  _|  $$  __$$\ $$$$$$$  |$$  __$$\ $$  __$$\ $$  __$$ |$$ |$$  __$$\ $$  __$$\
	// $$ /      $$ |  \__|$$$$$$$$ | $$$$$$$ | $$ |    $$$$$$$$ |$$  ____/ $$$$$$$$ |$$ |  $$ |$$ /  $$ |$$ |$$ |  $$ |$$ /  $$ |
	// $$ |      $$ |      $$   ____|$$  __$$ | $$ |$$\ $$   ____|$$ |      $$   ____|$$ |  $$ |$$ |  $$ |$$ |$$ |  $$ |$$ |  $$ |
	// \$$$$$$$\ $$ |      \$$$$$$$\ \$$$$$$$ | \$$$$  |\$$$$$$$\ $$ |      \$$$$$$$\ $$ |  $$ |\$$$$$$$ |$$ |$$ |  $$ |\$$$$$$$ |
	//  \_______|\__|       \_______| \_______|  \____/  \_______|\__|       \_______|\__|  \__| \_______|\__|\__|  \__| \____$$ |
	//                                                                                                                  $$\   $$ |
	//                                                                                                                  \$$$$$$  |
	//                                                                                                                   \______/
	//  http://devtracker.oshkoshcheese.com/customerorder/creatependingoffer/11/159

	public function creatependingofferAction() {
		$success = 1;
		$msg = '';
		$CustomerOrderID = $this->dispatcher->getParam("id");
		// $this->logger->log( "CustomerOrderID: $ref" );
		$EDIDocID = $this->dispatcher->getParam("relid");
		// $this->logger->log( "EDIDocID: $EDIDocID" );

		$OfferID = 'OFFER-ID-NOT-SET';

		// get order header
		$order = CustomerOrder::findfirst("CustomerOrderID = $CustomerOrderID")->toArray();

		$edicust = Customer::getCustomerIDByEDIDocID($EDIDocID);

		$expdate = new DateTime();
		$expdate = date_add($expdate, new DateInterval('P10D'));
		$formattedDate = $expdate->format('Y-m-d');
		$userID = $this->session->userAuth['UserID'] ?? 'AA024F9F-C9E6-4A44-BCE3-990FAB308B71';
		$user = User::getUserDetail($userID);

		$offerHeaderPost = array(
			'OfferDate' => 	$this->mysqlDate,
			'FOB' => 'OCS',
			'OfferExpiration' => $formattedDate,
			'TermsPID' => '544B69FB-A444-4C0A-B562-F5CF6B7A7B84',
			'Note' => 'EDI CUSTOMER ORDER EDI# ' . $EDIDocID,
			'UserID' => $userID,
			'CustomerID' => $edicust,
			'ContactID' => 'AA024F9F-C9E6-4A44-BCE3-990FAB308B71',
			'CustomerPhoneNumber' => '',
			'CustomerFaxNumber' => '',
			'CustomerEmail' => '',
			'OCSContactPhoneNumber' => '',
			'OCSContactFaxNumber' => '',
			'OCSContactEmail' => $user['Email'],
			'OfferStatusPID' => Offer::STATUS_EDIPENDING
		);

		// POST to Create Offer




		try {
			$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
			$url = $protocol . $_SERVER['HTTP_HOST'] . "/offer/save";
			$this->logger->log("Creating empty Pending Offer by url: $url");
			$responseobj = $this->utils->POST($url, $offerHeaderPost);
			$status = $responseobj['success'];
			$this->logger->log("Add empty PendingOffer success: $status");
			if ($status != 1) {

				$msg .= $responseobj['msg'];

				$this->logger->log("Error creating offer: " . $msg);
				return;
			} else {
				// Get OfferID back
				$OfferID = $responseobj['OfferID'];
				$this->logger->log("Created offer ID: $OfferID");
			}
		} catch (\Phalcon\Exception $e) {
			$this->logger->log("Exception creating offer: " . $e->getMessage());
			return;
		}

		// get order details
		$details =  CustomerOrderDetail::find("CustomerOrderID = $CustomerOrderID")->toArray();
		// $this->logger->log( 'OrderDetails:' );
		// $this->logger->log( $details );

		$offerItemVats = array();
		$offerLots = array();

		foreach ($details as $detail) {
			$validlotvats = Lot::getLotsbyCustProdNum($edicust, $detail['PartNum']);
			$this->logger->log("Lots for: " . $detail['PartNum'] . "  Need: " . $detail['Qty']);
			$this->logger->log($validlotvats);

			$qtyNeeded = $detail['Qty'];

			foreach ($validlotvats as $vlotv) {
				$this->logger->log('Lot candidate:');
				$this->logger->log($vlotv);

				$this->logger->log("Qty Needed: {$qtyNeeded} PAvail: {$vlotv['PAvail']}  Pieces: {$vlotv['Pieces']}");

				if ($qtyNeeded <= 0) break; // if we are done filling this orderline

				// For each vat steal as many pieces as possible and build item linez
				if (($vpieces = $vlotv['Pieces']) > 0) { // NOTE: Pieces = Net Avail Pieces after math
					$LotID = $vlotv['LotID'];
					$LotNumber = $vlotv['LotNumber'];
					$offerLots[$LotID] = 1; // build unique list of used lots

					$weightEach = $vlotv['Weight'] / $vpieces; // Ric: This should be doing variable weight.

					// Take everything  we need if there's enough, else take whats available
					// TODO: DON'T BREAK VATS!  But Break Vats if SAP? Or Walmart?
					// $qtyTaking = ( $vpieces >= $qtyNeeded ) ? $qtyNeeded : $vpieces;
					$qtyTaking = $vpieces;

					$qtyNeeded -= $qtyTaking; // reduce number still needed

					$offerLine = array(
						'LotID' => $LotID,
						'OfferID' => $OfferID,
						'VatID' => $vlotv['VatID'],
						'pieces' => $qtyTaking,
						'weight' => $qtyTaking * $weightEach,
						'price' => 0,
						'CustomerOrderDetailID' => $detail['CustomerOrderDetailID']
					);

					$this->logger->log('offerLine Array');
					$this->logger->log($offerLine);

					if (!isset($offerItemVats[$LotID])) $offerItemVats[$LotID] = array();
					array_push($offerItemVats[$LotID], $offerLine);
				}
				// Loop until item is fulfilled or until out of vats
			} // Next vat

			if (!empty($LotID)) {
				Lot::setLastPicked($LotID, $detail['PartNum'], $edicust, $LotNumber);
				$LotID = '';
			}


			if ($qtyNeeded > 0) { // && no more vats for this Prod Number
				// If you can't fill the order, produce error
				// NOTE: $msg && $success == 1 is success with a note for the end user in $msg.
				$msg .= 'Unable to find enough inventory for ' .  $detail['PartNum'] . ' to create full offer.<br>';
			}

			if ($qtyNeeded < 0) { // && no more vats for this Prod Number
				// If you can't fill the order, produce error
				// NOTE: $msg && $success == 1 is success with a note for the end user in $msg.
				$msg .= 'Offer exceeds order qty for ' .  $detail['PartNum'] . " so we don't break pallets.<br>";
			}
		} // Next detail

		// on done, POST each Lot that we are using to "/offer/newitem/$offerID/$lotID" to create blank offer items / offer item vats.
		// Finally, loop through offer data and update offer item vats with Qtys. (What about inventory)?

		$mastervatmap = array();

		foreach (array_keys($offerLots) as $lot) {
			$this->logger->log("Creating OfferItem by url: $url");
			try {
				$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
				$url = $protocol . $_SERVER['HTTP_HOST'] . "/offer/newitem/$OfferID/" . $lot;
				$responseobj = $this->utils->GET($url);
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log($e->getModel()->getMessages());
			}

			$status = $responseobj['success'];
			$this->logger->log("Response:" );
			$this->logger->log( $responseobj );

			if (!$status) {
				$this->logger->log($responseobj['msg']);
				$success = 0;
				$msg .= $responseobj['msg'];
			} else {
				$offerItemID = $responseobj['offeritemid'];
				$vatmap = $responseobj['vatmap'];
				$mastervatmap = array_merge($mastervatmap, $vatmap);
				$offerLots[$lot] = $offerItemID;
				$this->logger->log("Add OfferItem success, OIID: $offerItemID");
			}
		}

		$custOrderDetailUpdates = array();

		// need to send these by lot.
		foreach (array_keys($offerLots) as $lot) {
			$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
			$url = $protocol . $_SERVER['HTTP_HOST'] . '/offer/saveitemvats/';
			$this->logger->log("Saving OfferItemVat by url: $url postdata:");

			foreach (array_keys($offerItemVats[$lot]) as $key) {
				// TODO: VatID != OfferItemVatID
				$offerItemVats[$lot][$key]['OfferItemVatID'] = $mastervatmap[$offerItemVats[$lot][$key]['VatID']];
				$offerItemVats[$lot][$key]['OfferItemID'] = $offerLots[$lot];
				// make array of all offerItemIDs that play into this offer.
				if (isset($custOrderDetailUpdates[$offerItemVats[$lot][$key]['CustomerOrderDetailID']])) {
					$custOrderDetailUpdates[$offerItemVats[$lot][$key]['CustomerOrderDetailID']][] = $offerLots[$lot];
				} else {
					$custOrderDetailUpdates[$offerItemVats[$lot][$key]['CustomerOrderDetailID']] = [$offerLots[$lot]];
				}
			}

			$postdata = array('jsondata' => array(
				'lines' => $offerItemVats[$lot],
				'CostOut' => 0,
				'OfferItemID' => $offerLots[$lot],
				'NoteText' => ''
			));

			try {
				$responseobj = $this->utils->POST($url, $postdata);
				$status = $responseobj['success'];
				if (!$status) {
					$this->logger->log($responseobj['msg']);
					$success = 0;
					$msg .= $responseobj['msg'];
				} else {
					$this->logger->log("Update OfferItemVats success");
				}
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log($e->getModel()->getMessages());
			}
		}

		$this->logger->log("About to update OfferID ({$OfferID}) in Customer order({$CustomerOrderID}). Status: $status\n");

		// Write back to CustomerOrderDetail with OfferItemID, CustomerOrder with OfferID
		if ($status) {
			try {
				$order = CustomerOrder::findfirst("CustomerOrderID = $CustomerOrderID");
				$order->OfferID = $OfferID;
				$order->Status = CustomerOrder::STATUS_OFFERPENDING;
				$order->save();
			} catch (\Phalcon\Mvc\Model\Exception $e) {
				$this->logger->log("Failed: ");
				$this->logger->log($e->getModel()->getMessages());
			}

			$this->logger->log("About to update OrderOfferDetailMap");
			foreach ($custOrderDetailUpdates as $customerOrderDetailID => $offerItemIDs) {
				foreach (array_unique($offerItemIDs) as $offerItemID) {
					$this->logger->log("OrderOfferDetailMap OID: {$offerItemID} CODI: {$customerOrderDetailID} EDI: {$EDIDocID}");
					// $order = CustomerOrderDetail::findfirst( "CustomerOrderDetailID = $customerOrderDetailID" );
					try {
						$map = new OrderOfferDetailMap();
						$map->CustomerOrderDetailID = $customerOrderDetailID;
						$map->OfferItemID = $offerItemID;
						$map->EDIDocID = $EDIDocID;
						$map->save();
					} catch (\Phalcon\Mvc\Model\Exception $e) {
						$this->logger->log("Failed to update OrderOfferDetailMap: ");
						$this->logger->log($e->getModel()->getMessages());
					}
				}
			}
		}


		$this->logger->log("Pending Offer saved? Success = $status");

		$this->view->data = array(
			'success' => $success,
			'status' => ($success ? 'success' : 'error'),
			'msg' => $msg,
			'OfferID' => $OfferID
		);
		$this->view->pick("layouts/json");
	}
}
