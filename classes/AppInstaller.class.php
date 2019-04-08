<?php
namespace Core {
	class AppInstaller {
		private $app = '';

		public function __construct($app) {
			$this->app = $app;
		}

		public function proc() {
			$schemas = self::loadSchema($this->app);
			$schemaManager = \Schema\Manager::getInstance();

			foreach($schemas as $table_name=>$schema) {
				if(!$schemaManager->tableExists($table_name))
					$schemaManager->createTable($table_name, $schema);

				if(!$schemaManager->checkSchema($table_name, $schema)) {
					$schemaManager->alterSchema($table_name, $schema);
				}
			}

			$dataDir = Kernel::getPath('appdata') . 'app/' . $this->app . '/';

			$appInfo = self::getAppInfo($this->app);
			$schema = self::loadSchema($this->app, $modify);
			if($appInfo) {
				// create File
				if(!is_dir($dataDir))
					mkdir($dataDir);

				$install_info = $dataDir . 'install';

				$installInfo = array('version' => $appInfo->version, 'modify' => $modify);
				\Core\FileHandler::writeFile($install_info, json_encode($installInfo));
			}
		}

		public static function isInstalled($app = NULL) {
			if($app == NULL) return true;

			$dataDir = \Core\Kernel::getPath('appdata') . 'app/' . $app . '/';
			$install_info = $dataDir . 'install';

			// check file exists
			if(!is_file($install_info)) 
				return false;

			$installInfo = \Core\FileHandler::readFile($install_info);
			$installInfo = json_decode($installInfo);

			// check Schema Info Update date
			$appInfo = self::getAppInfo($app);
			$schema = self::loadSchema($app, $modify);
			
			if($installInfo->modify < $modify) 
				return false;
			
			if(version_compare($appInfo->version, $installInfo->version, '>')) 
				return false;

			return true;
		}

		public static function install($app = NULL) {
			if($app == NULL) return NULL;

			$installer = self::getInstaller($app);

			return $installer->proc();
		}

		public static function getInstaller($app = NULL) {
			if($app == NULL) return NULL;

			return new AppInstaller($app);
		}

		public static function loadSchema($app, &$modify = NULL) {
			$schemaDir = realpath(self::getAppPath() . $app . '/install/schema.json');
			if(!$schemaDir) {
				return array();
			}

			$modify = filemtime($schemaDir);
			$str = file_get_contents($schemaDir);

			return json_decode($str);
		}

		public static function getAppPath($app = NULL) {
			return realpath(_VCPROOT_ . '/app/' . $app) . '/';
		}

		public static function getAppInfo($app = NULL) {
			$path = self::getAppPath($app);
			$path .= 'info.json';

			if(is_file($path)) {
				$info = json_decode(\Core\FileHandler::readFile($path));

				return $info;
			} else {
				return NULL;
			}
		}
	}
}