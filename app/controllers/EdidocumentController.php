<?

use Phalcon\Mvc\Controller,
	Phalcon\Db\RawValue,
	Phalcon\Http\Response,
	Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;


class EDIDocumentController extends Controller {

	public static function getProcessor($edikey) {

		require_once(dirname(__FILE__) . "/{$edikey}/EDIFunctions.php");

		$reflection = new ReflectionClass($edikey . '_EDIFunctions');

		return $reflection->newInstanceWithoutConstructor();
	}

	//                 o   o    o  .o .oPYo. ooo.            o
	//                 8   `b  d'   8     `8 8  `8.          8
	// .oPYo. .oPYo.  o8P   `bd'    8    oP' 8   `8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8   8    .PY.    8 .oP'   8    8 .oooo8   8  .oooo8
	// 8    8 8.       8   .P  Y.   8 8'     8   .P 8    8   8  8    8
	// `YooP8 `Yooo'   8  .P    Y.  8 8ooooo 8ooo'  `YooP8   8  `YooP8
	// :....8 :.....:::..:..::::..::..............:::.....:::..::.....:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	/**
	 * Determines which 'getX12Data' method to call based on the transaction
	 *
	 * @param EDIDocument $ediDocument EDIDocument object
	 *
	 * @return mixed Returns data for x12 doc on success or false on failure
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 */
	private function getX12Data($ediDocument) {

		if (empty($ediDocument->EDIKey)) {
			return false;
		}

		$processor = self::getProcessor($ediDocument->EDIKey);

		$customer = Customer::getEDICustomer($ediDocument->EDIKey);

		if ($customer === false) {
			return false;
		}

		// NOTE: This is just for 947 only

		switch ($ediDocument->Transaction) {
			case EDIDocument::WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE:

				$warehouseAdjustmentList = WarehouseAdjustment::getWarehouseAdjustmentsByGroup($ediDocument->ControlNumber);
				$customer = Customer::getEDICustomer($ediDocument->EDIKey);

				$lot = Lot::findFirst("LotID = '" . $warehouseAdjustmentList[0]->LotID . "'");
				$vat = Vat::findFirst("VatID = '" . $warehouseAdjustmentList[0]->VatID . "'");

				return $processor->get947X12Data($this, $customer, $warehouseAdjustmentList, $lot, $vat);
			default:
				break;
		}

		return false;
	}

	//                 o   .oPYo.             8 ooooo                                           o   o              .oPYo.
	//                 8   8   `8             8   8                                             8                  8.
	// .oPYo. .oPYo.  o8P o8YooP' .oPYo. .oPYo8   8   oPYo. .oPYo. odYo. .oPYo. .oPYo. .oPYo.  o8P o8 .oPYo. odYo. `boo   oPYo. oPYo. .oPYo. oPYo.
	// Yb..   8oooo8   8   8   `b .oooo8 8    8   8   8  `' .oooo8 8' `8 Yb..   .oooo8 8    '   8   8 8    8 8' `8 .P     8  `' 8  `' 8    8 8  `'
	//   'Yb. 8.       8   8    8 8    8 8    8   8   8     8    8 8   8   'Yb. 8    8 8    .   8   8 8    8 8   8 8      8     8     8    8 8
	// `YooP' `Yooo'   8   8oooP' `YooP8 `YooP'   8   8     `YooP8 8   8 `YooP' `YooP8 `YooP'   8   8 `YooP' 8   8 `YooP' 8     8     `YooP' 8
	// :.....::.....:::..::......::.....::.....:::..::..:::::.....:..::..:.....::.....::.....:::..::..:.....:..::..:.....:..::::..:::::.....:..::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	private function setBadTransactionError($transaction) {
		$this->logger->log("Bad transaction '$transaction'");
		$this->view->data = array('success' => 0, 'error' => "Bad transaction '$transaction'");
		$this->view->pick("layouts/json");
	}

	//                                           o         .oPYo. ooo.   o ooo.                                               o
	//                                           8         8.     8  `8. 8 8  `8.                                             8
	// .oPYo. .oPYo. odYo. .oPYo. oPYo. .oPYo.  o8P .oPYo. `boo   8   `8 8 8   `8 .oPYo. .oPYo. o    o ooYoYo. .oPYo. odYo.  o8P
	// 8    8 8oooo8 8' `8 8oooo8 8  `' .oooo8   8  8oooo8 .P     8    8 8 8    8 8    8 8    ' 8    8 8' 8  8 8oooo8 8' `8   8
	// 8    8 8.     8   8 8.     8     8    8   8  8.     8      8   .P 8 8   .P 8    8 8    . 8    8 8  8  8 8.     8   8   8
	// `YooP8 `Yooo' 8   8 `Yooo' 8     `YooP8   8  `Yooo' `YooP' 8ooo'  8 8ooo'  `YooP' `YooP' `YooP' 8  8  8 `Yooo' 8   8   8
	// :....8 :.....:..::..:.....:..:::::.....:::..::.....::.....:.....::.......:::.....::.....::.....:..:..:..:.....:..::..::..:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	private function generateEDIAndX12Documents($control_number, $edikey, $transaction, $customer, $data, $lines = []) {
		$x12path = "/web/data/tracker.oshkoshcheese.com/x12-data/" . $customer->EDIKey;
		$control_number = str_pad($control_number, 9, '0', STR_PAD_LEFT);
		$filename = $this->utils->makeUniqueFileName("$edikey-$transaction-$control_number.x12", $x12path, true);

		$edidoc = new EDIDocument();
		$edidoc->EDIKey = $customer->EDIKey;
		$edidoc->Transaction = $transaction;
		$edidoc->DocISAID = $customer->EDIISAID;
		$edidoc->DocGSID = $customer->EDIGSID;
		$edidoc->ControlNumber = $control_number;
		$edidoc->Incoming = 0;
		$edidoc->Status = EDIDocument::STATUS_OUTBOX;
		$edidoc->X12FilePath = "$filename"; // Quote make it pass a copy, not a reference. On purpose.

		// $this->logger->log($edidoc->Transaction);

		// $this->logger->log($edidoc->toArray());
		try {
			if ($edidoc->save() == false) {
				$msg = 'Error in generateEDIAndX12Documents Saving EDIDoc: ' . implode("\n", $edidoc->getMessages());
				$this->logger->log($msg);
				$this->setEDIError($msg);
				return;
			}
		} catch (\Phalcon\Exception $exception) {
			$msg = 'Exception in generateEDIAndX12Documents Saving EDIDoc: ' . $exception->getMessage();
			$this->logger->log($msg);
			$this->setEDIError($msg);
			return;
		}

		$this->logger->log("EDIDoc ID: $edidoc->DocID");
		$this->logger->log("EDIDoc Filename: $filename");
		$this->view->logger = $this->logger;
		$this->view->filename = $filename;
		$this->view->data = $data;
		$this->view->lines = $lines;
		$this->view->pick("edidocument/x12/$edikey-$transaction-template");
	}


	//                                           o         .oPYo. ooo.   o ooo.                                               o
	//                                           8         8.     8  `8. 8 8  `8.                                             8
	// .oPYo. .oPYo. odYo. .oPYo. oPYo. .oPYo.  o8P .oPYo. `boo   8   `8 8 8   `8 .oPYo. .oPYo. o    o ooYoYo. .oPYo. odYo.  o8P
	// 8    8 8oooo8 8' `8 8oooo8 8  `' .oooo8   8  8oooo8 .P     8    8 8 8    8 8    8 8    ' 8    8 8' 8  8 8oooo8 8' `8   8
	// 8    8 8.     8   8 8.     8     8    8   8  8.     8      8   .P 8 8   .P 8    8 8    . 8    8 8  8  8 8.     8   8   8
	// `YooP8 `Yooo' 8   8 `Yooo' 8     `YooP8   8  `Yooo' `YooP' 8ooo'  8 8ooo'  `YooP' `YooP' `YooP' 8  8  8 `Yooo' 8   8   8
	// :....8 :.....:..::..:.....:..:::::.....:::..::.....::.....:.....::.......:::.....::.....::.....:..:..:..:.....:..::..::..:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	private function generateEDIDocument($customer, $transaction, $controlNumber, $filePath = '') {
		$ediDocument = new EDIDocument();

		$ediDocument->EDIKey = $customer->EDIKey;
		$ediDocument->Transaction = $transaction;
		$ediDocument->DocISAID = $customer->EDIISAID;
		$ediDocument->DocGSID = $customer->EDIGSID;
		$ediDocument->ControlNumber = $controlNumber;
		$ediDocument->Incoming = 0;
		$ediDocument->Status = EDIDocument::STATUS_OUTBOX;

		if ($filePath !== '') {
			$ediDocument->X12FilePath = $filePath;
		}

		return $ediDocument;
	}


	//                                           o          o    o  .o .oPYo.  ooooo  o 8
	//                                           8          `b  d'   8     `8  8        8
	// .oPYo. .oPYo. odYo. .oPYo. oPYo. .oPYo.  o8P .oPYo.   `bd'    8    oP' o8oo   o8 8 .oPYo.
	// 8    8 8oooo8 8' `8 8oooo8 8  `' .oooo8   8  8oooo8   .PY.    8 .oP'    8      8 8 8oooo8
	// 8    8 8.     8   8 8.     8     8    8   8  8.      .P  Y.   8 8'      8      8 8 8.
	// `YooP8 `Yooo' 8   8 `Yooo' 8     `YooP8   8  `Yooo' .P    Y.  8 8ooooo  8      8 8 `Yooo'
	// :....8 :.....:..::..:.....:..:::::.....:::..::.....:..::::..::.........:..:::::....:.....:
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	private function generateX12File($ediDocument, $x12Data, $fileName = '') {
		$this->logger->log("generateX12File()");

		if ($fileName === '') {
			$fileName = $this->generateX12FileNameFromEDIDocument($ediDocument);
		}

		$template = 'edidocument/x12/' . $ediDocument->EDIKey . '-' . $ediDocument->Transaction . '-template';

		$this->logger->log("Picking view: $template");
		$this->logger->log("X12 Data");
		$this->logger->log($x12Data);

		$this->view->filename = $fileName;
		$this->view->data = $x12Data;
		$this->view->lines = $x12Data['lines'];

		$this->view->pick($template);
	}


	//                                           o          o    o  .o .oPYo.  ooooo  o 8        o    o
	//                                           8          `b  d'   8     `8  8        8        8b   8
	// .oPYo. .oPYo. odYo. .oPYo. oPYo. .oPYo.  o8P .oPYo.   `bd'    8    oP' o8oo   o8 8 .oPYo. 8`b  8 .oPYo. ooYoYo. .oPYo.
	// 8    8 8oooo8 8' `8 8oooo8 8  `' .oooo8   8  8oooo8   .PY.    8 .oP'    8      8 8 8oooo8 8 `b 8 .oooo8 8' 8  8 8oooo8
	// 8    8 8.     8   8 8.     8     8    8   8  8.      .P  Y.   8 8'      8      8 8 8.     8  `b8 8    8 8  8  8 8.
	// `YooP8 `Yooo' 8   8 `Yooo' 8     `YooP8   8  `Yooo' .P    Y.  8 8ooooo  8      8 8 `Yooo' 8   `8 `YooP8 8  8  8 `Yooo'
	// :....8 :.....:..::..:.....:..:::::.....:::..::.....:..::::..::.........:..:::::....:.....:..:::..:.....:..:..:..:.....:
	// ::ooP'.::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	/**
	 * Generates a name for the X12 file
	 *
	 * @param EDIDocument $ediDocument EDIDocument object
	 *
	 * @return string
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 */
	private function generateX12FileNameFromEDIDocument($ediDocument): string {
		return $this->generateX12FileName($ediDocument->ControlNumber, $ediDocument->EDIKey, $ediDocument->Transaction);
	}

	/**
	 * Generates a name for the X12 file. This is mainly used if you need a file name before making the EDIDocument
	 *
	 * @param Int $controlNumber The control number for the edi transaction
	 * @param String $ediKey Key for the customer
	 * @param String $transaction Type of EDI transaction being performed
	 *
	 * @return string
	 *
	 * @author Robert Kinney
	 *
	 * @access private
	 */
	private function generateX12FileName($controlNumber, $ediKey, $transaction) {
		$paddedCtrlNum = str_pad($controlNumber, 9, '0', STR_PAD_LEFT);
		$fileName = $ediKey . '-' . $transaction . '-' . $paddedCtrlNum . '.x12';
		$filePath = EDIDocument::X12_FILEPATH . '/' . $ediKey;

		return $this->utils->makeUniqueFileName($fileName, $filePath, true);
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

	private function processWarehouseShippingOrder($transaction, $jsonEDIArray, $docid, $edikey) {

		$this->logger->log("testing EDIDocumentController.php processWarehouseShippingOrder");

		$this->logger->log("testing EDIDocumentController.php edikey");
		$this->logger->log($edikey);
		$processor = self::getProcessor($edikey);
		$processor->processWarehouseShippingOrder($this, $transaction, $jsonEDIArray, $docid);
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

	private function processStockTransferShippingAdvice($transaction, $jsonEDIArray, $docid, $edikey) {

		$processor = self::getProcessor($edikey);
		$processor->processStockTransferShippingAdvice($this, $transaction, $jsonEDIArray, $docid);
	}


	//    o    o  .o .oPYo. .oPYo.                                     o
	//    `b  d'   8     `8 8                                          8
	//     `bd'    8    oP' `Yooo. o    o .oPYo. .oPYo. .oPYo. oPYo.  o8P
	//     .PY.    8 .oP'       `8 8    8 8    8 8    8 8    8 8  `'   8
	//    .P  Y.   8 8'          8 8    8 8    8 8    8 8    8 8       8
	//   .P    Y.  8 8ooooo `YooP' `YooP' 8YooP' 8YooP' `YooP' 8       8
	// ::..::::..::.........:.....::.....:8 ....:8 ....::.....:..::::::..:
	// :::::::::::::::::::::::::::::::::::8 :::::8 :::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::..:::::..:::::::::::::::::::::::


	// Returns the form type. ie - 940, 943, etc...
	private function getFormType($segmentArray) {
		return $this->getElementBySegmentNameAndLabel($segmentArray, "ST", "ST01")["value"];
	}



	private function sendEmail($EDIDocument) {
		if ($this->config->settings['environment'] === "DEVELOPMENT") {
			$to = 'EDI Notifications <edi-notifications@fai2.com>';
		} else if ($this->config->settings['environment'] == 'PRODUCTION') {
			$to = 'Ric <ric@fai2.com>, Bobbie <bobbier@oshkoshcheese.com>'; // Ric Removed Jordan 6/16/2025
		}

		$headers = 'From: ric@fai2.com';

		$transactionText = '';
		$specificUrl = '';
		$listUrl = '';
		$type = '';

		if ($EDIDocument->Transaction == "940") {
			$transactionText = 'Warehouse Shipping Order';
			$type = 'customerorder';

			$customerOrder = CustomerOrder::findFirst([
				"EDIDocID = $EDIDocument->DocID",
				'order' => 'CustomerOrderID DESC'
			]);

			$id = $customerOrder->CustomerOrderID;
		}

		if ($EDIDocument->Transaction == "943") {
			$transactionText = 'Incoming Delivery';
			$type = 'delivery';

			$delivery = Delivery::findFirst([
				"EDIDocID = $EDIDocument->DocID",
				'order' => 'DeliveryID DESC'
			]);

			$id = $delivery->DeliveryID;
		}

		$base_url = $this->config->settings['base_url'];

		$listUrl = "$base_url/$type/incoming";
		$specificUrl = "$base_url/$type/incomingdetail/$id";
		$subject = "$transactionText has been Processed";

		$EDIKeys = Customer::getEDIKeys();

		$placeName = $EDIKeys[$EDIDocument->EDIKey] ?? '[UNKNOWN]';

		$message = <<<EOT
You have received a new $transactionText from $placeName.
Click the following link to see this $transactionText: $specificUrl
See all pending $transactionText(s) here: $listUrl
EOT;

		mail($to, $subject, $message, $headers);
	}

	//   ooYoYo. .oPYo. odYo. o    o
	//   8' 8  8 8oooo8 8' `8 8    8
	//   8  8  8 8.     8   8 8    8
	//   8  8  8 `Yooo' 8   8 `YooP'
	// ::..:..:..:.....:..::..:.....:
	// ::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::

	public function indexAction() {
		$edi = new EDIDocument;
		$pending = $edi->pendingCount();

		$this->view->title = 'EDI Activity';
		$this->view->menu = array(
			'Deliveries (' . ($pending->deliveryCount + $pending->lotCount) . ')' => 'tab',
			'Incoming Deliveries (943 -> 944) (' . $pending->deliveryCount . ')' => '/delivery/incoming', // Screen #1
			'Draft Lots (' . $pending->lotCount . ')' => '/lot/list/' . Lot::STATUS_DRAFT, // Screen #2

			'Orders (' . ($pending->offerCount + $pending->customerOrderCount) . ')' => 'tab',
			'Incoming Orders (940) (' . $pending->customerOrderCount . ')' => '/customerorder/list', // Screen #3
			'Pending Offers (' . $pending->offerCount . ')' => '/offer/list/' . Offer::STATUS_EDIPENDING, // Screen #4
			'Expired Offers' => '/offer/list/' . Offer::STATUS_EXPIRED // Screen #4
		);
	}


	//   .oPYo. ooo.   o ooo.                        o      o          o
	//   8.     8  `8. 8 8  `8.                      8                 8
	//   `boo   8   `8 8 8   `8 .oPYo. .oPYo. .oPYo. 8     o8 .oPYo.  o8P
	//   .P     8    8 8 8    8 8    8 8    ' Yb..   8      8 Yb..     8
	//   8      8   .P 8 8   .P 8    8 8    .   'Yb. 8      8   'Yb.   8
	//   `YooP' 8ooo'  8 8ooo'  `YooP' `YooP' `YooP' 8oooo  8 `YooP'   8
	// :::.....:.....::.......:::.....::.....::.....:......:..:.....:::..:
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	public function listAction() {
		$ediModel = new EDIDocument();
		// $this->logger->log('EDI Doc List');
		// there's no getEDIDocuments() call here because docs
		// are queried via ajax call when the page loads

		$pageNumber = $_POST['pageNumber'] ?? 1;

		if ($this->request->getPost('applyEDIFilters') == 'Apply Filters') {
			$pageNumber = 1;
			$_POST['pageNumber'] = 1;
		} else {
			if (isset($_POST['changePage'])) {
				if ($_POST['changePage'] === 'next') {
					$pageNumber++;
				}

				if ($_POST['changePage'] == 'previous') {
					$pageNumber--;
				}
			}
		}


		// $this->logger->log('EDI Doc List after filter thing');

		$pageNumber = max($pageNumber, 1);

		$_POST['changePage'] = "noChange";
		$_POST['pageNumber'] = max($pageNumber, 1);

		$docId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		// $this->logger->log('EDI Doc List paramid');

		try{
			$statuses = iterator_to_array($ediModel->getStatusList());
			$secondaryStatuses = iterator_to_array($ediModel->getSecondaryStatusList());
        } catch (\Phalcon\Exception $exception) {
            $this->logger->log("EXCEPTION");
            $this->logger->log($exception->getMessage());

		}
		// $this->logger->log('EDI Doc List about to view');

		$this->view->ediKeyArray = array_keys(Customer::getEDIKeys());
		$this->view->docId = $docId;
		$this->view->statii = array_merge($statuses, $secondaryStatuses);
		$this->view->transactions = $ediModel->getTransactionTypesList();
	}


	//  o                            8  o                 o
	//                               8                    8
	// o8 odYo. o    o   .oPYo. .oPYo8 o8 o    o .oPYo.  o8P
	//  8 8' `8 Y.  .P   .oooo8 8    8  8 8    8 Yb..     8
	//  8 8   8 `b..d'   8    8 8    8  8 8    8   'Yb.   8
	//  8 8   8  `YP'    `YooP8 `YooP'  8 `YooP' `YooP'   8
	// :....::..::...:::::.....::.....::8 :.....::.....:::..:
	// ::::::::::::::::::::::::::::::::oP :::::::::::::::::::
	// ::::::::::::::::::::::::::::::::..::::::::::::::::::::

	public function inventoryadjustAction() {
		// TODO ? EDI - Make this data oriented? - Not needed right now, but might be nice later on
		//  -- new setting in cutomer table?
		// EDI 947 stuff
		// Only include customers that are utilizing the inventory management section of EDI
		// TODO: Check if the 947 is in the Customer Edi selection

		$ediKeys = [];

		foreach (array_keys(Customer::getEDIKeys()) as $ediKey) {

			if (!Customer::getEDICustomer($ediKey, '947')) {
				continue;
			}

			$ediKeys[] = $ediKey;
		}

		$this->view->allEDIKeys = $ediKeys;
	}

	//                 o   o                             o
	//                 8                                 8
	// .oPYo. .oPYo.  o8P o8 odYo. o    o .oPYo. odYo.  o8P .oPYo. oPYo. o    o
	// 8    8 8oooo8   8   8 8' `8 Y.  .P 8oooo8 8' `8   8  8    8 8  `' 8    8
	// 8    8 8.       8   8 8   8 `b..d' 8.     8   8   8  8    8 8     8    8
	// `YooP8 `Yooo'   8   8 8   8  `YP'  `Yooo' 8   8   8  `YooP' 8     `YooP8
	// :....8 :.....:::..::....::..::...:::.....:..::..::..::.....:..:::::....8
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
	// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::...::


	public function getinventoryAction() {

		$ediKey = $this->dispatcher->getParam("id");

		if (empty($ediKey) && !empty($_POST['EDIKey'])) {
			$ediKey = $_POST['EDIKey'];
		}

		$customer = Customer::getEDICustomer($ediKey, '947');

		if (!$customer) {
			$this->logger->log("No EDI Customer");
			return;
		}

		$limited = true;

		foreach ($_POST as $postData) {
			if ($postData != null) {
				$limited = false;
				break;
			}
		}

		$this->view->limited = $limited;

		$this->view->inventory = $customer->getEDIInventory($_POST, $limited, NULL, NULL);

		$this->view->pick("edidocument/ajax/getinventory");
	}

	//                 o       8
	//                 8       8
	// .oPYo. .oPYo.  o8P .oPYo8 .oPYo. .oPYo. .oPYo.
	// 8    8 8oooo8   8  8    8 8    8 8    ' Yb..
	// 8    8 8.       8  8    8 8    8 8    .   'Yb.
	// `YooP8 `Yooo'   8  `YooP' `YooP' `YooP' `YooP'
	// :....8 :.....:::..::.....::.....::.....::.....:
	// ::ooP'.::::::::::::::::::::::::::::::::::::::::
	// ::...::::::::::::::::::::::::::::::::::::::::::

	public function getdocsAction() {
		$ediModel = new EDIDocument();
		$pageNumber = $_POST['pageNumber'] ?? 1;

		if (isset($_POST['changePage'])) {
			if ($_POST['changePage'] === 'next') {
				$pageNumber += 1;
			}

			if ($_POST['changePage'] == 'previous') {
				$pageNumber -= 1;
			}
		}

		$pageNumber = max($pageNumber, 1);

		if ($this->request->getPost('applyEDIFilters')) {
			// If they clicked the 'apply filters' button, then we need to set the page back to 1
			if ($this->request->getPost('applyEDIFilters') == 'Apply Filters') {
				$pageNumber = 1;
				$_POST['pageNumber'] = 1;
			}

			$docs = $ediModel->getEDIDocuments($_POST, $pageNumber);
		} else {
			$docs = $ediModel->getEDIDocuments();
		}

		unset($_POST['changePage']);

		$referenceArray = array();

		foreach ($docs as $key => $ediDocument) {
			$referenceArray[$ediDocument['DocID']] = '';

			if ($ediDocument['Transaction'] == '997') {
				continue;
			}

			if (empty($ediDocument['X12FilePath']) || !file_exists($ediDocument['X12FilePath'])) {
				// $this->logger->log("Trying to get reference number but X12FilePath is empty | Document ID: {$ediDocument['DocID']}");
				$referenceArray[$ediDocument['DocID']] = '<span style="font-size: 80%; font-style: italic;">file moved</span>';
				continue;
			}

			if (!array_key_exists($ediDocument['EDIKey'].$ediDocument['Transaction'], EDIDocument::REFERENCE_NUMBER_TRANSACTION_SEGMENT_MAP)) {
				// $this->logger->log("Trying to get reference number for unsupported transaction: {$ediDocument['Transaction']} | Document ID: {$ediDocument['DocID']}");
				$referenceArray[$ediDocument['DocID']] = '--';
				continue;
			}

			if (!empty($ediDocument['ReferenceNumber'])) {
				$referenceArray[$ediDocument['DocID']] = $ediDocument['ReferenceNumber'];
				continue;
			}

			[$segmentLabel, $dataIndex] = EDIDocument::REFERENCE_NUMBER_TRANSACTION_SEGMENT_MAP[$ediDocument['EDIKey'] . $ediDocument['Transaction']];
			$dataIndex--;
			$regex = "/~\s*{$segmentLabel}\\*(?:.*?\\*){{$dataIndex}}([^*~]*)/m";
			$x12 = file_get_contents($ediDocument['X12FilePath']);
			$matches = [];
			preg_match($regex, $x12, $matches);
			if (!empty($matches[1])) {
				// remove leading zeroes (DO NOT)
				// $referenceNumberMatches = [];
				// preg_match('/(\D*)(\d+)/', $matches[1], $referenceNumberMatches);
				// $referenceNumber = $referenceNumberMatches[1] . ltrim($referenceNumberMatches[2], '0');
				$referenceNumber = $matches[1];
				$referenceArray[$ediDocument['DocID']] = $referenceNumber;

				// Save back to DB
				try {
					// we have to set control number or Phalcon complains
					$controlNumber = $ediDocument['ControlNumber'] ?: new \Phalcon\Db\RawValue('""'); // because Phalcon thinks empty strings are null
					$this->logger->log("saving back ReferenceNumber: {$referenceNumber} for DocID: {$ediDocument['DocID']}");
					$sql = "UPDATE EDIDocument SET ReferenceNumber = '{$referenceNumber}', ControlNumber = '{$controlNumber}' WHERE DocID = '{$ediDocument['DocID']}'";
					$this->getDI()->getShared('modelsManager')->executeQuery($sql);
				} catch(\Phalcon\Exception $exception) {
					$msg = "Exception saving edidoc:\n" . $exception->getMessage();
					$this->logger->log($msg);
				}
			} else {
				$referenceArray[$ediDocument['DocID']] = 'Not Found';
			}
		}

		$this->view->documentCountArray = [
			'firstDocDate' => date('Y-m-d', strtotime($docs[0]['CreateDate'])),
			'lastDocDate' => date('Y-m-d', strtotime($docs[count($docs) - 1]['CreateDate'])),
		];
		$this->view->pageNumber = $pageNumber;
		$this->view->referenceArray = $referenceArray;
		$this->view->docs = $docs;
		$this->view->pick("edidocument/ajax/getdocs");
	}



	//                    8          o         .oPYo.                                 8                     .oPYo.   o           o
	//                    8          8         8                                      8                     8        8           8
	// o    o .oPYo. .oPYo8 .oPYo.  o8P .oPYo. `Yooo. .oPYo. .oPYo. .oPYo. odYo. .oPYo8 .oPYo. oPYo. o    o `Yooo.  o8P .oPYo.  o8P o    o .oPYo.
	// 8    8 8    8 8    8 .oooo8   8  8oooo8     `8 8oooo8 8    ' 8    8 8' `8 8    8 .oooo8 8  `' 8    8     `8   8  .oooo8   8  8    8 Yb..
	// 8    8 8    8 8    8 8    8   8  8.          8 8.     8    . 8    8 8   8 8    8 8    8 8     8    8      8   8  8    8   8  8    8   'Yb.
	// `YooP' 8YooP' `YooP' `YooP8   8  `Yooo' `YooP' `Yooo' `YooP' `YooP' 8   8 `YooP' `YooP8 8     `YooP8 `YooP'   8  `YooP8   8  `YooP' `YooP'
	// :.....:8 ....::.....::.....:::..::.....::.....::.....::.....::.....:..::..:.....::.....:..:::::....8 :.....:::..::.....:::..::.....::.....:
	// :::::::8 :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.::::::::::::::::::::::::::::::::::::::
	// :::::::..:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::...::::::::::::::::::::::::::::::::::::::::

	public function updateSecondaryStatusAction() {
		$documentAction = $_POST['action'];
		$documentID = $_POST['documentID'];

		$this->logger->log('updateSecondaryStatusAction()');
		$this->logger->log($_POST);

		$allowedActionsArray = [
			EDIDocument::SECONDARY_STATUS_ARCHIVE,
			EDIDocument::SECONDARY_STATUS_DUPLICATE,
			EDIDocument::SECONDARY_STATUS_CLEAR
		];

		if (!in_array($documentAction, $allowedActionsArray)) {
			$errorString = "Failed to set status '$documentAction' on EDI document $documentID";

			$this->view->data = array('success' => 0, 'error' => $errorString);
			$this->logger->log($errorString);
		} else {
			$EDIDocument = EDIDocument::findFirst("DocID = '$documentID'");

			if ($documentAction == EDIDocument::SECONDARY_STATUS_CLEAR) {
				$EDIDocument->SecondaryStatus = NULL;
			} else {
				$EDIDocument->SecondaryStatus = $documentAction;
			}

			$EDIDocument->save();
		}

		$this->view->pick("list");
	}

	//                   o    o .oPYo. .oPYo. o    o
	//                   8    8 8      8    8 8b   8
	//   .oPYo. .oPYo.  o8P   8 `Yooo. 8    8 8`b  8
	//   8    8 8oooo8   8    8     `8 8    8 8 `b 8
	//   8    8 8.       8    8      8 8    8 8  `b8
	//   `YooP8 `Yooo'   8  oP' `YooP' `YooP' 8   `8
	//  ::....8 :.....:::..:...::.....::.....:..:::..
	//  :::ooP'.:::::::::::::::::::::::::::::::::::::
	//  :::...:::::::::::::::::::::::::::::::::::::::

	public function getdocAction() {
		$docId = $this->dispatcher->getParam("id");
		$doc = EDIDocument::findFirst("DocID = '$docId'");

		$this->view->data = json_encode(json_decode($doc->JsonObject), JSON_PRETTY_PRINT);
		$this->view->pick("layouts/json");
	}

	//                 o   o    o  .o .oPYo.
	//                  8   `b  d'   8     `8
	//  .oPYo. .oPYo.  o8P   `bd'    8    oP'
	//  8    8 8oooo8   8    .PY.    8 .oP'
	//  8    8 8.       8   .P  Y.   8 8'
	//  `YooP8 `Yooo'   8  .P    Y.  8 8ooooo
	//  :....8 :.....:::..:..::::..::.........
	//  ::ooP'.:::::::::::::::::::::::::::::::
	//  ::...:::::::::::::::::::::::::::::::::

	public function getx12docAction() {
		$docId = $this->dispatcher->getParam("id");
		$doc = EDIDocument::findFirst("DocID = '$docId'");

		$file = file_get_contents($doc->X12FilePath);
		$file = preg_replace('/~[\r\n]*/', "~\n", $file); // add in \n's if lacking.

		$this->view->data = $doc->X12FilePath . "\n\n" . $file;
		$this->view->pick("layouts/json");
	}



	//       .oo .oPYo. .oPYo.   o         o                d'b
	//      .P 8 8          `8   8         8                8
	//     .P  8 `Yooo.    oP'   8 odYo.  o8P .oPYo. oPYo. o8P  .oPYo. .oPYo. .oPYo.
	//    oPooo8     `8 .oP'     8 8' `8   8  8oooo8 8  `'  8   .oooo8 8    ' 8oooo8
	//   .P    8      8 8'       8 8   8   8  8.     8      8   8    8 8    . 8.
	//  .P     8 `YooP' 8ooooo   8 8   8   8  `Yooo' 8      8   `YooP8 `YooP' `Yooo'
	//  ..:::::..:.....:.......::....::..::..::.....:..:::::..:::.....::.....::.....:
	//  :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	//  :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function updateAction() {
		if ($_POST) { // has enough data
			// if updating
			if (isset($_POST['DocID'])) {
				$docId = $_POST['DocID'];
				$doc = EDIDocument::findFirst("DocID = '$docId'");
				if (isset($doc->DocID)) {
					// use filtered post params to update record
				} else // doc id didn't exist
				{
					$this->view->data = array('success' => 0, 'msg' => "DocID Doesn't exist in _POST", 'type' => 'new');
					$this->view->pick("layouts/json");
					return;
				}
			} else // new document?
			{
				// $doc = EDIDocument::findFirst("GLC + identifier of doc + doc type");
				$doc = EDIDocument::findFirst(); // use post params
				// for list of post fields to create new
				// $doc->
			}

			if ($doc->save() == false) {
				$msg = "Error saving edidoc:\n" . implode("\n", $doc->getMessages());
				$this->logger->log($msg);
				// $this->db->rollback();
				$this->view->data = array('success' => 0, 'msg' => $msg, 'type' => 'new');
			} else {
				$this->view->data = array('success' => 1, 'msg' => 'success', 'type' => 'new');
			}
		} else {
			$this->view->data = array('success' => 0, 'msg' => 'Not enough data in _POST', 'type' => 'new');
		}
		$this->view->pick("layouts/json");
		return;
	}

	//                   o  .oPYo. ooo.   o .oPYo.
	//                   8  8.     8  `8. 8 8.
	//   .oPYo. .oPYo.  o8P `boo   8   `8 8 `boo   oPYo. oPYo. .oPYo. oPYo.
	//   Yb..   8oooo8   8  .P     8    8 8 .P     8  `' 8  `' 8    8 8  `'
	//     'Yb. 8.       8  8      8   .P 8 8      8     8     8    8 8
	//   `YooP' `Yooo'   8  `YooP' 8ooo'  8 `YooP' 8     8     `YooP' 8
	// :::.....::.....:::..::.....:.....::..:.....:..::::..:::::.....:..::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function setEDIError($errorString, $docid = 'UNKNOWN') {
		$this->logger->log($errorString . " DocID: '$docid'");

		$this->view->data = array('success' => 0, 'error' => $errorString . " DocID: '$docid'");

		$this->view->pick("layouts/json");
	}

	//                        o    o  .o .oPYo.
	//                        `b  d'   8     `8
	//   .oPYo. .oPYo. odYo.   `bd'    8    oP'
	//   8    8 8oooo8 8' `8   .PY.    8 .oP'
	//   8    8 8.     8   8  .P  Y.   8 8'
	//   `YooP8 `Yooo' 8   8 .P    Y.  8 8ooooo
	// :::....8 :.....:..::....::::..::.........
	// ::::ooP'.::::::::::::::::::::::::::::::::
	// ::::...::::::::::::::::::::::::::::::::::

	public function generatex12Action() {
		// "controller" 	=> "edidocument",
		// "action"			=> 1, (generatex12)
		// "transaction"	=> 2, (945)
		// "edikey" 		=> 3, (LOL)
		// "reference"		=> 4, (BoLID)
		// "reference2"		=> 5 (EDIDocID)

		// $newController = new \MyNS\Controllers\NewController();
		// $newController->myFunc();

		// tracker.oshkoshcheese.com/edidocument/generatex12/945/GLC/ref/ref2
		// http://devtracker.oshkoshcheese.com/edidocument/generatex12/945/GLC/328A5737-0797-4535-8A9A-D31C7230B47B/50

		$transaction = $this->dispatcher->getParam("transaction");
		$edikey = $this->dispatcher->getParam("edikey");

		$this->logger->log("EDIDocumentController.php - > generatex12Action() transaction");
		$this->logger->log($transaction);

		$this->logger->log("EDIDocumentController.php - > generatex12Action() edikey");
		$this->logger->log($edikey);

		if (!$cust = Customer::getEDICustomer($edikey, $transaction)) {
			$this->logger->log("generateX12: Transaction {$transaction} not allowed for {$edikey}");
			return false;
		}

		// ID to data we are needing to pull from in order to fill out an edi document. Example: Delivery->DeliveryID
		$ref = $this->dispatcher->getParam("reference");
		// $this->logger->log("RefID: $ref");
		$EDIDocID = $this->dispatcher->getParam("reference2");
		// $this->logger->log("EDIDocID: $EDIDocID");


		if (!$cust->EDIKey) {
			$this->logger->log("Bad EDI customer edikey '$edikey'");
			$this->view->data = array('success' => 0, 'error' => "Bad EDI customer edikey '$edikey'");
			$this->view->pick("layouts/json");
			return;
		}

		$ediDoc = EDIDocument::findFirst("DocID = '$EDIDocID'");
		if ($ediDoc) {
			$ediDoc = $ediDoc->toArray();
		} else {
			$this->logger->log("Bad EDI Doc ID '$EDIDocID'");
			$this->view->data = array('success' => 0, 'error' => "Bad EDI Doc ID '$EDIDocID'");
			$this->view->pick("layouts/json");
			return;
		}

		$acceptedEDIFormsArray = [
			EDIDocument::WAREHOUSE_SHIPPING_ADVICE,
			EDIDocument::WAREHOUSE_STOCK_TRANSFER_RECEIPT_ADVICE,
			EDIDocument::WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE,
			EDIDocument::ADVANCE_SHIP_NOTICE_MANIFEST
		];

		// If we are NOT using a valid form...
		if (in_array($transaction, $acceptedEDIFormsArray) === false) {
			$this->setBadTransactionError($transaction);

			return;
		}

		//   .oPYo.    .8  oooooo
		//   8'  `8   d'8  8
		//   8.  .8  d' 8  8pPYo.
		//   `YooP8 Pooooo     `8
		//       .P     8      .P
		//   `YooP'     8  `YooP'
		// :::.....:::::..::.....:
		// :::::::::::::::::::::::
		// :::::::::::::::::::::::


		// http://devtracker.oshkoshcheese.com/edidocument/generatex12/945/GLC/328A5737-0797-4535-8A9A-D31C7230B47B/50
		// http://devtracker.oshkoshcheese.com/edidocument/generatex12/945/GLC/03591B6E-B867-4B97-9E70-AD7302491BEE/76


		// $this->logger->log("EdiDocumentController.php -> generatex12Action() transaction");

		// $this->logger->log($transaction );
		// $this->logger->log(EDIDocument::WAREHOUSE_SHIPPING_ADVICE);
		// $this->logger->log($transaction == EDIDocument::WAREHOUSE_SHIPPING_ADVICE);
		if ($transaction == EDIDocument::WAREHOUSE_SHIPPING_ADVICE) {
			// $this->logger->log("Am I reaching this point where the transaction is 945 in the generatex12Action()");

			if (empty($edikey)) {
				return;
			}

			$this->logger->log("The EDI Key is not empty");

			$processor = self::getProcessor($edikey);

			$billOfLadingID = $ref;

			try {
				$bolData = BillOfLading::findFirst("BOLID = '$billOfLadingID'")->toArray();
				$offerData = Offer::getOfferInfo($this->db, $bolData['OfferID']);
				$CustomerOrder =  CustomerOrder::findFirst("OfferID = '" . $bolData['OfferID'] . "'");
				$CustomerOrderDetail =  CustomerOrderDetail::find("CustomerOrderID = $CustomerOrder->CustomerOrderID");

				$EDIData = $processor->getWarehouseShippingAdviceData($this, $cust, $bolData, $offerData, $CustomerOrder, $CustomerOrderDetail);
			} catch (\Phalcon\Exception $exception) {
				$this->logger->log("EXCEPTION");
				$this->logger->log($exception->getMessage());

				// Something went wrong, so we set this up to return failure
				$EDIData = false;
			} finally {
				if ($EDIData === false) {
					return json_encode(["success" => 0]);
				}
			}

			// $this->logger->log('EDI Data');
			// $this->logger->log($EDIData);

			$this->generateEDIAndX12Documents($CustomerOrder->CustomerOrderID, $edikey, $transaction, $cust, $EDIData, $EDIData['lines']);
			return;
		}

		//   .oPYo.    .8     .8
		//   8'  `8   d'8    d'8
		//   8.  .8  d' 8   d' 8
		//   `YooP8 Pooooo Pooooo
		//       .P     8      8
		//   `YooP'     8      8
		// :::.....:::::..:::::..:
		// :::::::::::::::::::::::
		// :::::::::::::::::::::::
		else if ($transaction == EDIDocument::WAREHOUSE_STOCK_TRANSFER_RECEIPT_ADVICE) {

			$processor = self::getProcessor($edikey);
			$EDIData = $processor->get944X12Data($this, $ref, $cust);
			$this->generateEDIAndX12Documents($ref, $edikey, $transaction, $cust, $EDIData, $EDIData['lines']);

			return;

		// .oPYo.    .8  oooooo
		// 8'  `8   d'8     .o'
		// 8.  .8  d' 8    .o'
		// `YooP8 Pooooo  .o'
		//     .P     8  .o'
		// `YooP'     8  o'
		// :.....:::::..:..:::::
		// :::::::::::::::::::::
		// :::::::::::::::::::::


		} else if ($transaction == EDIDocument::WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE) { // 947
			// TODO: LOL is the only customer we are generating this document for -- new setting in cutomer table?

			$processor = self::getProcessor($edikey);

			$warehouseAdjustmentList = WarehouseAdjustment::getWarehouseAdjustmentsByGroup($ref);

			$lot = Lot::findFirst("LotID = '" . $warehouseAdjustmentList[0]->LotID . "'");
			$vat = Vat::findFirst("VatID = '" . $warehouseAdjustmentList[0]->VatID . "'");

			$EDIData = $processor->get947X12Data($this, $cust, $warehouseAdjustmentList, $lot, $vat);

			$this->generateEDIAndX12Documents($ref, $edikey, $transaction, $cust, $EDIData);


			return;
		} else {
			$this->setBadTransactionError($transaction);

			return;
		}
	}

	//  o                             o                       o                      o
	//                                8
	// o8 odYo. o    o .oPYo. odYo.  o8P .oPYo. oPYo. o    o o8 odYo. .oPYo. o    o o8 oPYo. o    o
	//  8 8' `8 Y.  .P 8oooo8 8' `8   8  8    8 8  `' 8    8  8 8' `8 8    8 8    8  8 8  `' 8    8
	//  8 8   8 `b..d' 8.     8   8   8  8    8 8     8    8  8 8   8 8    8 8    8  8 8     8    8
	//  8 8   8  `YP'  `Yooo' 8   8   8  `YooP' 8     `YooP8  8 8   8 `YooP8 `YooP'  8 8     `YooP8
	// :....::..::...:::.....:..::..::..::.....:..:::::....8 :....::..:....8 :.....::....:::::....8
	// :::::::::::::::::::::::::::::::::::::::::::::::::ooP'.::::::::::::::8 ::::::::::::::::::ooP'.
	// :::::::::::::::::::::::::::::::::::::::::::::::::...::::::::::::::::..::::::::::::::::::...::

	public function inventoryinquiryAction() {

		// /edidocument/inventoryinquiry/LOL
		$ediKey = $this->dispatcher->getParam("id");

		$customer = Customer::getEDICustomer($ediKey, '846');

		if (empty($ediKey)) {
			return false;
		}

		$processor = self::getProcessor($ediKey);

		// Need to check if we have already sent one for the day
		if (InventoryInquiry::existsForDate()) {
			$this->logger->log("Inventory Inquiry already exists");

			return false;
		}

		if ($customer === false) {
			$this->logger->log("Failed to get customer");
			return false;
		}

		// Only used to give the x12 an ID to work with
		$inventoryInquiry = new InventoryInquiry();

		// Saving this right away. It's mainly here to keep track of what number we are on.
		if ($inventoryInquiry->save() === false) {
			$this->logger->log("Failed to save Inventory Inquiry");

			// If this fails, we can't do anything else
			return false;
		}

		$inventoryInquiryData = $processor->getInventoryInquiryData($this, $customer, $inventoryInquiry);

		$fileName = $this->generateX12FileName($inventoryInquiry->InventoryInquiryID, $customer->EDIKey, EDIDocument::INVENTORY_INQUIRY_ADVICE);

		$this->logger->log("fileName");
		$this->logger->log($fileName);

		$ediDocument = $this->generateEDIDocument(
			$customer,
			EDIDocument::INVENTORY_INQUIRY_ADVICE,
			$inventoryInquiry->InventoryInquiryID,
			$fileName
		);

		if ($ediDocument->save() == false) {
			$this->logger->log("Failed to save EDI Document");
			return false;
		}

		$this->generateX12File($ediDocument, $inventoryInquiryData);
	}

	//             8      8
	//             8      8
	// .oPYo. .oPYo8 .oPYo8
	// .oooo8 8    8 8    8
	// 8    8 8    8 8    8
	// `YooP8 `YooP' `YooP'
	// :.....::.....::.....
	// ::::::::::::::::::::
	// ::::::::::::::::::::

	// Used for manual EDI Document generation
	public function addAction() {
		$response = ['success' => 0, 'message' => ''];

		$ediKey = $this->dispatcher->getParam("edikey");
		$transaction = $this->dispatcher->getParam("transaction");
		$controlNumber = $this->dispatcher->getParam("controlnumber");

		$customer = Customer::getEDICustomer($ediKey);

		if ($customer === false) {
			$response['message'] = "Failed to get customer using EDI Key: $ediKey";

			return json_encode($response);
		}

		$ediDocument = $this->generateEDIDocument($customer, $transaction, $controlNumber);

		if ($ediDocument->save() == false) {
			foreach ($ediDocument->getMessages() as $message) {
				$this->logger->log('Error saving edidoc - ' . $message);
			}

			$this->logger->log("couldn't save edi document");

			$this->setEDIError("Error Saving EDI Doc -", implode("\n", $ediDocument->getMessages()));

			$response['message'] = "Failed to save EDI Document.";

			return json_encode($response);
		}

		$response['EDIDocumentID'] = $ediDocument->DocID;
		$response['success'] = (int)($response['message'] === '');

		return json_encode($response);
	}


	//                8              o    o  .o .oPYo.
	//                8              `b  d'   8     `8
	// ooYoYo. .oPYo. 8  .o  .oPYo.   `bd'    8    oP'
	// 8' 8  8 .oooo8 8oP'   8oooo8   .PY.    8 .oP'
	// 8  8  8 8    8 8 `b.  8.      .P  Y.   8 8'
	// 8  8  8 `YooP8 8  `o. `Yooo' .P    Y.  8 8ooooo
	// ..:..:..:.....:..::...:.....:..::::..::.........
	// ::::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::

	public function makex12Action() {
		$this->logger->log("makex12Action");

		$response = ['success' => 0];

		$this->logger->log($this->dispatcher->getParams());

		$ediDocumentID = $this->dispatcher->getParam("edidocumentid");
		$ediDocument = EDIDocument::findFirst("DocID = $ediDocumentID");

		if ($ediDocument === false) {
			$this->logger->log("edi doc false");

			$response['message'] = "Failed to get EDI Document using DocID: '$ediDocumentID'";

			return json_encode($response);
		}

		$x12Data = $this->getX12Data($ediDocument);

		if ($x12Data === false) {
			$this->logger->log("edi doc false");

			$response['message'] = "Failed to get data for X12 using transaction: " . $ediDocument->Transaction;

			return json_encode($response);
		}

		$fileName = $this->generateX12FileNameFromEDIDocument($ediDocument);
		$ediDocument->X12FilePath = $fileName;

		if ($ediDocument->save() === false) {
			$this->logger->log("edi doc false");

			$response['message'] = "Failed to save new EDI Document: " . $ediDocument->DocID;

			return json_encode($response);
		}

		$this->generateX12File($ediDocument, $x12Data, $fileName);
	}

	//   o    o  .o .oPYo.   o           o .oPYo. .oPYo. o    o
	//   `b  d'   8     `8   8           8 8      8    8 8b   8
	//    `bd'    8    oP'  o8P .oPYo.   8 `Yooo. 8    8 8`b  8
	//    .PY.    8 .oP'     8  8    8   8     `8 8    8 8 `b 8
	//   .P  Y.   8 8'       8  8    8   8      8 8    8 8  `b8
	//  .P    Y.  8 8ooooo   8  `YooP' oP' `YooP' `YooP' 8   `8
	//  ..::::..::.........::..::.....:...::.....::.....:..:::..
	//  ::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	//  ::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function x12tojsonAction(): void {

		$this->view->data = array('success' => 1);

		$this->logger->log(dirname(__FILE__));
		// robert:  http://devtracker.oshkoshcheese.com/edidocument/x12tojson/940/GLC/{docid}
		// robert:  http://devtracker.oshkoshcheese.com/edidocument/x12tojson/{transaction}/{edikey}/{docid}

		$transaction = $this->dispatcher->getParam("transaction");

		$edikey = $this->dispatcher->getParam("edikey");

		// TODO: Add other forms? -- call Customer to find valid docs
		$acceptedFormsArray = [
			"warehouseShippingOrder" 		=> "940",
			"stockTransferShippingAdvice" 	=> "943"
		];

		$docid = $this->dispatcher->getParam("reference");
		$cust = Customer::getEDICustomer($edikey);
		if (!$cust->EDIKey) {
			$this->setEDIError("Bad EDI customer edikey '$edikey'", $docid);
			return;
		}

		// If the form we are checking is not accepted
		if (in_array($transaction, $acceptedFormsArray) == false) {
			$this->setEDIError("Bad EDI Transaction for x12toJson - ", $transaction);
			return;
		}

		// get 3-digit transaction from doc id record
		$EDIDocument = EDIDocument::findFirst("DocID = '$docid'");

		if ($EDIDocument->Status != EDIDocument::STATUS_TRANSLATED) {
			$this->setEDIError("Cannot process document with the status $EDIDocument->Status -", $docid);
			return;
		}

		$jsonEDIArray = json_decode($EDIDocument->JsonObject, true);

		if ($jsonEDIArray == NULL) {
			$this->setEDIError("Cannot decode EDI json -", $docid);
			return;
		}

		$this->logger->log('jsonDecoded');

		// Sanity check to make sure that we are using the correct form type
		if ($this->getFormType($jsonEDIArray) != $transaction) {
			$this->setEDIError("Transaction type does not match file type for document -", $docid);
			return;
		}

		if ($transaction == "940") {

			$this->processWarehouseShippingOrder($transaction, $jsonEDIArray, $docid, $edikey);
		}

		if ($transaction == "943") {
			$this->processStockTransferShippingAdvice($transaction, $jsonEDIArray, $docid, $edikey);
		}

		$EDIDocument->Status = EDIDocument::STATUS_IMPORTED;

		if ($EDIDocument->save() == false) {
			$message = "Error updating the EDI document status:\n" . implode("\n", $EDIDocument->getMessages());
			$this->logger->log("$message\n");
			$this->setEDIError("Error updating the EDI document status -", $EDIDocument->DocID);
			return;
		}

		if ($this->view->data['success'] == 1) {
			$this->sendEmail($EDIDocument);
		}

		$this->view->pick("layouts/json");
	}

	public function getElementsBySegmentName($segmentArray, $segmentName) {
		// If the segmentArray is not empty
		if (!empty($segmentArray)) {
			foreach ($segmentArray as $segment) {
				if ($segment["segment"] == $segmentName) {
					return $segment["elements"];
				}
			}
		}

		return false;
	}

	public function getSegmentBySegmentName($segmentArray, $segmentName) {
		// If the segmentArray is not empty
		if (!empty($segmentArray)) {
			foreach ($segmentArray as $segment) {
				if ($segment["segment"] == $segmentName) {
					return $segment;
				}
			}
		}

		return false;
	}

	public function getElementBySegmentNameAndLabel($segmentArray, $segmentName, $labelName) {
		$segment = $this->getSegmentBySegmentName($segmentArray, $segmentName);

		if ($segment == false) {
			return $segment;
		}

		return $this->getElementFromSegmentByLabel($segment, $labelName);
	}

	public function getElementFromSegmentByLabel($segment, $labelName) {
		// If it is an array and it's not empty
		if (is_array($segment) && empty($segment) == false) {
			foreach ($segment["elements"] as $element) {
				if (is_array($element)) {
					if ($element["label"] == $labelName) {
						return $element;
					}
				}
			}
		}

		return false;
	}

	// Gets a specific segment that contains a specific element value
	public function getSegmentWithElementValue($segmentArray, $segmentName, $elementValue) {
		foreach ($segmentArray as $segment) {
			if ($segment["segment"] == $segmentName) {
				if ($this->elementsContainValue($segment["elements"], $elementValue)) {
					return $segment;
				}
			}
		}

		return false;
	}

	// Checks an array of elements to see if they contain a certain value
	public function elementsContainValue($elements, $value) {
		foreach ($elements as $element) {
			if ($element["value"] == $value) {
				return true;
			}
		}

		return false;
	}


	public function getSegementValueAfterEntity($jsonEDIArray, $segment, $entityValue, $offset, $segmentName, $labelName) {
		// Find the position of the "N1" segment with the given entityValue
		$position = array_search(
			$this->getSegmentWithElementValue($jsonEDIArray, $segment, $entityValue),
			$jsonEDIArray
		);

		// Use the position and offset to get the desired segment
		$segment = $this->getSegmentBySegmentName(
			array_slice($jsonEDIArray, $position + $offset),
			$segmentName
		);

		// Return the value of the desired label from the segment
		return $this->getElementFromSegmentByLabel($segment, $labelName)["value"];
	}
}
