<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class ParameterController extends Controller {
	// this lists the parameter groups
	public function listAction() {
		$connection = $this->db;
		$connection->connect();
		$sql = 'select ParameterGroupID, Name, Heading1, CreateDate from ParameterGroup order by Name';
		$groups = $connection->query($sql);
		$groups->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$groups = $groups->fetchAll($groups);

		$this->view->groups = $groups;
	}

	// edit page that lists the parameters for a given group
	public function editAction() {
		$ParameterGroupID = $this->dispatcher->getParam("id"); // from index.php dispatcher
		$archiveFlag = $this->dispatcher->getParam("relid"); // from index.php dispatcher // 4th param if set
		$connection = $this->db;
		$connection->connect();

// TODO: DeactiveDate = Archived
		$sql = 'select Name, Heading1, Heading2, Heading3, Heading4 from ParameterGroup where ParameterGroupID = ?';

		$params = array($ParameterGroupID);
		$group = $connection->query($sql, $params);
		$group->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$group = $group->fetchAll($group);

		$groupName = '';
		if (isset($group[0])) {
			$groupName = $group[0]['Name'];
		}

		$this->view->parameterGroup = $group[0];
		$this->view->ParameterGroupID = $ParameterGroupID;
		$this->view->groupName = $groupName;
		$this->view->archiveFlag = $archiveFlag;
	}

	// this is the function(called via ajax) that gets parameters
	// for a given group on the /parameter/edit/... page
	public function getparametersAction() {
		// TODO: DeactiveDate = Archived
		$ParameterGroupID = $this->dispatcher->getParam("id"); // from index.php dispatcher
		$archiveFlag = $this->dispatcher->getParam("relid"); // from index.php dispatcher // 4th param if set
		// TODO: get another param for archived
		// TODO: add date binding if archived param is not set

		if ( $archiveFlag == 'A') {
			$parameters = Parameter::find(array(
				'conditions' => 'ParameterGroupID = :ParameterGroupID:',
				'bind' => array('ParameterGroupID' => $ParameterGroupID),
				'order' => 'Value1'
			));
		} else {
			$parameters = Parameter::find(array(
				'conditions' => 'ParameterGroupID = :ParameterGroupID: AND DeactivateDate > :now:',
				'bind' => array('ParameterGroupID' => $ParameterGroupID, 'now' => date("Y-m-d H:i:s")),
				'order' => 'Value1'
			));
		}

		$parameterGroup = Parameter::getParameterGroup($ParameterGroupID);

		$this->view->parameterGroup = $parameterGroup;
		$this->view->ParameterGroupID = $ParameterGroupID;
		$this->view->parameters = $parameters;
		$this->view->utils = $this->utils;
		$this->view->archiveFlag = $archiveFlag;
		$this->view->pick("parameter/ajax/getparameters");
	}

	public function getsingleparameterAction() {
			$ParameterID = $this->dispatcher->getParam("id");
			$parameter = Parameter::findFirst("ParameterID = '$ParameterID'");
			$DeactivateDate = '';
			if ($parameter->DeactivateDate) {
					$DeactivateDate = $this->utils->slashDate($parameter->DeactivateDate);
			}

			$parameter->DeactivateDate = $DeactivateDate;
			$this->view->data = $parameter;
			$this->view->pick("layouts/json");
	}


	public function saveAction() {
		if ($this->request->isPost() == true) {
			$ParameterID = $this->request->getPost('ParameterID');

			if (!$ParameterID) {
				$ParameterID = 0;
			}

			try {
				$parameter = Parameter::findFirst("ParameterID = '$ParameterID'");
			} catch (Exception $e) {
				$this->logger->log('Exception loading parameter - ' . $e->getMessage());
			}

			if (!$parameter) { // add new parameter
				$parameter = new Parameter();
				$parameter->ParameterID = $this->UUID;
				$parameter->CreateDate = $this->mysqlDate;
				$parameter->CreateId = $this->session->userAuth['UserID'] ?? User::EDI_USER; // sometimes called in 943 EDI processing  // id of user who is logged in
			} else { // update existing parameter
				$parameter->UpdateDate = $this->mysqlDate;
				$parameter->UpdateId = $this->session->userAuth['UserID'] ?? User::EDI_USER; // id of user who is logged in
			}

			$parameter->ReadOnly = 0;
			$parameter->ParameterGroupID = $this->request->getPost('ParameterGroupID');
			$parameter->Value1 = $this->request->getPost('Value1');
			$parameter->Value2 = $this->request->getPost('Value2');
			$parameter->Value3 = $this->request->getPost('Value3');
			$parameter->Value4 = $this->request->getPost('Value4');

			$parameter->Description = $this->request->getPost('Description');

			$DeactivateDate = $this->request->getPost('DeactivateDate');
			if ($DeactivateDate) {
				$DeactivateDate = $this->utils->dbDate($DeactivateDate);
			} else {
				$DeactivateDate = '2050-12-31 00:00:00.000000';
			}
			$parameter->DeactivateDate = $DeactivateDate;

			try {
				$success = 1;
				if ($parameter->save() == false) {
					foreach ($parameter->getMessages() as $message) {
						$this->logger->log('error saving parameter - ' . $message);
					}
					$success = 0;
				}
			} catch (Exception $e) {
				$this->logger->log('Exception saving parameter - ' . $e->getMessage());
			}

			$this->view->data = array('success' => $success);
			$this->view->pick("layouts/json");
		}
	}
}
