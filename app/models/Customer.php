<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Phalcon\Mvc\Model\Criteria;

class Customer extends Model {
	public $CustomerID;
	public $Name;
	public $Phone;
	public $Fax;
	public $Email;
	public $HandlingCharge;
	public $StorageCharge;
	public $TermsPID;
	public $Active;
	public $EDIFlag;
	public $EDIISAID;
	public $EDIGSID;
	public $EDIKey;
	public $InventoryReportName;
	public $SalesReportName;
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;
	public $EDIDocCodes;

	public function initialize() {
		// set the db table to be used
		$this->setSource("Customer");
		$this->hasMany("CustomerID", "Contact", "CustomerID");
		$this->hasMany("CustomerID", "Lot", "CustomerID");
		$this->hasMany("CustomerID", "Offer", "CustomerID");
		$this->hasMany("CustomerID", "ShipToAddress", "CustomerID");
	}

	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public function getCustomers() {
		$custs = Customer::find(array(
			"Name NOT LIKE 'Z - VOID%'",
			"order" => "Active DESC, Name"
		));
		return $custs;
	}

	// will include currentID Customer even if not current if present
	public static function getActiveCustomers($currentID) {
		$addl = (isset($currentID) ? " OR CustomerID = '$currentID'" : '');

		$rows = Customer::find(array(
			'conditions' => "(Active = 1 AND Name NOT LIKE 'Z%-%VOID%')" . $addl,
			'order' => "Name ASC"
		));

		$data = array();
		array_push($data, array('CustomerID' => '', 'Name' => 'Select...'));
		foreach ($rows as $r) {
			$d = array(
				'CustomerID' => $r->CustomerID,
				'Name' => $r->Name,
				'HandlingCharge' => $r->HandlingCharge,
				'TermsPID' => $r->TermsPID,
				'Phone' => $r->Phone,
				'Fax' => $r->Fax,
				'Email' => $r->Email
			);
			array_push($data, $d);
		}
		return $data;
	}

	public static function getEDIKeys() {
		$rows = Customer::find(array(
			'conditions' => "EDIKey is not null AND EDIKey <> '' AND EDIFlag = 1",
			'order' => "Name ASC"
		));

		$data = array();

		foreach ($rows as $r) {
			$data[$r->EDIKey] = $r->Name;
		}

		return $data;
	}

	public static function getCustomer($customerId) {
		$customerData = Customer::findFirst("CustomerID = '$customerId'");
		// getContact() is a magic function created by the hasMany "Contact" relationship created in the initialize function above
		$customerData->contacts = $customerData->getContact(array('order' => 'Active DESC, LastName, FirstName'));
		return $customerData;
	}

	public static function getEDICustomer($edikey, $EDIDocCode = "") {
		$customerData = Customer::findFirst("EDIKey = '$edikey'");
		if (!$customerData) return false;

		if (!empty($EDIDocCode) && !in_array($EDIDocCode, preg_split("/[^0-9]+/", $customerData->EDIDocCodes))) {
			return false;
		}
		return $customerData;
	}

	public static function getCustomerIDByEDIDocID($ediDocID) {
		$sql = "SELECT
					c.CustomerID
				FROM
					Customer c
					JOIN EDIDocument d ON d.EDIKey = c.EDIKey
				WHERE
					d.DocID = '$ediDocID'";

		$customerID = self::getDb()->query($sql);
		$customerID = $customerID->fetchAll($customerID);

		return $customerID[0]["CustomerID"];
	}


	//                 o  o                             o
	//                 8  8                             8
	// .oPYo. .oPYo.  o8P 8 odYo. o    o .oPYo. odYo.  o8P .oPYo. oPYo. o    o
	// 8    8 8oooo8   8  8 8' `8 Y.  .P 8oooo8 8' `8   8  8    8 8  `' 8    8
	// 8    8 8.       8  8 8   8 `b..d' 8.     8   8   8  8    8 8     8    8
	// `YooP8 `Yooo'   8  8 8   8  `YP'  `Yooo' 8   8   8  `YooP' 8     `YooP8
	// :....8 :.....:::..:....::..::...:::.....:..::..::..::.....:..:::::....8
	// ::ooP'.::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
	// ::...::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::...::

	// INVENTORY: Report

	public function getInventory($custId = NULL, $sort = NULL, $invType = NULL) {
		$db = self::getDb();
		$db->connect();

		if (!$custId) $custId = $this->CustomerID;

		if ($sort) {
			$orderBy = "ORDER BY $sort, LotNumber, MakeDate DESC";
		} else {
			$orderBy = 'ORDER BY LotNumber, MakeDate DESC';
		}

		if ($invType) {
			if (strtoupper($invType) == 'SET-ASIDE') $invType = "AND L.InventoryTypePID = '61D4197D-22EB-4B50-8D5F-B94C07CB6509'";
			elseif (strtoupper($invType) == 'AGING') $invType = "AND L.InventoryTypePID = '85102C3F-33C1-482A-9750-4FB4A5EA7A7B'";
			elseif (strtoupper($invType) == 'COMMITTED') $invType = "AND L.InventoryTypePID = '9BB1E25F-8E9F-457B-84FB-6ADC005EAE2E'";
			else $invType = '';
		} else $invType = '';

		$sql = "SELECT DISTINCT
		   L.LotID, L.LotNumber, L.CustomerPONumber, L.Cost, L.DateIn, P.Value1 AS LotDescription,
		   MIN(V.MakeDate) AS MakeDate, SUM(I.Pieces) AS Pieces, SUM(I.Weight) AS Weight,
		   VF.Name AS FactoryName, VF.Number AS FactoryNumber, C.CustomerID, C.Name,
		   RP.Value1 AS RoomName, LT.Value1 As InventoryType
		 , L.ProductCode
		FROM Lot AS L
		LEFT OUTER JOIN Vat AS V ON L.LotID = V.LotID
		LEFT OUTER JOIN InventoryStatus AS I ON I.VatID = V.VatID
			AND I.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'
		LEFT OUTER JOIN Customer AS C ON C.CustomerID = L.CustomerID
		LEFT OUTER JOIN Parameter AS P ON P.ParameterID = L.DescriptionPID
		LEFT OUTER JOIN Parameter AS RP ON RP.ParameterID = L.RoomPID
		LEFT OUTER JOIN Parameter AS LT ON LT.ParameterID = L.InventoryTypePID
		LEFT OUTER JOIN Factory AS VF ON VF.FactoryID = L.FactoryID
		WHERE C.CustomerID = ? AND I.Pieces > 0 $invType
		GROUP BY L.LotID
		$orderBy";

		$resSet = $db->query($sql, array($custId));
		return $resSet->fetchAll($resSet);
	}


	//                 o  ooo.            o          o 8             8 o
	//                 8  8  `8.          8            8             8 8
	// .oPYo. .oPYo.  o8P 8   `8 .oPYo.  o8P .oPYo. o8 8 .oPYo. .oPYo8 8 odYo. o    o
	// 8    8 8oooo8   8  8    8 8oooo8   8  .oooo8  8 8 8oooo8 8    8 8 8' `8 Y.  .P
	// 8    8 8.       8  8   .P 8.       8  8    8  8 8 8.     8    8 8 8   8 `b..d'
	// `YooP8 `Yooo'   8  8ooo'  `Yooo'   8  `YooP8  8 8 `Yooo' `YooP' 8 8   8  `YP'
	// :....8 :.....:::..:.....:::.....:::..::.....::....:.....::.....:....::..::...::
	// ::ooP'.::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// INVENTORY: Report

	public function getDetailedInventory($custId = NULL, $sort = NULL, $invType = NULL) {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		if (!$custId) $custId = $this->CustomerID;

		if ($sort) {
			$orderBy = "ORDER BY $sort, LotNumber, MakeDate DESC";
		} else {
			$orderBy = 'ORDER BY LotNumber, MakeDate DESC';
		}

		if ($invType) {
			if (strtoupper($invType) == 'SET-ASIDE') $invType = "AND L.InventoryTypePID = '61D4197D-22EB-4B50-8D5F-B94C07CB6509'";
			elseif (strtoupper($invType) == 'AGING') $invType = "AND L.InventoryTypePID = '85102C3F-33C1-482A-9750-4FB4A5EA7A7B'";
			elseif (strtoupper($invType) == 'COMMITTED') $invType = "AND L.InventoryTypePID = '9BB1E25F-8E9F-457B-84FB-6ADC005EAE2E'";
			else $invType = '';
		} else $invType = '';

		// $STATUS_AVAILABLE = 'D99FC80E-52BC-4AD0-9B10-3E5A5F07EAE0';

		$sql = "SELECT DISTINCT
			L.LotID, L.LotNumber, L.CustomerPONumber, L.Cost, L.DateIn, P.Value1 AS LotDescription,
			V.MakeDate AS MakeDate, I.Pieces AS Pieces, I.Weight AS Weight, VF.Name AS FactoryName,
			VF.Number AS FactoryNumber, C.CustomerID, C.Name, RP.Value1 AS RoomName,
			LT.Value1 As InventoryType, L.ProductCode, V.CustomerLotNumber, V.VatNumber, V.VatID
		FROM Lot AS L
		LEFT OUTER JOIN Vat AS V ON L.LotID = V.LotID
		LEFT OUTER JOIN InventoryStatus AS I ON I.VatID = V.VatID
			AND I.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'
		LEFT OUTER JOIN Customer AS C ON C.CustomerID = L.CustomerID
		LEFT OUTER JOIN Parameter AS P ON P.ParameterID = L.DescriptionPID
		LEFT OUTER JOIN Parameter AS RP ON RP.ParameterID = L.RoomPID
		LEFT OUTER JOIN Parameter AS LT ON LT.ParameterID = L.InventoryTypePID
		LEFT OUTER JOIN Factory AS VF ON VF.FactoryID = L.FactoryID
		WHERE C.CustomerID = ? AND I.Pieces > 0 $invType
		$orderBy";

		$resSet = $db->query($sql, array($custId));

		return $resSet->fetchAll($resSet);
	}

	// NOTE: This is used by EDI Inventory Report. It is not finished.
	// INVENTORY: Report

	public function getEDIInventory($postFilters, $limited = true, $custId = NULL, $sort = NULL) {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		if (!$custId) $custId = $this->CustomerID;
		// TODO: Make sure lots are not draft lots.
		if ($sort) {
			$orderBy = "ORDER BY $sort, LotNumber, MakeDate DESC";
		} else {
			$orderBy = 'ORDER BY LotNumber, MakeDate DESC';
		}

		$postFiltersSqlString = $this->getEDIPostFiltersString($postFilters);

		// If we don't want to see empty lots then only get lots with more than 0 pieces
		if ($postFilters['showEmptyLots'] == false) {
			$havingString = " HAVING Pieces > 0";
		} else {
			$havingString = '';
		}

		if ($limited) {
			$limit = "LIMIT 50";
		}

		// INVENTORY: add soldUnshipped

		$sql = "SELECT DISTINCT
		   L.LotID, L.LotNumber, L.CustomerPONumber, L.Cost, L.DateIn, P.Value1 AS LotDescription,
		   MIN(V.MakeDate) AS MakeDate, SUM(I.Pieces) AS Pieces, SUM(I.Weight) AS Weight, VF.Name AS FactoryName,
		   VF.Number AS FactoryNumber, C.CustomerID, C.Name, RP.Value1 AS RoomName, LT.Value1 As InventoryType,
		   L.ProductCode, V.CustomerLotNumber, V.VatNumber, V.VatID, L.StorageUnit
		FROM Lot AS L
		LEFT OUTER JOIN Vat AS V ON L.LotID = V.LotID
		LEFT OUTER JOIN InventoryStatus AS I ON I.VatID = V.VatID
			AND I.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'
		LEFT OUTER JOIN Customer AS C ON C.CustomerID = L.CustomerID
		LEFT OUTER JOIN Parameter AS P ON P.ParameterID = L.DescriptionPID
		LEFT OUTER JOIN Parameter AS RP ON RP.ParameterID = L.RoomPID
		LEFT OUTER JOIN Parameter AS LT ON LT.ParameterID = L.InventoryTypePID
		LEFT OUTER JOIN Factory AS VF ON VF.FactoryID = L.FactoryID
		WHERE C.CustomerID = ? $postFiltersSqlString
		AND L.StatusPID <> ?
		GROUP BY V.VatID
		$havingString
		$orderBy
		$limit";

		$resSet = $db->query($sql, array($custId, Lot::STATUS_DRAFT));

		return $resSet->fetchAll($resSet);
	}


	//                 o  .oPYo.   o                                    ooo.            o
	//                 8  8        8                                    8  `8.          8
	// .oPYo. .oPYo.  o8P `Yooo.  o8P .oPYo. oPYo. .oPYo. .oPYo. .oPYo. 8   `8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8   8      `8   8  8    8 8  `' .oooo8 8    8 8oooo8 8    8 .oooo8   8  .oooo8
	// 8    8 8.       8       8   8  8    8 8     8    8 8    8 8.     8   .P 8    8   8  8    8
	// `YooP8 `Yooo'   8  `YooP'   8  `YooP' 8     `YooP8 `YooP8 `Yooo' 8ooo'  `YooP8   8  `YooP8
	// :....8 :.....:::..::.....:::..::.....:..:::::.....::....8 :.....:.....:::.....:::..::.....:
	// ::ooP'.::::::::::::::::::::::::::::::::::::::::::::::ooP'.:::::::::::::::::::::::::::::::::
	// ::...::::::::::::::::::::::::::::::::::::::::::::::::...:::::::::::::::::::::::::::::::::::

	public function getStorageData($startDate, $endDate, $warehouseId = NULL, $billingFrequency = "1 MONTH") {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		$custId = $this->CustomerID;

		if (!$custId) return false;

		$dateClause = "";

		if ($billingFrequency == "2 WEEKS") {
			$dateClause = "BillingFrequency = '2 WEEKS'";
		} else {
			$dateClause = "BillingFrequency <> '2 WEEKS'";

			$startMDay = getdate($startDate)['mday'];

			if ($endDate == strtotime('last day of this month', $endDate)) {
				$endMDay = 31;
			} else {
				$endMDay = getdate($endDate)['mday'];
			}

			if ($startMDay > $endMDay) {
				$dateClause .= " AND (DAYOFMONTH(DateIn) >= $startMDay OR DAYOFMONTH(DateIn) <= $endMDay)";
			} else {
				$dateClause .= " AND DAYOFMONTH(DateIn) >= $startMDay AND DAYOFMONTH(DateIn) <= $endMDay";
			}
		}

		$dbStartDate = date('Y-m-d 00:00:00.000000', $startDate);
		$dateClause .= " AND DateIn < '$dbStartDate'";

		$params = array("$dateClause AND InventoryTypePID = '85102C3F-33C1-482A-9750-4FB4A5EA7A7B' AND Archived <> 1");

		if ($warehouseId && ($count = count($warehouseId))) {
			if ($count > 1) {
				$qmarks = array();
				foreach ($warehouseId as $i => $wid) $qmarks[] = "?$i";
				$params[0] .= " AND WarehouseID in (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND WarehouseID = ?0";
			}

			$params['bind'] = $warehouseId;
		}

		$params['order'] = "0 + LotNumber";

		$lots = $this->getLot($params);

		return $lots;
	}

	public function getNewStorageData($startDate, $endDate, $warehouseId = NULL, $billingFrequency = '1 MONTH') {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		$custId = $this->CustomerID;

		if (!$custId) return false;

		$dateClause = "UNIX_TIMESTAMP(DateIn) >= $startDate AND UNIX_TIMESTAMP(DateIn) <= $endDate";

		if ($billingFrequency == "2 WEEKS") {
			$dateClause .= " AND BillingFrequency = '2 WEEKS'";
		} else {
			$dateClause .= " AND BillingFrequency <> '2 WEEKS'";
		}

		$params = array("$dateClause AND InventoryTypePID = '85102C3F-33C1-482A-9750-4FB4A5EA7A7B' AND Archived <> 1");

		if ($warehouseId && ($count = count($warehouseId))) {
			if ($count > 1) {
				$qmarks = array();
				foreach ($warehouseId as $i => $wid) $qmarks[] = "?$i";
				$params[0] .= " AND WarehouseID in (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND WarehouseID = ?0";
			}

			$params['bind'] = $warehouseId;
		}

		$params['order'] = "0 + LotNumber";

		$lots = $this->getLot($params);

		return $lots;
	}


	//                 o  .oPYo.               o
	//                 8  8    8
	// .oPYo. .oPYo.  o8P 8      o    o oPYo. o8 odYo. .oPYo.
	// 8    8 8oooo8   8  8      8    8 8  `'  8 8' `8 8    8
	// 8    8 8.       8  8    8 8    8 8      8 8   8 8    8
	// `YooP8 `Yooo'   8  `YooP' `YooP' 8      8 8   8 `YooP8
	// :....8 :.....:::..::.....::.....:..:::::....::..:....8
	// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::ooP'.
	// ::...:::::::::::::::::::::::::::::::::::::::::::::...::

	public function getCuringData($startDate, $endDate, $warehouseId = NULL, $roomPid = NULL, $ownedBy = NULL) {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		$custId = $this->CustomerID;

		if (!$custId) return false;

		$startMDay = getdate($startDate)['mday'];

		if ($endDate == strtotime('last day of this month', $endDate)) $endMDay = 31;
		else $endMDay = getdate($endDate)['mday'];

		if ($startMDay > $endMDay) {
			$dateClause = "(DAYOFMONTH(DateIn) >= $startMDay OR DAYOFMONTH(DateIn) <= $endMDay)";
		} else {
			$dateClause = "DAYOFMONTH(DateIn) >= $startMDay AND DAYOFMONTH(DateIn) <= $endMDay";
		}

		$dbStartDate = date('Y-m-d 00:00:00.000000', $startDate);

		$dateClause .= " AND DateIn < '$dbStartDate'";

		$params = array("$dateClause AND InventoryTypePID IN ('9BB1E25F-8E9F-457B-84FB-6ADC005EAE2E', '61D4197D-22EB-4B50-8D5F-B94C07CB6509')");

		$params['bind'] = [];

		if ($warehouseId && ($count = count($warehouseId))) {
			if ($count > 1) {
				$qmarks = array();
				foreach ($warehouseId as $i => $wid) {
					$qmarks[] = "?$i";
				}

				$params[0] .= " AND WarehouseID IN (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND WarehouseID = ?0";
			}

			$params['bind'] = $warehouseId;
		}

		if ($roomPid && ($count = count($roomPid))) {
			$offset = count($params['bind']);

			if ($count > 1) {
				$qmarks = array();
				foreach ($roomPid as $i => $wid) {
					$j = $offset + $i;
					$qmarks[] = "?$j";
				}

				$params[0] .= " AND RoomPID IN (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND RoomPID = ?$offset";
			}

			$params['bind'] = array_merge($params['bind'], $roomPid);
		} else // exclude LCD
		{
			$params[0] .= " AND RoomPID <> '73882D17-529F-4974-823D-93A5AD15AD9B'";
		}

		if ($ownedBy && ($count = count($ownedBy))) {
			$offset = count($params['bind']);

			if ($count > 1) {
				$qmarks = array();
				foreach ($ownedBy as $i => $wid) {
					$j = $offset + $i;
					$qmarks[] = "?$j";
				}

				$params[0] .= " AND OwnedBy IN (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND OwnedBy = ?$offset";
			}

			$params['bind'] = array_merge($params['bind'], $ownedBy);
		}

		$params['order'] = "0 + LotNumber";

		try {
			$lots = $this->getLot($params);
		} catch (\Exception $e) {
			$this->getDI()->getShared('logger')->log($e->getMessage());
			throw $e;
		}

		return $lots;
	}

	public function getNewCuringData($startDate, $endDate, $warehouseId = NULL, $roomPid = NULL, $ownedBy = NULL) {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		$custId = $this->CustomerID;

		if (!$custId) return false;

		$dateClause = "UNIX_TIMESTAMP(DateIn) >= $startDate AND UNIX_TIMESTAMP(DateIn) <= $endDate";

		$params = array("$dateClause AND InventoryTypePID IN ('9BB1E25F-8E9F-457B-84FB-6ADC005EAE2E', '61D4197D-22EB-4B50-8D5F-B94C07CB6509')");

		$params['bind'] = [];

		if ($warehouseId && ($count = count($warehouseId))) {
			if ($count > 1) {
				$qmarks = array();
				foreach ($warehouseId as $i => $wid) {
					$qmarks[] = "?$i";
				}

				$params[0] .= " AND WarehouseID IN (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND WarehouseID = ?0";
			}

			$params['bind'] = $warehouseId;
		}

		if ($roomPid && ($count = count($roomPid))) {
			$offset = count($params['bind']);

			if ($count > 1) {
				$qmarks = array();
				foreach ($roomPid as $i => $wid) {
					$j = $offset + $i;
					$qmarks[] = "?$j";
				}

				$params[0] .= " AND RoomPID IN (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND RoomPID = ?$offset";
			}

			$params['bind'] = array_merge($params['bind'], $roomPid);
		} else // exclude LCD
		{
			$params[0] .= " AND RoomPID <> '73882D17-529F-4974-823D-93A5AD15AD9B'";
		}

		if ($ownedBy && ($count = count($ownedBy))) {
			$offset = count($params['bind']);

			if ($count > 1) {
				$qmarks = array();
				foreach ($ownedBy as $i => $wid) {
					$j = $offset + $i;
					$qmarks[] = "?$j";
				}

				$params[0] .= " AND OwnedBy IN (" . implode(',', $qmarks) . ")";
			} else {
				$params[0] .= " AND OwnedBy = ?$offset";
			}

			$params['bind'] = array_merge($params['bind'], $ownedBy);
		}

		$params['order'] = "0 + LotNumber";

		$lots = $this->getLot($params);

		return $lots;
	}

	private function getEDIPostFiltersString($postFilters) {
		$filterString = '';

		if (!$postFilters['showEmptyLots']) {
			$filterString .= ' AND I.Pieces > 0 ';
		}

		if ($postFilters['productCode']) {
			$filterString .= ' AND L.ProductCode LIKE \'%' . $postFilters['productCode'] . '%\' ';
		}

		if ($postFilters['custLotNumber']) {
			$filterString .= ' AND V.CustomerLotNumber LIKE \'%' . $postFilters['custLotNumber'] . '%\' ';
		}

		return $filterString;
	}
}
