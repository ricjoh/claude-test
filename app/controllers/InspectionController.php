<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Db\RawValue;

class InspectionController extends Controller
{
	public function exportAction()
	{
		$custId = $this->session->customerAuth['CustomerID'];
		$lotNumbers = $this->request->getPost('lotNumbers');
		$lotNumbers = explode(',', $lotNumbers);
		$customer = Customer::getCustomer($custId);

		$qmarks = '';
		$counter = 0;
		$inStr = '';
		$numLotNumbers = count($lotNumbers);
		foreach ( $lotNumbers as $lotNumber ) {
			$comma = ( ($counter + 1) == $numLotNumbers ) ? '' : ',';
			$inStr .= '?' . $counter . $comma;
			$counter++;
		}
		// 32866,24305,27141,43844,37492,35125,26262
		$lots = $customer->getLot([ 'order' => 'LotNumber', 'conditions' => "LotNumber in ( {$inStr} )", 'bind' => $lotNumbers ]);

		require_once(dirname(__FILE__)."/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Inspection');

		$rows = array();

		$columns = array(
			'LCD Item Number', // ProductCode
			'LCD Batch', // CustomerPONumber
			'OCS Batch', // LotNumber
			'LCD Lot Number', // CustomerLotNumber
			'LCD Tank Number', // VatNumber
			'Pallet',
			'Mold Count',
			'Comments',
			'Inspection Date'
		);
		array_push($rows, $columns);

        $ci = count($lots);
        $rowNum = 1;

		if ( $ci ) {
			$rowNum = 1;

			$db = $this->getDI()->getShared('db');
			$db->connect();
			foreach ($lots as $lot)
			{
				$sql = "select distinct CustomerLotNumber, VatNumber
				from Vat where LotID = ?
				order by LPAD(lower(VatNumber), 15, 0)";
				$vats = $db->query($sql, array($lot->LotID));
				$vats = $vats->fetchAll($vats);
				foreach ( $vats as $vat )
				{
					$customerLotNumber = $vat['CustomerLotNumber'];
					$vatNumber = $vat['VatNumber'];
					// foreach ($lot->getVat(['order' => 'LPAD(lower(VatNumber), 15, 0)']) as $vat) { // the LPAD is a trick to make varchars sort more numerical-like
					// $customerLotNumber = $vat->CustomerLotNumber;
					// $vatNumber = $vat->VatNumber;
					// $inspection = $vat->inspection;
					//
					// $inspectionDate = strtotime($inspection->InspectionDate);
					// $inspectionDate = PHPExcel_Shared_Date::PHPToExcel($inspectionDate);
					$dateIn = strtotime($row['DateIn']);
					$dateIn = PHPExcel_Shared_Date::PHPToExcel($dateIn);

					$xlRow = array(
						$lot->ProductCode,
						$lot->CustomerPONumber,
						$lot->LotNumber,
						$customerLotNumber,
						$vatNumber,
						'',
						'',
						'',
						date('Y-m-d')
					);
					array_push($rows, $xlRow);

					$rowNum++;
					// }
				}
			}
			$db->close();
		}

		$sheet->fromArray($rows);

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
		$sheet->getStyle('A1:I1')->applyFromArray($headerStyle); // NOTE: This used to be $sheet->getStyle('1') for the whole first row, but that causes MASSIVE memory usage in Excel
		$sheet->getColumnDimension('A')->setAutoSize(TRUE);
		$sheet->getColumnDimension('B')->setAutoSize(TRUE);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE);
		$sheet->getColumnDimension('H')->setWidth(40);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE);

		$sheet->getStyle('I2:I' . ($rowNum) )->getNumberFormat()->setFormatCode( 'mm/dd/yy' );

		$moldCountValidation = $sheet->getDataValidation( 'G2:G' . ($rowNum) );
		$moldCountValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$moldCountValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
		$moldCountValidation->setAllowBlank(false);
		$moldCountValidation->setShowInputMessage(true);
		$moldCountValidation->setShowErrorMessage(true);
		$moldCountValidation->setShowDropDown(true);
		$moldCountValidation->setErrorTitle('Input error');
		$moldCountValidation->setError('Value is not in list.');
		$moldCountValidation->setPromptTitle('Pick from list');
		$moldCountValidation->setPrompt('Please pick a value from the drop-down list.');
		$moldCountValidation->setFormula1('"0,1,2,3,4,5,6,7,8,9,10,11"');

		// $sheet->getProtection()->setPassword('protected for your safety');
		// $sheet->getProtection()->setSheet(true);

		$editableStyle = $sheet->getStyle("F2:I$rowNum");
		// $editableStyle->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
		$editableStyle->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

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

		$filename = 'inspection_';
		$lotList = array_slice($lotNumbers, 0, 5);
		$filename .= implode('_', $lotList);
		if (count($lotNumbers) > 5) $filename .= '_etc';
		$response->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}


	//  ### #     # ######  ####### ######  #######
	//   #  ##   ## #     # #     # #     #    #
	//   #  # # # # #     # #     # #     #    #
	//   #  #  #  # ######  #     # ######     #
	//   #  #     # #       #     # #   #      #
	//   #  #     # #       #     # #    #     #
	//  ### #     # #       ####### #     #    #

	public function importAction()
	{
		if (!$_FILES['inspectionImport'] || !$_FILES['inspectionImport']['tmp_name']) return;

		$this->view->isImporting = true;

		require_once(dirname(__FILE__)."/../../lib/PHPExcel/PHPExcel.php");

		$phpxl = PHPExcel_IOFactory::load($_FILES['inspectionImport']['tmp_name']);
		$sheet = $phpxl->getActiveSheet();

		$highestRow = $sheet->getHighestDataRow();
		$highestCol = $sheet->getHighestDataColumn();
		$highestColIdx = PHPExcel_Cell::columnIndexFromString($highestCol);

		$headings = $sheet->rangeToArray(
		    "A1:{$highestCol}1",     // The worksheet range that we want to retrieve
		    NULL,        // Value that should be returned for empty cells
		    FALSE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
		    FALSE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
		    FALSE         // Should the array be indexed by cell row and cell column
		)[0];

		// $this->logger->log($headings);

		$lots = array();
		$lotsImported = 0;
		$rowsInserted = 0;
		$rowsUpdated = 0;
		$rowsFailed = array();

		for ($row = 2; $row <= $highestRow; $row++) {
			$inspectionData = array();
			$fullRowData = array();

			$lotNumber;
			$vatNumber;
			for ($col = 0; $col <= $highestColIdx; $col++) {
				$value = trim($sheet->getCellByColumnAndRow($col, $row)->getValue()); // ->getFormattedValue();
				$heading = $headings[$col];

				if (!$heading) continue;

				switch ($heading) {
					case 'OCS Batch':
						$lotNumber = $value;
						break;

					case 'LCD Tank Number':
						$vatNumber = trim($value);
						break;

					case 'Inspection Date':
						$value = PHPExcel_Style_NumberFormat::toFormattedString($value, PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
					case 'Mold Count':
					case 'Pallet':
					case 'Comments':
						$dbCol = str_replace(' ', '', $heading);

						// $this->logger->log("$row: '$heading' ($dbCol) = $value");

						if (!$dbCol || (!$value && !is_numeric($value))) continue 2;

						$inspectionData[$dbCol] = $value;

						break;
				}

				$fullRowData[$heading] = $value;
			}

			// $fullRowData = $inspectionData;
			// $fullRowData['lotNumber'] = $lotNumber;
			// $fullRowData['vatNumber'] = $vatNumber;
			$fullRowData['rowNum'] = $row;
			// if ( strlen($vatNumber) ) {
			// 	$vatNumber = (integer) $vatNumber;
			// }
			// $validVatNumber = ($vatNumber || $vatNumber === 0) ? true : false;

			$lot = NULL;
			$vat = NULL;

			$hasVatNumber = strlen($vatNumber);

			if ($lotNumber)
			{
				if ( ($lot = Lot::findFirst("LotNumber = '$lotNumber'")) )
				{
					if ( $hasVatNumber &&
						 ($vatNumber = is_numeric($vatNumber) ? (integer) $vatNumber : NULL) != NULL )
					{
						$vats = $lot->getVat("VatNumber = '$vatNumber'");

						if (count($vats) > 0)
							$vat = $vats[0];
					}
				}
			}

			// if ($lotNumber && $validVatNumber && $inspectionData['InspectionDate'] && isset($inspectionData['MoldCount']))
			if ($lotNumber && $lot && $inspectionData['InspectionDate'] && isset($inspectionData['MoldCount']))
			{
				// $this->logger->log($inspectionData);

				// $lot = Lot::findFirst("LotNumber = '$lotNumber'");
				// $vats = $lot->getVat("VatNumber = '$vatNumber'");

				// $vat = count($vats) > 0 ? $vats[0] : '';

				// $this->logger->log("Lot $lotNumber - ".$lot->LotID);
				// $this->logger->log("Vat $vatNumber - ".$vat->VatID);


				// 	Updated where clause for LotID Changes - 5/31 PS
				/*
					$inspection_wh = "LotID = '{$lot->LotID}' AND InspectionDate = '{$inspectionData['InspectionDate']}'";

					if ($vat) $inspection_wh .= " AND VatID = '{$vat->VatID}'";

					$inspection = Inspection::findFirst($inspection_wh) ?: false;
				*/

				$inspection = false; // Inspection::findFirst("VatID = '{$vat->VatID}' AND InspectionDate = '{$inspectionData['InspectionDate']}'") ?: false;

				$dbAction = '';
				if (!$inspection) {
					// $this->logger->log('Creating new inspection');
					$inspection = new Inspection();
					$inspection->InspectionID	= $this->utils->UUID(mt_rand(0, 65535));
					$inspection->VatID			= ($vat) ? $vat->VatID : new RawValue('default');
					$inspection->LotID			= $lot->LotID;

					$dbAction = 'insert';
				}
				else {
					$dbAction = 'update';
				}

				if ( $inspection->save($inspectionData) == false )
				{
					$fullRowData['errorMessage'] = 'Error while saving this row';
					array_push($rowsFailed, $fullRowData);
					$this->logger->log("Error while saving inspection data");
					$this->logger->log($inspection->getMessages());
				}
				else
				{
					$lots[$lotNumber] = 1;
					if ( $dbAction == 'update' ) {
						$rowsUpdated++;
					}
					else if ( $dbAction == 'insert' ) {
						$rowsInserted++;
					}
				}

			}
			else
			{
				if (!$lotNumber) $fullRowData['errorMessage'] = 'Missing OCS Batch Number';
				elseif (!$lot) $fullRowData['errorMessage'] = 'Invalid OCS Batch Number';
				// elseif (!$validVatNumber) $fullRowData['errorMessage'] = 'Invalid Vat Number';

				// 	Un-Comment to bark about a bad $vatNumber if it's present and not found in the Vat table.
				// elseif ($hasVatNumber && !$vat) $fullRowData['errorMessage'] = 'Invalid Vat Number';
				elseif (!$inspectionData['InspectionDate']) $fullRowData['errorMessage'] = 'Missing Inspection Date';
				elseif (!$inspectionData['MoldCount']) $fullRowData['errorMessage'] = 'Missing Mold Count';

				array_push($rowsFailed, $fullRowData);
			}
		}
		$lotsImported = count($lots);
		$this->view->lotsImported = $lotsImported;
		$this->view->rowsInserted = $rowsInserted;
		$this->view->rowsUpdated = $rowsUpdated;
		$this->view->rowsFailed = $rowsFailed;
	}


	//  ######  ####### ######  ####### ######  #######
	//  #     # #       #     # #     # #     #    #
	//  #     # #       #     # #     # #     #    #
	//  ######  #####   ######  #     # ######     #
	//  #   #   #       #       #     # #   #      #
	//  #    #  #       #       #     # #    #     #
	//  #     # ####### #       ####### #     #    #

	public function reportAction()
	{
		$all = $this->request->get('type') === 'all';

		$inspection = new Inspection();
		$reportData = $inspection->getReportData(null, $all);
		$this->view->reportData = $reportData;
		$this->view->all = $all;
	}

	public function reportxlsAction()
	{
		require_once(dirname(__FILE__)."/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Inspection Report');

		$rows = array();

		$columns = array(
			'LCD Item Number', // ProductCode
			'LCD Batch', // CustomerPONumber
			'OCS Batch', // LotNumber
			'LCD Lot Number', // CustomerLotNumber
			'LCD Tank Number', // VatNumber
			'Pallet',
			'Mold Count',
			'Comments',
			'Inspection Date'
		);
		array_push($rows, $columns);


		$all = $this->request->get('type') === 'all';

		$sort = NULL;

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

		$inspection = new Inspection();
		$reportData = $inspection->getReportData($sort, $all);

        $numRecords = count($reportData);
        $rowNum = 1;
		if ( $numRecords ) {
			$rowNum = 1;

			foreach ($reportData as $row) {
				// $inspection = $vat->inspection;
				//
				// $inspectionDate = strtotime($inspection->InspectionDate);
				// $inspectionDate = PHPExcel_Shared_Date::PHPToExcel($inspectionDate);
				$inspectionDate = strtotime($row['InspectionDate']);
				$inspectionDate = PHPExcel_Shared_Date::PHPToExcel($inspectionDate);

				$xlRow = array(
					$row['ProductCode'],
					$row['CustomerPONumber'],
					$row['LotNumber'],
					$row['CustomerLotNumber'],
					$row['VatNumber'],
					$row['Pallet'],
					$row['MoldCount'],
					$row['Comments'],
					$inspectionDate
				);
				array_push($rows, $xlRow);

				$rowNum++;
			}
		}

		$sheet->fromArray($rows);

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
		$sheet->getStyle('A1:I1')->applyFromArray($headerStyle); // NOTE: This used to be $sheet->getStyle('1') for the whole first row, but that causes MASSIVE memory usage in Excel
		$sheet->getColumnDimension('A')->setAutoSize(TRUE);
		$sheet->getColumnDimension('B')->setAutoSize(TRUE);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE);
		$sheet->getColumnDimension('H')->setAutoSize(TRUE);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE);

		$sheet->getStyle('I2:I' . ($rowNum) )->getNumberFormat()->setFormatCode( 'mm/dd/yy' );

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
		$response->setHeader('Content-Disposition', 'attachment; filename="inspection_report.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}
}
