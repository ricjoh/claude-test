<?php

use Phalcon\Mvc\Controller;

class LogincustomerController extends Controller
{

	// this is what actually logs them in
    private function _registerSession($customer)
    {
		// you can access these variables like so
		// $this->session->customerAuth['Name']
        $this->session->set('customerAuth', array(
            'CustomerID' => $customer->CustomerID,
            'Name' => $customer->Name
        ));
    }

	// this will request /app/views/<controllername>/index.phtml
	public function indexAction()
	{

		if ($this->request->isPost())
		{
			$LoginID = trim($this->request->getPost('LoginID'));
			$password = trim($this->request->getPost('password'));

			$active = 1; // make sure they are an active user
			$customer = Customer::findFirst(array(
				'conditions' => 'LoginID = :LoginID: AND Active = :Active:',
				'bind' => array('LoginID' => $LoginID, 'Active' => $active),
			));


			if ($customer)
			{
				if ($this->security->checkHash($password, $customer->Password))
				// if ( $password === $customer->Password )
				{
					// The password is valid
					$this->_registerSession($customer);
						return $this->response->redirect('clients');
				}
				else
				{
					$this->flash->error("Invalid login information. Please try again.");
				}
			}
			else
			{
				$this->flash->error("Invalid login information. Please try again.");
			}
		}

	}

	public function logoutAction()
	{
		$this->session->remove('customerAuth');
		return $this->response->redirect('clients/login');
	}

//	 public function encryptpasswordsAction() {
//	 	$content = '';
//
//	 	$logins = Customer::find('LoginID IS NOT NULL');
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
