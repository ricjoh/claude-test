<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Db\RawValue;

class GradingController extends Controller
{
	public function exportAction()
	{
		$numOfRows = 100;
		$custId = $this->session->customerAuth['CustomerID'];
		$customer = Customer::getCustomer($custId);

		$lotNumbers = $this->request->getPost('lotNumbers');
		$lotNumbers = explode(',', $lotNumbers);
		$qmarks = '';
		$counter = 0;
		$inStr = '';
		$numLotNumbers = count($lotNumbers);
		foreach ( $lotNumbers as $lotNumber ) {
			$comma = ( ($counter + 1) == $numLotNumbers ) ? '' : ',';
			$inStr .= '?' . $counter . $comma;
			$counter++;
		}
		// 32866,24305,27141
		$lots = $customer->getLot([ 'order' => 'LotNumber', 'conditions' => "LotNumber in ( {$inStr} )", 'bind' => $lotNumbers ]);

		require_once(dirname(__FILE__)."/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Grading');

		$rows = array();

		$columns = array(
			'LCD Item Number', // ProductCode
			'LCD Batch', // CustomerPONumber
			'OCS Batch', // LotNumber
			'LCD Lot Number', // CustomerLotNumber
			'LCD Tank Number', // VatNumber
			'Exterior Color',
			'Interior Color',
			'Knit',
			'Application',
			'Flavor',
			'Net # Graded',
			'Wheel Destination',
			'Comments',
			'Grading Date'
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
					// $grading = $vat->grading;
					//
					// $gradingDate = strtotime($grading->GradingDate);
					// $gradingDate = PHPExcel_Shared_Date::PHPToExcel($gradingDate);
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
						'',
						'',
						'',
						'',
						'',
						date('Y-m-d') // date('m/d/Y')
					);
					array_push($rows, $xlRow);

					$rowNum++;
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
		$sheet->getStyle('A1:N1')->applyFromArray($headerStyle); // NOTE: This used to be $sheet->getStyle('1') for the whole first row, but that causes MASSIVE memory usage in Excel
		$sheet->getColumnDimension('A')->setAutoSize(TRUE);
		$sheet->getColumnDimension('B')->setAutoSize(TRUE);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE);
		$sheet->getColumnDimension('H')->setAutoSize(TRUE);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE);
		$sheet->getColumnDimension('J')->setAutoSize(TRUE);
		$sheet->getColumnDimension('K')->setAutoSize(TRUE);
		$sheet->getColumnDimension('L')->setAutoSize(TRUE);
		$sheet->getColumnDimension('M')->setWidth(40);
		$sheet->getColumnDimension('N')->setAutoSize(TRUE);

		$sheet->getStyle('N2:N' . ($rowNum + $numOfRows) )->getNumberFormat()->setFormatCode( 'mm/dd/yy' );

		$extColorValidation = $sheet->getDataValidation( 'F2:F' . ($rowNum + $numOfRows) );
		$extColorValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$extColorValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
		$extColorValidation->setAllowBlank(false);
		$extColorValidation->setShowInputMessage(true);
		$extColorValidation->setShowErrorMessage(true);
		$extColorValidation->setShowDropDown(true);
		$extColorValidation->setErrorTitle('Input error');
		$extColorValidation->setError('Value is not in list.');
		$extColorValidation->setPromptTitle('Pick from list');
		$extColorValidation->setPrompt('Please pick a value from the drop-down list.');
		$extColorValidation->setFormula1('"1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,10.5,11"');

		$intColorValidation = $sheet->getDataValidation( 'G2:G' . ($rowNum + $numOfRows) );
		$intColorValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$intColorValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
		$intColorValidation->setAllowBlank(false);
		$intColorValidation->setShowInputMessage(true);
		$intColorValidation->setShowErrorMessage(true);
		$intColorValidation->setShowDropDown(true);
		$intColorValidation->setErrorTitle('Input error');
		$intColorValidation->setError('Value is not in list.');
		$intColorValidation->setPromptTitle('Pick from list');
		$intColorValidation->setPrompt('Please pick a value from the drop-down list.');
		$intColorValidation->setFormula1('"1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,10.5,11"');

		$knitValidation = $sheet->getDataValidation( 'H2:H' . ($rowNum + $numOfRows) );
		$knitValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$knitValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
		$knitValidation->setAllowBlank(false);
		$knitValidation->setShowInputMessage(true);
		$knitValidation->setShowErrorMessage(true);
		$knitValidation->setShowDropDown(true);
		$knitValidation->setErrorTitle('Input error');
		$knitValidation->setError('Value is not in list.');
		$knitValidation->setPromptTitle('Pick from list');
		$knitValidation->setPrompt('Please pick a value from the drop-down list.');
		$knitValidation->setFormula1('"1,1.5,2,2.5,3,3.5,4"');

		// $intColorValidation = $sheet->getDataValidation( 'I2:I' . ($rowNum + $numOfRows) );
		// $intColorValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		// $intColorValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
		// $intColorValidation->setAllowBlank(false);
		// $intColorValidation->setShowInputMessage(true);
		// $intColorValidation->setShowErrorMessage(true);
		// $intColorValidation->setShowDropDown(true);
		// $intColorValidation->setErrorTitle('Input error');
		// $intColorValidation->setError('Value is not in list.');
		// $intColorValidation->setPromptTitle('Pick from list');
		// $intColorValidation->setPrompt('Please pick a value from the drop-down list.');
		// $intColorValidation->setFormula1('"C,B,I,G,S,W"');

		// $sheet->getProtection()->setPassword('protected for your safety');
		// $sheet->getProtection()->setSheet(true);

		$editableStyle = $sheet->getStyle("F2:N$rowNum");
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

		$filename = 'grading_';
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
		if (!$_FILES['gradingImport'] || !$_FILES['gradingImport']['tmp_name']) return;

		$this->view->isImporting = true;

		require_once(dirname(__FILE__)."/../../lib/PHPExcel/PHPExcel.php");

		$phpxl = PHPExcel_IOFactory::load($_FILES['gradingImport']['tmp_name']);
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
			/*if ($row[0] == null && $row[1] == null && $row[2] == null && $row[3] == null && $row[4] == null && $row[5] == null && $row[6] == null && $row[7] == null
				&& $row[8] == null && $row[9] == null && $row[10] == null && $row[11] == null && $row[13] == null){
				continue;
			}*/
			$gradingData = array();
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

					case 'Grading Date':
						$value = PHPExcel_Style_NumberFormat::toFormattedString($value, PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
					case 'Exterior Color':
					case 'Interior Color':
					case 'Knit':
					case 'Application':
					case 'Flavor':
					case 'Net # Graded':
					case 'Wheel Destination':
					case 'Comments':
						$dbCol = str_replace([' ', '#'], ['', 'Num'], $heading);

						// $this->logger->log("$row: '$heading' ($dbCol) = $value");

						if (!$dbCol || !$value) continue 2;

						$gradingData[$dbCol] = $value;

						break;
				}

				$fullRowData[$heading] = $value;
			}

			// $fullRowData = $gradingData;
			// $fullRowData['lotNumber'] = $lotNumber;
			// $fullRowData['vatNumber'] = $vatNumber;
			$fullRowData['rowNum'] = $row;

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
/*
			$lot = NULL;
			if ($lotNumber) $lot = Lot::findFirst("LotNumber = '$lotNumber'");

			$validVatNumber = false;

			if ($lot) {
				if ( strlen($vatNumber) ) {
					$vatNumber = (integer) $vatNumber;
				}
				$validVatNumber = ($vatNumber || $vatNumber === 0) ? true : false;

				if ($validVatNumber) {
					$vats = $lot->getVat("VatNumber = '$vatNumber'");
					$vat = count($vats) > 0 ? $vats[0] : false;

					$validVatNumber = !!$vat;
				}

				// $this->logger->log("Lot $lotNumber - ".$lot->LotID);
				// $this->logger->log("Vat $vatNumber - ".$vat->VatID);
			}
*/

			// if ($lotNumber && $lot && $validVatNumber && $gradingData['GradingDate'] && $gradingData['ExteriorColor'] && $gradingData['InteriorColor'] && $gradingData['Knit'] && $gradingData['Application'] && $gradingData['Flavor']) {
			if ($lotNumber && $lot && $gradingData['GradingDate'] && $gradingData['ExteriorColor'] && $gradingData['InteriorColor'] && $gradingData['Knit'] && $gradingData['Application'] && $gradingData['Flavor'])
			{
				// $this->logger->log($gradingData);

				// $grading = Grading::findFirst("VatID = '{$vat->VatID}' AND GradingDate = '{$gradingData['GradingDate']}'") ?: false;
				$grading_wh = "LotID = '{$lot->LotID}' AND GradingDate = '{$gradingData['GradingDate']}'";

				if ($vat) $grading_wh .= " AND VatID = '{$vat->VatID}'";

				$grading = Grading::findFirst($grading_wh) ?: false;

				$dbAction = '';
				if (!$grading) {
					// $this->logger->log('Creating new grading');
					$grading = new Grading();
					$grading->GradingID = $this->utils->UUID(mt_rand(0, 65535));
					$grading->VatID		= ($vat) ? $vat->VatID : new RawValue('default');
					$grading->LotID		= $lot->LotID;

					$dbAction = 'insert';
				}
				else {
					$dbAction = 'update';
				}

				if ( $grading->save($gradingData) == false )
				{
					$fullRowData['errorMessage'] = 'Error while saving this row';
					array_push($rowsFailed, $fullRowData);
					$this->logger->log("Error while saving grading data");
					$this->logger->log($grading->getMessages());
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
				if (!$lotNumber && !is_numeric($vatNumber)){continue;}
				if (!$lotNumber) $fullRowData['errorMessage'] = 'Missing OCS Batch Number';
				elseif (!$lot) $fullRowData['errorMessage'] = 'Invalid OCS Batch Number';
				// elseif (!$validVatNumber) $fullRowData['errorMessage'] = 'Invalid Vat Number';

				// 	Un-Comment to bark about a bad $vatNumber if it's present and not found in the Vat table.
				// elseif ($hasVatNumber && !$vat) $fullRowData['errorMessage'] = 'Invalid Vat Number';
				elseif (!$gradingData['GradingDate']) $fullRowData['errorMessage'] = 'Missing Grading Date';
				elseif (!$gradingData['ExteriorColor']) $fullRowData['errorMessage'] = 'Missing Exterior Color';
				elseif (!$gradingData['InteriorColor']) $fullRowData['errorMessage'] = 'Missing Interior Color';
				elseif (!$gradingData['Knit']) $fullRowData['errorMessage'] = 'Missing Knit';
				elseif (!$gradingData['Application']) $fullRowData['errorMessage'] = 'Missing Application';
				elseif (!$gradingData['Flavor']) $fullRowData['errorMessage'] = 'Missing Flavor';

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

		$grading = new Grading();
		$reportData = $grading->getReportData(null, $all);
		$this->view->reportData = $reportData;
		$this->view->all = $all;
	}

	public function reportxlsAction()
	{
		require_once(dirname(__FILE__)."/../../lib/PHPExcel/PHPExcel.php");

		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		$phpxl = new PHPExcel();
		$sheet = $phpxl->getActiveSheet();
		$sheet->setTitle('Grading Report');

		$rows = array();

		$columns = array(
			'LCD Item Number', // ProductCode
			'LCD Batch', // CustomerPONumber
			'OCS Batch', // LotNumber
			'LCD Lot Number', // CustomerLotNumber
			'LCD Tank Number', // VatNumber
			'Exterior Color',
			'Interior Color',
			'Knit',
			'Application',
			'Flavor',
			'Net # Graded',
			'Wheel Destination',
			'Comments',
			'Grading Date'
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

		$grading = new Grading();
		$reportData = $grading->getReportData($sort, $all);

        $numRecords = count($reportData);
        $rowNum = 1;
		if ( $numRecords ) {
			$rowNum = 1;

			foreach ($reportData as $row) {
				// $grading = $vat->grading;
				//
				// $gradingDate = strtotime($grading->GradingDate);
				// $gradingDate = PHPExcel_Shared_Date::PHPToExcel($gradingDate);
				$gradingDate = strtotime($row['GradingDate']);
				$gradingDate = PHPExcel_Shared_Date::PHPToExcel($gradingDate);

				$xlRow = array(
					$row['ProductCode'],
					$row['CustomerPONumber'],
					$row['LotNumber'],
					$row['CustomerLotNumber'],
					$row['VatNumber'],
					$row['ExteriorColor'],
					$row['InteriorColor'],
					$row['Knit'],
					$row['Application'],
					$row['Flavor'],
					$row['NetNumGraded'],
					$row['WheelDestination'],
					$row['Comments'],
					$gradingDate
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
		$sheet->getStyle('A1:N1')->applyFromArray($headerStyle); // NOTE: This used to be $sheet->getStyle('1') for the whole first row, but that causes MASSIVE memory usage in Excel
		$sheet->getColumnDimension('A')->setAutoSize(TRUE);
		$sheet->getColumnDimension('B')->setAutoSize(TRUE);
		$sheet->getColumnDimension('C')->setAutoSize(TRUE);
		$sheet->getColumnDimension('D')->setAutoSize(TRUE);
		$sheet->getColumnDimension('E')->setAutoSize(TRUE);
		$sheet->getColumnDimension('F')->setAutoSize(TRUE);
		$sheet->getColumnDimension('G')->setAutoSize(TRUE);
		$sheet->getColumnDimension('H')->setAutoSize(TRUE);
		$sheet->getColumnDimension('I')->setAutoSize(TRUE);
		$sheet->getColumnDimension('J')->setAutoSize(TRUE);
		$sheet->getColumnDimension('K')->setAutoSize(TRUE);
		$sheet->getColumnDimension('L')->setAutoSize(TRUE);
		$sheet->getColumnDimension('M')->setAutoSize(TRUE);
		$sheet->getColumnDimension('N')->setAutoSize(TRUE);

		$sheet->getStyle('N2:N' . ($rowNum) )->getNumberFormat()->setFormatCode( 'mm/dd/yy' );

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
		$response->setHeader('Content-Disposition', 'attachment; filename="grading_report.xlsx"');

		// Set the content of the response
		$response->setContent($content);

		// Return the response
		return $response;
	}

}
