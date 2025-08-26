<?php

use Phalcon\Loader;
use Phalcon\Tag;
use Phalcon\Mvc\Url;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Db\Profiler as ProfilerDb;
// use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\UserPlugin\Plugin\Security;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Exception;
use Phalcon\Http\Response;

// $debug = new \Phalcon\Debug(); $debug->listen();


function print_array($array, $depth = 1, $indentation = 0) {
	$return_str = '';
	if (is_array($array)) {
		$return_str .= "Array(\n";
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if ($depth) {
					$return_str .= "Max depth reached.";
				} else {
					for ($i = 0; $i < $indentation; $i++) {
						$return_str .= "  ";
					}
					echo $key . "=Array(";
					print_array($value, $depth - 1, $indentation + 1);
					for ($i = 0; $i < $indentation; $i++) {
						$return_str .= "  ";
					}
					$return_str .= ");";
				}
			} else {
				for ($i = 0; $i < $indentation; $i++) {
					$return_str .= "  ";
				}
				$return_str .= $key . "=>" . $value . "\n";
			}
		}
		$return_str .= ");\n";
	} else {
		$return_str .= "It is not an array\n";
	}

	return $return_str;
}

try {

	// Define apppath
	define('APP_PATH', realpath('..') . '/');

	// Register an autoloader
	$loader = new Loader();
	$loader->registerDirs(
		array(
			'../app/controllers/',
			'../app/models/',
			'../app/plugins/'
		)
	)->register();

	// Create a DI
	$di = new FactoryDefault();

	// Set up config
	$configFile = APP_PATH . 'app/config/config.ini';

	$config = new \Phalcon\Config\Adapter\Ini($configFile);

	$di['config'] = $config;

	define('LOG_PATH', $di['config']->settings->logpath);
	define('ENVIRONMENT', $di['config']->settings->environment);

	$di['UUID'] = function () {
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	};

	#returns a date that can be used in mysql datetime field type
	$di['mysqlDate'] = function () {
		return date("Y-m-d H:i:s");
	};

	$di['utils'] = function () use ($di) {
		class utilityClass {
			private static $xlCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
			private $di;

			function __construct($di) {
				$this->di = $di;
			}

			public function dbDate($dt) {
				// return date('Y-m-d 00:00:00.000000', strtotime($dt));
				$date = new DateTime($dt);
				return $date->format('Y-m-d 00:00:00.000000');
			}

			public function slashDate($dt) {
				if (!isset($dt)) return '';
				$date = new DateTime($dt);
				return $date->format('m/d/y');
			}

			public function slashFullDate($dateTime) {
				if (!isset($dateTime)) return '';
				$date = new DateTime($dateTime);
				return $date->format('m/d/Y');
			}

			public function makeUniqueFileName($filename, $directory, $fullPath = false) {
				$iterator = 1;
				$prober = $filename;

				// Need to make sure the directory ends with a "/" character
				if (substr($directory, -1) !== "/") {
					$directory .= "/";
				}

				while (file_exists($directory . $prober)) {
					$punt = strrpos($filename, ".");
					// Offset for bracket is based on the length of the iterator.
					$bracketOffset = 2 + floor(log10($iterator) + 1);

					if (substr($filename, ($punt - $bracketOffset), 1) !== "[" && substr($filename, ($punt - 1), 1) !== "]") {
						$prober = substr($filename, 0, $punt);
					} else {
						$prober = substr($filename, 0, ($punt - $bracketOffset));
					}

					$prober .= "[$iterator]" . substr($filename, ($punt), strlen($filename) - $punt);

					$iterator++;
				}

				if ($fullPath) {
					return $directory . $prober;
				}

				return $prober;
			}

			// $num must be passed in so that if UUID is called within
			// the same function a random UUID will still be returned
			public function UUID($num) {
				return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, $num), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
			}

			public function getExcelMast($args) {
				$sheet = $args['sheet'];
				if (!empty($args['datePrintedColumn'])) {
					$letters = range('A', 'Z');
					$datePrintedColumn = strtoupper($args['datePrintedColumn']);
					$letterAtIndex = 0;
					foreach ($letters as $index => $letter) {
						if ($letter == $datePrintedColumn) {
							$letterAtIndex = $index;
							break;
						}
					}

					$letterAtNextIndex = $letterAtIndex + 1;
					if ($letterAtIndex && isset($letters[$letterAtNextIndex])) {
						$printedOn = 'Printed On: ' . date('n/j/Y');
						$cellRange = $letters[$letterAtIndex] . '1:' . $letters[$letterAtNextIndex] . '1';
						$sheet->mergeCells($cellRange);
						$sheet->setCellValueByColumnAndRow($letterAtIndex, 1, $printedOn);
					}
				}
				$bullet = html_entity_decode('&bull;', ENT_QUOTES, 'UTF-8');
				$rows = array();
				$columns = array(
					'Oshkosh Cold Storage Co., Inc.',
				);
				array_push($rows, $columns);
				$columns = array(
					"1110 Industrial Avenue $bullet Oshkosh, Wisconsin 54901",
				);
				array_push($rows, $columns);
				$columns = array(
					"920-231-0610 $bullet 800-458-0146 $bullet Fax: 920-231-9441",
				);
				array_push($rows, $columns);

				$style = array(
					'font' => array(
						'bold' => true,
						'size' => 18
					)
				);
				$sheet->getStyle('1')->applyFromArray($style);
				$style = array(
					'font' => array(
						'size' => 12
					)
				);
				$sheet->getStyle('2')->applyFromArray($style);
				$sheet->getStyle('3')->applyFromArray($style);
				array_push($rows, array('')); // add a blank spacer row
				return array('rows' => $rows, 'sheet' => $sheet);
			}

			public function getXLCol($index) {
				return static::$xlCols[$index];
			}

			public function copyXLRow(&$ws_from, &$ws_to, $row_from, $row_to) {
				$offset = $row_to - $row_from;
				$ws_to->getRowDimension($row_to)->setRowHeight($ws_from->getRowDimension($row_from)->getRowHeight());
				$lastColumn = $ws_from->getHighestColumn();
				++$lastColumn;
				for ($c = 'A'; $c != $lastColumn; ++$c) {
					$cell_from = $ws_from->getCell($c . $row_from);
					$cell_to = $ws_to->getCell($c . $row_to);
					$cell_to->setXfIndex($cell_from->getXfIndex()); // black magic here

					$val = $cell_from->getValue();

					if (strpos($val, '=') === 0) {
						// do offset stuff
						// $val = preg_replace('/\d+/e', "\$1+$offset", $val);

						$val = preg_replace_callback(
							'/([A-Z])(\d+)/',
							function ($matches) use ($offset) {
								return $matches[1] . (intval($matches[2]) + $offset);
							},
							$val
						);
					}

					$cell_to->setValue($val);
				}
			}

			public function getEnumValues($table, $column) {
				$sql =  "SELECT
							COLUMN_TYPE
						FROM
							information_schema.`COLUMNS`
						WHERE
							TABLE_NAME = ?
							AND COLUMN_NAME = ?";

				$enumString = $this->di->getShared('db')->query($sql, [$table, $column]);
				$enumString = $enumString->fetchAll($enumString)[0][0];

				return explode(",", str_replace(array("enum(", ")", "'"), "", $enumString));
			}

			public function GET($url, $json = true) {
				$curlerror = false;
				// if you are GETing from a URL within this app, allow it in app\plugins\SecurityPlugin.php
				$this->di->getShared('logger')->log( $url );
				$ch = curl_init($url); // GET is default
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 4);
				$response = curl_exec($ch);

				if (curl_error($ch) != '') {
					$response = "<pre>".curl_error($ch) . "\nURL: $url</pre>";
					$this->di->getShared('logger')->log($response);
					$curlerror = true;
				}

				if ( $json ) {
					if ( $curlerror ) {
						$json = array('error' => $response);
					} else {
						$json = json_decode($response, true);
					}
					curl_close($ch);
					return ($json);
				} else {
					curl_close($ch);
					return ($response);
				}
			}

			public function POST($url, $data, $json = false) {
				$this->di->getShared('logger')->log( $url );
				$this->di->getShared('logger')->log( "Data: " );
				$this->di->getShared('logger')->log( $data );
				// if you are POSTing from a URL within this app, allow it in app\plugins\SecurityPlugin.php
				$ch = curl_init($url); // GET is default
				curl_setopt($ch, CURLOPT_POST, true); // change to post
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				if ( $json ) {
					curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json') );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $data ); // $data is already json encoded
				} else {
					// curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
					curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($data) ); // $data=array(); $postfields = "Hello=World&Foo=Bar&Baz=Wombat"
				}
				$response = curl_exec($ch);

				if (curl_error($ch) != '') {
					$this->di->getShared('logger')->log(curl_error($ch));
					$this->di->getShared('logger')->log("URL: $url");
				}

				$json = json_decode($response, true);
				curl_close($ch);
				return ($json);
			}
		}

		return new utilityClass($di);
	};

	$di['logger'] = function () {

		class customLogger {
			private $logpath;

			function __construct($path) {
				$this->logpath = $path ?: LOG_PATH; // default log path
			}

			public function log($var, $isDevOnlyLog = false) {
				$text = print_r($var, true);
				$this->writeLog($text, $isDevOnlyLog);
			}

			public function log_array($var, $depth = 1, $isDevOnlyLog = false) {
				$text = print_array($var, $depth);
				$this->writeLog($text, $isDevOnlyLog);
			}

			// Determines if something should actually be logged or not
			private function activityShouldLog($isDevOnlyLog) {
				return $isDevOnlyLog === false || $this['config']->settings->environment === "DEVELOPMENT";
			}

			// Generates the lines and writes to the log file
			private function writeLog($text, $isDevOnlyLog) {
				if ($this->activityShouldLog($isDevOnlyLog)) {
					$lines = explode("\n", $text);
					$lines = explode("\r", $text);

					foreach ($lines as $line) {
						error_log($line, 3, $this->logpath);
					}

					error_log("\n", 3, $this->logpath);
				}
			}
		}

		$logger = new customLogger(LOG_PATH);
		return $logger;
	};

	// Set up config
	$configFile = APP_PATH . 'app/config/config.ini';

	$config = new \Phalcon\Config\Adapter\Ini($configFile);

	$di['config'] = $config;

	$di['securityPlugin'] = new SecurityPlugin();

	// Dispatcher setup
	$di['dispatcher'] = function () use ($di) {

		$eventsManager = new EventsManager;

		/**
		 * Check if the user is allowed to access certain action using the SecurityPlugin
		 */
		$eventsManager->attach('dispatch:beforeDispatch', $di['securityPlugin']);

		$dispatcher = new Dispatcher;
		$dispatcher->setEventsManager($eventsManager);

		return $dispatcher;
	};

	$di['modelsManager'] = function () {

		$eventsManager = new \Phalcon\Events\Manager();
		$eventsManager->attach('model', function ($event, $model) {
			if ($event->getType() == 'notSaved') {
				// throw new Exception(implode(", ", $model->getMessages()));
				// error_log( implode(", ", $model->getMessages() ), 3, LOG_PATH );
				// error_log( "\n", 3, LOG_PATH );
			}
		});
		$modelsManager = new \Phalcon\Mvc\Model\Manager();
		$modelsManager->setEventsManager($eventsManager);
		return $modelsManager;

		// $modelsManager = new Phalcon\Mvc\Model\Manager();
		// return $modelsManager;
	};

	// session needs to be setup before flash will work
	$di['session'] = function () {
		$session = new \Phalcon\Session\Adapter\Files();
		$session->start();
		return $session;
	};

	// apparently you have to set a flash object as well
	// as a flashSession object for things to work properly
	$di['flash'] = function () {

		$flash = new \Phalcon\Flash\Session(array(
			'error' => 'alert alert-danger',
			'success' => 'bg-success',
			'notice' => 'alert alert-info',
		));
		return $flash;
	};

	$di['flashSession'] = function () {
		$flash = new \Phalcon\Flash\Session(array(
			'error' => 'alert alert-danger',
			'success' => 'bg-success',
			'notice' => 'alert alert-info',
		));

		return $flash;
	};

	// Set up routing

	$di['router'] = new \Phalcon\Mvc\Router();

	#user login
	$di['router']->add(
		"/login",
		array(
			"controller" => "loginuser",
			"action"     => "index"
		)
	);

	#user logout
	$di['router']->add(
		"/logout",
		array(
			"controller" => "loginuser",
			"action"     => "logout"
		)
	);

	#customer catchall
	$di['router']->add(
		"/clients/:action",
		array(
			"controller" => "customer",
			"action"     => 1
		)
	);

	#customer login
	$di['router']->add(
		"/clients/login",
		array(
			"controller" => "logincustomer",
			"action"     => "index"
		)
	);

	#customer logout
	$di['router']->add(
		"/clients/logout",
		array(
			"controller" => "logincustomer",
			"action"     => "logout"
		)
	);

	$di['router']->add(
		"/clients(/?)",
		array(
			"controller" => "customer",
			"action"     => "clienthome"
		)
	);

	$di['router']->add(
		"/clients/inventoryreport",
		array(
			"controller" => "customer",
			"action"     => "inventoryreport"
		)
	);

	$di['router']->add(
		"/clients/grading/:action",
		array(
			"controller" => "grading",
			"action"     => 1
		)
	);

	$di['router']->add(
		"/clients/inspection/:action",
		array(
			"controller" => "inspection",
			"action"     => 1
		)
	);


	$di['router']->add(
		"/edidocument/:action/([0-9]{3})/([A-Z0-9]+)/([A-F0-9\-]+)",
		array(
			"controller" 	=> "edidocument",
			"action"		=> 1,
			"transaction"	=> 2,
			"edikey" 		=> 3,
			"reference"		=> 4
		)
	);

	$di['router']->add(
		"/edidocument/:action/([0-9]{3})/([A-Z0-9]+)/([A-F0-9\-]+)/([A-F0-9\-]+)",
		array(
			"controller" 	=> "edidocument",
			"action"		=> 1,
			"transaction"	=> 2,
			"edikey" 		=> 3,
			"reference"		=> 4,
			"reference2"	=> 5
		)
	);

	$di['router']->add(
		"/edidocument/add/([0-9]{3})/([A-Z0-9]+)/([A-F0-9\-]+)",
		array(
			"controller" 	=> "edidocument",
			"action"		=> "add",
			"transaction"	=> 1,
			"edikey" 		=> 2,
			"controlnumber"	=> 3
		)
	);

	$di['router']->add(
		"/edidocument/inventoryinquiry/([A-Z0-9]+)",
		array(
			"controller" 	=> "edidocument",
			"action"		=> "inventoryinquiry",
			"edikey"		=> 1
		)
	);

	$di['router']->add(
		"/edidocument/getinventory/([A-Z0-9]+)",
		array(
			"controller" 	=> "edidocument",
			"action"		=> "getinventory",
			"edikey"		=> 1
		)
	);

	$di['router']->add(
		"/warehouseadjustment/sendedi/([A-Z0-9]+)/([A-F0-9\-]+)",
		array(
			"controller" 				=> "warehouseadjustment",
			"action"					=> "sendedi",
			"edikey" 					=> 1,
			"warehouseadjustmentgroup"	=> 2
		)
	);

	$di['router']->add(
		"/warehouseadjustment/:action/([A-F0-9\-]+)",
		array(
			"controller" 	=> "warehouseadjustment",
			"action"		=> 1,
			"id"			=> 2
		)
	);

	$di['router']->add(
		"/offer/:status",
		array(
			"controller" => "offer",
			"status"     => 1
		)
	);

	$di['router']->add(
		"/:controller/:action/([A-F0-9\-]+)/([A-F0-9\-]+)",  // UUID works as ID or ints. added Related ID
		array(
			"controller" => 1,
			"action"     => 2,
			"id" 	 	 => 3,
			"relid" 	 => 4
		)
	);

	$di['router']->add(
		"/billoflading/list/([A-F0-9\-]+)/([0-9]+)",  // UUID works as ID or ints. Added page number
		array(
			"controller" => "billoflading",
			"action"     => "list",
			"id" 	 	 => 1,
			"page" 	 	 => 2
		)
	);

	$di['router']->add(
		"/billoflading/print/([A-F0-9\-]+)/([01])",  // UUID works as ID or 0/1. Added bool for product codes
		array(
			"controller" 		=> "billoflading",
			"action"     		=> "print",
			"id" 	 	 		=> 1,
			"showProductCodes"	=> 2
		)
	);

	$di['router']->add(
		"/:controller/:action/([a-z\-]+)/([A-F0-9\-]+)",  // UUID works as ID or ints. added Related ID
		array(
			"controller" => 1,
			"action"     => 2,
			"type" 	 	 => 3,
			"id" 	 	 => 4
		)
	);

	$di['router']->add(
		"/:controller/:action/([A-Z0-9\-]+)",  // UUID works as ID or ints.
		array(
			"controller" => 1,
			"action"     => 2,
			"id" 	 	 => 3
		)
	);

	$di['router']->add(
		"/clients/inventorydetailxls/([A-F0-9\-]+)",
		array(
			"controller" => "customer",
			"action"     => "inventorydetailxls",
			"id" 	 	 => 1
		)
	);

	$di['router']->add(
		"/offer/printoffer/([A-F0-9\-]+)",  // UUID works as ID or 0/1. Added bool for product codes
		array(
			"controller" 		=> "offer",
			"action"     		=> "printoffer",
			"id" 	 	 		=> 1
		)
	);

	$di['router']->add(
		"/edidocument/makex12/([0-9]{3})/([A-F0-9\-]+)",
		array(
			"controller" 	=> "edidocument",
			"action"		=> "makex12",
			"transaction"	=> 1,
			"edidocumentid" => 2
		)
	);

	$di['router']->add(
		"/delivery/checklicenseplate/([a-zA-Z0-9]*)",
		array(
			"controller" 		=> "delivery",
			"action"     		=> "checklicenseplate",
			"licensePlate" 	 	=> 1
		)
	);

	$di['router']->add(
		"/:controller/new",
		array(
			"controller" => 1,
			"action"     => 'edit',
			"id" 	     => 'NEW'
		)
	);

	#main/root of site (master search)
	$di['router']->add(
		"/",
		array(
			"controller" => "search",
			"action"     => "searchall"
		)
	);

	$di['profiler'] = function () use ($di) {
		return new ProfilerDb();
	};

	// Set the database service
	$di['db'] = function () use ($di) {

		$connection = new DbAdapter(array(
			"host"     => $di['config']->database->host,
			"username" => $di['config']->database->username,
			"password" => $di['config']->database->password,
			"dbname"   => $di['config']->database->dbname
		));

		if ($di['config']->database->profile) {
			$eventsManager = new EventsManager();

			$profiler = $di->getProfiler();
			$logger = $di->getLogger();

			$eventsManager->attach('db', function ($event, $connection) use ($profiler, $logger) {
				if ($event->getType() === 'beforeQuery') {
					$profiler->startProfile(
						$connection->getSQLStatement()
					);
				}

				if ($event->getType() === 'afterQuery') {
					$profiler->stopProfile();
					$profile = $profiler->getLastProfile();

					$log = array(
						'SQL Statement: ' . $profile->getSQLStatement(),
						// "\tStart Time: " . $profile->getInitialTime(),
						// "\tFinal Time: " . $profile->getFinalTime(),
						"\tTotal Elapsed Time: " . $profile->getTotalElapsedSeconds()
					);

					$logger->log(implode("\n", $log));
				}
			});

			$connection->setEventsManager($eventsManager);
		}

		$connection->connect();
		$connection->query('SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
		return $connection;

		/* return new DbAdapter(array(
            "host"     => $di['config']->database->host,
            "username" => $di['config']->database->username,
            "password" => $di['config']->database->password,
            "dbname"   => $di['config']->database->dbname
        )); */
	};

	// Setting up the view component
	$di['view'] = function () {
		$view = new View();
		$view->setViewsDir('../app/views/');
		return $view;
	};

	// Setup a base URI so that all generated URIs include the "tutorial" folder
	$di['url'] = function () {
		$url = new Url();
		$url->setBaseUri('/');
		return $url;
	};

	// Setup the tag helpers
	$di['tag'] = function () {
		return new Tag();
	};

	// Handle the request
	$application = new Application($di);

	echo $application->handle()->getContent();
} catch (Exception $e) {
	if (ENVIRONMENT === "DEVELOPMENT") {
		echo ("<h1>index.php Exception</h1>");
		echo ("<pre>$e</pre>");
	} else {
		$response = new Response();
		$response->setStatusCode(404, 'Not Found');
		$response->setHeader('Content-Type', 'text/html');
		$response->setContent("<body style='font-family: sans-serif; font-size: 32px; text-align: center; line-height: 2;'><h1>404</h1><p>Sorry, the page doesn't exist.</p>");
		$response->send();
	}
}
