<?php

use Phalcon\Mvc\Model;

class WarehouseReceiptItem extends Model
{

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("WarehouseReceiptItem");
		$this->belongsTo("ReceiptID", "WarehouseReceipt", "ReceiptID");
        $this->hasOne("LotID", "Lot", "LotID");
    }

	public function getPieces() {
		if (! isset($this->Pieces) ) {
			// error_log('saving Pieces');
			$balances = $this->getLot()->getLotBalancesAtDate( strtotime($this->WarehouseReceipt->ReceiptDate) );
			$this->Pieces = $balances['Pieces'];

			if (!isset($this->Weight)) $this->Weight = $balances['Weight'];

			$this->save();
		}

		return $this->Pieces;
	}

	public function getWeight() {
			// error_log('saving Weight');
			$balances = $this->getLot()->getLotBalancesAtDate( strtotime($this->WarehouseReceipt->ReceiptDate) );
		$balancesChanged = false;

		if (isset($this->Weight) == false || $this->Weight != $balances['Weight']) {
			$this->Weight = $balances['Weight'];

			$balancesChanged = true;
		}

		if (isset($this->Pieces) == false || $this->Pieces != $balances['Pieces'])  {
			$this->Pieces = $balances['Pieces'];
			$balancesChanged = true;
		}

		if ($balancesChanged) {

			$this->save();
		}

		return $this->Weight;
	}
}
