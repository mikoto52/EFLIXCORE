<?php
namespace Core {
	use \Exception as Exception;
	
	/**************************
	 * for Compatablily
	 **************************/
	class CoreController extends Controller{}
	class CoreModel extends Model{}
	class CoreDatabase extends Database{}

	class Kernel {
		public static $__instance = NULL;
		public $headers = array();
		public $dbConn = NULL;
		public $cacheEngine = NULL;
		public static $dbConf = NULL;
		public $variables = NULL;
		public $response = NULL;
		public $request = NULL;
		public $panic = false;
		public $boot = false;
		public $phase = 'ready';
		public $panic_object = NULL;
		public $is_cli = false;
		public $appData = '';
		public static $registeredMethod = [];
		public static $shutdownFunc = [];
		public static $__ENV = [];
		
		/**
		 * @brief	Call dynamiclly registered Kernel method
		 */
		static function __callStatic($method, $args) {
			if(isset(self::$registeredMethod[$method])) {
				$closure = self::$registeredMethod[$method];
			} else {
				throw new \Exception(sprintf("Unable to invoke '%s' method (method not defined or registered)", $method));
			}
			
			return call_user_func_array($closure, $args);
		}
		
		function __construct() {
			
			// Kernel Variables
			$this->variables = new hashTable();
			$GLOBALS['__VARIABLES'] = &$this->variables;
			
			// Initialize Response & Request Class
			$this->response = &response::getInstance();
			$this->request = request::getInstance();
			$this->headers = &$this->response->headers;
			
		}
		
		/**
		 * Continue Kernel when FATEL Error
		 */
		public static function __continue() {
			$oKernel = Kernel::getInstance();
			$phase = Kernel::getInstance()->phase;
			if($phase == 'proc') {
				return $oKernel->proc();
			}
		}
		
		public static function getMicroTime() {
			$microTime = microtime();
			$microTime = explode(" ", $microTime);
			return (double)$microTime[0] + (double)$microTime[1];
		}
		
		public static function isCLI() {
			return self::getInstance()->is_cli;
		}
		
		public static function Flush() {
			/**
			 * Deprecated Functions
			 */
			Trigger::execute("Kernel.Flush.Before", array());
			Trigger::execute("Kernel.Flush.After", array());
		}
		
		public function Boot($CLI = NULL) {
			
			if($CLI == "CLI") {
				DisplayHandler::$writer = "CLI";
				$this->is_cli = true;
			}

			$this->appData = realpath(__DIR__ . '/../App_Data/');
			if(!$this->appData) 
				throw new Exception("Unable to access appdata directory.");
			
			// Load Configuration
			
			// load db config
			self::$dbConf = include_once(sprintf('%s/config/db.config.php', self::getPath('appdata')));
			
			// initialize DB
			self::getDBConn();
			
			try {
				// process Register
				$this->procRegister();

				$output = Trigger::execute("Kernel.After.Register");
				$output = Trigger::execute("Kernel.Register.After");
				
				if($output->isFatel()) {
					$this->panic($output);
					return;
				}
				
				Trigger::execute("Kernel.Boot.Before", array());
				
				if($_SERVER['HTTP_ACCEPT'] == "application/json") {
					self::setContentType("application/json");
					self::addHeader("Cache-Control", "no-cache");
					self::addHeader("Expires", "Sat, 26 Jul 1997 05:00:00 GMT");
				}else if($_SERVER['HTTP_ACCEPT'] == "application/xml" || $_SERVER['HTTP_ACCEPT'] == "text/xml") {
					self::setContentType("application/xml");
					self::addHeader("Cache-Control", "no-cache");
					self::addHeader("Expires", "Sat, 26 Jul 1997 05:00:00 GMT");
				}
				
				Trigger::execute("Kernel.Boot.After", array());
				
			} catch(Exception $e) {
				$this->panic(new \Core\ExceptionErrorObject(-1, $e->getMessage(), $e));
			}
	
			$this->boot = true;
		}

		public static function getStorageConfig($key) {
			if(!isset(self::$dbConf['storage'][$key])) 
				return null;

			return self::$dbConf['storage'][$key];
		}

		public static function getExtraConfig($key) {
			if(!isset(self::$dbConf['extra'][$key])) 
				return null;

			return self::$dbConf['extra'][$key];
		}
		
		public function panic($panicObject) {
			
			$this->panic = true;
			$this->panic_object = $panicObject;
			
			DisplayHandler::display($panicObject);
			exit;
		}
		
		public function isPanic() {
			return $this->panic;
		}
		
		public function initDatabase() {
			$dbconf = &self::$dbConf;
			
			if(!$dbconf['prefix']) {
				$dbconf['prefix'] = 'vs_';
			}
			if(strtoupper($dbconf['db']) == "MYSQL") {
				$dbConn = new MySQLDatabase($dbconf['host'], $dbconf['id'], $dbconf['password'], $dbconf['name']);

				// initialize Query Builder
				$config = array(
					'driver'	=> strtolower($dbconf['db']),
					'host'		=> $dbconf['host'],
					'database'	=> $dbconf['name'],
					'username'	=> $dbconf['id'],
					'password'	=> $dbconf['password'],
					'charset'	=> 'utf8mb4',
					'collation'	=> 'utf8mb4_unicode_ci',
					'prefix'	=> $dbconf['prefix']
				);
				new \Pixie\Connection($config['driver'], $config, 'Core\\QueryBuilder');
			} else if(strtoupper($dbconf['db']) == "SQLITE3") {
				$dbConn = new SQLite3Database($dbconf['host']);
				// initialize Query Builder
				$config = array(
					'driver'	=> 'sqlite',
					'database'	=> $dbconf['host'],
					'prefix'	=> $dbconf['prefix']
				);
				new \Pixie\Connection('sqlite', $config, 'Core\\QueryBuilder');
			} else {
				$dbConn = NULL;
			}
			
			if($dbConn === NULL) {
				throw new \Exception("Unable to initialize Database!");
			}
			
			$this->dbConn = &$dbConn;

			return $dbConn;
		}
		
		static public function addHtmlHeader($html) {
			$response = Kernel::getResponse();
			$response->htmlheaders[] = $html;
		}
		
		static public function setTitle($title) {
			$response = Kernel::getResponse();
			$response->title = $title;
		}
		
		public function procRegister() {
			$appList = $this->getAppList();
			$loadFileList = array();

			if(!self::isInstalled()) 
				self::install();
				
			foreach($appList as $app) {
				if(!AppInstaller::isInstalled($app)) {
					AppInstaller::install($app);
				}

				// process register
				$registerFile = sprintf('%s/app/%s/%s.register.php', _VCPROOT_, $app, $app);
				if(is_file($registerFile)) {
					$loadFileList[] = $registerFile;
				}
			}

			foreach($loadFileList as $v) {
				include_once($v);
			}
	
		}
		
		public function getAppList() {
			$appDir = sprintf("%s/app/", _VCPROOT_);
			$list = scandir($appDir, 1);
			foreach($list as $k=>$v) {
				if($v === "." || $v === "..") unset($list[$k]);
			}
			
			return $list;
		}
		
		public static function sendRedirect($url) {
			$response = Kernel::getResponse();
			$response->sendRedirect($url);
		}
		
		public static function getRequest() {
			$self = self::getInstance();
			return $self->request;
		}
		
		public static function getResponse() {
			$self = self::getInstance();
			return $self->response;
		}
		
		public static function setHeaderString($str) {
			$response = response::getInstance();
			return $response->setHeaderString($str);
		}
		
		public static function setHTTPStatus($stat) {
			$response = response::getInstance();
			return $response->status = $stat;
		}
		
		public static function setContentType($mime) {
			$response = self::getResponse();
			$response->contentType = $mime;
		}
		
		public static function set($k, $v) {
			$self = self::getInstance();
			return $self->variables->set($k, $v);
		}
		
		public static function get($k) {
			$self = self::getInstance();
			return $self->variables->get($k);
		}
		
		public static function getInstance() {
			if(!self::$__instance) {
				self::$__instance = new self;
			}
			
			return self::$__instance;
		}
		
		public function getHeader($header) {
			return $this->headers[$header];
		}
		
		public function addHeader($header, $val) {
			$this->headers[$header] = $val;
		}
		
		public function setHeader($header, $val) {
			$this->addHeader($header,$val);
		}
		
		public function removeHeader($header) {
			unset($this->headers[$header]);
		}
		
		static function getAbsolutePath() {
		
			return _VIRTROOT_;
		}
		
		static function getRelativePath() {
			return _VIRTPATH_;
		}
		
		static function getDocumentRoot() {
			return $_SERVER['DOCUMENT_ROOT'];
		}
		
		static function getURI() {
			$path = self::getPath();
			$uri = self::getRouterURI();
			if($path == '/') {
				return $uri;
			}else{
				return preg_replace("#\/$#", "", $path) . $uri;
			}
		}
		
		static function getRouterURI() {
			$path = self::getPath();
			if($path == "/") {
				$output = str_replace(sprintf("?%s", $_SERVER['QUERY_STRING']), "", $_SERVER['REQUEST_URI']);
				return $output;
			} else {
				$output = '/' . preg_replace("#^".($path)."#i", "", $_SERVER['REQUEST_URI']);
				$output = str_replace(sprintf("?%s", $_SERVER['QUERY_STRING']), "", $output);
				return $output;
			}
		}
		
		function proc() {
			if($this->boot === false) {
				try {
					$this->Boot();
				} catch(Exception $e) {
					return new ExceptionErrorObject(-1, $e->getMessage(), $e);
				}
			}
			$this->phase = 'proc'; // Set Kernel Phase
			if(!$this->isPanic()) {
				try {
					// boot kernel & process request
					$output = Router::process();

				} catch(Exception $e) {
					return new ExceptionErrorObject(-1, $e->getMessage(), $e);
				}
			} else {
				$output = $this->panic_object;
			}
			$this->phase = 'procEnd'; // Set Kernel Phase

			return $output;
		}
		
		public static function getDBConnection() {
			return self::getDBConn();
		}
		
		public static function getDBConn() {
			// trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

			$self = self::getInstance();
			if($self->dbConn === NULL) {
				$dbConn = $self->initDatabase();
			} else {
				$dbConn = $self->dbConn;
			}
			return $dbConn;
		}
		
		public static function isDebugMode() {
			if( _VCPDEBUG_ === true ){
				return true;
			}
			
			return false;
		}

		public static function callErrorHandler($errno, $errstr, $errfile, $errline) {
			switch ($errno){
		        case E_ERROR: // 1 //
		            $typestr = 'Fatel error'; break;
		        case E_WARNING: // 2 //
		            $typestr = 'Warning'; break;
		        case E_PARSE: // 4 //
		            $typestr = 'Parser error'; break;
		        case E_NOTICE: // 8 //
		            $typestr = 'Notice'; break;
		        case E_STRICT: // 2048 //
		            $typestr = 'Strict Standard'; break;
		        case E_DEPRECATED: // 8192 //
		        case E_USER_DEPRECATED:
		            $typestr = 'Deprecated'; break;
		    }
		    
		    if($errno == E_NOTICE) return;
		    
			$message = '['.$typestr.'] '.$errstr.' in '.$errfile.' on line '.$errline;
			// Insert to Log
			Kernel::getLogger()->write($message, 'Runtime');
			Kernel::getResponse()->addCoreError($message);
		}

		public static function callShutdownFunction() {
			$e = error_get_last();
			$errno = $e['type'];
			$errstr = $e['message'];
			$errfile = $e['file'];
			$errline = $e['line'];
			
			if($errno != E_ERROR) return;
			
			switch ($errno){
		        case E_ERROR: // 1 //
		            $typestr = 'Fatel error'; break;
		        case E_WARNING: // 2 //
		            $typestr = 'Warning'; break;
		        case E_PARSE: // 4 //
		            $typestr = 'Parser error'; break;
		        case E_NOTICE: // 8 //
		            $typestr = 'Notice'; break;
		        case E_STRICT: // 2048 //
		            $typestr = 'Strict Standard'; break;
		        case E_DEPRECATED: // 8192 //
		            $typestr = 'Deprecated'; break;
		    }
		    
			$message = $errstr;

			if(self::$shutdownFunc != NULL)
				call_user_func_array(self::$shutdownFunc, array($typestr, $message, $errfile, $errline));
			else
				DisplayHandler::display(new FatelErrorObject(-1, $message, $errfile, $errline));

			exit;
		}

		public static function registerShutdownFunction($func = NULL) {
			if($func == NULL) return;

			self::$shutdownFunc = $func;
		}
		
		/**
		 * Register Kernel Method dynamiclly
		 */
		public static function registerMethod($name, $closure) {
			$self = Kernel::getInstance();
			if(isset(self::$registeredMethod[$name]) || method_exists($self, $name) || method_exists(self, $name)) {
				throw new Exception(sprintf("Method '%s' already registered or defined to Kernel!", $name));
			}
			
			self::$registeredMethod[$name] = $closure;
		}

		public static function setRegisteredMethod($method) {
			self::$registeredMethod = $method;
		}

		public static function getRegisteredMethod() {
			return self::$registeredMethod;
		}

		public static function getPath($path = NULL) {
			if($path == 'appdata') {
				$path = self::getInstance()->appData;
				if(substr($path, -1) == '/' || substr($path, -1) == '\\')
					return $path;
				else {
					$path .= '/';
					return $path;
				}
			} else if($path == NULL) {
				return str_replace(self::getDocumentRoot(), "", self::getAbsolutePath());
			} else {
				return NULL;
			}
		}

		public static function isInstalled() {
			$flag = self::getPath('appdata') . 'installed';
			if(is_file($flag)) 
				return true;
			else
				return false;
		}

		public static function install() {
			$flag = self::getPath('appdata');
			FileHandler::writeFile($flag . 'installed', time());
			mkdir($flag . 'cache');
			mkdir($flag . 'cache/register');
			mkdir($flag . 'cache/kernel');
			mkdir($flag . 'app');
			mkdir($flag . 'storage');
		}
		
		/***
		 * Version 0.4 Addon
		 */
		public static function getEnv($k) {
			if($k == 'root') return _EFLIXROOT_;
			if($k == 'path') return _EFLIXPATH_;
			if($k == 'host') return _EFLIXHOST_;
			if($k == 'debug') return _EFLIXDEBUG_;
			
			return self::$__ENV[$k];
		}
		
		public static function setEnv($k, $v = null ) {
			if($k == 'root') return;
			if($k == 'path') return;
			if($k == 'host') return;
			if($k == 'debug') return;
			
			self::$__ENV[$k] = $v;	
		}
		
		public static function getLogger() {
			return Logger::getInstance();
		}
	}
}