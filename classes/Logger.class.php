<?php
namespace Core {
	
	class Logger {
		public $date = null;
		public $logPath = null;
		public static $__instance = null;
		public function __construct() {
			$this->date = date('Ymd');
			$this->logPath = sprintf('%s%s', Kernel::getPath('appdata'), 'logs');
			
			if(!is_dir($this->logPath)) mkdir($this->logPath);
		}
		
		public static function getInstance() {
			if(!self::$__instance) self::$__instance = new self();
			
			return self::$__instance;
		}
		
		public static function write($msg, $app = 'Core') {
			$self = self::getInstance();
			
			return $self->__write($msg, $app);
		}
		
		public static function writeRaw($msg, $app) {
			$self = self::getInstance();
			return $self->__writeRaw($msg, $app);
		}
		
		public function __write($msg, $app = 'Core') {
			if($msg == null || $msg == '') return;
			
			$msg = sprintf("[%s][%s] %s\n", date('Y-m-d H:i:s'), $app, $msg);
			return $this->__writeRaw($msg, $app);
		}
		
		public function __writeRaw($msg, $app = 'Core') {
			if($msg == null || $msg == '') return;
			if(!Kernel::getPath('appdata')) return;
			
			$logPath = sprintf('%s/%s', $this->logPath, $app);
			$logFile = sprintf('%s.log', $this->date);
			if(!is_dir($logPath)) mkdir($logPath);
			
			$logAbsPath = sprintf('%s/%s', $logPath, $logFile);
			FileHandler::appendFile($logAbsPath, $msg);
		}
	}
}