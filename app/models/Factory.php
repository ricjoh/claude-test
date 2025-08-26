<?php

use Phalcon\Mvc\Model;

class Factory extends Model
{
	public $FactoryID;
	public $VendorID;
	public $Name;
	public $Number;
	public $Active;
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;
	public $FacilityLocation;
	public $EDIFlag;
	public $EDIISAID;
	public $EDIGSID;
	public $EDIKey;

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("Factory");
    }

	public static function getFactories()
	{
		$factories = Factory::find(array(
            "order" => "Active DESC, Name ASC"
            ));
		return $factories;
	}

    // will include currentID factory even if not current if present
	public static function getActiveFactories( $currentID )
	{
        $addl = (isset( $currentID ) ? " OR FactoryID = '$currentID'" : '');

		$rows = Factory::find(array(
            'conditions' => "Active = 1" . $addl,
            'order' => "Name ASC"
            ));

		$data = array();
        array_push($data, array( 'CustomerID' => '', 'Name' => 'Select...'  ) );
		foreach ( $rows as $r )
		{
			$d = array(
				'FactoryID' => $r->FactoryID,
				'Name' => $r->Name,
				'Number' => $r->Number,
                'FacilityLocation' =>$r->FacilityLocation
			);
			array_push($data, $d);
		}
		return $data;

	}

}
