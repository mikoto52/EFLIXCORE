<?php
namespace Core {
	class FileHandler {
		public $fp = NULL;
		public $filesize = 0;
		public function __construct($file, $mode) {
			if(is_file($file)) $this->filesize = filesize($file);
			$this->fp = fopen($file, $mode);
			if(!$this->fp) throw new \Exception("Unable to create File Pointer : " . $file);
		}

		public static function open($file, $mode) {
			return new self($file, $mode);
		}

		public static function readFile($file) {
			return FileHandler::open($file, 'r')->read();
		}

		public static function writeFile($file, $content) {
			return FileHandler::open($file, 'w')->write($content);
		}
		
		public static function appendFile($file, $content) {
			return FileHandler::open($file, 'a+')->write($content);
		}

		public function read() {
			if($this->fp == NULL) throw new \Exception("NullPointerException");
			return fread($this->fp, $this->filesize);
		}

		public function write($cont) {
			if($this->fp == NULL) throw new \Exception("NullPointerException");
			fwrite($this->fp, $cont);
		}

		public function close() {
			fclose($this->fp);
			$this->fp = NULL;
		}
		
		public static function scanDirectory($dir) {
			try {
				$dh = opendir($dir);
			} catch(\Exception $e) {
				throw new \Exception($e);
			}
			
			$files = [];
			while (false !== ($filename = readdir($dh))) {
				if($filename == '.' || $filename == '..') continue;
				$files[] = $filename;
			}
			
			return $files;
		}
	}
}