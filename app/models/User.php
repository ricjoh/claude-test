<?php

use Phalcon\Mvc\Model;

class User extends Model {

	public $id;

	public $name;

	public $email;

	public const EDI_USER = '00000000-0000-0000-0000-000000000000';

	public const SU_ROLE_PID = 'E5928C79-0878-44C8-B715-0A838FA8FC61';
	public const ACTIVE_PID = '79F139DD-801C-40F2-8C9D-59D4666B5B0B';

	public function initialize() {
		// set the db table to be used
		$this->setSource("User");
	}

	public function getUsers() {
		$users = User::find(array(
			"LoginID NOT LIKE 'ZVOID%'",
			"order" => "LastName, FirstName"
		));
		return $users;
	}

	// will include currentID vendor even if not current if present
	public static function getActiveUsers($currentID) {
		$addl = (isset($currentID) ? " OR UserID = '$currentID'" : '');

		$rows = User::find(array(
			'conditions' => "(Email IS NOT NULL AND LoginID NOT LIKE 'ZVOID%' AND StatusPID = '" . self::ACTIVE_PID . "')" . $addl,
			'order' => "LastName, FirstName ASC"
		));

		$data = array();
		array_push($data, array('UserID' => '', 'FullName' => 'Select...'));
		foreach ($rows as $r) {
			$d = array(
				'UserID' => $r->UserID,
				'FullName' => $r->FullName,
				'FirstName' => $r->FirstName,
				'LastName' => $r->LastName,
				'Phone' => $r->Phone,
				'Fax' => $r->Fax,
				'Email' => $r->Email
			);
			array_push($data, $d);
		}
		return $data;
	}

	public static function getUserDetail($currentID) {

		if ($currentID == '00000000-0000-0000-0000-000000000000')
			return array(
				'UserID' => '00000000-0000-0000-0000-000000000000',
				'LastName' => 'EDI',
				'FirstName' => '',
				'Email' => 'ric@fai2.com'
			);

            $results = User::findFirst(array(
			'conditions' => "UserID = '$currentID'"
            ));
            if ( $results ) return $results->toArray();
            return array(
				'UserID' => $currentID,
				'LastName' => 'Unknown',
				'FirstName' => '',
				'Email' => 'missing@sample.com'
			);
	}
}
