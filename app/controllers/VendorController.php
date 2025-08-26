<?

use Phalcon\Mvc\Controller;

class VendorController extends Controller
{

	public function indexAction()
	{
		$this->view->title = "Vendor Index";
	}

	public function listAction()
	{
		// there's no getVendors() call here because vendors
		// are queried via ajax call when the page loads
		$vendorId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		$this->view->vendorId = $vendorId;
	}


    // BD387D68-CBB8-4B04-A18D-0EB9B591EE99
    // E1CF6F91-F027-4B75-BE65-E894F27F6878

	public function singleAction()
	{
        // $this->view->disable();
		$vendorId = $this->dispatcher->getParam("id"); // from index.php dispatcher
		if ( $vendorId == 'new' ) $vendorId = 0;
		$this->view->vendorId = $vendorId;

        // $this->logger->log('single vendor id = ' . $this->view->vendorId);

		$vendorData = new stdClass();
		if ( $vendorId )
		{
			$vendorData = Vendor::findFirst("VendorID = '$vendorId'");
		}
		$this->view->vendorData = $vendorData;
    }

	public function saveAction()
	{
        if ($this->request->isPost() == true)
		{
			$vendorId = $this->request->getPost('VendorID');
			if ( !$vendorId ) {
				$vendorId = 0;
			}

			$vendor = Vendor::findFirst("VendorID = '$vendorId'");
			if ( !$vendor ) { // add new vendor
				$vendor = new Vendor();
				$vendor->VendorID = $this->UUID;
				$vendor->CreateDate = $this->mysqlDate;
				$vendor->CreateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}
			else { // update existing vendor
				$vendor->UpdateDate = $this->mysqlDate;
				$vendor->UpdateId = $this->session->userAuth['UserID']; // id of user who is logged in
			}

			$vendor->NoFactory = 0;
			$vendor->Name = $this->request->getPost('Name');
			$active = $this->request->getPost('Active') ? 1 : 0;
			$vendor->Active = $active;

			$success = 1;
			try{
				if ( $vendor->save() == false) {
					foreach ($vendor->getMessages() as $message) {
						$this->logger->log('error saving vendor - ' . $message);
					}
					$success = 0;
				}
			}catch(Exception $e){
				$this->logger->log('error saving vendor - ' . $e->getMessage());
				$success = 0;
			}

			$this->view->data = array('success' => $success);
			$this->view->pick("layouts/json");

		}
	}

	// delete vendor
	public function deleteAction()
	{
		$vendorId = $this->dispatcher->getParam("id");

		$vendor = Vendor::findFirst("VendorID = '$vendorId'");
		$success = 1;
		if ($vendor != false)
		{
			if ( $vendor->delete() == false )
			{
				foreach ($vendor->getMessages() as $message) {
					$this->logger->log('error deleting vendor - ' . $message);
				}
				$success = 0;
			}
		}

		$this->view->data = array('success' => $success);
		$this->view->pick("layouts/json");
	}

	// ajax list
	public function getvendorsAction()
	{
		$vendors = Vendor::getVendors();
		$this->view->vendors = $vendors;
		$this->view->pick("vendor/ajax/getvendors");
	}

	// get a single vendor
	public function getvendorAction()
	{
		$vendorId = $this->dispatcher->getParam("id");
		$vendor = Vendor::findFirst("VendorID = '$vendorId'");

		$this->view->data = $vendor;
		$this->view->pick("layouts/json");
	}

}
