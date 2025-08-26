<?php

use Phalcon\Mvc\Model;

class Warehouse extends Model
{
	public function initialize()
    {
        $this->setSource("Warehouse");
    }
    
	public static function getWarehouses()
	{
		$warehouses = Warehouse::find(array(
			'order' => 'Name ASC'
		));

		return $warehouses;
	}

	public static function getActiveWarehouses( $currentID = 0 )
	{
		$data = array(array('WarehouseID' => '', 'Name' => 'Select...'));

		$rows = Warehouse::find(array(
		    'order' => "Name ASC"
	    ));

		foreach ( $rows as $r ) 
		{
			array_push($data, array(
				'WarehouseID' => $r->WarehouseID,
				'Name' => $r->Name
			));
		}

		return $data;
	}
}
