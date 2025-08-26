<?php

use Phalcon\Mvc\Controller;

class VatController extends Controller
{

	//             8      8 o     o          o
	//             8      8 8     8          8
	// .oPYo. .oPYo8 .oPYo8 8     8 .oPYo.  o8P
	// .oooo8 8    8 8    8 `b   d' .oooo8   8
	// 8    8 8    8 8    8  `b d'  8    8   8
	// `YooP8 `YooP' `YooP'   `8'   `YooP8   8
	// :.....::.....::.....::::..::::.....:::..:
	// :::::::::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::

	public function addvatAction()
	{
		if ($this->request->isPost() == true) {
			// begin a transaction
			$this->db->begin();
			$VatID = $this->utils->UUID(mt_rand(0, 65535));


			$vat = new Vat();
			$vat->VatID = $VatID;
			$vat->CreateDate = $this->mysqlDate;
			$vat->CreateId = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in
			$vat->LotID = $this->request->getPost('LotID');
			$mdate = '';
			if (!empty($this->request->getPost('MakeDate'))) { // else leave it null
				$vat->MakeDate = $this->utils->dbDate($this->request->getPost('MakeDate'));
			}

			// If the date entered is over 100 years ago
			if (strtotime($vat->MakeDate) < strtotime("-100 years")) {
				$badMakeDate = "The Make Date entered was " . $this->request->getPost('MakeDate') . ".";
				$this->logger->log($badMakeDate);
				$this->view->data = array('success' => 0, 'msg' => "$badMakeDate Please enter a valid Make Date.");
				$this->view->pick("layouts/json");
				return;
			}
			$vat->Pieces = $this->request->getPost('Pieces');

			$VatNumber = $this->request->getPost('VatNumber');
			$vat->VatNumber = isset($VatNumber) ?  $VatNumber : '';


			$CustomerLotNumber = $this->request->getPost('CustomerLotNumber');
			if ($CustomerLotNumber) {
				$vat->CustomerLotNumber = $CustomerLotNumber;
			}

			$Moisture = $this->request->getPost('Moisture');
			if ($Moisture) {
				$vat->Moisture = $Moisture;
			}

			$FDB = $this->request->getPost('FDB');
			if ($FDB) {
				$vat->FDB = $FDB;
			}

			$PH = $this->request->getPost('PH');
			if ($PH) {
				$vat->PH = $PH;
			}

			$Salt = $this->request->getPost('Salt');
			if ($Salt) {
				$vat->Salt = $Salt;
			}

			$NoteText = $this->request->getPost('NoteText');
			if ($NoteText) {
				$vat->NoteText = $NoteText;
			}

			$Weight = 0.00;
			$WeightPost = $this->request->getPost('Weight');
			if ($WeightPost) {
				$Weight = $WeightPost;
			}
			$vat->Weight = $Weight;

			$vat->DeliveryDetailID = $this->request->getPost('DeliveryDetailID');

			$success = 1;
			$msg = '';
			try {
				if ($vat->save() == false) {
					$msg = "Error saving vat:\n\n" . implode("\n", $vat->getMessages());
					$this->logger->log($msg);
					$this->db->rollback();
					$this->view->data = array('success' => 0, 'msg' => $msg);
					$this->view->pick("layouts/json");
					return;
				}
			} catch (\Phalcon\Exception $e) {
				$msg = "Exception saving vat:\n" . implode("\n",  $e->getMessages());
				$this->logger->log($msg);
				$this->db->rollback();
				$this->view->data = array('success' => 0, 'msg' => $msg);
				$this->view->pick("layouts/json");
				return;
			}

			$parameters = array(
				InventoryStatus::STATUS_UNAVAILABLE,
				InventoryStatus::STATUS_OFFERED,
				InventoryStatus::STATUS_SOLDUNSHIPPED
			); // Unavailable, Offered, SoldUnshipped

			foreach ($parameters as $p) {
				$UUID = $this->utils->UUID(mt_rand(0, 65535));
				$inv = new InventoryStatus();
				$inv->InventoryStatusID = $UUID;
				$inv->VatID = $VatID;
				$inv->Pieces = 0;
				$inv->Weight = 0;
				$inv->InventoryStatusPID = $p;
				$inv->CreateDate = $this->mysqlDate;
				$inv->CreateID = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in

				try {
					if ($inv->save() == false) {
						$msg = "Error saving inventory:\n" . implode("\n", $inv->getMessages());
						$this->logger->log($msg);
						$this->db->rollback();
						$this->view->data = array('success' => 0, 'msg' => $msg);
						$this->view->pick("layouts/json");
						return;
					}
				} catch (\Phalcon\Exception $e) {
					$msg = "Exception saving inventory:\n" . implode("\n",  $e->getMessage());
					$this->logger->log($msg);
					$this->db->rollback();
					$this->view->data = array('success' => 0, 'msg' => $msg);
					$this->view->pick("layouts/json");
					return;
				}
			}

			$inv = new InventoryStatus();
			$inv->InventoryStatusID = $this->utils->UUID(mt_rand(0, 65535));
			$inv->VatID = $VatID;
			$inv->Pieces = $this->request->getPost('Pieces');
			$inv->Weight = $Weight;
			$inv->InventoryStatusPID = InventoryStatus::STATUS_AVAILABLE;
			$inv->CreateDate = $this->mysqlDate;
			$inv->CreateID = $this->session->userAuth['UserID'] ?: '00000000-0000-0000-0000-000000000000'; // id of user who is logged in

			try {
				if ($inv->save() == false) {
					$msg = "Error saving inventory:\n\n" . implode("\n", $inv->getMessages());
					$this->logger->log($msg);
					$this->db->rollback();
					$this->view->data = array('success' => 0, 'msg' => $msg);
					$this->view->pick("layouts/json");
					return;
				}
			} catch (\Phalcon\Exception $e) {
				$this->logger->log("Exception saving inventory2: " . implode("\n", $e->getMessage()));
				$this->db->rollback();
				$this->view->data = array('success' => 0, 'msg' => $msg);
				$this->view->pick("layouts/json");
				return;
			}

			// save changes to the db
			$this->db->commit();

			$reload = 0;
			if (null !== $this->request->getPost('reload')) {
				$reload = $this->request->getPost('reload');
			}
			$this->view->data = array('success' => $success, 'msg' => $msg, 'reload' => $reload);
			$this->view->pick("layouts/json");
		}
	}


	//      8        8          o
	//      8        8          8
	// .oPYo8 .oPYo. 8 .oPYo.  o8P .oPYo.
	// 8    8 8oooo8 8 8oooo8   8  8oooo8
	// 8    8 8.     8 8.       8  8.
	// `YooP' `Yooo' 8 `Yooo'   8  `Yooo'
	// :.....::.....:..:.....:::..::.....:
	// :::::::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::

	public function deleteAction()
	{
		if ($this->request->isPost() == true) {

			$VatID = $this->request->getPost('VatID');
			$this->logger->log('delete vat id ' . $VatID);

			$msg = '';
			$success = 1;

			$vat = Vat::findFirst("VatID = '$VatID'");


			if ($vat != false) {
				// begin a transaction
				$this->db->begin();


				try {
					if ($vat->delete() == false) {
						$msg = "Error deleting inventory:\n\n" . implode("\n", $vat->getMessages());
						$this->logger->log("error deleting vat: \n\n");
						$this->logger->log($msg);
						$this->db->rollback();
						$this->view->data = array('success' => 0, 'msg' => $msg);
						$this->view->pick("layouts/json");
						return;
					}

					// delete from InventoryStatus
					$inventoryVats = InventoryStatus::find(array(
						'conditions' => 'VatID = :VatID:',
						'bind' => array('VatID' => $VatID)
					));

					if ($inventoryVats->delete() == false) {
						$msg = "Error deleting inventory vat:\n\n" . implode("\n", $inventoryVats->getMessages());
						$this->logger->log("error deleting inventory vat: \n\n");
						$this->logger->log($msg);
						$this->db->rollback();
						$this->view->data = array('success' => 0, 'msg' => $msg);
						$this->view->pick("layouts/json");
						return;
					}
				} catch (\Phalcon\Exception $e) {
					$this->logger->log("Exception deleting inventory vat: " . implode("\n", $e->getMessage()));
					$this->db->rollback();
					$this->view->data = array('success' => 0, 'msg' => $msg);
					$this->view->pick("layouts/json");
					return;
				}

				// save changes to the db
				$this->db->commit();
			}


			$this->view->data = array('success' => $success, 'msg' => $msg);
			$this->view->pick("layouts/json");
		}
	}


	//                    8          o
	//                    8          8
	// o    o .oPYo. .oPYo8 .oPYo.  o8P .oPYo.
	// 8    8 8    8 8    8 .oooo8   8  8oooo8
	// 8    8 8    8 8    8 8    8   8  8.
	// `YooP' 8YooP' `YooP' `YooP8   8  `Yooo'
	// :.....:8 ....::.....::.....:::..::.....:
	// :::::::8 :::::::::::::::::::::::::::::::
	// :::::::..:::::::::::::::::::::::::::::::

	public function updateAction()
	{
		$vatId = $this->dispatcher->getParam("id");
		$vat = Vat::findFirst("VatID = '$vatId'");
		$success = 0;
		$message = '';

		if ($vat) {
			$this->logger->log('Updating vat ' . $vatId . "\nNext message should be success or fail.");

			$val = $_POST["value"];
			if ($_POST["name"] == 'MakeDate') {
				$val = $this->utils->dbDate($val);
			}

			if ($_POST["name"] == 'VatNumber' && !isset($val)) {
				$val = '';
			}

			$good = $vat->save(array(
				"UpdateDate" => $this->mysqlDate,
				"UpdateId" => $this->session->userAuth['UserID'], // id of user who is logged in,
				$_POST["name"] => $val
			));

			// Shouldn't have to address SoldUnshipped here.

			if ($_POST["name"] == 'Pieces' || $_POST["name"] == 'Weight') {
				$key = $_POST["name"];
				$this->logger->log("calculating avail for $key");

				$unavail = InventoryStatus::findFirst(
					"VatID = '$vatId' AND InventoryStatusPID = '" . InventoryStatus::STATUS_UNAVAILABLE . "'"
				);

				$this->logger->log("unavail is currently " . $unavail->$key);

				$avail = InventoryStatus::findFirst(
					"VatID = '$vatId' AND InventoryStatusPID = '" . InventoryStatus::STATUS_AVAILABLE . "'"
				);

				$availValue = $val - ($unavail->$key ?? 0);

				if ($availValue < 0) {
					try {
						$availValue = 0;
						$unavail->UpdateDate = $this->mysqlDate;
						$unavail->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in,
						$unavail->$key = $val;
						$unavail->save();
						$this->logger->log("changed unavail to $val");
					} catch (\Phalcon\Exception $e) {
						$this->logger->log("Exception saving unavail: " . $e->getMessage());
					}
				}

				$avail->$key = $availValue;
				$this->logger->log("changing avail to $availValue");
				try {
					$avail->UpdateDate = $this->mysqlDate;
					$avail->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in,
					$avail->save();
				} catch (\Phalcon\Exception $e) {
					$this->logger->log("Exception saving avail: " . $e->getMessage());
				}
			}

			if (!$good) {
				$this->logger->log('Failure saving Vat! Messages Follow?');
				foreach ($vat->getMessages() as $message) {
					$this->logger->log('Error saving Vat: ' . $message);
				}
				$success = 0;
				$message = 'Error saving Vat. See error log.';
			} else {
				$success = 1;
				$message = 'Success saving Vat!';
			}
		} else {
			$success = 0;
			$message = 'Error saving Vat: Vat ID: "' . $vatId . '" is not valid.';
			$this->logger->log($message);
		}

		$retArray = array('success' => $success, 'status' => ($success ? 'success' : 'error'), 'msg' => $message);

		if (isset($availValue)) $retArray['avail'] = $availValue;

		$this->view->data = $retArray;
		$this->view->pick("layouts/json");
	}
}
