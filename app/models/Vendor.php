<?php

use Phalcon\Mvc\Model;

class Vendor extends Model
{
	public $VendorID;
	public $Name;
	public $NoFactory;
	public $Active;
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;
	public $EDIFlag;
	public $EDIISAID;
	public $EDIGSID;
	public $EDIKey;

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("Vendor");
		// dont think we need this relationship anymore
        // $this->hasMany("VendorID", "Factory", "VendorID");
    }

	public static function getVendors()
	{
		$vendors = Vendor::find(array(
            "Name NOT LIKE 'Z%-%VOID%'",
            "order" => "Active DESC, Name ASC"
            ));
		return $vendors;
	}

    // will include currentID vendor even if not current if present
	public static function getActiveVendors( $currentID )
	{
        $addl = (isset( $currentID ) ? " OR VendorID = '$currentID'" : '');

		$rows = Vendor::find(array(
            'conditions' => "(Active = 1 AND Name NOT LIKE 'Z%-%VOID%')" . $addl,
            'order' => "Name ASC"
            ));

		$data = array();
        array_push($data, array( 'CustomerID' => '', 'Name' => 'Select...'  ) );
		foreach ( $rows as $r )
		{
			$d = array(
				'VendorID' => $r->VendorID,
				'Name' => $r->Name
			);
			array_push($data, $d);
		}
		return $data;

	}

}
