<?php
/*****
see lp.sql for table creation
$lp	= LogisticsUnit::getNextLicensePlate();
 */


use Phalcon\Mvc\Model;

class LogisticsUnit extends Model
{
	public function initialize()
	{
		$this->setSource("LogisticsUnit");
	}


	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}

	public static function getLogger() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}

	public static function getNextLicensePlate( $vatID = null )
	{
		$db = self::getDb();
		$db->connect();

		$sql = 	"INSERT INTO LogisticsUnit (VatID) VALUES ('{$vatID}')";
		$db->query($sql);
		$sql = 	"SELECT LicensePlate FROM LogisticsUnit WHERE LogisticsUnitID = LAST_INSERT_ID()";
		$result = $db->query($sql);
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result = $result->fetchAll($result);
		$licensePlate = $result[0]['LicensePlate'];
		return $licensePlate;
	}


	public static function getLastLicensePlate()
	{
		$db = self::getDb();
		$db->connect();

		$sql = 	"SELECT LicensePlate FROM LogisticsUnit ORDER BY LogisticsUnitID DESC LIMIT 1";
		$result = $db->query($sql);
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result = $result->fetchAll($result);
		$licensePlate = $result[0]['LicensePlate'];
		return $licensePlate;
	}

	public static function getConfig()
	{
		$db = self::getDb();
		$db->connect();

		$sql = 	"SELECT * FROM LogisticsUnitConfig";
		$result = $db->query($sql);
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result = $result->fetchAll($result);

		if (is_array($result) && count($result) > 0) {
			foreach ($result as $result) {
				$config[$result["cname"]] = $result["cvalue"];
			}
		}

		return $config;
	}

	public static function putConfig( $config )
	{
		$db = self::getDb();
		$db->connect();

		$sql = 	"DELETE FROM LogisticsUnitConfig";
		$db->query($sql);

		$sql = 	"INSERT INTO LogisticsUnitConfig (cname, cvalue) VALUES ";
		foreach ($config as $key => $value) {
			$result[] = "('{$key}', '{$value}')";
		}
		$sql .= implode(',', $result);
		$db->query($sql);
	}
}
