<?php
namespace Schema {
	class Manager {
		// Table Name Prefix
		public $prefix = '';
		public $dbConf = NULL;
		public static $__INSTANCE = NULL;

		public function __construct() {
			$dbConf = &\Core\Kernel::$dbConf;
			$this->dbConf = &$dbConf;
			$this->prefix = $dbConf['prefix'];
		}

		public static function getInstance() {
			$dbConf = &\Core\Kernel::$dbConf;
			if(strtoupper($dbConf['db']) == 'MYSQL') {
				return MySQLManager::getInstance();
			} else if(strtoupper($dbConf['db']) == 'SQLITE3') {
				return SQLiteManager::getInstance();
			} else {
				throw new \Exception("Unsupported DB Type");
			}
		}

		public function buildQuery($qr, $args = NULL) {
			$repFrom = array('[QT]', '[SQT]');
			$repTo = array($this->qt, $this->sqt);
			$qr = str_replace($repFrom, $repTo, $qr);
			$qr = preg_replace_callback("/\[([A-Za-z0-9_-]+)\]/i", function($match) use ($args) {
				return $args[$match[1]];
			}, $qr);
			return $qr;
		}

		public static function getTableName($table_name) {
			$dbConf = &\Core\Kernel::$dbConf;

			return $dbConf['prefix'] . $table_name;
		}
	}
}