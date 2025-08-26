<?php

use Phalcon\Mvc\Controller;

class LoginuserController extends Controller {

	// this is what actually logs them in
	private function _registerSession($user) {
		// you can access these variables like so
		// $this->session->userAuth['FirstName']
		$this->session->set('userAuth', array(
			'UserID' => $user->UserID,
			'FirstName' => $user->FirstName,
			'LastName' => $user->LastName,
			'FullName' => $user->FullName,
		));
		// update last login to current date
		$user->LastLogin = $this->mysqlDate;
		$user->save();
	}

	// this will request /app/views/<controllername>/index.phtml
	public function indexAction() {
		if ($this->request->isPost()) {
			$LoginID = trim($this->request->getPost('LoginID'));
			$password = trim($this->request->getPost('password'));

			$user = User::findFirst(array(
				'conditions' => 'LoginID = :LoginID: AND StatusPID = :StatusPID:',
				'bind' => array('LoginID' => $LoginID, 'StatusPID' => USER::ACTIVE_PID), // make sure they are an active user
			));

			if ($user) {
				if ($this->security->checkHash(
					$password,
					$user->Password
				)) {
					// The password is valid
					$this->_registerSession($user);

					// Check if the url_after_login parameter exists
					$urlAfterLogin = $this->request->get('url_after_login');

					if ($urlAfterLogin) {
						// Redirect to the provided URL
						return $this->response->redirect($urlAfterLogin);
					} else {
						// Redirect to the root of the site
						return $this->response->redirect('');
					}
				} else {
					$this->flash->error("Invalid login information. Please try again.");
				}
			} else {
				$this->flash->error("Invalid login information. Please try again.");
			}
		}
	}

	public function logoutAction() {
		$this->logger->log('logging out... ID:' . $this->session->userAuth['UserID'] );
		$user = User::findFirst(array(
			'conditions' => 'UserID = :UserID:',
			'bind' => array('UserID' => $this->session->userAuth['UserID']),
		));
		// update last logout to current date
		$user->LastLogout = $this->mysqlDate;
		$user->save();
		$this->session->remove('userAuth');
		return $this->response->redirect('login');
	}

	//	 public function encryptpasswordsAction() {
	//	 	$content = '';
	//
	//	 	$logins = User::find('LoginID IS NOT NULL');
	//
	//	 	foreach ($logins as $login) {
	//	 		$encrypted = $this->security->hash($login->Password);
	//
	//	 		$content .= "<p>$login->LoginID : $login->Password --> $encrypted</p>";
	//
	//	 		if ($this->security->checkHash($login->Password, $encrypted)) $content .= '<p>It checks out</p>';
	//
	//	 		$login->Password = $encrypted;
	//	 		$login->save();
	//	 	}
	//
	//	 	return $this->response->setContent($content);
	//	 }

}
