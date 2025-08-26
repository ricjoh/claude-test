<?php

use Phalcon\Mvc\Controller;

class UserController extends Controller
{

	// this will request /app/views/<controllername>/index.phtml
	public function detailAction()
	{
        $userId = $this->dispatcher->getParam("id"); // from index.php dispatcher
        // $this->logger->log( "Detail: $userId" );
        $this->view->data = User::getUserDetail( $userId );
        $this->view->pick("layouts/json");
	}

	public function editAction()
	{
		$userId = $this->dispatcher->getParam("id"); // from index.php dispatcher
        if ( $userId == 'NEW' ) $userId = 0;
		$this->view->userId = $userId;

		$userData = new stdClass();
		if ( $userId )
		{
			$userData = User::findFirst("UserID = '$userId'");
		}

		$paramModel = new Parameter();
		$statuses = $paramModel->getValuesForGroupId(
			'5ABE7F55-F75E-4F3F-8D5B-EFA95625BED6',
		);

		$roles = $paramModel->getValuesForGroupId(
			'F9626EBA-6D4A-408D-BDF6-9A8FFBB00631',
		);

		$userModel = new User();
		$loginIds = array();
		$users = $userModel->getUsers();
		foreach ( $users as $u ) {
			$loginId = trim($u->LoginID);
			if ( $loginId ) {
				$loginIds[strtolower($loginId)] = 1;
			}
		}

		// $this->logger->log($statuses);
		$this->view->loginIds = $loginIds;
		$this->view->userData = $userData;
		$this->view->statuses = $statuses;
		$this->view->roles = $roles;
		$this->view->isCurrentUser = $this->session->userAuth['UserID'] == $userId;

		// page has been posted to
        if ($this->request->isPost() == true)
		{

			// $this->logger->log('posted to user controller, user id = ' . $userId);
			$user = User::findFirst("UserID = '$userId'");

			# delete user
			if ( $this->request->getPost('deleteUser') )
			{
				if ($user != false)
				{
					if ($user->delete() == false)
					{
						$msg = '<strong>Error deleting user</strong></br />';
						foreach ($user->getMessages() as $message) {
							$msg .= $message . '<br />';
						}
						$this->flash->error($msg);
					} else
					{
						$this->flash->success("User deleted successfully");
					}
				}

			}
			elseif ( $this->request->getPost('saveUser') )
			{

				if ( !$user ) { // add new user
					$user = new User();
					$user->UserID = $this->UUID;
					$user->RolePID = User::SU_ROLE_PID;
					$user->RequirePasswordChange = 0;
					$user->CreateDate = $this->mysqlDate;
					$user->CreateID = $this->session->userAuth['UserID']; // id of user who is logged id
				}
				else { // update existing user
					$user->UpdateDate = $this->mysqlDate;
					$user->UpdateID = $this->session->userAuth['UserID']; // id of user who is logged id
				}

				$user->FirstName = $this->request->getPost('FirstName');
				$user->LastName = $this->request->getPost('LastName');
				$user->FullName = $this->request->getPost('FirstName') . ' ' . $this->request->getPost('LastName');
				$user->LoginID = $this->request->getPost('LoginID');
				$password = $this->request->getPost('Password');
				if ( $password ) {
					$user->Password = $this->security->hash($password);
					// $user->Password = $password;
				}
				$user->StatusPID = $this->request->getPost('StatusPID');
				$user->RolePID = $this->request->getPost('RolePID');

				$optionalFields = array('Email', 'Phone', 'Fax');
				foreach ( $optionalFields as $of ) {
					$val = trim($this->request->getPost($of));
					if ( $val ) {
						$user->$of = $val;
					}
				}

				#save user
				if ( $user->save() == false) {
					foreach ($user->getMessages() as $message) {
						$this->logger->log('error saving user - ' . $message);
					}
					$this->flash->error("Error saving user");
				}
				else {
					$this->flash->success("User saved successfully");
				}
			}

			// Forward back to list page
			return $this->response->redirect('user/list');

        }


	}

	public function listAction()
	{
		$user = new User();
		$users = $user->getUsers();

		$this->view->users = $users;
	}

}
