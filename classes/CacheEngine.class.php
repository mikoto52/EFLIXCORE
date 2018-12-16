<?php
namespace Core {
	class CacheEngine {
		private $cacheStorage = '';
		private $baseKEY = '00e835f0f17ff598046fd549670b00e4fd5fa8d5dbd5fc67bbdef5e582a9d57d';
		private $baseIV = '5bb61639acbd1bce390880209ee9eb1b';

		public static function getInstance() {
			if($GLOBALS['__oCACHEENGINEINST'] == NULL)
				$GLOBALS['__oCACHEENGINEINST'] = new self();

			return $GLOBALS['__oCACHEENGINEINST'];
		}

		public function __construct() {
			$this->cacheStorage = \Core\Kernel::getStorageConfig('cache');
			
			$cacheDir = $this->cacheStorage . '/misc/';
			if(!is_dir($cacheDir))
				mkdir($cacheDir);
		}

		public function getTemplateCache($cacheKey, $mtime = 0) {
			$cacheDir = $this->cacheStorage . '/template/';
			$cacheFile = $cacheDir . $cacheKey . '.bin';

			if(!is_file($cacheFile))
				return null;

			$tcache = FileHandler::readFile($cacheFile);
			$tcache = $this->parseCacheData($tcache);
			if(!$tcache)
				return null;

			if($tcache->mtime < $mtime)
				return null;

			return $tcache->data;
		}

		public function generateTemplateCache($cacheKey = NULL, $buff, $mtime, $css, $js) {
			if(!$cacheKey) return;
			// If Config not loaded, then NOT use Cachenhandler
			if(!$this->cacheStorage) return;

			$cacheDir = $this->cacheStorage . '/template/';
			if(!is_dir($cacheDir))
				mkdir($cacheDir);

			$data = new \stdClass;
			$data->css = $css;
			$data->js = $js;
			$data->stpl = $buff;
			$data = json_encode($data);

			$content = $this->createCacheData($mtime, $data);
			
			$fp = FileHandler::open($cacheDir . $cacheKey . '.bin', 'w');
			$fp->write($content);
			$fp->close();

			return;
		}

		public static function processAutoloadCache() {
			$self = self::getInstance();
			$cacheDir = $self->cacheStorage . '/autoload/';

			include_once($cacheDir . 'autoload.cache.php');
		}

		public function generateAutoloadCache() {
			$list = get_included_files();
			unset($list[0]);
			$content = '<?php' . PHP_EOL;
			$content .= '/***' . PHP_EOL;
			$content .= ' * Cachefile generated by EFLIX Autoload Cacher' . PHP_EOL;
			$content .= ' * Time : ' . time() . PHP_EOL;
			$content .= ' ***/' . PHP_EOL;
			$content .= 'if(!defined(\'__VCP__\')) exit();' . PHP_EOL . PHP_EOL;
			foreach($list as $k => $v) {
				if(preg_match("/(includes[\/|\\\\|config[\/|\\\\])/i", $v)){
					unset($list[$k]);
				} else { 
					$content .= sprintf('if(is_file(\'%s\'))' . PHP_EOL, $v);
					$content .= sprintf('	include_once(\'%s\');' . PHP_EOL, $v);
				}
			}

			$cacheDir = $this->cacheStorage . '/autoload/';
			if(!is_dir($cacheDir)) mkdir($cacheDir);

			FileHandler::writeFile($cacheDir . 'autoload.cache.php', $content);

		}

		public function createCacheData($mtime = NULL, $data) {
			if(!$mtime) $mtime = time();

			$cacheObject = new \stdClass();
			$cacheObject->mtime = $mtime;
			$cacheObject->ctime = time();
			$cacheObject->data = $data;

			$cacheObject = json_encode($cacheObject);

			return bin2hex($cacheObject);
		}

		public function parseCacheData($data) {	
			$key = pack('H*', $this->baseKEY);
   			$iv = pack('H*', $this->baseIV);

   			// $data = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
   			$data = trim(hex2bin($data));
   			if(!$data) 
   				return null;

   			return json_decode($data);

		}

	}
}






