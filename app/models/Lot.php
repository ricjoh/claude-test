<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class Lot extends Model
{

	public const STATUS_EXISTING = '103A03A1-9B62-4FD1-BFDD-32C2C0026415';
	public const STATUS_DRAFT  = 'BABA841F-7D26-4A4D-8342-897F4F500B64';
	public const STATUS_TRANSFERRED = '0F409430-F083-4CCE-868F-BE85B5B906EA';

	public const UNIT_PALLET = 'pallet';
	public const UNIT_LB = 'lb';

	public $LotID;
	public $LotNumber;
	public $DateIn;
	public $DescriptionPID;
	public $VendorID;
	public $FactoryID;
	public $InventoryTypePID;
	public $CustomerID;
	public $Cost;
	public $FirstMonthRate;
	public $AdditionalMonthRate;
	public $RoomPID;
	public $Handling;
	public $HandlingUnit;
	public $Storage;
	public $StorageUnit;
	public $Pallets;
	public $BillingFrequency;
	public $CustomerPONumber;
	public $ProductCode; // PartNum / ProdNum
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;
	public $Site;
	public $RoomPID2;
	public $RoomPID3;
	public $Site2;
	public $Site3;
	public $NoteText;
	public $WarehouseID;
	public $OwnedBy;
	public $TempChangeDate;
	public $RoomTemp;
	public $RoomTemp2;
	public $RoomTemp3;
	public $StatusPID;
	public $Transferred;
	public $TransferredFrom;
	public $DeliveryID;
	public $Archived;

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("Lot");
		$this->hasMany("LotID", "Vat", "LotID");
		$this->hasMany("LotID", "OfferItem", "LotID");
		$this->hasMany("LotID", "WarehouseReceiptItem", "LotID");
		$this->hasManyToMany("LotID", "WarehouseReceiptItem", "LotID", "ReceiptID", "WarehouseReceipt", "ReceiptID");
		$this->belongsTo("CustomerID", "Customer", "CustomerID");
		$this->belongsTo("VendorID", "Vendor", "VendorID");
		$this->belongsTo("FactoryID", "Factory", "FactoryID");
		$this->belongsTo('WarehouseID', 'Warehouse', 'WarehouseID');
		$this->belongsTo('OwnedBy', 'Warehouse', 'WarehouseID', ['alias' => 'OwnedBy']);
	}

	public static function getDb()
	{
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getLogger()
	{
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}

	public static function getLocation($LotID)
	{
		$db = self::getDb();
		$db->connect();
		$sql = "SELECT Value1 AS Room, Site
				FROM Lot LEFT JOIN Parameter p
				ON RoomPID = ParameterId
				WHERE LotID = ?";

		$params = array($LotID);

		try {
			$results = $db->query($sql, $params);
			$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
			$results = $results->fetchAll($results);
		} catch (\Exception $e) {
			$logger->log('Exception in getLotsByStatus: ' . $e->getMessage());
			throw $e;
		}
		$location = $results[0]['Room'];
		if ($results[0]['Site']) $location .= ' | ' . $results[0]['Site'];
		return $location;
	}


	public static function getLotsByStatus($statusPID)
	{
		$db = self::getDb();
		$db->connect();
		$logger = self::getLogger();

		$sql = "SELECT l.LotNumber, l.LotID, p.Value1 as Description, c.Name as CustomerName,
l.CustomerPONumber, l.ProductCode,	l.DateIn, d.EDIDocID, l.TransferredFrom,
COUNT( v.VatID ) AS Vats,  SUM(v.Weight) AS Weight, SUM(v.Pieces) AS Pieces
	FROM Vat v, Lot l
	LEFT JOIN Customer c ON l.CustomerID = c.CustomerID
	LEFT JOIN Parameter p ON l.DescriptionPID = p.ParameterID
	LEFT JOIN Delivery d ON l.DeliveryID = d.DeliveryID
WHERE l.LotID = v.LotID
AND l.StatusPID = ?
GROUP BY l.LotNumber, l.LotID, Description, EDIDocID, CustomerName, CustomerPONumber, ProductCode, DateIn
ORDER BY EDIDocID DESC, DateIn DESC, CustomerPONumber DESC";

		$statusPID = $statusPID ?: self::STATUS_DRAFT;

		$params = array($statusPID);
		try {
			$results = $db->query($sql, $params);
			$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
			$results = $results->fetchAll($results);
		} catch (\Exception $e) {
			$logger->log('Exception in getLotsByStatus: ' . $e->getMessage());
			throw $e;
		}

		return $results;
	}


	// o                             o
	// 8                             8
	// 8 odYo. o    o .oPYo. odYo.  o8P .oPYo. oPYo. o    o
	// 8 8' `8 Y.  .P 8oooo8 8' `8   8  8    8 8  `' 8    8
	// 8 8   8 `b..d' 8.     8   8   8  8    8 8     8    8
	// 8 8   8  `YP'  `Yooo' 8   8   8  `YooP' 8     `YooP8
	// ....::..::...:::.....:..::..::..::.....:..:::::....8
	// ::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
	// ::::::::::::::::::::::::::::::::::::::::::::::::...::

	/********************
	DROP TABLE IF EXISTS `LotLastPicked`;
	CREATE TABLE `LotLastPicked` (
		`CustomerID` VARCHAR(64) NOT NULL,
		`ProductCode` VARCHAR(32) NOT NULL,
		`LotID` VARCHAR(64) NOT NULL,
		`LotNumber` VARCHAR(64),
		`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (CustomerID,ProductCode),
	KEY LotID (LotID)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	*******************/

	public static function setLastPicked($LotID, $ProductCode, $CustomerID, $LotNumber = null)
	{
		$db = self::getDb();
		$db->connect();
		$sql = "DELETE FROM LotLastPicked WHERE ProductCode = ? AND CustomerID = ?";
		$params = array($ProductCode, $CustomerID);
		$db->query($sql, $params);
		$sql = "INSERT INTO LotLastPicked (LotID, ProductCode, CustomerID, LotNumber) VALUES (?, ?, ?, ?)";
		$params = array($LotID, $ProductCode, $CustomerID, $LotNumber);
		$db->query($sql, $params);
	}

	// INVENTORY: Used only by Create Pending Offer (@7/23/2024)

	public static function getLotsbyCustProdNum($CustomerID, $ProductCode, $removeSoldFromAvail = true)
	{
		global $dateSort;
		$dateSort = [];

		if (!function_exists("makeDateSortLot")) {
			function makeDateSortLot($v1, $v2)
			{
				return strcmp($v1["MakeDate"] . $v1["LotNumber"], $v2["MakeDate"] . $v2["LotNumber"]);
			}
		}

		if (!function_exists("makeDateLotSortLot")) {
			function makeDateLotSortLot($v1, $v2)
			{
				global $dateSort;
				return strcmp($dateSort[$v1["LotNumber"]].$v1["MakeDate"], $dateSort[$v2["LotNumber"]].$v2["MakeDate"]);
			}
		}


		$db = self::getDb();
		$db->connect();

		$logger = self::getLogger();
		$retval = array();

		// StatusPID = PID "Existing" Lot Status
		$sql_lots = "SELECT l.LotNumber, l.LotID, l.DescriptionPID, l.ProductCode, IF ( ISNULL(llp.ProductCode), 1, 0) as LastPicked
			FROM Lot l LEFT OUTER JOIN LotLastPicked llp ON llp.LotID = l.LotID  AND llp.CustomerID = ? AND llp.ProductCode = ?
			WHERE l.StatusPID = '" . self::STATUS_EXISTING . "'" .
			"	AND l.CustomerID = ?
				AND l.ProductCode = ?
			GROUP BY l.LotNumber, l.LotID, l.DescriptionPID, l.ProductCode, llp.ProductCode";

		$params = array($ProductCode, $CustomerID, $CustomerID, $ProductCode);
		$lots = $db->query($sql_lots, $params);
		$lots->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$lots = $lots->fetchAll($lots);


		foreach ($lots as $lot) {

			// returns avail - offered.
			$sql_vats = "SELECT i.InventoryStatusPID, v.VatID, v.CustomerLotNumber,
					v.LotID, v.VatNumber, v.MakeDate, v.DeliveryDetailID,
					i.Pieces as PAvail, i2.Pieces as POffered,
					i.Weight as WAvail, i2.Weight as WOffered,
					i.Pieces - i2.Pieces AS Pieces,
					i.Weight - i2.Weight AS Weight
				FROM Vat AS v, InventoryStatus AS i, InventoryStatus AS i2
				WHERE i.VatID = v.VatID AND i2.VatID = v.VatID
					AND i.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'" .
				"AND i2.InventoryStatusPID = '" . InventoryStatus::STATUS_OFFERED . "'" .
				"AND v.LotID = ?
				AND i.Pieces - i2.Pieces > 0
				ORDER BY v.LotID, v.MakeDate ASC";

			$params = array($lot['LotID']);
			$vats = $db->query($sql_vats, $params);
			$vats->setFetchMode(Phalcon\Db::FETCH_ASSOC);
			$vats = $vats->fetchAll($vats);
			$hasPieces = false;

			$earliestMakeDate = '30000'; // greater than '2024-*'
			// assemble things into $retval
			foreach ($vats as $vat) {
				if ($vat['Pieces'] > 0) {
					if ($removeSoldFromAvail) {
						$sold = Vat::getVatSoldUnshipped($vat['VatID']);
						$vat['SoldPieces'] = $sold[0]['SoldPieces'];
						$vat['SoldWeight'] = $sold[0]['SoldWeight'];
						$vat['PAvail'] -= $vat['SoldPieces'];
						$vat['WAvail'] -= $vat['SoldWeight'];
						// NOTE: Offered covers Sold/Unshipped. This should not happen.
						if ($vat['POffered'] < $vat['SoldPieces']) {
							$vat['Pieces'] -= ($vat['SoldPieces'] - $vat['POffered']);
							$vat['Weight'] -= ($vat['SoldWeight'] - $vat['WOffered']);
						}
					}
					if ($vat['Pieces'] > 0) {
						if ($vat['MakeDate'] < $earliestMakeDate) $earliestMakeDate = $vat['MakeDate'];
						array_push($retval, array_merge($lot, $vat));
						$hasPieces = true;
					}
				}
			}
			if ( count($vats) && $hasPieces ) $dateSort[$lot['LotNumber']] = $lot['LastPicked'] . str_replace( '00:00:00.000000',$lot['LotNumber'], $earliestMakeDate );
		}

		$logger->log($dateSort);

		usort($retval, 'makeDateLotSortLot');

		$logger->log("getLotsbyCustProdNum");
		// $logger->log($retval);
		return $retval;
	}


	//                 o       .oo                o 8        8      8        o     o          o
	//                 8      .P 8                  8        8      8        8     8          8
	// .oPYo. .oPYo.  o8P    .P  8 o    o .oPYo. o8 8 .oPYo. 8oPYo. 8 .oPYo. 8     8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8   8    oPooo8 Y.  .P .oooo8  8 8 .oooo8 8    8 8 8oooo8 `b   d' .oooo8   8  Yb..
	// 8    8 8.       8   .P    8 `b..d' 8    8  8 8 8    8 8    8 8 8.      `b d'  8    8   8    'Yb.
	// `YooP8 `Yooo'   8  .P     8  `YP'  `YooP8  8 8 `YooP8 `YooP' 8 `Yooo'   `8'   `YooP8   8  `YooP'
	// :....8 :.....:::..:..:::::..::...:::.....::....:.....::.....:..:.....::::..::::.....:::..::.....:
	// ::ooP'.::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::...::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	public function getAvailableVats($removeSoldFromAvail = true)
	{
		$sql = "SELECT v.VatID, v.CustomerLotNumber, v.LotID, v.VatNumber,
					v.MakeDate, v.Moisture, v.FDB, v.PH, v.Salt,
					i.Pieces, i.Weight, v.DeliveryDetailID
				FROM
					Vat AS v,
					InventoryStatus AS i
				WHERE i.VatID = v.VatID
					AND i.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'\n" .
			"	AND v.LotID = '$this->LotID'
					AND i.Pieces > 0
				ORDER BY v.MakeDate, 0+v.VatNumber, v.VatNumber, v.CustomerLotNumber";

		$vats = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		$newvats = array(); // can't modify $vats directly in situ

		foreach ($vats as $vat) {
			if ($removeSoldFromAvail) {
				$sold = Vat::getVatSoldUnshipped($vat->VatID);
				$vat->SoldPieces = $sold[0]['SoldPieces'];
				$vat->SoldWeight = $sold[0]['SoldWeight'];
				$vat->Pieces -= $vat->SoldPieces;
				$vat->Weight -= $vat->SoldWeight;
			}
			if ($vat->Pieces > 0.001) $newvats[] = $vat;
		}

		return $newvats;
	}

	public static function archive()
	{
		$sql = "CREATE TEMPORARY TABLE lotids
					SELECT
				DISTINCT MAX(l.LotID) as LotID
					FROM
						Lot AS l
						INNER JOIN Vat vt ON l.LotID = vt.LotID AND vt.pieces > 0
						LEFT JOIN InventoryStatus ist ON vt.VatID = ist.VatID and ist.InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'
				LEFT JOIN OfferItem oi ON oi.LotID = l.LotID
				LEFT JOIN Offer o ON o.OfferID = oi.OfferID
					WHERE
				l.Archived <> 1
					GROUP BY
						l.LotNumber
					HAVING
						sum(ist.Pieces) <= 0
		        AND max(o.OfferDate) < DATE_SUB(NOW(), INTERVAL 2 YEAR);

			UPDATE Lot, lotids
			SET Lot.Archived = 1
			WHERE Lot.LotID = lotids.LotID";

		self::getDb()->query($sql);

		exit;
	}

	public function getLotNumbers()
	{
		$sql = "select distinct LotNumber from Lot";
		$lotNumbers = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $lotNumbers;
	}

	public function getLotVendors()
	{
		$sql = "select distinct v.Name, v.VendorID from Vendor v Inner Join Lot l on v.VendorID = l.VendorID where v.Active = 1 order by v.name";
		$vendors = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $vendors;
	}

	public function getSavedLotTemperatures()
	{
		$sql = "select RoomTemp, group_concat(DISTINCT RoomPID) as RoomPID " .
			"from Lot where RoomTemp IS NOT NULL " .
			"group by RoomTemp";

		$temperatures = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $temperatures;
	}

	public function getLotFactories()
	{
		$sql = "select distinct f.Name, f.FactoryID from Factory f Inner Join Lot l on f.FactoryID = l.FactoryID where f.Active = 1 order by f.name";
		$factories = $this->getDI()->getShared('modelsManager')->executeQuery($sql);
		return $factories;
	}

	public function getLotWarehouses()
	{
		return $this->getDI()->getShared('modelsManager')->executeQuery(
			"select distinct w.Name, w.WarehouseID " .
				"from Warehouse w " .
				"inner join Lot l on w.WarehouseID = l.WarehouseID " .
				"order by w.name"
		);
	}

	public function getLotWarehouseLookup()
	{
		$rows = $this->getDI()->getShared('modelsManager')->executeQuery(
			"select distinct w.Name, w.WarehouseID " .
				"from Warehouse w " .
				"inner join Lot l on w.WarehouseID = l.WarehouseID " .
				"order by w.name"
		)->toArray();

		$data = array();
		foreach ($rows as $r) {
			$data[$r['WarehouseID']] = $r['Name'];
		}

		return $data;
	}


	// 8          o       .oo          o   o         o   o
	// 8          8      .P 8          8                 8
	// 8 .oPYo.  o8P    .P  8 .oPYo.  o8P o8 o    o o8  o8P o    o
	// 8 8    8   8    oPooo8 8    '   8   8 Y.  .P  8   8  8    8
	// 8 8    8   8   .P    8 8    .   8   8 `b..d'  8   8  8    8
	// 8 `YooP'   8  .P     8 `YooP'   8   8  `YP'   8   8  `YooP8
	// ..:.....:::..:..:::::..:.....:::..::..::...:::..::..::....8
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
	// :::::::::::::::::::::::::::::::::::::::::::::::::::::::...::

	public function getLotActivity()
	{
		$db = self::getDb();
		$db->connect();

		$sql = "SELECT o.OfferID,
					   o.OfferDate,
					   o.FOB,
					   o.OfferExpiration,
					   o.Note,
					   o.SaleDate,
					   o.CustomerID,
					   o.OfferStatusPID,
					   p.Value1 AS Status,
					   oi.Pieces,
					   oi.Pallets,
					   oi.Weight,
					   oi.MakeDate,
					   oi.NoteText,
					   oi.Cost,
					   c.Name AS CustomerName,
					   bol.BOLID,
					   bol.ShipperNumber,
					   bol.StatusPID AS BOLStatusPID
				   FROM Offer AS o
					   LEFT JOIN OfferItem AS oi ON o.OfferID = oi.OfferID
					   LEFT JOIN Customer AS c ON c.CustomerID = o.CustomerID
					   LEFT JOIN Parameter AS p ON p.ParameterID = o.OfferStatusPID
					   LEFT JOIN BillOfLading AS bol ON bol.OfferID = o.OfferID
				   WHERE oi.LotID = ?
				   ORDER BY oi.CreateDate";

		$params = array($this->LotID);

		try {
			$result_set = $db->query($sql, $params);
			$result_set->setFetchMode(Phalcon\Db::FETCH_ASSOC);
			$result_set = $result_set->fetchAll($result_set);
		} catch (\Exception $e) {
			$this->getDI()->getShared('logger')->log($e->getMessage());
			throw $e;
		}
		return $result_set;
	}

	/************************************************* */

	public function getLotBalancesAtDate($date, $isNewLot = false, $removeSoldFromAvail = true)
	{
		$availPieces = 0;
		$availWeight = 0;

		$soldPallets = 0;
		$soldPieces = 0;
		$soldWeight = 0;

		$pallets = $this->Pallets;

		// If the lot is new, then we are going off of the original balances
		if ($isNewLot) {
			$availVats = Vat::find("LotID = '" . $this->LotID . "'");
		} else {
			$availVats = $this->getAvailableVats($removeSoldFromAvail);
		}

		// Gives us the lot balances at start
		foreach ($availVats as $vat) {
			$availPieces += $vat->Pieces;
			$availWeight += $vat->Weight;
		}

		// If it is NOT a new Lot, then we take Offers into account
		if ($isNewLot === false) {
			$date = date('Y-m-d 00:00:00.000000', $date);

			$sql = "SELECT
					SUM(oi.Pieces) Pieces,
					SUM(oi.Weight) Weight,
					SUM(oi.Pallets) Pallets
				FROM
					Offer o
					JOIN OfferItem oi ON oi.OfferID = o.OfferID
				WHERE
					o.OfferStatusPID in ('C5F3B7B9-9340-46B4-820A-504AC2A98A00', 'D05C2544-E8BC-45C9-A3C8-3C7E3AD3F831')
					AND o.OfferDate > :offerdate:
					AND oi.LotID = :lotid:";

			$params = array('lotid' => $this->LotID, 'offerdate' => $date);

			$sold = $this->getDI()->getShared('modelsManager')->executeQuery($sql, $params)[0];

			$soldPieces = $sold->Pieces;
			$soldWeight = $sold->Weight;
			$soldPallets = $sold->Pallets;
			if ($this->LotID == '78ABE21A-09BC-437B-A1CF-196118B6E397') {
				$this->getDI()->getShared('logger')->log("SoldPieces: " . $soldPieces . " SoldWeight: " . $soldWeight);
			}

			$pallets = $this->getAvailablePallets() + $soldPallets;
		}

		return [
			'Pieces'  => $availPieces + $soldPieces,
			'Weight'  => $availWeight + $soldWeight,
			'Pallets' => $pallets
		];
	}

	public function getLotBalances($removeSoldFromAvail = true)
	{
		$availVats = $this->getAvailableVats($removeSoldFromAvail);

		$availPieces = 0;
		$availWeight = 0;

		foreach ($availVats as $vat) {
			$availPieces += $vat->Pieces;
			$availWeight += $vat->Weight;
		}

		return [
			'Pieces' => $availPieces,
			'Weight' => $availWeight
		];
	}

	public function getDescription()
	{
		return Parameter::getValue($this->DescriptionPID);
	}

	public function getPieces()
	{
		$pieces = 0;

		foreach ($this->vat as $vat) $pieces += $vat->Pieces;

		return $pieces;
	}

	public function getWeight()
	{
		$weight = 0;

		foreach ($this->vat as $vat) $weight += $vat->Weight;

		return $weight;
	}



	public function getPossibleReceipts()
	{
		$limit = 10;

		$possibleReceipts = WarehouseReceipt::query()
			->columns(
				"DISTINCT " .
					"WarehouseReceipt.ReceiptID, " .
					"WarehouseReceipt.ReceiptNumber, " .
					"WarehouseReceipt.ReceiptDate, " .
					"WarehouseReceipt.CreateDate, " .
					"WarehouseReceipt.CreateId, " .
					"WarehouseReceipt.UpdateDate, " .
					"WarehouseReceipt.UpdateId "
			)->innerJoin(
				"WarehouseReceiptItem",
				"WarehouseReceiptItem.ReceiptID = WarehouseReceipt.ReceiptID"
			)->innerJoin(
				"Lot",
				"Lot.LotID = WarehouseReceiptItem.LotID"
			)->where(
				"Lot.CustomerID = :CustomerID: and WarehouseReceiptItem.LotID <> :ThisLotID:",
				[
					"CustomerID"	=> $this->CustomerID,
					"ThisLotID"		=> $this->LotID
				]
			)->orderBy(
				"WarehouseReceipt.ReceiptDate DESC"
			)->limit(
				$limit
			)->execute();

		return $possibleReceipts;
	}

	// Gets the number of pallets being used by a given Lot
	public function getUsedPallets()
	{
		$soldStatus = Offer::STATUS_SOLD;  // TODO: INVENTORY: Pallets
		$shippedStatus = Offer::STATUS_SHIPPED;  // TODO: INVENTORY: Pallets

		$sql = "SELECT
					SUM(oi.Pallets) as palletSum
				FROM
					OfferItem oi
					LEFT JOIN Offer o ON o.OfferID = oi.OfferID
				WHERE
					LotID = '$this->LotID'
					AND o.OfferStatusPID in ('$soldStatus', '$shippedStatus')";

		$sum = $this->getDI()->getShared('modelsManager')->executeQuery($sql);

		// If we get an empty set, then return 0
		return $sum[0]['palletSum'] ?? 0;
	}

	// Gets the number of Pallets still available for a given Lot
	public function getAvailablePallets()
	{
		// Return the difference between the number of lot pallets and used pallets
		return $this->Pallets - $this->getUsedPallets();
	}

	public static function getAvailablePalletsByLotID($lotID)
	{
		$lot = self::findFirst("LotID = '$lotID'");

		return $lot->getAvailablePallets();
	}

	public static function getUsedPalletsByLotID($lotID)
	{
		$soldStatus = Offer::STATUS_SOLD;  // TODO: INVENTORY: Pallets
		$shippedStatus = Offer::STATUS_SHIPPED;  // TODO: INVENTORY: Pallets

		$sql = "SELECT
			SUM(oi.Pallets) as palletSum
		FROM
			OfferItem oi
			LEFT JOIN Offer o ON o.OfferID = oi.OfferID
		WHERE
			LotID = '$lotID'
			AND o.OfferStatusPID in ('$soldStatus', '$shippedStatus')";

		$usedPallets = self::getDb()->query($sql);
		$usedPallets = $usedPallets->fetchAll($usedPallets);

		return $usedPallets[0]['palletSum'] ?? 0;
	}

	public static function getBillingFrequencyArray()
	{
		$sql =  "SELECT
					COLUMN_TYPE
				FROM
					information_schema.`COLUMNS`
				WHERE
					TABLE_NAME = 'Lot'
					AND COLUMN_NAME = 'BillingFrequency'";

		$enumString = self::getDb()->query($sql);
		$enumString = $enumString->fetchAll($enumString)[0][0];

		return explode(",", str_replace(array("enum(", ")", "'"), "", $enumString));
	}
}
