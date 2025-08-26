<?php

use Phalcon\Mvc\Controller;

class SearchController extends Controller {
	public function quicksearchAction() {
		$data = array();

		// query lot numbers
		$conditions = "LotNumber LIKE :LotNumber: ";
		$val = $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"LotNumber" => $val
		);

		$results = Lot::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'LotID, LotNumber'
		));

		foreach ($results as $d) {
			array_push($data, array(
				'id' => $d->LotID,
				'name' => 'Lot: ' . $d->LotNumber,
				'type' => 'lot'
			));
		}

		// query lot numbers
		$conditions = "CustomerPONumber LIKE :PONumber: ";
		$val = $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"PONumber" => $val
		);

		$results = Lot::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'LotID, LotNumber, CustomerPONumber'
		));

		foreach ($results as $d) {
			array_push($data, array(
				'id' => $d->LotID,
				'name' => 'PO #: ' . $d->CustomerPONumber . ' in Lot ' . $d->LotNumber,
				'type' => 'lot'
			));
		}

		// query customers
		$conditions = "Name LIKE :Name: and Active = :Active: AND Name NOT LIKE :NotName:";
		$val = '%' . $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"Name" => $val,
			"Active" => 1,
			"NotName" => 'Z - VOID%'
		);

		$results = Customer::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'CustomerID, Name'
		));

		foreach ($results as $d) {
			array_push($data, array(
				'id' => $d->CustomerID,
				'name' => 'Customer: ' . $d->Name,
				'type' => 'customer'
			));
		}

		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function searchallAction() {
		$selectTab = $this->request->getQuery("tab");
		$lot = $this->request->getQuery("lot");
		$autosearch = $this->request->getQuery("autosearch") && $selectTab ? TRUE : FALSE;

		$lotModel = new Lot();

		$lotVendors		= $lotModel->getLotVendors();
		$lotFactories	= $lotModel->getLotFactories();
		$lotWarehouses	= $lotModel->getLotWarehouses();
		$lotTemps		= $lotModel->getSavedLotTemperatures();

		$inventoryTypes		= Parameter::getValuesForGroupId('13BB5520-1B36-4783-823B-F4C1A114BCB9');
		$offerStatuses		= Parameter::getValuesForGroupId('2AD6C035-FD2C-4553-AAA8-E2B983DF46C1');
		$lotDescriptions	= Parameter::getValuesForGroupId('40E74C81-EF36-4700-A38C-F39B64F7E7D1');
		$lotRooms			= Parameter::getValuesForGroupId('46AE19E9-0230-4306-AF82-D15C763C6673');

		$searchTemps = array();
		foreach ($lotTemps as $r)
			if (($temp	= trim($r['RoomTemp'])) && $r['RoomPID'])
				$searchTemps[$temp] = true;

		foreach ($lotRooms as $r)
			if (($temp = trim($r['Value2'])))
				$searchTemps[$temp] = true;

		ksort($searchTemps);

		$searchTemps = array_keys($searchTemps);

		$this->view->selectTab = $selectTab;
		$this->view->lot = $lot;
		$this->view->autosearch = $autosearch;
		$this->view->lotDescriptions = $lotDescriptions;
		$this->view->lotRooms = $lotRooms;
		$this->view->roomTemperatures = $searchTemps;
		$this->view->lotVendors = $lotVendors;
		$this->view->lotFactories = $lotFactories;
		$this->view->lotWarehouses = $lotWarehouses;
		$this->view->inventoryTypes = $inventoryTypes;
		$this->view->offerStatuses = $offerStatuses;
		$this->view->title = "CHEESE!";
	}

	public function customernameAction() {
		$data = array();

		$conditions = "Name LIKE :Name: AND Name NOT LIKE :NotName: ";
		$val = '%' . $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"Name" => $val,
			"NotName" => 'Z - VOID%'
		);

		$onlyActive = $this->request->getPost('onlyActive');
		if ($onlyActive) {
			$conditions .= ' and Active = :Active: ';
			$parameters['Active'] = 1;
		}

		$results = Customer::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'CustomerID, Name'
		));

		foreach ($results as $d) {
			array_push($data, array('id' => $d->CustomerID, 'name' => $d->Name));
		}
		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function vendornameAction() {
		$data = array();

		$conditions = "Name LIKE :Name: AND Name NOT LIKE :NotName: ";
		$val = '%' . $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"Name" => $val,
			"NotName" => 'Z%-%VOID%'
		);

		$results = Vendor::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'VendorID, Name'
		));

		foreach ($results as $d) {
			array_push($data, array('id' => $d->VendorID, 'name' => $d->Name));
		}
		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function factorynameAction() {
		$data = array();

		$conditions = "Name LIKE :Name: ";
		$val = '%' . $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"Name" => $val
		);

		$results = Factory::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'FactoryID, Name'
		));

		foreach ($results as $d) {
			array_push($data, array('id' => $d->FactoryID, 'name' => $d->Name));
		}
		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function lotnumberAction() {
		$data = array();

		// query lot numbers
		$conditions = "LotNumber LIKE :LotNumber: AND Archived = 0";
		$val = $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"LotNumber" => $val
		);

		$results = Lot::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'LotID, LotNumber'
		));

		foreach ($results as $d) {
			array_push($data, array('id' => $d->LotID, 'name' => $d->LotNumber, 'type' => 'lot'));
		}
		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	// TODO: Needs to be adjusted. This is for the autofill of the customer lot number
	public function edicustlotnumberAction() {
		$data = array();

		$ediKey = $this->dispatcher->getParam("id");

		$this->logger->log("edicustlotnumberAction ediKey");
		$this->logger->log($ediKey);

		$customer = Customer::getEDICustomer($ediKey);

		if (!$customer) {
			$this->logger->log("No EDI Customer");
			return;
		}

		// TODO: Remove hard-coded customer ID. This is the ID for LOL - Can be done later, this is just for inventory control with LOL
		// $customerID = 'A4F58F60-9827-4EC6-A6F5-ACF103A6C032';

		$customerLotNumber = $this->request->getPost('searchVal');

		$results = Vat::ediCustomerLotAutoFill($this->db, $customerLotNumber, $customer->CustomerID);

		$this->logger->log($results);

		foreach ($results as $d) {
			array_push($data, array('name' => $d['CustomerLotNumber'], 'type' => 'lot'));
		}

		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function ediproductcodeAction() {
		$data = array();

		$ediKey = $this->dispatcher->getParam("id");

		$this->logger->log("ediproductcodeAction ediKey");
		$this->logger->log($ediKey);

		$customer = Customer::getEDICustomer($ediKey);

		if (!$customer) {
			$this->logger->log("No EDI Customer");
			return;
		}

		// TODO: Remove hard-coded customer ID. This is the ID for LOL - Can be done later, this is just for inventory control with LOL
		// $customerID = 'A4F58F60-9827-4EC6-A6F5-ACF103A6C032';

		// query lot numbers
		$conditions = "ProductCode LIKE :ProductCode: AND Archived = 0 AND CustomerID = :CustomerID:";

		$val = '%' . $this->request->getPost('searchVal') . '%';

		$parameters = array(
			"ProductCode" => $val,
			"CustomerID" => $customer->CustomerID,
		);

		$results = Lot::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'DISTINCT ProductCode',
			'order' => 'ProductCode ASC'
		));

		foreach ($results as $d) {
			array_push($data, array('name' => $d->ProductCode, 'type' => 'product code'));
		}

		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function shippernumberAction() {
		$data = array();

		// query lot numbers
		$conditions = "ShipperNumber LIKE :ShipperNumber: ";
		$val = $this->request->getPost('searchVal') . '%';
		$parameters = array(
			"ShipperNumber" => $val
		);

		$results = BillOfLading::find(array(
			$conditions,
			"bind" => $parameters,
			'columns' => 'BOLID, ShipperNumber'
		));

		foreach ($results as $d) {
			array_push($data, array('id' => $d->BOLID, 'name' => $d->ShipperNumber));
		}
		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function lotsAction() {


		$lots = $this->lotsSearch();
		// $this->logger->log('num records = ' . count($lots));
		$this->view->lots = $lots;
		$this->view->pick("search/ajax/lotresults");
	}

	// INVENTORY: Report
	public function lotsSearch() {

		$AVAIL = InventoryStatus::STATUS_AVAILABLE;
		$SOLDUN = InventoryStatus::STATUS_SOLDUNSHIPPED;

		$IncludeSoldUnshipped = $this->request->getPost('IncludeSoldUnshipped');
		if ($IncludeSoldUnshipped) { // default is to take sold stuff out of inventory.
			$SOLDUN = 'ignore'; // make the status for soldun wrong so it finds no records. This this makes sold items 0 so nothing gets taken away.
		}

		$sql = "SELECT l.LotNumber, l.LotID, l.Cost, l.Pallets, v.Name AS VendorName, f.Name AS FactoryName, p.Value1 AS Description,
c.Name AS CustomerName, SUM(vt.Weight) AS VatWeight, SUM(vt.Pieces) AS VatPieces,
(SELECT MIN(MakeDate) FROM Vat WHERE LotID = l.LotID) AS MakeDate,
SUM(iavail.Pieces) - SUM(COALESCE( isold.Pieces, 0 )) AS AvailablePieces,
SUM(iavail.Weight) - SUM(COALESCE( isold.Weight, 0 )) AS AvailableWeight,
l.CustomerPONumber, l.ProductCode,
proom.Value1 AS RoomNumber,   IFNULL(l.RoomTemp, proom.Value2)  AS RoomTemp,  l.Site,
proom2.Value1 AS RoomNumber2, IFNULL(l.RoomTemp2, proom.Value2) AS RoomTemp2, l.Site2,
proom3.Value1 AS RoomNumber3, IFNULL(l.RoomTemp3, proom.Value2) AS RoomTemp3, l.Site3,
inventype.Value1 AS InventoryType, l.DateIn
FROM Lot l
LEFT JOIN Customer c ON l.CustomerID = c.CustomerID
LEFT JOIN Vendor v ON l.VendorID = v.VendorID
LEFT JOIN Factory f ON l.FactoryID = f.FactoryID
LEFT JOIN Parameter p ON l.DescriptionPID = p.ParameterID
LEFT JOIN Parameter proom ON l.RoomPID = proom.ParameterID
LEFT JOIN Parameter proom2 ON l.RoomPID2 = proom2.ParameterID
LEFT JOIN Parameter proom3 ON l.RoomPID3 = proom3.ParameterID
LEFT JOIN Parameter inventype ON l.InventoryTypePID = inventype.ParameterID
INNER JOIN Vat vt ON l.LotID = vt.LotID AND vt.pieces > 0
LEFT JOIN InventoryStatus iavail ON vt.VatID = iavail.VatID AND iavail.InventoryStatusPID = '{$AVAIL}'
LEFT OUTER JOIN InventoryStatus isold ON vt.VatID = isold.VatID AND isold.InventoryStatusPID = '{$SOLDUN}'
WHERE 1 = ? ";

		$params = array(1);

		$fields = array(
			'LotNumber',
			'CustomerID',
			'DescriptionPID',
			'VendorID',
			'FactoryID',
			'InventoryTypePID',
			'ProductCode'
		);

		foreach ($fields as $f) {
			$val = $this->request->getPost($f);
			if ($val) {
				$sql .= " AND l.$f = ? ";
				array_push($params, $val);
			}
		}

		if (($Room = $this->request->getPost('RoomPID'))) {
			$Rooms = explode(",", $Room);
			$count = count($Rooms);

			if ($count > 0) {
				$qmarks = str_split(str_repeat("?", $count));
				$sql .= " AND l.RoomPID " . (
					($count == 1) ? " = ?" : " in (" .
					implode(',', str_split(str_repeat("?", $count))) .
					")"
				);

				foreach ($Rooms as $RoomPID)
					array_push($params, $RoomPID);
			}
		}

		if (($Temp = trim($this->request->getPost('RoomTemperature')))) {
			$sql .= " AND (l.RoomTemp = ? OR proom.Value2 = ?)";
			array_push($params, $Temp);
			array_push($params, $Temp);
		}

		$WarehouseID = $this->request->getPost('WarehouseID');

		if ($WarehouseID && ($count = count($WarehouseID))) {
			if ($count > 1) {
				// create a string array of the right number of ?-marks
				$qmarks = str_split(str_repeat("?", $count));
				$sql .= " AND l.WarehouseID in (" . implode(',', $qmarks) . ") ";
			} else {
				$sql .= "AND l.WarehouseID = ? ";
			}

			foreach ($WarehouseID as $id)
				array_push($params, $id);
		}

		$OwnedBy = $this->request->getPost('OwnedBy');

		if ($OwnedBy && ($count = count($OwnedBy))) {
			if ($count > 1) {
				// create a string array of the right number of ?-marks
				$qmarks = str_split(str_repeat("?", $count));
				$sql .= " AND l.OwnedBy in (" . implode(',', $qmarks) . ") ";
			} else {
				$sql .= "AND l.OwnedBy = ? ";
			}

			$sql .= "AND l.InventoryTypePID <> '85102C3F-33C1-482A-9750-4FB4A5EA7A7B' "; // AGING does not have Owned By

			foreach ($OwnedBy as $id)
				array_push($params, $id);
		}

		$CustomerPONumber = $this->request->getPost('CustomerPONumber');
		if ($CustomerPONumber) {
			$sql .= " AND l.CustomerPONumber LIKE ? ";
			$val = '%' . $CustomerPONumber . '%';
			array_push($params, $val);
		}

		$MakeDateFrom = $this->request->getPost('MakeDateFrom');
		if ($MakeDateFrom) {
			$MakeDateFrom = $this->utils->dbDate($MakeDateFrom);
			$sql .= " and vt.MakeDate >= ? ";
			array_push($params, $MakeDateFrom);
		}

		$MakeDateTo = $this->request->getPost('MakeDateTo');
		if ($MakeDateTo) {
			$MakeDateTo = $this->utils->dbDate($MakeDateTo);
			$sql .= " and vt.MakeDate <= ? ";
			array_push($params, $MakeDateTo);
		}

		$DateInFrom = $this->request->getPost('DateInFrom');
		if ($DateInFrom) {
			$DateInFrom = $this->utils->dbDate($DateInFrom);
			$sql .= " and l.DateIn >= ? ";
			array_push($params, $DateInFrom);
		}

		$DateInTo = $this->request->getPost('DateInTo');
		if ($DateInTo) {
			$DateInTo = $this->utils->dbDate($DateInTo);
			$sql .= " and l.DateIn <= ? ";
			array_push($params, $DateInTo);
		}

		$ShowArchived = $this->request->getPost('ShowArchived');
		if (!$ShowArchived) {
			$sql .= " and l.archived = 0";
		}

		$MaxItems = $this->request->getPost('MaxItems');
		if ($MaxItems) {
			$limit = $MaxItems;
		}

		$having = '';
		$OnlyAvailable = $this->request->getPost('OnlyAvailable');
		if ($OnlyAvailable && $OnlyAvailable == 'available') {
			$having = "\nHAVING (SUM(iavail.Pieces) - SUM(COALESCE( isold.Pieces, 0 )))> 0 ";  // INVENTORY: if soldunshiopped
		}

		$sortList = $this->request->getPost('sortList');
		// $this->logger->log($sortList);
		$orderBy = ($sortList ? $sortList . ', ' : '') . 'MakeDate DESC, l.LotNumber';

		$sql .= " GROUP BY l.LotNumber, l.LotID $having\nORDER BY $orderBy ";
		$connection = $this->db;
		$connection->connect();
		$lots = $connection->query($sql, $params);
		$lots->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$lots = $lots->fetchAll($lots);
		return $lots;
	}

	public function offersAction() {
		$offers = array();
		$connection = $this->db;
		$connection->connect();

		// these were switched out on 9/8/15 with the sum() subqueries below
		// at Diane's request so that weight/pieces were summed for the entire offer
		// min(OfferItem.Weight) as OfferWeight,
		// min(OfferItem.Pieces) as OfferPieces

		$params = array();
		$sql = "
			SELECT
			Customer.Name as CustomerName,
			Offer.OfferID,
			statusp.Value1 as Status,
			Offer.OfferDate,
			Offer.OfferExpiration,
			Offer.SaleDate,
			(select sum(Weight) from OfferItem where OfferID = Offer.OfferID) as OfferWeight,
			(select sum(Pieces) from OfferItem where OfferID = Offer.OfferID) as OfferPieces
			FROM Lot
			RIGHT OUTER JOIN Vat ON Lot.LotID = Vat.LotID
			RIGHT OUTER JOIN OfferItemVat ON Vat.VatID = OfferItemVat.VatID
			RIGHT OUTER JOIN (Parameter
				  RIGHT OUTER JOIN OfferItem ON Parameter.ParameterID = OfferItem.DescriptionPID )
			   ON OfferItemVat.OfferItemID = OfferItem.OfferItemID
			RIGHT OUTER JOIN (Customer
				  RIGHT OUTER JOIN (Parameter AS Parameter_1
						RIGHT OUTER JOIN Offer ON Parameter_1.ParameterID = Offer.TermsPID)
					ON Customer.CustomerID = Offer.CustomerID )
			   ON OfferItem.OfferID = Offer.OfferID
			LEFT OUTER JOIN Contact attention_contact ON attention_contact.ContactID = Offer.Attention
			RIGHT OUTER JOIN Parameter statusp ON Offer.OfferStatusPID = statusp.ParameterID
			WHERE 1 = 1"; //( OfferItemVat.Pieces > 0 ) ";
		// got rid of sum(OfferItemVat.EstPallets) as EstPallets

		$CustomerID = $this->request->getPost('CustomerID');
		if ($CustomerID) {
			$sql .= " AND Offer.CustomerID = ? ";
			array_push($params, $CustomerID);
		}

		$OfferStatusPID = $this->request->getPost('OfferStatusPID');
		if ($OfferStatusPID) {
			$sql .= " AND statusp.ParameterID = ? ";
			array_push($params, $OfferStatusPID);
		}

		$OfferDateFrom = $this->request->getPost('OfferDateFrom');
		if ($OfferDateFrom) {
			$OfferDateFrom = $this->utils->dbDate($OfferDateFrom);
			$sql .= " and Offer.OfferDate >= ? ";
			array_push($params, $OfferDateFrom);
		}

		$OfferDateTo = $this->request->getPost('OfferDateTo');
		if ($OfferDateTo) {
			$OfferDateTo = $this->utils->dbDate($OfferDateTo);
			$sql .= " and Offer.OfferDate <= ? ";
			array_push($params, $OfferDateTo);
		}

		$LotNumber = $this->request->getPost('LotNumber');
		if ($LotNumber) {
			$sql .= " and Lot.LotNumber = ? ";
			array_push($params, $LotNumber);
		}

		$WarehouseID = $this->request->getPost('WarehouseID');
		if ($WarehouseID && ($count = count($WarehouseID))) {
			if ($count > 1) {
				// create a string array of the right number of ?-marks
				$qmarks = str_split(str_repeat("?", $count));
				$sql .= " AND Lot.WarehouseID in (" . implode(',', $qmarks) . ") ";
			} else {
				$sql .= "AND Lot.WarehouseID = ? ";
			}
			foreach ($WarehouseID as $id)
				array_push($params, $id);
		}

		$sql .= "
			GROUP BY
				Customer.Name,
				Offer.OfferID,
				statusp.Value1,
				Offer.OfferDate,
				Offer.OfferExpiration,
				Offer.SaleDate
			ORDER BY
			  Offer.OfferDate desc";

		$limit = 100;
		$MaxItems = $this->request->getPost('MaxItems');
		if ($MaxItems) {
			$limit = $MaxItems;
		}

		$offers = $connection->query($sql, $params);
		$offers->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$offers = $offers->fetchAll($offers);
		// $this->logger->log('num records = ' . count($offers));

		$this->view->offers = $offers;
		// $this->logger->log('doing offer search');
		$this->view->pick("search/ajax/offerresults");
	}

	public function bolsAction() {
		// $this->logger->log('doing bol search');

		$sql = " select b.ShippedToName, b.ShipperNumber, b.SealNumber, b.CreateDate, b.BOLID
			from BillOfLading b
			WHERE 1 = :Dummy: ";
		$params = array("Dummy" => 1);
		$limit = 100;

		$fields = array('ShipperNumber');
		foreach ($fields as $f) {
			$val = $this->request->getPost($f);
			if ($val) {
				$sql .= " AND b.$f = :$f: ";
				$params[$f] = $val;
			}
		}

		$BOLDateFrom = $this->request->getPost('BOLDateFrom');
		if ($BOLDateFrom) {
			$BOLDateFrom = $this->utils->dbDate($BOLDateFrom);
			$sql .= " and b.BOLDate >= :BOLDateFrom: ";
			$params['BOLDateFrom'] = $BOLDateFrom;
		}

		$BOLDateTo = $this->request->getPost('BOLDateTo');
		if ($BOLDateTo) {
			$BOLDateTo = $this->utils->dbDate($BOLDateTo);
			$sql .= " and b.BOLDate <= :BOLDateTo: ";
			$params['BOLDateTo'] = $BOLDateTo;
		}

		$CreateDateFrom = $this->request->getPost('CreateDateFrom');
		if ($CreateDateFrom) {
			$CreateDateFrom = $this->utils->dbDate($CreateDateFrom);
			$sql .= " and b.CreateDate >= :CreateDateFrom: ";
			$params['CreateDateFrom'] = $CreateDateFrom;
		}

		$CreateDateTo = $this->request->getPost('CreateDateTo');
		if ($CreateDateTo) {
			$CreateDateTo = $this->utils->dbDate($CreateDateTo);
			$sql .= " and b.CreateDate <= :CreateDateTo: ";
			$params['CreateDateTo'] = $CreateDateTo;
		}

		$MaxItems = $this->request->getPost('MaxItems');
		if ($MaxItems) {
			$limit = $MaxItems;
		}

		$sql .= ' ORDER BY b.CreateDate DESC ';
		$sql .= ' LIMIT ' . $limit;
		// $this->logger->log($sql);
		// $this->logger->log($params);

		$bols = $this->modelsManager->executeQuery($sql, $params);
		// foreach ( $bols as $d ) {
		// 	$this->logger->log($d->LotNumber);
		// }
		// $this->logger->log('num records = ' . count($bols));

		$this->view->bols = $bols;
		$this->view->pick("search/ajax/bolresults");
	}
}
