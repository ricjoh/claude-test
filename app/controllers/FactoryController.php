<?

use Phalcon\Mvc\Controller;

class FactoryController extends Controller
{

	public function listAction()
	{
		// there's no getFactories() call here because factories
		// are queried via ajax call when the page loads
		$factoryId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		$this->view->factoryId = $factoryId;
	}

	/*
	public function singleAction()
	{
        // $this->view->disable();
		$vendorId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		if ( $vendorId == 'new' ) $vendorId = 0;
		$this->view->vendorId = $vendorId;

        $this->logger->log('single vendor id = ' . $this->view->vendorId);

		$vendorData = new stdClass();
		if ( $vendorId )
		{
			$vendorData = Vendor::findFirst("VendorID = '$vendorId'");
		}
		$this->view->vendorData = $vendorData;
    }
    */
	public function saveAction()
	{
        if ($this->request->isPost() == true)
		{
			$FactoryID = $this->request->getPost('FactoryID');
			if ( !$FactoryID ) {
				$FactoryID = 0;
			}

			$factory = Factory::findFirst("FactoryID = '$FactoryID'");
			if ( !$factory ) { // add new factory
				$factory = new Factory();
				$factory->FactoryID = $this->UUID;
				$factory->CreateDate = $this->mysqlDate;
				$factory->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}
			else { // update existing factory
				$factory->UpdateDate = $this->mysqlDate;
				$factory->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}

			$factory->Name = $this->request->getPost('Name');
			$factory->Number = $this->request->getPost('Number');
			$factory->FacilityLocation = $this->request->getPost('FacilityLocation');
			$active = $this->request->getPost('Active') ? 1 : 0;
			$factory->Active = $active;

			$success = 1;
			if ( $factory->save() == false) {
				foreach ($factory->getMessages() as $message) {
					$this->logger->log('error saving factory - ' . $message);
				}
				$success = 0;
			}

			$this->view->data = array('success' => $success);
			$this->view->pick("layouts/json");

		}
	}

	// delete factory
	public function deleteAction()
	{
		$FactoryID = $this->dispatcher->getParam("id");

		$factory = Factory::findFirst("FactoryID = '$FactoryID'");
		$success = 1;
		if ($factory != false)
		{
			if ( $factory->delete() == false )
			{
				foreach ($factory->getMessages() as $message) {
					$this->logger->log('error deleting factory - ' . $message);
				}
				$success = 0;
			}
		}

		$this->view->data = array('success' => $success);
		$this->view->pick("layouts/json");
	}

	// ajax list
	public function getfactoriesAction()
	{
		$factories = Factory::getFactories();
		$this->view->factories = $factories;
		$this->view->pick("factory/ajax/getfactories");
	}

	// get a single factory
	public function getfactoryAction()
	{
		$FactoryID = $this->dispatcher->getParam("id");
		$factory = Factory::findFirst("FactoryID = '$FactoryID'");

		$this->view->data = $factory;
		$this->view->pick("layouts/json");
	}
}
