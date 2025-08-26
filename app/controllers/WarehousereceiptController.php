<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class WarehousereceiptController extends Controller {
	public function saveAction() {
		if ($this->request->isPost() == true) {
			$this->logger->log($_POST);
			$success = 1;

			$ReceiptID = $this->request->getPost('ReceiptID');

			if (!$ReceiptID) {
				$ReceiptID = 0;
			}

			$receipt = WarehouseReceipt::findFirst("ReceiptID = '$ReceiptID'");

			if (!$receipt) { // add new receipt
				$receipt = new WarehouseReceipt();

				$ReceiptID = $this->UUID;
				$receipt->ReceiptID = $ReceiptID;
				$receipt->CreateDate = $this->mysqlDate;
				$receipt->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
			} else { // update existing receipt
				$receipt->UpdateDate = $this->mysqlDate;
				$receipt->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}

			if ($this->request->hasPost('ReceiptDate')) {
				$receipt->ReceiptDate = $this->utils->dbDate($this->request->getPost('ReceiptDate'));
			} else if ($this->request->hasPost('lotdatein')) {
				$receipt->ReceiptDate = $this->utils->dbDate($this->request->getPost('lotdatein'));
			} else {
				$receipt->ReceiptDate = $this->mysqlDate;
			}

			if ($receipt->save() == false) {
				$this->logger->log('FAIL!');
				$msg = "Error saving receipt:\n\n" . implode("\n", $receipt->getMessages());
				$this->logger->log("$msg\n");
				$success = 0;
			}

			if ($success) {
				$lots = array();

				// If we have a default lot, add it to the list to make sure it gets added
				if ($this->request->getPost('defaultLot')) {
					array_push($lots, $this->request->getPost('defaultLot'));
				}

				$postLots = $this->request->getPost('Lots') ?? [];

				foreach ($postLots as $postLot) {
					array_push($lots, $postLot);
				}

				$append = $this->request->hasPost('append') && $this->request->getPost('append') == 'true';

				// Just to make sure that some lots are coming back. Can't have a receipt with no lots.
				if (!count($lots)) {
					$this->logger->log('FAIL!');
					$msg = "Error saving receipt: No lots specified!";
					$this->logger->log("$msg\n");
					$success = 0;
				}

				$sort = $append ? WarehouseReceiptItem::maximum(["ReceiptID = '$ReceiptID'", 'column' => 'Sort']) : 0;
				$lotIDs = [];

				$useLotNumbers = $this->request->getPost('useField') == 'lotNumber';

				foreach ($lots as $lookup) {

					if (!$lookup) continue;

					if ($useLotNumbers) {
						$lot = Lot::findFirst("LotNumber = $lookup");
					} else {
						$lot = Lot::findFirst("LotID = '$lookup'");
					}

					// push id to array
					$lotIDs[] = $lot->LotID;

					if (!$lot) {
						$this->logger->log('FAIL!');
						$msg = "Error saving lot to receipt: Lot " . "$lookup " . "not found";
						$this->logger->log("$msg\n");
						$success = 0;
						continue;
					}

					if (isset($receipt->CustomerID) && $receipt->CustomerID != $lot->CustomerID) {
						$this->logger->log('CustomerID mismatch');
						$msg = "That receipt is for a different customer";
						$this->logger->log("$msg\n");
						$success = 0;
						continue;
					}

					if (!isset($receipt->CustomerID) || !isset($receipt->Handling) || !isset($receipt->Storage)) {
						$this->logger->log('storing Customer, Handling and/or Storage');
						if (!isset($receipt->CustomerID)) {
							$receipt->CustomerID = $lot->CustomerID;
						}
						if (!isset($receipt->Handling)) {
							$receipt->Handling = $lot->Handling;
							$receipt->HandlingUnit = $lot->HandlingUnit;
						}
						if (!isset($receipt->Storage)) {
							$receipt->Storage = $lot->Storage;
							$receipt->StorageUnit = $lot->StorageUnit;
						}

						$receipt->save();
					}

					$avail = $lot->getLotBalances();

					$item = WarehouseReceiptItem::findFirst("LotID = '$lot->LotID' AND ReceiptID = '$ReceiptID'");

					if ($item) {
						$this->logger->log('Lot was already there, but going ahead anyway');
					} else {
						$this->logger->log('Lot was not there, creating new item');
						$item = new WarehouseReceiptItem();

						$item->ReceiptItemID = $this->utils->UUID(mt_rand(0, 65535));
						$item->ReceiptID = $ReceiptID;
						$item->LotID = $lot->LotID;
						$item->WarehouseReceipt = $receipt;
						$item->Pieces = $avail['Pieces'];
						$item->Weight = $avail['Weight'];
					}

					$item->Sort = $sort;
					$sort++;

					if ($success) {
						if ($item->save() == false) {
							$this->logger->log('FAIL!');
							$msg = "Error saving receipt item:\n\n" . implode("\n", $item->getMessages());
							$this->logger->log("$msg\n");
							$success = 0;
						}
					}
				}

				if (!$append) {
					$notIn = "NOT IN ('" . implode("', '", $lotIDs) . "')";
					$deletedItems = WarehouseReceiptItem::find("ReceiptID = '$ReceiptID' AND LotID $notIn");
					if ($deletedItems->valid()) {
						$this->logger->log('deleting items');
						$deletedItems->delete();
					}
				}
			}

			$this->view->data = array(
				'success' => $success,
				'status' => ($success ? 'success' : 'error'),
				'msg' => $msg,
				'ReceiptID' => $ReceiptID,
				'ReceiptNumber' => $receipt->ReceiptNumber
			);
			$this->view->pick("layouts/json");
		}
	}

	/*
	PARAMS:
	[id] => [Warehouse Receipt ID]
    [printWithCert] => 0 or 1
	*/
	public function printAction() {
		$receiptId = $this->dispatcher->getParam("id");

		$receipt = WarehouseReceipt::findFirst("ReceiptID = '$receiptId'");

		if (!$receipt) {
			$this->logger->log('Receipt not found: ' . $receiptId);
			return $this->response->redirect('');
		}

		$receiptData = $receipt->toArray();

		$receiptData['ReceiptDate'] = $this->utils->slashDate($receiptData['ReceiptDate']);

		$items = $receipt->getWarehouseReceiptItem(['order' => 'Sort']);
		$lots = $receipt->getLot(['order' => 'Sort']);

		if (!$receipt->Handling || !$receipt->Storage) {
			$this->logger->log('storing Handling and/or Storage');

			if (!$receipt->Handling) {
				$receipt->Handling = $lots[0]->Handling;
				$receipt->HandlingUnit = $lots[0]->HandlingUnit;
			}

			if (!$receipt->Storage) {
				$receipt->Storage = $lots[0]->Storage;
				$receipt->StorageUnit = $lots[0]->StorageUnit;
			}

			$receipt->save();
		}

		$itemsArray = array();

		foreach ($items as $item) {
			array_push($itemsArray, $item);
		}

		$items = $itemsArray;

		//$lots = $lots->toArray();

		$description = $items[0]->Lot->Description ?: 'NO DESCRIPTION AVAILABLE';
		$factoryName = $lots[0]->factory->Name ?? 'NO FACTORY NAME AVAILABLE';

		$this->view->receiptData = $receiptData;
		$this->view->items = $items;
		$this->view->possibleLots = $receipt->getPossibleLots();
		$this->view->description = $description;
		$this->view->factoryName = $factoryName;
		$this->view->lots = $lots->toArray();
		$this->view->customerName = $receipt->getCustomer()->Name;
		$this->view->handling = $receipt->Handling;
		$this->view->handlingUnit = $receipt->HandlingUnit;
		$this->view->storage = $receipt->Storage;
		$this->view->storageUnit = $receipt->StorageUnit;
	}
}
