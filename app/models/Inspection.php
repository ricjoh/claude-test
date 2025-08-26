<?php

use Phalcon\Mvc\Model;

class Inspection extends Model
{

/***************
| InspectionID   | varchar(64) | NO
| VatID          | varchar(64) | YES
| LotID          | varchar(64) | YES
| InspectionDate | datetime(6) | YES
| MoldCount      | int(2)      | YES
| Comments       | text        | YES
******************/

	public function initialize()
    {

        Phalcon\Mvc\Model::setup(['exceptionOnFailedSave' => true]);
		// set the db table to be used
        $this->setSource("Inspection");
        // $this->belongsTo("VatID", "Vat", "VatID");
		$this->belongsTo("LotID", "Lot", "LotID");
    }

    // NOTE $this->InspectionID should be valid. Build a query like in the comments in Lot model or the cheat sheet at:
    // http://phalcon.io/cheat-sheet/

	public function getReportData($sort = NULL, $all = false) {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		if ($sort) {
			$orderBy = "ORDER BY $sort, InspectionDate DESC";
		} else {
			$orderBy = 'ORDER BY InspectionDate DESC';
		}

		if ($all) {
			$sql = "select l.ProductCode, l.CustomerPONumber, l.LotNumber, v.CustomerLotNumber, v.VatNumber, i.*
			from Inspection i
			inner join Lot l on l.LotID = i.LotID
			left outer join Vat v on v.VatID = i.VatID
			$orderBy";
		} else {
			// get only current
			$sql = "select l.ProductCode, l.CustomerPONumber, l.LotNumber, v.CustomerLotNumber, v.VatNumber, i.*
			from Inspection i
			inner join Lot l on l.LotID = i.LotID
			left outer join Vat v on v.VatID = i.VatID
			left join Inspection next on i.VatID = next.VatID AND i.InspectionDate < next.InspectionDate
			WHERE next.InspectionDate IS NULL
			$orderBy";
		}

		$resSet = $db->query($sql);
		$db->close();

		return $resSet->fetchAll($resSet);
	}
}
