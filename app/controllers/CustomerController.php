<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class CustomerController extends Controller
{

	// test cust id FF0F5DE8-F0E5-4EA8-9F5D-03E7A19CA752

	public function singleAction()
	{
		$customerId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		if ($customerId == 'NEW') $customerId = false;
		$this->view->customerId = $customerId;

		$customerData = new stdClass();
		if ($customerId) {
			$customerData = Customer::findFirst("CustomerID = '$customerId'");
		}
		$this->view->customerData = $customerData;
	}

	public function editAction()
	{

		$customerId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		if ($customerId == 'NEW') $customerId = 0;
		$this->view->customerId = $customerId;

		$customerData = new stdClass;
		if ($customerId) {
			$customerModel = new Customer();
			$customerData = $customerModel->getCustomer($customerId);
		}
		$this->view->customerData = $customerData;

		$paramModel = new Parameter();
		$paymentTerms = $paramModel->getValuesForGroupId(
			'58DE9214-197F-4867-9910-44FFB312C99E'
		);
		$this->view->paymentTerms = $paymentTerms;

		$custModel = new Customer();
		$loginIds = array();
		$customers = $custModel->getCustomers();
		foreach ($customers as $c) {
			$loginId = trim($c->LoginID);
			if ($loginId) {
				$loginIds[strtolower($loginId)] = 1;
			}
		}
		$this->view->loginIds = $loginIds;


		// page has been posted to
		if ($this->request->isPost() == true) {
			$customer = Customer::findFirst("CustomerID = '$customerId'");

			// delete customer
			if ($this->request->getPost('deleteCustomer')) {
				if ($customer != false) {
					if ($customer->delete() == false) {
						$msg = '<strong>Error deleting customer</strong></br />';
						foreach ($customer->getMessages() as $message) {
							$msg .= $message . '<br />';
						}
						$this->flash->error($msg);
					} else {
						$this->flash->success("Customer deleted successfully");
					}
				}
			} elseif ($this->request->getPost('saveCustomer')) {
				if (!$customer) { // add new customer
					$customer = new Customer();
					$customer->CustomerID = $this->UUID;
					$customer->CreateDate = $this->mysqlDate;
					$customer->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
				} else { // update existing customer
					$customer->UpdateDate = $this->mysqlDate;
					$customer->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
				}

				$customer->Name = $this->request->getPost('Name');
				$customer->Phone = $this->request->getPost('Phone');
				$customer->Fax = $this->request->getPost('Fax');
				$customer->HandlingCharge = $this->request->getPost('HandlingCharge');
				$customer->StorageCharge = $this->request->getPost('StorageCharge');
				$customer->Email = $this->request->getPost('Email');
				$customer->TermsPID = $this->request->getPost('TermsPID');
				$customer->LoginID = $this->request->getPost('LoginID');
				$active = $this->request->getPost('Active') ? 1 : 0;

				$customer->EDIFlag = $this->request->getPost('EDIFlag') ? 1 : 0;
				$customer->EDIISAID = $this->request->getPost('EDIISAID');
				$customer->EDIGSID = $this->request->getPost('EDIGSID');
				$customer->EDIKey = $this->request->getPost('EDIKey');
				$customer->EDIDocCodes = $this->request->getPost('EDIDocCodes');

				$customer->Active = $active;
				$password = $this->request->getPost('Password');
				if ($password) {
					$customer->Password = $this->security->hash($password);
					// $customer->Password = $password;
				}

				if ($customer->save() == false) {
					foreach ($customer->getMessages() as $message) {
						$this->logger->log('error saving customer - ' . $message);
					}
					$this->flash->error("Error saving customer");
				} else {
					$this->flash->success("Customer saved successfully");
				}
			}

			// Forward back to list page
			return $this->response->redirect('customer/list');
		}
	}

	public function overviewAction()
	{
		$customerId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		$customerModel = new Customer();
		$customer = $customerModel->getCustomer($customerId);

		$sixMonthsAgo = mktime(0, 0, 0, date("m") - 6, date("d"),   date("Y"));
		$sixMonthsAgoSql = date("Y-m-d H:i:s", $sixMonthsAgo);

		$lots = $customer->getLot(
			array(
				'conditions' => 'DateIn > :DateIn:',
				'bind' => array("DateIn" => $sixMonthsAgoSql),
				'order' => 'DateIn DESC'
			)
		);

		$contacts = $customer->getContact(
			array(
				'conditions' => 'Active = :Active:',
				'bind' => array("Active" => 1),
				'order' => 'LastName, FirstName'
			)
		);

		$data = Parameter::getValuesForGroupId('40E74C81-EF36-4700-A38C-F39B64F7E7D1');
		$descriptions = array();
		foreach ($data as $d) {
			$descriptions[$d['ParameterID']] = $d['Value1'];
		}

		$offers = $customer->getOffer(
			array(
				'conditions' => 'OfferDate > :OfferDate:',
				'bind' => array("OfferDate" => $sixMonthsAgoSql),
				'order' => 'OfferDate DESC'
			)
		);

		$data = Parameter::getValuesForGroupId('2AD6C035-FD2C-4553-AAA8-E2B983DF46C1');
		$offerStatuses = array();
		foreach ($data as $d) {
			$offerStatuses[$d['ParameterID']] = $d['Value1'];
		}

		$this->view->offerStatuses = $offerStatuses;
		$this->view->offers = $offers;
		$this->view->descriptions = $descriptions;
		$this->view->contacts = $contacts;
		$this->view->customer = $customer;
		$this->view->lots = $lots;
	}

	public function listAction()
	{
		$customer = new Customer();
		$customers = $customer->getCustomers();

		$this->view->customers = $customers;
	}

	public function getcontactsAction()
	{
		$customerId = $this->dispatcher->getParam("id");

		$customerData = new stdClass();
		$customerData->contacts = array();
		if ($customerId) {
			$customerModel = new Customer();
			$customerData = $customerModel->getCustomer($customerId);
		}

		$this->view->contacts = $customerData->contacts;
		$this->view->pick("customer/ajax/getcontacts");
	}

	public function getshiptoaddressesAction()
	{
		$customerId = $this->dispatcher->getParam("id");
		$customer = Customer::findFirst("CustomerID = '$customerId'");

		if (!$customer) {
			$customerModel = new Customer();
			$customer = $customerModel->getCustomer($customerId);
		}

		$this->view->shipToAddresses = $customer->getShipToAddress(['order' => 'Active DESC, CreateDate DESC ']) ?: array();
		$this->view->pick("customer/ajax/getshiptoaddresses");
	}

	public function getcontactlistAction()
	{
		$customerId = $this->dispatcher->getParam("id");

		$customerData = new stdClass();
		$customerData->contacts = array();
		if ($customerId) {
			$customerModel = new Customer();
			$customerData = $customerModel->getCustomer($customerId);
		}

		$data = array('' => 'Select...');

		foreach ($customerData->contacts as $r) {
			$data[$r->ContactID] = $r->FirstName . ' ' . $r->LastName;
		}

		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}

	public function getsinglecontactAction()
	{
		$contactId = $this->dispatcher->getParam("id");

		$contact = Contact::findFirst("ContactID = '$contactId'");
		$this->view->data = $contact;
		$this->view->pick("layouts/json");
	}

	public function getsingleshiptoaddressAction()
	{
		$shipToAddressId = $this->dispatcher->getParam("id");
		$shipToAddress = ShipToAddress::findFirst("ID = '$shipToAddressId'");

		$this->view->data = $shipToAddress;
		$this->view->pick("layouts/json");
	}

	public function getcontactdetailAction()
	{
		$contactId = $this->dispatcher->getParam("id");

		$contact = Contact::findFirst("ContactID = '$contactId'")->toArray();

		$this->view->data = $contact;
		$this->view->pick("layouts/json");
	}

	public function savecontactAction()
	{
		if ($this->request->isPost() == true) {
			$ContactID = $this->request->getPost('ContactID');
			if (!$ContactID) {
				$ContactID = 0;
			}
			$contact = Contact::findFirst("ContactID = '$ContactID'");

			if (!$contact) { // add new contact
				$contact = new Contact();
				$contact->ContactID = $this->UUID;
				$contact->CreateDate = $this->mysqlDate;
				$contact->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
				$contact->CustomerID = $this->request->getPost('CustomerID');
			} else { // update existing contact
				$contact->UpdateDate = $this->mysqlDate;
				$contact->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}
			$contact->FirstName = $this->request->getPost('FirstName');
			$contact->LastName = $this->request->getPost('LastName');
			$contact->BusPhone = $this->request->getPost('BusPhone');
			$contact->MobilePhone = $this->request->getPost('MobilePhone');
			$contact->HomePhone = $this->request->getPost('HomePhone');
			$contact->OtherPhone = $this->request->getPost('OtherPhone');
			$contact->Fax = $this->request->getPost('Fax');
			$contact->BusEmail = $this->request->getPost('BusEmail');
			$active = $this->request->getPost('Active') ? 1 : 0;
			$contact->Active = $active;

			$success = 1;
			if ($contact->save() == false) {
				foreach ($contact->getMessages() as $message) {
					$this->logger->log('error saving contact - ' . $message);
				}
				$success = 0;
			}

			$this->view->data = array('success' => $success);
			$this->view->pick("layouts/json");
		}
	}

	public function saveshiptoaddressAction()
	{
		if ($this->request->isPost() != true) {
			return;
		}

		$shipToAddressID = $this->request->getPost('shipToAddressID');

		if (!$shipToAddressID) {
			$shipToAddressID = 0;
		}

		$shipToAddress = ShipToAddress::findFirst("ID = '$shipToAddressID'");

		if (!$shipToAddress) {
			$shipToAddress = new ShipToAddress();

			$shipToAddress->CreateDate = $this->mysqlDate;
			$shipToAddress->CreateID = $this->session->userAuth['UserID'];
			$shipToAddress->CustomerID = $this->request->getPost('CustomerID');
		} else {
			$shipToAddress->UpdateDate = $this->mysqlDate;
			$shipToAddress->UpdateID = $this->session->userAuth['UserID'];
		}

		$shipToAddress->Name = $this->request->getPost('Name');
		$shipToAddress->Address = $this->request->getPost('Address');
		$shipToAddress->Address2 = $this->request->getPost('Address2');
		$shipToAddress->City = $this->request->getPost('City');
		$shipToAddress->State = $this->request->getPost('State');
		$shipToAddress->Zip = $this->request->getPost('Zip');
		$shipToAddress->ConsignedName = $this->request->getPost('ConsignedName');
		$shipToAddress->Nickname = $this->request->getPost('Nickname');
		$shipToAddress->Active = $this->request->getPost('Active') ? 1 : 0;

		$success = 1;

		if ($shipToAddress->save() == false) {
			foreach ($shipToAddress->getMessages() as $message) {
				$this->logger->log('error saving contact - ' . $message);
			}

			$success = 0;
		}

		$this->view->data = array('success' => $success);
		$this->view->pick("layouts/json");
	}

	public function deletecontactAction()
	{
		$contactId = $this->dispatcher->getParam("id");
		$contact = Contact::findFirst("ContactID = '$contactId'");
		$success = 1;
		if ($contact != false) {
			if ($contact->delete() == false) {
				foreach ($contact->getMessages() as $message) {
					$this->logger->log('error deleting contact - ' . $message);
				}
				$success = 0;
			}
		}
		$this->view->data = array('success' => $success);
		$this->view->pick("layouts/json");
	}

	public function clienthomeAction()
	{
		$allowGrading = false;
		$allowInspection = false;
		$custId = $this->session->customerAuth['CustomerID'];
		if ($custId === 'B343939D-BCA4-4777-AF99-2EE44FF04D4E') {
			$allowGrading = true;
			$allowInspection = true;
		}
		if ($custId) {
			$this->view->custid = $custId;
			$this->view->allowGrading = $allowGrading;
			$this->view->allowInspection = $allowInspection;
		}
	}


	//  o                             o                       .oPYo.                              o
	//                                8                       8   `8                              8
	// o8 odYo. o    o .oPYo. odYo.  o8P .oPYo. oPYo. o    o o8YooP' .oPYo. .oPYo. .oPYo. oPYo.  o8P
	//  8 8' `8 Y.  .P 8oooo8 8' `8   8  8    8 8  `' 8    8  8   `b 8oooo8 8    8 8    8 8  `'   8
	//  8 8   8 `b..d' 8.     8   8   8  8    8 8     8    8  8    8 8.     8    8 8    8 8       8
	//  8 8   8  `YP'  `Yooo' 8   8   8  `YooP' 8     `YooP8  8    8 `Yooo' 8YooP' `YooP' 8       8
	// :....::..::...:::.....:..::..::..::.....:..:::::....8 :..:::..:.....:8 ....::.....:..::::::..:
	// :::::::::::::::::::::::::::::::::::::::::::::::::ooP'.:::::::::::::::8 :::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::...:::::::::::::::::..:::::::::::::::::::::::

	// INVENTORY: Report
	// TODO: Create a version for Corporate
	public function inventoryreportAction()
	{

		if ( !($custId = $this->dispatcher->getParam("id")) ) { // INFO the call is (not) coming from in the house!
			$custId = $this->session->customerAuth['CustomerID'];
		}
		if ($custId) {
			$customer = Customer::getCustomer($custId);
			$invType = $this->request->getQuery('invType');

			$inventory = $customer->getInventory(NULL, NULL, $invType);

			$this->view->custid = $custId;
			$this->view->inventory = $inventory;
			$this->view->invType = strtoupper($invType);
		}
	}

	//  o                             o                      ooo.            o          o 8
	//                                8                      8  `8.          8            8
	// o8 odYo. o    o .oPYo. odYo.  o8P .oPYo. oPYo. o    o 8   `8 .oPYo.  o8P .oPYo. o8 8
	//  8 8' `8 Y.  .P 8oooo8 8' `8   8  8    8 8  `' 8    8 8    8 8oooo8   8  .oooo8  8 8
	//  8 8   8 `b..d' 8.     8   8   8  8    8 8     8    8 8   .P 8.       8  8    8  8 8
	//  8 8   8  `YP'  `Yooo' 8   8   8  `YooP' 8     `YooP8 8ooo'  `Yooo'   8  `YooP8  8 8
	// :....::..::...:::.....:..::..::..::.....:..:::::....8 .....:::.....:::..::.....::....
	// :::::::::::::::::::::::::::::::::::::::::::::::::ooP'.:::::::::::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::::::::::::::::::::...:::::::::::::::::::::::::::::::::

	public function inventoryxlsAction()
	{
		if ( !($custId = $this->dispatcher->getParam("id")) ) { // INFO the call is (not) coming from in the house!
			$custId = $this->session->customerAuth['CustomerID'];
		}
		$customer = Customer::getCustomer($custId);

		require_once(dirname(__FILE__) . "/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Inventory');

		$rows = array();

		$sort = '';

		if ($sortJSON = $this->request->getQuery('sort')) {
			$sortArray = json_decode($sortJSON);
			$sort = '';

			foreach ($sortArray as $idx => $sortItem) {
				if ($idx > 0) {
					$sort .= ', ';
				}

				$ascDesc = $sortItem[1] ? 'DESC' : 'ASC';
				$sort .= "$sortItem[0] $ascDesc";
			}
		}

		if ($customer->Name == 'DAIRICONCEPTS') {
			if ($sort) {
				$sort .= ', ';
			}

			$sort .= 'ProductCode, MakeDate ASC';

			$boldStyle = array(
				'font' => array(
					'bold' => true
				)
			);

			$topBorderStyle = array(
				'borders' => array(
					'top' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN
					)
				)
			);
		}

		$invType = $this->request->getQuery('invType');

		$inventory = $customer->getInventory(NULL, $sort, $invType);

		$columns = array(
			'PO #',
			'Lot #',
			'Description',
			'Make Date',
			'Factory',
			'Pieces',
			'Weight',
			'Date In',
			'Product Code',
			'Location'
		);
		array_push($rows, $columns);

		$ci = count($inventory);
		$rowNum = 1;

		if ($ci) {
			$product_code_weight = 0;
			$last_product_code = '[NULL]';

			$rowNum = 1;

			foreach ($inventory as $row) {

				if ($customer->Name == 'DAIRICONCEPTS' && $row['ProductCode'] != $last_product_code && $last_product_code != '[NULL]') {
					if ($last_product_code != '') {
						array_push($rows, array(
							'',
							'',
							'',
							"Total weight for product code $last_product_code:",
							'',
							'',
							$product_code_weight
						));

						$rowNum++;

						$sheet->mergeCells("D$rowNum:F$rowNum");
						$sheet->getStyle("D$rowNum:G$rowNum")->applyFromArray($boldStyle);
						$sheet->getStyle("G$rowNum")->applyFromArray($topBorderStyle);
					}

					array_push($rows, array(''));
					$rowNum++;

					$product_code_weight = 0;
				}

				$makeDate = strtotime($row['MakeDate']);
				$makeDate = PHPExcel_Shared_Date::PHPToExcel($makeDate);
				$dateIn = strtotime($row['DateIn']);
				$dateIn = PHPExcel_Shared_Date::PHPToExcel($dateIn);

				$xlRow = array(
					$row['CustomerPONumber'],
					$row['LotNumber'],
					$row['LotDescription'],
					$makeDate,
					$row['FactoryName'],
					$row['Pieces'],
					$row['Weight'],
					$dateIn,
					$row['ProductCode'],
					$row['RoomName']
				);
				array_push($rows, $xlRow);

				$product_code_weight += $row['Weight'];
				$last_product_code = $row['ProductCode'];

				$rowNum++;
			}
		}

		$sheet->fromArray($rows);

		$this->sumTotal($sheet, $rowNum, 'F');
		$this->sumTotal($sheet, $rowNum, 'G');

		$headerStyle = array(
			'font' => array(
				'bold' => true
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'borders' => array(
				'bottom' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$centerAlign = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
		);
		$rightAlign = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
			),
		);
		$leftAlign = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
			),
		);

		$sheet->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


		$sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
		$sheet->getColumnDimension('A')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('B')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE); // setWidth(45);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('H')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE); // setWidth(22);
		$sheet->getColumnDimension('J')->setAutoSize(TRUE); // setWidth(15);

		$sheet->getStyle('D2:D' . ($rowNum + 1))->getNumberFormat()->setFormatCode('mm/dd/yy');
		$sheet->getStyle('H2:H' . ($rowNum + 1))->getNumberFormat()->setFormatCode('mm/dd/yy');
		// $sheet->getStyle('D2:D' . ($ci + 1) )->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DMYSLASH);
		// $sheet->getStyle('H2:H' . ($ci + 1) )->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DMYSLASH);

		$sheet->getStyle('F2:F' . ($rowNum + 1))->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle('G2:G' . ($rowNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

		// Ensure that the product code displays properly
		$sheet->getStyle('I2:I' . ($rowNum))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

		// $sheet->freezePaneByColumnAndRow(0, 1);

		$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		// capture the output of the save method and put it into $content as a string
		ob_start();
		$objWriter = PHPExcel_IOFactory::createWriter($phpxl, "Excel2007");
		$objWriter->save("php://output");

		$content = ob_get_contents();
		ob_end_clean();

		// Getting a response instance
		$response = new Response();

		$filename = 'inventory';
		if ($invType) $filename .= "_$invType";

		$response->setHeader("Content-Type", "application/vnd.ms-excel");
		$response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}

	// INVENTORY: Report
	// TODO: Create a version for Corporate

	public function inventorydetailreportAction()
	{

		if ( !($custId = $this->dispatcher->getParam("id")) ) { // INFO the call is (not) coming from in the house!
			$custId = $this->session->customerAuth['CustomerID'];
		}

		if ($custId) {
			$customer = Customer::getCustomer($custId);

			$invType = $this->request->getQuery('invType');

			$inventory = $customer->getDetailedInventory(NULL, NULL, $invType);

			$this->view->custid = $custId;
			$this->view->inventory = $inventory;
			$this->view->invType = strtoupper($invType);
		}
	}

	public function inventorydetailxlsAction()
	{
		if ( !($custId = $this->dispatcher->getParam("id")) ) { // INFO the call is (not) coming from in the house!
			$custId = $this->session->customerAuth['CustomerID'];
		}
		$customer = Customer::getCustomer($custId);

		require_once(dirname(__FILE__) . "/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Inventory Detail');

		$rows = array();

		$sort = NULL;

		// if ($sortJSON = $this->request->getQuery('sort')) {
		// 	$sortArray = json_decode($sortJSON);
		// 	$sort = '';

		// 	foreach ($sortArray as $idx => $sortItem) {
		// 		if ($idx > 0) {
		// 			$sort .= ', ';
		// 		}

		// 		$sortField = $sortItem[0];
		// 		if ($sortField == 'VatNumber' || $sortField == 'CustomerLotNumber') {
		// 			$sortField = 'LPAD(lower(' . $sortField . '), 15, 0)'; // the LPAD is a trick to make varchars sort more numerical-like
		// 		}
		// 		$ascDesc = $sortItem[1] ? 'DESC' : 'ASC';
		// 		$sort .= "$sortField $ascDesc";
		// 	}
		// }

		$invType = $this->request->getQuery('invType');

		$inventory = $customer->getDetailedInventory(NULL, $sort, $invType);

		$columns = array(
			'PO #',
			'Lot #',
			'Cust Lot #',
			'Vat #',
			'Description',
			'Make Date',
			'Factory',
			'Pieces',
			'Weight',
			'Date In',
			'Product Code',
			'Location'
		);
		array_push($rows, $columns);

		$ci = count($inventory);
		$rowNum = 1;

		if ($ci) {

			$rowNum = 1;

			foreach ($inventory as $row) {

				$makeDate = strtotime($row['MakeDate']);
				$makeDate = PHPExcel_Shared_Date::PHPToExcel($makeDate);
				$dateIn = strtotime($row['DateIn']);
				$dateIn = PHPExcel_Shared_Date::PHPToExcel($dateIn);

				$xlRow = array(
					$row['CustomerPONumber'],
					$row['LotNumber'],
					" " . strval($row['CustomerLotNumber']),
					" " . strval($row['VatNumber']),
					$row['LotDescription'],
					$makeDate,
					$row['FactoryName'],
					$row['Pieces'],
					$row['Weight'],
					$dateIn,
					$row['ProductCode'],
					$row['RoomName']
				);
				array_push($rows, $xlRow);

				$rowNum++;
			}
		}
$this->logger->log($rows);
		$sheet->fromArray($rows);

		$this->sumTotal($sheet, $rowNum, 'H');
		$this->sumTotal($sheet, $rowNum, 'I');

		$headerStyle = array(
			'font' => array(
				'bold' => true
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'borders' => array(
				'bottom' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$sheet->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
		$sheet->getColumnDimension('A')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('B')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE); // setWidth(45);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE); // setWidth(12);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('H')->setAutoSize(TRUE); // setWidth(20);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE); // setWidth(22);
		$sheet->getColumnDimension('J')->setAutoSize(TRUE); // setWidth(15);
		$sheet->getColumnDimension('K')->setAutoSize(TRUE); // setWidth(15);
		$sheet->getColumnDimension('L')->setAutoSize(TRUE); // setWidth(15);

		$sheet->getStyle('C2:C' . ($rowNum))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		$sheet->getStyle('D2:D' . ($rowNum))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);


		$sheet->getStyle('F2:F' . ($rowNum))->getNumberFormat()->setFormatCode('mm/dd/yy');
		$sheet->getStyle('J2:J' . ($rowNum))->getNumberFormat()->setFormatCode('mm/dd/yy');

		$sheet->getStyle('H2:H' . ($rowNum + 1))->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle('I2:I' . ($rowNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

		// Ensure that the product code displays properly
		$sheet->getStyle('K2:K' . ($rowNum))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

		// $sheet->freezePaneByColumnAndRow(0, 1);

		$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		// capture the output of the save method and put it into $content as a string
		ob_start();
		$objWriter = PHPExcel_IOFactory::createWriter($phpxl, "Excel2007");
		$objWriter->save("php://output");

		$content = ob_get_contents();
		ob_end_clean();

		// Getting a response instance
		$response = new Response();

		$filename = 'inventory_detail';
		if ($invType) $filename .= "_$invType";

		$response->setHeader("Content-Type", "application/vnd.ms-excel");
		$response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}

	// .oPYo.   o                                     .oPYo.                              o
	// 8        8                                     8   `8                              8
	// `Yooo.  o8P .oPYo. oPYo. .oPYo. .oPYo. .oPYo. o8YooP' .oPYo. .oPYo. .oPYo. oPYo.  o8P
	//     `8   8  8    8 8  `' .oooo8 8    8 8oooo8  8   `b 8oooo8 8    8 8    8 8  `'   8
	//      8   8  8    8 8     8    8 8    8 8.      8    8 8.     8    8 8    8 8       8
	// `YooP'   8  `YooP' 8     `YooP8 `YooP8 `Yooo'  8    8 `Yooo' 8YooP' `YooP' 8       8
	// :.....:::..::.....:..:::::.....::....8 :.....::..:::..:.....:8 ....::.....:..::::::..:
	// ::::::::::::::::::::::::::::::::::ooP'.::::::::::::::::::::::8 :::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::...::::::::::::::::::::::::..:::::::::::::::::::::::

	// INVENTORY: Report

	public function billingreportsAction()
	{
		$customerId = $this->dispatcher->getParam("id");

		if ($customerId) $customer = Customer::findFirst("CustomerID = '$customerId'");

		if (!$customer) {
			$this->logger->log("No customer found. Redirecting to list.");
			return $this->response->redirect('customer/list');
		}

		// $this->logger->log("Customer: $customerId");

		$this->view->customer = $customer;
		$this->view->lotWarehouses = Warehouse::getWarehouses();
		$this->view->billingFrequencyArray = $this->utils->getEnumValues('Lot', 'BillingFrequency');
	}


	//          o                                     o    o  o     .oPYo.
	//          8                                     `b  d'  8     8
	// .oPYo.  o8P .oPYo. oPYo. .oPYo. .oPYo. .oPYo.   `bd'   8     `Yooo.
	// Yb..     8  8    8 8  `' .oooo8 8    8 8oooo8   .PY.   8         `8
	//   'Yb.   8  8    8 8     8    8 8    8 8.      .P  Y.  8          8
	// `YooP'   8  `YooP' 8     `YooP8 `YooP8 `Yooo' .P    Y. 8oooo `YooP'
	// :.....:::..::.....:..:::::.....::....8 :.....:..::::..:......:.....:
	// ::::::::::::::::::::::::::::::::::ooP'.:::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::...:::::::::::::::::::::::::::::::

	// INVENTORY: Report

	public function storagereportxlsAction()
	{
		$customerId = $this->dispatcher->getParam("id");

		if ($customerId) $customer = Customer::findFirst("CustomerID = '$customerId'");

		if (!$customer) {
			$this->logger->log("No customer found. Redirecting to list.");
			return $this->response->redirect('customer/list');
		}

		$startDate = strtotime($this->request->get('StartDate'));
		$endDate = strtotime($this->request->get('EndDate'));

		$warehouseId = $this->request->get('WarehouseID');
		$billingFrequency = $this->request->get('BillingFrequency');

		if (!$startDate || !$endDate || $startDate > $endDate) {
			$this->logger->log("Invalid Date Range. Redirecting to form.");
			return $this->response->redirect('customer/billingreports/' . $customerId);
		}

		require_once(dirname(__FILE__) . "/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Storage Report');

		$newStyle = array(
			'font' => array(
				'color' => ['rgb' => 'FF0000']
				// 'bold' => true
			)
		);

		// Go get the old and newer lots for the report
		$lots = iterator_to_array($customer->getStorageData($startDate, $endDate, $warehouseId, $billingFrequency));
		$newLots = iterator_to_array($customer->getNewStorageData($startDate, $endDate, $warehouseId, $billingFrequency));

		$newLotsArray = array();

		// Making a hash of the new lot numbers so it's easier to tell what's new
		foreach ($newLots as $newLot) {
			$newLotsArray[$newLot->LotNumber] = $newLot->LotNumber;
		}

		// Combine them together
		$combinedLots = array_merge($lots, $newLots);

		$numRecords = count($combinedLots);

		$subtitle = $customer->Name;

		if ($warehouseId && count($warehouseId) === 1) {
			$warehouseName = Warehouse::findFirst($warehouseId)->Name;
			$subtitle .= " - $warehouseName";
		}

		$rows = array(
			['OSHKOSH COLD STORAGE CO INC'],
			[$subtitle . ' ' . date('m/d/y', $startDate) . ' - ' . date('m/d/y', $endDate)],
			['']
		);

		$rowNum = count($rows);
		// $ratesRowNum = $rowNum;

		$columns = array(
			/* A */	'W/R#',
			/* B */ 'LOT #',
			/* C */ 'DESCRIPTION',
			/* D */ 'CUST PO #',
			/* E */ 'PROD CODE',
			/* F */ 'STORAGE DATES',
			/* G */ 'PCS',
			/* H */ 'WEIGHT',
			/* I */ 'PAL',
			/* J */ 'HDLG RATE',
			/* K */ 'HDLG CHG',
			/* L */ 'STG RATE',
			/* M */ 'STG CHG',
			/* N */ 'TMP CHG',
			/* O */ 'TOTAL'
		);

		array_push($rows, $columns);

		$lastCol = $this->utils->getXLCol(count($columns) - 1);

		$rowNum++;
		$headerRowNum = $rowNum;
		$dataRowNum = $rowNum + 1;

		$lastDayOfPreviousMonth = strtotime('last day of last month', $endDate);
		$dateOneMonthBack = strtotime('-1 month', $endDate);
		$dateToCompare = min($lastDayOfPreviousMonth, $dateOneMonthBack);

		foreach ($combinedLots as $lot) {
			$storageDates = $this->getPeriodDates(
				strtotime($lot->DateIn),
				$startDate,
				$endDate,
				$lot->BillingFrequency
			);

			$isLotNew = array_key_exists($lot->LotNumber, $newLotsArray);

			// For each individual storage date on a lot
			foreach ($storageDates as $storageDate) {
				$balances = $lot->getLotBalancesAtDate($storageDate['start'], $isLotNew);

				if (empty($balances['Pieces']) || ($lot->StorageUnit == 'pallet' && empty($balances['Pallets']))) {
					continue;
				}

				$rowNum++;

				$receipt = '';
				if (count($lot->warehousereceipt)) {
					$receipt = $lot->getWarehouseReceipt(['order' => 'CreateDate'])->getLast()->ReceiptNumber;
				}

				$pallets = '';

				$storageCharges = "=PRODUCT(H{$rowNum},L{$rowNum})";

				if ($lot->StorageUnit == 'pallet') {
					$pallets = $balances['Pallets'] ?: 0;
					$storageCharges = "=PRODUCT(I{$rowNum},L{$rowNum})";
				}

				if ($isLotNew) {
					$handlingCharges = "=PRODUCT(H{$rowNum},J{$rowNum})";

					if ($lot->HandlingUnit == 'pallet') {
						$pallets = $balances['Pallets'] ?: 0;
						$handlingCharges = "=PRODUCT(I{$rowNum},J{$rowNum})";
					}
				}

				$tmpchg = '';
				$tempChangeDate = strtotime($lot->TempChangeDate);

				if ($tempChangeDate != false) {
					if ($tempChangeDate > $dateToCompare && $tempChangeDate <= $endDate) {
						$tmpchg = "=PRODUCT(H{$rowNum},0.002)";
					}
				}

				$xlRow = array(
					/* A */	$isLotNew == true ? $receipt : '',
					/* B */ $lot->LotNumber,
					/* C */ $lot->Description,
					/* D */ $lot->CustomerPONumber,
					/* E */ $lot->ProductCode,
					/* F */ $storageDate['formatted'],
					/* G */ $balances['Pieces'],
					/* H */ $balances['Weight'],
					/* I */ $pallets,
					/* J */ $isLotNew == true ? $lot->Handling : '',
					/* K */ $isLotNew == true ? $handlingCharges : '',
					/* L */ $lot->Storage,
					/* M */ $storageCharges,
					/* N */ $tmpchg,
					/* O */ "=K{$rowNum} + M{$rowNum} + N{$rowNum}"
				);

				if ($isLotNew) {
					$sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray($newStyle);
				}

				array_push($rows, $xlRow);
			}
		}

		$lastDataRowNum = $rowNum;

		if ($numRecords && ($rowNum - $headerRowNum > 0)) {
			$totalsRow = array('', '', '', '', '', 'TOTALS:');

			for ($i = count($totalsRow); $i < count($columns); $i++) {
				if (strpos($columns[$i], 'RATE') !== false) {
					$totalsRow[] = '';
				} else {
					$col = $this->utils->getXLCol($i);
					$start = $headerRowNum + 1;
					$totalsRow[] = "=SUM({$col}{$start}:{$col}{$rowNum})";
				}
			}

			array_push($rows, $totalsRow);
			$rowNum++;
		}

		$sheet->fromArray($rows, null, 'A1', true);

		$sheet->mergeCells("A1:{$lastCol}1");
		$sheet->mergeCells("A2:{$lastCol}2");
		$sheet->mergeCells("A3:{$lastCol}3");

		$titleStyle = array(
			'font' => array(
				'bold' => true,
				'size' => 22
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);

		$subTitleStyle = array(
			'font' => array(
				'bold' => true,
				'size' => 18
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);

		$headerStyle = array(
			'font' => array(
				'bold' => true
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$centerStyle = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);

		$dataStyle = array(
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$totalsStyle = array(
			'font' => array(
				'bold' => true
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		// error_log("A{$dataRowNum}:{$lastCol}{$lastDataRowNum}");

		$sheet->getStyle("A1:{$lastCol}1")->applyFromArray($titleStyle);
		$sheet->getStyle("A2:{$lastCol}2")->applyFromArray($subTitleStyle);
		// $sheet->getStyle("I4:K4")->applyFromArray($headerStyle);
		$sheet->getStyle("A{$headerRowNum}:{$lastCol}{$headerRowNum}")->applyFromArray($headerStyle); // NOTE: This used to be $sheet->getStyle('1') for the whole first row, but that causes MASSIVE memory usage in Excel
		$sheet->getStyle("A{$dataRowNum}:{$lastCol}{$lastDataRowNum}")->applyFromArray($dataStyle);

		$sheet->getStyle("A{$dataRowNum}:B{$lastDataRowNum}")->applyFromArray($centerStyle);
		$sheet->getStyle("D{$dataRowNum}:F{$lastDataRowNum}")->applyFromArray($centerStyle);
		$sheet->getStyle("J{$dataRowNum}:J{$lastDataRowNum}")->applyFromArray($centerStyle);
		$sheet->getStyle("L{$dataRowNum}:L{$lastDataRowNum}")->applyFromArray($centerStyle);

		$sheet->getStyle("I{$dataRowNum}:I{$rowNum}")->applyFromArray($centerStyle);

		$sheet->getStyle("F{$rowNum}")->applyFromArray($centerStyle);
		$sheet->getStyle("F{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray($totalsStyle);

		foreach ($columns as $i => $colName) {
			$xlCol = $this->utils->getXLCol($i);

			if ($i >= 9 && $i <= 12) {
				$sheet->getColumnDimension($xlCol)->setWidth(10);
			} else {
				$sheet->getColumnDimension($xlCol)->setAutoSize(TRUE);
			}
		}

		$sheet->getColumnDimension('O')->setAutoSize(FALSE);
		$sheet->getColumnDimension('O')->setWidth(14.5);

		$sheet->getRowDimension(1)->setRowHeight(36);
		$sheet->getRowDimension(2)->setRowHeight(28);

		$sheet->getStyle("G{$dataRowNum}:G{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle("H{$dataRowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$sheet->getStyle("I{$dataRowNum}:I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle("K{$dataRowNum}:K{$rowNum}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
		$sheet->getStyle("M{$dataRowNum}:O{$rowNum}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
		$sheet->getStyle("J{$dataRowNum}:J{$rowNum}")->getNumberFormat()->setFormatCode('$0.0000');
		$sheet->getStyle("L{$dataRowNum}:L{$rowNum}")->getNumberFormat()->setFormatCode('$0.0000');

		// PRINT SETTINGS
		$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		$sheet->getPageSetup()->setFitToWidth(1);
		$sheet->getPageSetup()->setFitToHeight(0);

		$sheet->getPageMargins()->setTop(0.25);
		$sheet->getPageMargins()->setRight(0.25);
		$sheet->getPageMargins()->setLeft(0.25);
		$sheet->getPageMargins()->setBottom(0.25);

		// $sheet->getStyle("{$lastCol}2:{$lastCol}{$rowNum}") )->getNumberFormat()->setFormatCode( 'mm/dd/yy' );

		// capture the output of the save method and put it into $content as a string
		ob_start();
		$objWriter = PHPExcel_IOFactory::createWriter($phpxl, "Excel2007");
		$objWriter->save("php://output");

		$content = ob_get_contents();
		ob_end_clean();

		// Getting a response instance
		$response = new Response();

		// $response->setHeader("Content-Type", "application/vnd.ms-excel");
		$response->setHeader("Content-Type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"); // for Excel2007
		$response->setHeader('Content-Disposition', 'attachment; filename="storage_report.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}


	// .oPYo.               o               o    o  o     .oPYo.
	// 8    8                               `b  d'  8     8
	// 8      o    o oPYo. o8 odYo. .oPYo.   `bd'   8     `Yooo.
	// 8      8    8 8  `'  8 8' `8 8    8   .PY.   8         `8
	// 8    8 8    8 8      8 8   8 8    8  .P  Y.  8          8
	// `YooP' `YooP' 8      8 8   8 `YooP8 .P    Y. 8oooo `YooP'
	// :.....::.....:..:::::....::..:....8 ..::::..:......:.....:
	// :::::::::::::::::::::::::::::::ooP'.::::::::::::::::::::::
	// :::::::::::::::::::::::::::::::...::::::::::::::::::::::::

	// INVENTORY: Report

	public function curingreportxlsAction()
	{
		$customerId = $this->dispatcher->getParam("id");

		if ($customerId) $customer = Customer::findFirst("CustomerID = '$customerId'");

		if (!$customer) {
			$this->logger->log("No customer found. Redirecting to list.");
			return $this->response->redirect('customer/list');
		}

		$startDate = strtotime($this->request->get('StartDate'));
		$endDate = strtotime($this->request->get('EndDate'));

		$warehouseId = $this->request->get('WarehouseID');

		$roomPid = $this->request->get('RoomPID');
		$ownedBy = $this->request->get('OwnedBy');

		if (!$startDate || !$endDate || $startDate > $endDate) {
			$this->logger->log("Invalid Date Range. Redirecting to form.");
			return $this->response->redirect('customer/billingreports/' . $customerId);
		}

		require_once(dirname(__FILE__) . "/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Curing Report');


		$newStyle = array(
			'font' => array(
				'color' => ['rgb' => 'FF0000']
				// 'bold' => true
			)
		);

		$boldStyle = array(
			'font' => array(
				'bold' => true
			)
		);

		$topBorderStyle = array(
			'borders' => array(
				'top' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$lots = $customer->getCuringData($startDate, $endDate, $warehouseId, $roomPid, $ownedBy);
		$newLots = $customer->getNewCuringData($startDate, $endDate, $warehouseId, $roomPid, $ownedBy);

		$numRecords = count($lots) + count($newLots);

		$subtitle = $customer->Name . ' - CURING CHARGES';

		if ($warehouseId && count($warehouseId) === 1) {
			$warehouseName = Warehouse::findFirst($warehouseId)->Name;
			$subtitle .= " - $warehouseName";
		}

		$subtitle .= ' - ' . date('m/d/y', $startDate) . ' - ' . date('m/d/y', $endDate);

		if ($roomPid && count($roomPid) === 1) {
			$roomName = Parameter::getValue($roomPid[0]);
			$subtitle .= " - $roomName LOADS";
		}

		$factories = [];

		// if ( $numRecords ) {
		$rows = array(
			['OSHKOSH COLD STORAGE CO INC'],
			[$subtitle],
			[''],
		);

		$rowNum = count($rows);

		$columns = array(
			/* A */	'LOT #',
			/* B */ 'CUST PO #',
			/* C */ 'DESCRIPTION',
			/* D */ 'ORIG COST',
			/* E */ 'PROD CODE',
			/* F */ 'FACTORY',
			/* G */ 'OWNED BY',
			/* H */ 'CURING DATES',
			/* I */ 'PCS',
			/* J */ 'WEIGHT',
			/* K */ 'RATE',
			/* L */ 'TOTAL CHARGES'
		);

		array_push($rows, $columns);

		$lastCol = $this->utils->getXLCol(count($columns) - 1);

		$rowNum++;
		$headerRowNum = $rowNum;
		$dataRowNum = $rowNum + 1;

		$endMDay = getdate($endDate)['mday'];

		foreach ($lots as $lot) {
			$curingDates = $this->getPeriodDates(
				strtotime($lot->DateIn),
				$startDate,
				$endDate
			);

			$curingDates = $curingDates[0];

			$balances = $lot->getLotBalancesAtDate($curingDates['start']);

			if (!$balances['Pieces']) continue;

			$rowNum++;

			$receipt = '';
			if (count($lot->warehousereceipt)) {
				$receipt = $lot->getWarehouseReceipt(['order' => 'CreateDate'])->getLast()->ReceiptNumber;
			}

			$curingCharges = "=PRODUCT(J{$rowNum},K{$rowNum})"; // this is a rough way to do things

			$factory = '';
			if ($lot->FactoryID) {
				if (empty($factories[$lot->FactoryID])) {
					$factories[$lot->FactoryID] = Factory::findFirstByFactoryID($lot->FactoryID)->Name;
				}

				$factory = $factories[$lot->FactoryID];
			}


			$xlRow = array(
				$lot->LotNumber,
				$lot->CustomerPONumber,
				$lot->Description,
				$lot->Cost,
				$lot->ProductCode,
				$factory,
				$lot->getOwnedBy()->Name,
				$curingDates['formatted'],
				$balances['Pieces'],
				$balances['Weight'],
				$lot->AdditionalMonthRate,
				$curingCharges
			);
			array_push($rows, $xlRow);
		}


		foreach ($newLots as $lot) {

			$curingDates = $this->getPeriodDates(
				strtotime($lot->DateIn),
				$startDate,
				$endDate
			);

			$curingDates = $curingDates[0];

			$balances = $lot->getLotBalancesAtDate($curingDates['start']);

			if (!$balances['Pieces']) continue;


			$rowNum++;

			$curingCharges = "=PRODUCT(J{$rowNum},K{$rowNum})";

			$factory = '';
			if ($lot->FactoryID) {
				if (empty($factories[$lot->FactoryID])) {
					$factories[$lot->FactoryID] = Factory::findFirstByFactoryID($lot->FactoryID)->Name;
				}

				$factory = $factories[$lot->FactoryID];
			}


			$xlRow = array(
				$lot->LotNumber,
				$lot->CustomerPONumber,
				$lot->Description,
				$lot->Cost,
				$lot->ProductCode,
				$factory,
				$lot->getOwnedBy()->Name,
				$curingDates['formatted'],
				$balances['Pieces'],
				$balances['Weight'],
				$lot->FirstMonthRate,
				$curingCharges
			);

			$sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray($newStyle);

			array_push($rows, $xlRow);
		}

		$lastDataRowNum = $rowNum;

		if ($numRecords) {
			array_push($rows, ['']);
			$rowNum++;

			$totalsRow = array('', '', '', '', '', '', '', 'TOTALS:');

			for ($i = count($totalsRow); $i < count($columns); $i++) {
				if (strpos($columns[$i], 'RATE') !== false) {
					$totalsRow[] = '';
				} else {
					$col = $this->utils->getXLCol($i);
					$start = $headerRowNum + 1;
					$totalsRow[] = "=SUM({$col}{$start}:{$col}{$rowNum})";
				}
			}

			array_push($rows, $totalsRow);
			$rowNum++;
		}

		$sheet->fromArray($rows, null, 'A1', true);

		$sheet->mergeCells("A1:{$lastCol}1");
		$sheet->mergeCells("A2:{$lastCol}2");
		$sheet->mergeCells("A3:{$lastCol}3");

		$titleStyle = array(
			'font' => array(
				'bold' => true,
				'size' => 22
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);

		$subTitleStyle = array(
			'font' => array(
				'bold' => true,
				'size' => 18
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);

		$headerStyle = array(
			'font' => array(
				'bold' => true
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'borders' => array(
				'bottom' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$centerStyle = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);

		$dataStyle = array(
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$totalsStyle = array(
			'font' => array(
				'bold' => true
			),
			'borders' => array(
				'top' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$sheet->getStyle("A1:{$lastCol}1")->applyFromArray($titleStyle);
		$sheet->getStyle("A2:{$lastCol}2")->applyFromArray($subTitleStyle);
		$sheet->getStyle("A{$headerRowNum}:{$lastCol}{$headerRowNum}")->applyFromArray($headerStyle); // NOTE: This used to be $sheet->getStyle('1') for the whole first row, but that causes MASSIVE memory usage in Excel
		$sheet->getStyle("A{$dataRowNum}:{$lastCol}{$lastDataRowNum}")->applyFromArray($dataStyle);

		$sheet->getStyle("A{$dataRowNum}:B{$lastDataRowNum}")->applyFromArray($centerStyle);
		$sheet->getStyle("E{$dataRowNum}:H{$lastDataRowNum}")->applyFromArray($centerStyle);

		$sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray($totalsStyle);

		foreach ($columns as $i => $colName) {
			$xlCol = $this->utils->getXLCol($i);

			if ($i == 8) {
				$sheet->getColumnDimension($xlCol)->setWidth(10);
			} else {
				$sheet->getColumnDimension($xlCol)->setAutoSize(TRUE);
			}
		}

		$sheet->getRowDimension(1)->setRowHeight(36);
		$sheet->getRowDimension(2)->setRowHeight(28);

		$sheet->getStyle("D{$dataRowNum}:D{$rowNum}")->getNumberFormat()->setFormatCode('$0.0000');

		$sheet->getStyle("I{$dataRowNum}:I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle("J{$dataRowNum}:J{$rowNum}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$sheet->getStyle("K{$dataRowNum}:K{$rowNum}")->getNumberFormat()->setFormatCode('$0.0000');
		$sheet->getStyle("L{$dataRowNum}:L{$rowNum}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

		// PRINT SETTINGS
		$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		$sheet->getPageSetup()->setFitToWidth(1);
		$sheet->getPageSetup()->setFitToHeight(0);

		$sheet->getPageMargins()->setTop(0.25);
		$sheet->getPageMargins()->setRight(0.25);
		$sheet->getPageMargins()->setLeft(0.25);
		$sheet->getPageMargins()->setBottom(0.25);

		// $sheet->getStyle("{$lastCol}2:{$lastCol}{$rowNum}") )->getNumberFormat()->setFormatCode( 'mm/dd/yy' );

		// capture the output of the save method and put it into $content as a string
		ob_start();
		$objWriter = PHPExcel_IOFactory::createWriter($phpxl, "Excel2007");
		$objWriter->save("php://output");

		$content = ob_get_contents();
		ob_end_clean();

		// Getting a response instance
		$response = new Response();

		// $response->setHeader("Content-Type", "application/vnd.ms-excel");
		$response->setHeader("Content-Type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"); // for Excel2007
		$response->setHeader('Content-Disposition', 'attachment; filename="curing_report.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}

	private function sumTotal($sheet, $rowNum, $column)
	{
		$sheet->setCellValue(
			$column . ($rowNum + 1),
			'=SUM(' . $column . '2:' . $column . $rowNum . ')'
		);
		$totalStyle = array(
			'borders' => array(
				'top' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				),
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
		);
		$sheet->getStyle($column . ($rowNum + 1) . ':' . $column . ($rowNum + 1))->applyFromArray($totalStyle);
		// $sheet->getStyle($column . $rowNum + 1 . ":" . $column . $rowNum + 1)->applyFromArray($totalStyle);
	}

	private function getPeriodDates($dateIn, $startDate, $endDate, $billingFrequency = '1 MONTH')
	{
		// Instantiate an array to hold the billing periods
		$billingPeriodArray = [];
		$dayInSec = 86400;

		if ($billingFrequency == '2 WEEKS') {
			$twoWeeksInSecs = $dayInSec * 14;

			// Number of 2 week periods from the when the lot's date in to the start date of the requested report
			// Calculate how many billing periods have been entered since the beginning of the lot and the end date of the report
			$numOfPriorPeriods = max(0, ceil((($startDate - $dateIn)) / $twoWeeksInSecs));

			// Start of the report range plus the number of billing periods times two weeks
			$startOfPeriod = $dateIn + $numOfPriorPeriods * $twoWeeksInSecs;

			// The next billing period will start in two weeks, so this billing period ends one day prior
			$endOfPeriod = $startOfPeriod + $twoWeeksInSecs - $dayInSec;


			// 1st period
			array_push($billingPeriodArray, $this->constructBillingPeriodArray($startOfPeriod, $endOfPeriod));

			if ($endOfPeriod < $endDate) { // generate and push another period.
				// Start of the report range plus the number of billing periods times two weeks
				$startOfPeriod = $endOfPeriod + $dayInSec;

				// The next billing period will start in two weeks, so this billing period ends one day prior
				$endOfPeriod = $startOfPeriod + $twoWeeksInSecs - $dayInSec;

				// 1st period
				array_push($billingPeriodArray, $this->constructBillingPeriodArray($startOfPeriod, $endOfPeriod));
			}
		} else {
			$mDayIn = getdate($dateIn)['mday'];

			if ($mDayIn > getdate($endDate)['mday']) {
				$refDate = $startDate;
			} else {
				$refDate = $endDate;
			}

			$startYm = date('Y-m-', $refDate);
			$startMonth = date('m', $refDate);
			$startOfPeriod = strtotime($startYm . $mDayIn);

			$endOfPeriod = strtotime('+1 month', $startOfPeriod);
			$endOfPeriod = strtotime('yesterday', $endOfPeriod);

			if ($startMonth == 1 && $mDayIn > 28) {
				$endOfPeriod = strtotime('last day of next month', $startOfPeriod);
				$endOfPeriod = strtotime('yesterday', $endOfPeriod);
			}

			if ($startMonth == 2 && $mDayIn == 29) {
				$endOfPeriod = strtotime('last day of next month', $startOfPeriod);
				$endOfPeriod = strtotime('yesterday', $endOfPeriod);
			}

			if ($startMonth == 2 && $mDayIn == 28) {
				$endOfPeriod = strtotime('last day of next month', $startOfPeriod);

				$whatYear = date('Y', $refDate); // maybe broken if large selection range?
				if (($whatYear % 4 == 0 && $whatYear % 100 != 0) || $whatYear % 400 == 0) { // if a leap year
					$endOfPeriod = strtotime('4 days ago', $endOfPeriod); // March 27th
				}
			}

			if (($startMonth == 3 || $startMonth == 5 || $startMonth == 7 || $startMonth == 8
				|| $startMonth == 10 || $startMonth == 12) && $mDayIn == 31) {
				$endOfPeriod = strtotime('last day of next month', $startOfPeriod);
				$endOfPeriod = strtotime('yesterday', $endOfPeriod);
			}

			if (($startMonth == 4 || $startMonth == 6 || $startMonth == 9 || $startMonth == 11) && $mDayIn == 30) {
				$endOfPeriod = strtotime('last day of next month', $startOfPeriod);
			}

			array_push($billingPeriodArray, $this->constructBillingPeriodArray($startOfPeriod, $endOfPeriod));
		}

		// This creates a LOT of logs
		// $this->logger->log( $billingPeriodArray );

		return $billingPeriodArray;
	}

	public function validateclientlotsAction()
	{
		$invalidLots = array();
		$validLots = array();

		$lotsInput = $this->request->getPost('lotsInput');
		$lotsTemp = preg_split('/\s+|,/', $lotsInput);
		$lots = array();
		foreach ($lotsTemp as $lot) {
			$lot = trim($lot);
			if ($lot) {
				array_push($lots, $lot);
			}
		}

		$numLots = count($lots);
		if ($numLots) {
			$custId = $this->session->customerAuth['CustomerID'];
			$sql = 'select LotNumber from Lot where CustomerID = ? ';
			$params = array($custId);
			if ($numLots > 1) {
				$qmarks = str_split(str_repeat("?", $numLots)); // create a string array of the right number of ?-marks
				$sql .= "AND LotNumber in (" . implode(',', $qmarks) . ") ";
			} else {
				$sql .= "AND LotNumber = ? ";
			}

			foreach ($lots as $lot) {
				array_push($params, $lot);
			}

			// get valid lots for this customer from the db
			$connection = $this->db;
			$connection->connect();
			$lotsFound = $connection->query($sql, $params);
			$lotsFound = $lotsFound->getInternalResult()->fetchAll(PDO::FETCH_COLUMN);

			// go through list of supplied lots to see
			// if any of them were not found in our query above
			foreach ($lots as $lot) {
				if (!in_array($lot, $lotsFound)) {
					array_push($invalidLots, $lot);
				} else {
					array_push($validLots, $lot);
				}
			}
		}
		$data = array(
			'validLots' => $validLots,
			'invalidLots' => $invalidLots
		);
		$this->view->data = $data;
		$this->view->pick("layouts/json");
	}



	private function constructBillingPeriodArray($startOfPeriod, $endOfPeriod)
	{
		return [
			'start' => $startOfPeriod,
			'end' => $endOfPeriod,
			'formatted' => date('m/d/y', $startOfPeriod) . ' - ' . date('m/d/y', $endOfPeriod)
		];
	}
}
