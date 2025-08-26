<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class ContaminenttestController extends Controller
{
	public function editAction() {
		$this->view->pick("contaminenttest/ajax/edit");

		// CheeseTests = 8DF6985A-0DD6-417C-9C1E-E1F106D3A5A8
		// CheeseTestResults = 4191E342-FA34-4649-A469-4586814BB3F8

		$cheeseTests = Parameter::getValuesForGroupId('8DF6985A-0DD6-417C-9C1E-E1F106D3A5A8', array('orderBy' => 'ParameterID'));
		$cheeseTestResults = Parameter::getValuesForGroupId('4191E342-FA34-4649-A469-4586814BB3F8');

		// create artificial 'N/A' option
		array_unshift($cheeseTestResults, array('ParameterID' => '00000000-0000-0000-0000-000000000000', 'Value1' => ''));

		$lotId = $this->dispatcher->getParam("id");

		$tests = array();

		$testGroup = ContaminentTestGroup::findFirst("LotID = '$lotId'");
		if ($testGroup) {
			$testGroupTests = $testGroup->getContaminentTest(array('order' => 'TestPerformedPID'));

			foreach ($testGroupTests as $test) {
				$tests[$test->TestPerformedPID] = array(
					'TestResultsPID' => $test->TestResultsPID,
					'TestResults' => Parameter::getValue($test->TestResultsPID),
					'NoteText' => $test->NoteText
				);
			}
		}

		$this->view->lotId = $lotId;
		$this->view->tests = $tests;
		$this->view->testGroup = $testGroup ? $testGroup->toArray() : array('TestDate' => $this->mysqlDate);
		$this->view->cheeseTests = $cheeseTests;
		$this->view->cheeseTestResults = $cheeseTestResults;
	}

	public function saveAction()
	{
		$lotId = $this->dispatcher->getParam("id");

		if ($this->request->isPost() == true && $lotId) {
			// $this->logger->log( '*********************** Posted *************************' );
			// $this->logger->log( $_REQUEST );

			$cheeseTests = Parameter::getValuesForGroupId('8DF6985A-0DD6-417C-9C1E-E1F106D3A5A8', array('orderBy' => 'ParameterID'));

			$testGroup = ContaminentTestGroup::findFirst("LotID = '$lotId'");

			$testGroupTests = array();

			if ($testGroup) {
				$testGroupTestsSet = $testGroup->getContaminentTest();

				foreach ($testGroupTestsSet as $testGroupTest) {
					$testGroupTests[$testGroupTest->TestPerformedPID] = $testGroupTest;
				}
			}

			$testGroupId = $testGroup ? $testGroup->ContaminentTestGroupID : 0;

			$updateFields = array(
				'TestDate',
				'NoteText'
			);

			$newTestGroupId = '';

			if ( ! $testGroupId ) { // add new test group
				$this->logger->log( 'new test group' );
				$testGroup = new ContaminentTestGroup();

				$newTestGroupId = $this->utils->UUID(mt_rand(0, 65535));
				$testGroup->ContaminentTestGroupID = $newTestGroupId;
				$testGroupId = $newTestGroupId;

				$testGroup->LotID = $lotId;
				$testGroup->CreateDate = $this->mysqlDate;
				$testGroup->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
			} else { // update existing lot
				$this->logger->log( 'found BOL' );
				$testGroup->UpdateDate = $this->mysqlDate;
				$testGroup->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}

			$this->logger->log( 'update fields' );

			foreach ( $updateFields as $f ) {
				$postValue = $this->request->getPost( $f );

				// $this->logger->log( $f . " = " . $postValue );

				// if ($postValue) {
					if ( $f == 'TestDate') {
						$testGroup->{ $f } = $this->utils->dbDate( $postValue );
					} else {
						$testGroup->{ $f } = $postValue;
					}
				// }
			}

			$this->logger->log( "CreateDate = " . $testGroup->CreateDate );
			$this->logger->log( "CreateId = " . $testGroup->CreateId );
			$this->logger->log( "UpdateDate = " . $testGroup->UpdateDate );
			$this->logger->log( "UpdateId = " . $testGroup->UpdateId );

			$this->logger->log( "saving group..." );

			$success = 1;
			try {
				if ( $testGroup->save() == false) {
					$this->logger->log( 'FAIL!' );
					$msg = "Error saving Test Group:\n\n" . implode("\n", $testGroup->getMessages() );
					$this->logger->log( "$msg\n" );
					$success = 0;
				}
			} catch (\Exception $e) {
				$msg = "Error saving Test Group:\n\n" . $e->getMessage();
				$this->logger->log( 'FAIL! ' . $msg );
				$success = 0;
			}

			$this->logger->log( "save group? Success = $success" );

			// now do individual tests themselves
			if ($success) {
				foreach ($cheeseTests as $cheeseTestParam) {
					$pid = $cheeseTestParam['ParameterID'];
					$results = $this->request->getPost($pid);
					$note = $this->request->getPost("note_$pid");

					$test = isset($testGroupTests[$pid]) ? $testGroupTests[$pid] : FALSE;

					if ( ! $test ) { // add new test
						$this->logger->log( 'new test' );
						$test = new ContaminentTest();

						$newTestId = $this->utils->UUID(mt_rand(0, 65535));
						$test->ContaminentTestID = $newTestId;
						// $testId = $newTestId;

						$test->ContaminentTestGroupID = $testGroupId;
						$test->TestPerformedPID = $pid;

						$test->CreateDate = $this->mysqlDate;
						$test->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
					} else { // update existing lot
						$this->logger->log( 'found test' );
						$test->UpdateDate = $this->mysqlDate;
						$test->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
					}

					$test->TestResultsPID = $results ? $results : '00000000-0000-0000-0000-000000000000';
					$test->NoteText = $note;
					try {
						if ( $test->save() == false) {
							$this->logger->log( 'FAIL!' );
							$msg = "Error saving Test:\n\n" . implode("\n", $test->getMessages() );
							$this->logger->log( "$msg\n" );
							$success = 0;

							break;
						}
					} catch (\Exception $e) {
						$this->logger->log( 'FAIL!' );
						$msg = "Error saving Test:\n\n" . $e->getMessage();
						$success = 0;
					}
				}
			}

			$this->view->data = array('success' => $success,
									  'status' => ($success ? 'success' : 'error'),
									  'msg' => $msg,
									  'testGroupId' => $testGroupId,
									  'newTestGroupId' => $newTestGroupId );
			$this->view->pick("layouts/json");

		}
	}
}
