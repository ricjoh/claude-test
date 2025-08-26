<?php

use Phalcon\Mvc\Model;

class Parameter extends Model {

	public $ParameterID;
	public $ParameterGroupID;
	public $Value1;
	public $Value2;
	public $Value3;
	public $Value4;
	public $Description;
	public $DeactivateDate;
	public $ReadOnly;
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;

	public const PARAMETER_GROUP_ROOMS = '46AE19E9-0230-4306-AF82-D15C763C6673';
	public const PARAMETER_GROUP_DESCS = '40E74C81-EF36-4700-A38C-F39B64F7E7D1';

	public function initialize() {
		// set the db table to be used
		$this->setSource("Parameter");
	}

	public static function getValue($paramId, $which = 'Value1') {
		if (empty($paramId)) {
			return '';
		}

		$row =  Parameter::findFirst(
			array(
				'columns' => 'ParameterID, Value1, Value2, Value3, Value4, Description',
				'conditions' => 'ParameterID = :PID:',
				'bind' => array('PID' => $paramId)
			)
		);

		return $row[$which];
	}

	public static function getLabel($groupId, $which = 'Value1') {
		if ($which == 'Value1') {
			return ($groupId == self::PARAMETER_GROUP_ROOMS) ? 'Room' :
				'Value 1';
		} elseif ($which == 'Value2') {
			return ($groupId == self::PARAMETER_GROUP_ROOMS) ? 'Temperature' :
				'Value 2';
		} elseif ($which == 'Value3') {
			return 'Value 3';
		} elseif ($which == 'Value4') {
			return 'Value 4';
		}

		return $which;
	}

	// $args['orderBy'] - the field to order by, if nothing is passed in data will be ordered by "Value1" field
	public static function getValuesForGroupId($groupId, $args = array()) {
		$parameterGroup = self::getParameterGroup($groupId);
		$orderBy = !empty($args['orderBy']) ? $args['orderBy'] : 'Value' . $parameterGroup['PrimarySortField'];
		$rows =  Parameter::find(
			array(
				'columns' => 'ParameterID, Value1, Value2, Value3, Value4, Description',
				'conditions' => 'ParameterGroupID = :ParameterGroupID: AND Value1 IS NOT NULL AND DeactivateDate > :now:',
				'bind' => array('ParameterGroupID' => $groupId, 'now' => date("Y-m-d H:i:s")),
				'order' => $orderBy
			)
		);

		$data = array();
		foreach ($rows as $r) {
			$d = array(
				'ParameterID' => $r->ParameterID,
				'Value1' => $r->Value1,
				'Value2' => $r->Value2,
				'Value3' => $r->Value3,
				'Value4' => $r->Value4,
				'Description' => $r->Description
			);
			array_push($data, $d);
		}
		return $data;
	}


	public static function getEDIPcWeight($prodnum, $edikey) {
		if (empty($prodnum . $edikey)) {
			return '';
		}

		$rowraw =  Parameter::findFirst(
			array(
				'conditions' => 'ParameterGroupID = :PGID: AND Value3 = :V3: AND Value4 = :V4:',
				'bind' => array(
					'PGID' => self::PARAMETER_GROUP_DESCS,
					'V3' => $prodnum,
					'V4' => $edikey
				)
			)
		);

		if ($rowraw) {
			$row = $rowraw->toArray();
		} else {
			return false;
		}

		return isset($row['Value2']) ? $row['Value2'] : false;
	}

	public static function getParameterGroup($parameterGroupID) {
		$di = \Phalcon\DI::getDefault();
		$db = $di->getShared('db');
		$sql = "SELECT
					Name, Heading1, Heading2, Heading3, Heading4, PrimarySortField
				FROM
					ParameterGroup
				WHERE
					ParameterGroupID = ?";

		$params = array($parameterGroupID);

		$db->connect();

		$result_set = $db->query($sql, $params);
		$result_set->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result_set = $result_set->fetchAll($result_set);

		return $result_set[0];
	}
}
