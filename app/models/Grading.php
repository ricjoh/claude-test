<?php

use Phalcon\Mvc\Model;

class Grading extends Model
{

/***************
| GradingID     | varchar(64)  | NO
| VatID         | varchar(64)  | YES
| LotID         | varchar(64)  | YES
| GradingDate   | datetime(6)  | YES
| ExteriorColor | varchar(100) | YES
| InteriorColor | varchar(100) | YES
| Knit          | varchar(100) | YES
| Flavor        | varchar(65)  | YES
| Comments      | text         | YES
******************/

	public function initialize()
    {

        Phalcon\Mvc\Model::setup(['exceptionOnFailedSave' => true]);
		// set the db table to be used
        $this->setSource("Grading");
        // $this->belongsTo("VatID", "Vat", "VatID");
		$this->belongsTo("LotID", "Lot", "LotID");
    }

    // NOTE $this->GradingID should be valid. Build a query like in the comments in Lot model or the cheat sheet at:
    // http://phalcon.io/cheat-sheet/

	public function getReportData($sort = NULL, $all = false) {
		$db = $this->getDI()->getShared('db');
		$db->connect();

		if ($sort) {
			$orderBy = "ORDER BY $sort, GradingDate DESC";
		} else {
			$orderBy = 'ORDER BY GradingDate DESC';
		}

		if ($all) {
			$sql = "select l.ProductCode, l.CustomerPONumber, l.LotNumber, v.CustomerLotNumber, v.VatNumber, g.*
			from Grading g
			inner join Lot l on l.LotID = g.LotID
			left outer join Vat v on v.VatID = g.VatID
			$orderBy";
		} else {
			// get only current
			$sql = "select l.ProductCode, l.CustomerPONumber, l.LotNumber, v.CustomerLotNumber, v.VatNumber, g.*
			from Grading g
			inner join Lot l on l.LotID = g.LotID
			left outer join Vat v on v.VatID = g.VatID
			left join Grading next on g.VatID = next.VatID AND g.GradingDate < next.GradingDate
			WHERE next.GradingDate IS NULL
			$orderBy";
		}

		$resSet = $db->query($sql);
		$db->close();

		return $resSet->fetchAll($resSet);
	}


}
