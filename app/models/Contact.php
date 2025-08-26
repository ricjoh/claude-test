<?php

use Phalcon\Mvc\Model;

class Contact extends Model
{

/************
ContactID	varchar	64	true		NO
CustomerID	varchar	64			NO
Prefix	varchar	10			YES
FirstName	varchar	25			NO
MiddleInitial	varchar	1			YES
LastName	varchar	35			NO
Suffix	varchar	10			YES
Title	varchar	25			YES
BusPhone	varchar	25			NO
MobilePhone	varchar	25			YES
HomePhone	varchar	25			YES
OtherPhone	varchar	25			YES
OtherPhoneType	varchar	15			YES
Fax	varchar	25			YES
BusEmail	varchar	100			YES
AltEmail	varchar	100			YES
Active	tinyint				NO
CreateDate	datetime				NO
CreateId	varchar	64			NO
UpdateDate	datetime				YES
UpdateId	varchar	64			YES
*****************/

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("Contact");
        $this->belongsTo("CustomerID", "Customer", "CustomerID");
    }

}
