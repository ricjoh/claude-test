<?php
// namespace Tracker\Controllers;
use Phalcon\Mvc\Controller;
// use Phalcon\Http\Response;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Dispatcher;

class LogisticsUnitController extends Controller
{

	public function configAction()
	{
		$status = '';
		if ($this->request->isPost()) {
			foreach ( $this->request->getPost() as $key => $value ) {
				$config[$key] = trim($value);
			}
			LogisticsUnit::putConfig($config);
			$status = 'Config saved';
		}
		else {
			$this->logger->log('GET mode');
		}
		$this->logger->log("Getting Config...");
		$config = LogisticsUnit::getConfig();
		$this->logger->log($config);
		$this->view->data = $config ?? [];
		$this->view->status = $status;
		$this->view->lastplate = LogisticsUnit::getLastLicensePlate();
	}

	public function statusAction() {

		$this->logger->log('-----STATUS-----');
		$this->view->disable();
		// TODO: Cache this
		$config = LogisticsUnit::getConfig();
		$printserver = 'http://' . $config['Zebra_Server_IP_Address'] . ':' . $config['Zebra_Server_Port'];
		try {
		 	$url = "{$printserver}/status";
			$response = $this->utils->GET($url, false);
		} catch (\Phalcon\Mvc\Model\Exception $e) {
		 	$this->logger->log("Exception Getting Printer Status: " . $e->getModel()->getMessages());
		}
		$this->logger->log($response);
		echo $response;
	}

	public function rawprintAction() {

		$this->logger->log('-----rawprintAction-----');
		$this->view->disable();
		// TODO: Cache this
		$config = LogisticsUnit::getConfig();
		$printserver = 'http://' . $config['Zebra_Server_IP_Address'] . ':' . $config['Zebra_Server_Port'];

		$this->logger->log($_REQUEST);

		$labeldata['label'] = $this->request->getPost( 'label' );

		try {
		 	$url = "{$printserver}/print";
		 	$response = $this->utils->POST($url, json_encode($labeldata), true);
		} catch (\Phalcon\Mvc\Model\Exception $e) {
		 	$this->logger->log($e->getModel()->getMessages());
		}
		$this->logger->log($response);
		// echo $response;
	}

	public function printlabelsAction()
	{
		$BOLID = $this->dispatcher->getParam("id");
		$this->logger->log('Labels for BOL with ID: ' . $BOLID . "\n\n");

		try {
			$bolData = BillOfLading::findFirst("BOLID = '$BOLID'")->toArray();
			$offerData = Offer::getOfferInfo($this->db, $bolData['OfferID']);
			$custOrder =  CustomerOrder::findFirst("OfferID = '" .  $bolData['OfferID'] . "'");
		} catch (\Exception $e) {
			$msg = "Exception getting label data:\n" . $e->getMessage();
			$this->logger->log( 'FAIL! ' . $msg );
			$success = 0;
		}

		// $ediKey = EDIDocument::getEDIKeyByEDIDocID($custOrder->EDIDocID);
		// $customer = Customer::getEDICustomer($ediKey);

		$shiptoaddr = $custOrder->ShipToAddr1 . '_0A'; // newline on label
		$shiptoaddr .= $custOrder->ShipToAddr2 ? $custOrder->ShipToAddr2 . '_0A'  : '';
		$shiptoaddr .= sprintf( '%s, %s  %s',
								$custOrder->ShipToCity,
								$custOrder->ShipToState,
								$custOrder->ShipToZIP );
		if ($offerData) {

			// TODO: Ship From,
			$headerdata = [
				'ShipTo' => $custOrder->ShipToName,
				'Address' => $shiptoaddr,
				'PostalCode' => substr( $custOrder->ShipToZIP,0,5),
				'CustOrder' => $custOrder->CustomerOrderNum,
				'TotalPieces' => $custOrder->TotalCustomerOrderQty,
				'EDINum' => $custOrder->EDIDocID,
				'Carrier' => $bolData[ 'CarrierName' ],
				'DistributionCenter' => $custOrder->DistributionCenter,
				'ProductType' => $custOrder->ProductType,
				'ProductDept' => $custOrder->ProductDept,
				'Walmart' => $custOrder->Walmart,
				'BOL' => $bolData[ 'ShipperNumber' ],
				'Program' => '',

			];
			foreach ($offerData as $offerLine) {
				// $this->logger->log("line");
				// $this->logger->log($offerLine);
				// $row = array();
				$orderLineraw = false;
				$offerLinecount++;

				$mapraw = OrderOfferDetailMap::findFirst("OfferItemID = '" . $offerLine['OfferItemID'] . "'");

				if ($mapraw) {
					$map = $mapraw->toArray();
					$orderLineraw = CustomerOrderDetail::findFirst("CustomerOrderDetailID = '" . $map['CustomerOrderDetailID'] . "'");
				}

				if ($orderLineraw !== false) {
					$orderLine = $orderLineraw->toArray();
				} else {
					$this->logger->log("Can't find OfferItemID: " . $offerLine['OfferItemID'] . " in CustomerOrderDetail making educated guess");
					$sku = $skulookup[$offerLine['DescriptionPID']];
					$orderLine = ['PartNum' =>  $sku]; // stub this for sure
					// Find the customer order line with this SKU. Hopefully this sku doesn't occurr onthe order twice or we're fucked.
					$orderLineraw = CustomerOrderDetail::findFirst("EDIDocID = '" . $custOrder->EDIDocID . "' AND PartNum = '" . $skulookup[$offerLine['DescriptionPID']] . "'");
					if ($orderLineraw !== false) {
						$orderLine = $orderLineraw->toArray();
					}
				}

				$this->logger->log( $offerLine );

				$detaildata = [];
				$detaildata[ 'ItemDescription' ] = $description[$orderLine['PartNum']];
				$detaildata[ 'PartNum' ] = $orderLine['PartNum']; // TODO: Walmart customer partnum from orderdetail?
				if ($orderLine['CustomerOrderDetailID']) {
					$shipped =  CustomerShipDetail::findFirst(
						"CustomerOrderDetailID = " . $orderLine['CustomerOrderDetailID']
					);

					if ($shipped) {
						$detaildata[ 'Qty' ] = (isset($shipped)) ?  $shipped->QtyShipped : 0;
					}
				} else {
					// god forbid.
					$detaildata[ 'Qty' ] = $offerLine['PiecesfromVat'];
				}

				// TODO: License plate write a Logistincsunit function to query this directly

				$OfferItemVat = OfferItemVat::findFirst([
					'conditions' => 'OfferItemVatID = :id:',
					'bind' => ['id' => $offerLine['OfferItemVatID']]
				]);
// $this->logger->log($offerLine['OfferItemVatID']);
// $this->logger->log($OfferItemVat->toArray());
				$Vat = Vat::findFirst([
					'conditions' => 'VatID = :id:',
					'bind' => ['id' => $OfferItemVat->VatID]
				]);
// $this->logger->log($OfferItemVat->VatID);
// $this->logger->log($Vat->toArray());
				$DeliveryDetail = DeliveryDetail::findFirst([
					'conditions' => 'DeliveryDetailID = :id:',
					'bind' => ['id' => $Vat->DeliveryDetailID]
				]);

// $this->logger->log($Vat->DeliveryDetailID);
// $this->logger->log($DeliveryDetail);

				if ($DeliveryDetail && !empty( $DeliveryDetail->LicensePlate )) {
					$detaildata[ 'LicensePlate' ] = $DeliveryDetail->LicensePlate;
				} else {
					$detaildata[ 'LicensePlate' ] = LogisticsUnit::getNextLicensePlate();
					$DeliveryDetail->LicensePlate = $detaildata[ 'LicensePlate' ];
					try {
						if ($DeliveryDetail->save() == false) {
							$msg = "Error saving new licenseplate:\n" . implode(
								"\n",
								$DeliveryDetail->getMessages()
							);
							$this->logger->log($msg);
							$errors = true;
						}
					} catch (\Phalcon\Mvc\Model\Exception $e) {
						$msg = "Exception saving new licenseplate:\n" . $e->getMessage();
						$this->logger->log($msg);
						$errors = true;
					}
				}
				array_push($labels, $detaildata);
			}

			foreach ($labels as $detaildata) {
				$data = array_combine($headerdata, $detaildata);
				$labeldata = ['label' => $this->renderlabelAction($data)];
				$this->logger->log($labeldata['label']);
			}

		}
		// try {
		// 	$url = 'http://69.67.193.130:2345/print';
		// 	$responseobj = $this->utils->POST($url, json_encode($labeldata), true);
		// } catch (\Phalcon\Mvc\Model\Exception $e) {
		// 	$this->logger->log($e->getModel()->getMessages());
		// }
		}

		function renderlabelAction($data){
		$this->view->data = $data;
		$foo = $this->view->getRender( 'logisticsunit', 'renderlabel',[] );
		return $foo;
	}

}
