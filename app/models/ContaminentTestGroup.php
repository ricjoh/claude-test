<?php

use Phalcon\Mvc\Model;

class ContaminentTestGroup extends Model
{

	public function initialize()
    {
		// set the db table to be used
        $this->setSource("ContaminentTestGroup");
        $this->hasMany("ContaminentTestGroupID", "ContaminentTest", "ContaminentTestGroupID");
    }

	// public static function getTestsByLotID($lotId) {
	// 	$group = ContaminentTestGroup::findFirst("LotID = '$lotId'");
	// 	$id = $group->ContaminentTestGroupID;

	// 	$db = $group->getDI()->getShared('db');

	// 	$sql = "SELECT ctg.ContaminentTestGroupID, ct.ContaminentTestID, ct.TestPerformedPID, ct.TestResultsPID, ct.NoteID, ct.NoteText, p.";

	// 	$params = array( $offerId ); // '00075718-F812-4D25-B5DC-22982DCCEA8A'

	// 	// Multilot: 5F688E55-D3E0-4235-A7C4-982EA06AD707
	// 	// Nice Size: 4A057A5F-FB34-4894-B6D9-06CEE581D508

	// 	$db->connect();

	// 	$result_set = $db->query($sql, $params);
	// 	$result_set->setFetchMode(Phalcon\Db::FETCH_ASSOC);
	// 	$result_set = $result_set->fetchAll($result_set);

	// 	return $result_set;
	// }
}
