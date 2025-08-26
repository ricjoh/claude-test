<?php

use Phalcon\Mvc\Model;

class ContaminentTest extends Model
{

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("ContaminentTest");
        $this->belongsTo("ContaminentTestGroupID", "ContaminentTestGroup", "ContaminentTestGroupID");
    }

}
