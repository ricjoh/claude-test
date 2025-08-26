<?php

use EDIDocument as GlobalEDIDocument;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class EDIDocument extends Model
{

	// TODO: Convert these to Parameters
	// Before that convert getStatusList()
	public const STATUS_CONVERTED = 'Converted';
	public const STATUS_IMPORTED = 'Imported';
	public const STATUS_NEW = 'New';
	public const STATUS_TRANSLATED = 'Translated';
	public const STATUS_ERROR = 'Error';
	public const STATUS_SENT = 'Sent';
	public const STATUS_RECEIVED = 'Received';
	public const STATUS_PENDING = 'Pending';
	public const STATUS_OUTBOX = 'Outbox';


	// Sets the default limit for how many docs we show on the EDI Documents page
	public const DOCS_PER_PAGE = 50;

	public const SECONDARY_STATUS_ARCHIVE = 'Archived';
	public const SECONDARY_STATUS_DUPLICATE = 'Duplicate';
	public const SECONDARY_STATUS_CLEAR = 'Clear';

	// Array of statuses and secondary statuses that are hidden from the EDI doc list by default
	public const HIDDEN_BY_DEFAULT_STATUSES = [
		self::SECONDARY_STATUS_ARCHIVE,
		self::SECONDARY_STATUS_DUPLICATE,
		self::SECONDARY_STATUS_CLEAR
	];

	// Types of EDI documents
	public const INVENTORY_INQUIRY_ADVICE = '846';
	public const ADVANCE_SHIP_NOTICE_MANIFEST = '856';
	public const WAREHOUSE_STOCK_TRANSFER_SHIPMENT_ADVICE_TO_RETAILER = '940';
	public const WAREHOUSE_STOCK_TRANSFER_SHIPMENT_ADVICE = '943';
	public const WAREHOUSE_STOCK_TRANSFER_RECEIPT_ADVICE = '944';
	public const WAREHOUSE_SHIPPING_ADVICE = '945';
	public const WAREHOUSE_INVENTORY_ADJUSTMENT_ADVICE = '947';
	public const FUNCTIONAL_ACKNOWLEDGEMENT = '997';

	public const X12_FILEPATH = '/web/data/tracker.oshkoshcheese.com/x12-data';

	public const REFERENCE_NUMBER_TRANSACTION_SEGMENT_MAP = [
		'GLC940' => ['W05', 2],
		'GLC943' => ['W06', 2],
		'GLC944' => ['W17', 4],
		'GLC945' => ['W06', 2],
		'SAP940' => ['W05', 2],
		'SAP943' => ['W06', 2],
		'SAP944' => ['W17', 4],
		'SAP945' => ['W06', 2],
		'SALM940' => ['W05', 3],
		'SALM943' => ['W06', 2],
		'SALM944' => ['W17', 4],
		'SALM945' => ['W06', 2],
	];

	public $DocID;
	public $EDIKey;
	public $Transaction;
	public $ControlNumber;
	public $Incoming;
	public $DocISAID;
	public $DocGSID;
	public $Status;
	public $SecondaryStatus;
	public $X12FilePath;
	public $JsonObject;
	public $CreateDate;
	public $UpdateDate;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("EDIDocument");
		// $this->skipAttributes(
		//     [
		//         'DocID'
		//     ]
		// );
	}

	public function pendingCount() {
		$sql = "select " .
			"(select count(Delivery.DeliveryID) from Delivery WHERE Delivery.StatusPID = '" . Delivery::STATUS_PENDING .  "') as deliveryCount, " .
			"(select count(Lot.LotID) from Lot WHERE Lot.StatusPID = '" . Lot::STATUS_DRAFT . "') AS lotCount, " .
			"(select count(Offer.OfferID) from Offer WHERE OfferStatusPID = '" . Offer::STATUS_EDIPENDING . "') AS offerCount, " .
			"(select count(CustomerOrder.CustomerOrderID) from CustomerOrder WHERE CustomerOrder.Status = '" . CustomerOrder::STATUS_PENDING . "') AS customerOrderCount " .
			"FROM BillOfLading LIMIT 1";
		try {
			$result = $this->getDI()->getShared('modelsManager')->executeQuery($sql)->getFirst();
		} catch (Exception $e) {
			$this->getDI()->getShared('logger')->log( $sql . ";" );
			$this->getDI()->getShared('logger')->log( 'Exception getting count: ' );
			$this->getDI()->getShared('logger')->log( $e->getMessage() );
		}

		if ( $result ) {
			$result->Total = $result[ 'deliveryCount' ] + $result[ 'lotCount' ] +
							$result[ 'offerCount' ] + // $result[ 'bolCount' ] +
							$result[ 'customerOrderCount' ] ? : 0;
		}
		else
		{
			$result = array( 'Total' => 0 );
		}

		return $result;
	}

	public function lastID() {
		$sql = "SELECT MAX( DocID ) AS DocID FROM EDIDocument";
		$docid = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $docid[0]->DocID;
	}

	public function getEDIDocuments($postFilters = array(), $pageNumber = 1, $documentsPerPage = self::DOCS_PER_PAGE)
	{
		$excludedPostFilters = ["applyEDIFilters", "pageNumber", "changePage", "Reference", "EDIKeys", "resetEDIFilters", "Status"];

		$sqlOffset = ($pageNumber - 1) * $documentsPerPage;

		$sql = "SELECT
			ed.DocID, ed.EDIKey, ed.Transaction, ed.ControlNumber, ed.Incoming,
			ed.DocISAID, ed.DocGSID, ed.Status, ed.X12FilePath, ed.CreateDate, ed.UpdateDate,
			ed.SecondaryStatus, ed.ReferenceNumber
		FROM
			EDIDocument ed ";

		$sql .= $this->getEDIDocumentFilterString($postFilters, $excludedPostFilters);
		$sql .= " ORDER BY ed.DocID DESC ";
		$sql .= " LIMIT $sqlOffset, $documentsPerPage ";
		// $this->getDI()->getShared('logger')->log($sql . ";");

		$docs = [];

		try {
			$docs = $this->getDI()->getShared('modelsManager')->executeQuery($sql)->toArray();
		} catch (Exception $e) {
			$this->getDI()->getShared('logger')->log($sql . ";");
			$this->getDI()->getShared('logger')->log('Exception in getEDIDocuments:');
			$this->getDI()->getShared('logger')->log($e->getMessage());
		}

		return $docs;
	}

	public function getEDIDocumentCount($postFilters = array())
	{
		$excludedPostFilters = ["applyEDIFilters", "pageNumber", "changePage", "Reference", "EDIKeys", "resetEDIFilters", "Status"];

		$sql = "SELECT count(*) as docCount FROM EDIDocument ed LEFT JOIN Delivery d ON d.EDIDocID = ed.DocID ";
		$sql .= $this->getEDIDocumentFilterString($postFilters, $excludedPostFilters);

		try {
			$docs = $this->getDI()->getShared('modelsManager')->executeQuery($sql)->toArray();
		} catch (Exception $e) {
			$this->getDI()->getShared('logger')->log($sql . ";");
			$this->getDI()->getShared('logger')->log('Exception in getEDIDocumentCount:');
			$this->getDI()->getShared('logger')->log($e->getMessage());

			return 0;
		}

		return $docs[0]['docCount'];
	}

	public function getStatusList()
	{
		$sql = "select distinct Status from EDIDocument";
		$statii = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $statii;
	}

	public function getSecondaryStatusList()
	{
		$sql = "select distinct SecondaryStatus as Status from EDIDocument WHERE SecondaryStatus IS NOT NULL";
		$secondaryStatii = $this->getDI()->getShared('modelsManager')->executeQuery($sql);

		return $secondaryStatii;
	}

	public function getEDIKeys()
	{
		$sql = "select distinct EDIKey from EDIDocument";
		$ediKeys = $this->getDI()->getShared('modelsManager')->executeQuery($sql);

		return $ediKeys;
	}

	public function getTransactionTypesList()
	{
		$sql = "select distinct Transaction from EDIDocument";
		$xactions = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $xactions;
	}

	private function getEDIDocumentFilterString($postFilters, $excludedPostFilters = array())
	{
		$filterString = "WHERE ed.Transaction <> '997'";

		if (!empty($postFilters['EDIKeys'])) {
			$filterString .= " AND ed.EDIKey IN ('" . implode('\', \'', $postFilters['EDIKeys']) . "') ";
		}

		// $postFilters is an associative array where the key is the post variable name and the value is the value
		foreach ($postFilters as $postVariable => $postFilter) {
			// If the post variable is set
			if ($postFilters[$postVariable] || $postFilters[$postVariable] != "") {
				// If it's not the applyEDIFilters post variable or a date
				if (!in_array($postVariable, $excludedPostFilters) && strpos($postVariable, 'Date') === false) {
					$filterString .= " AND ed." . $postVariable . " = '$postFilter'";
				}
				}
			}

		if ($postFilters['Status']) {
			$filterString .= " AND (ed.Status = '{$postFilters['Status']}' AND ed.SecondaryStatus IS NULL OR ed.SecondaryStatus = '{$postFilters['Status']}')";
		} else {
			$filterString .= " AND (ed.Status NOT IN ('" . implode('\', \'', self::HIDDEN_BY_DEFAULT_STATUSES) . "')";
			$filterString .= " AND (ed.SecondaryStatus NOT IN ('" . implode('\', \'', self::HIDDEN_BY_DEFAULT_STATUSES) . "') OR ed.SecondaryStatus IS NULL))";
		}

		if ($postFilters[ 'ArrivedFromDate' ]) {
			$filterString .= " AND ed.CreateDate >= '{$postFilters[ 'ArrivedFromDate' ]}'";
		}

		if ($postFilters[ 'ArrivedToDate' ]) {
			// Adding a day because the "TO" date selector would limit results to day prior to selected
			$arrivedToDate = date('Y-m-d', strtotime($postFilters[ 'ArrivedToDate' ] . ' + 1 days'));
			$filterString .= " AND ed.CreateDate <= '{$arrivedToDate}'";
		}

		if ($postFilters[ 'UpdatedFromDate' ]) {
			$filterString .= " AND ed.UpdateDate >= '{$postFilters[ 'UpdatedFromDate' ]}'";
		}

		if ($postFilters[ 'UpdatedToDate' ]) {
			// Adding a day because the "TO" date selector would limit results to day prior to selected
			$updatedToDate = date('Y-m-d', strtotime($postFilters[ 'UpdatedToDate' ] . ' + 1 days'));
			$filterString .= " AND ed.UpdateDate <= '{$updatedToDate}'";
		}

		if ($postFilters['Reference']) {
			$filterString .= " AND ed.ReferenceNumber LIKE '%" . $postFilters['Reference'] . "%'";
		}

		return $filterString;
	}

	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getEDIKeyByEDIDocID($EDIDocID)
	{
		$ediKey = self::findFirst(['conditions' => "DocID = '$EDIDocID'", 'columns' => 'EDIKey']);

		return $ediKey ? $ediKey->EDIKey : false;
	}
}
