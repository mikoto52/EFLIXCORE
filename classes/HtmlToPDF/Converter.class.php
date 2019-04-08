<?php
namespace HtmlToPDF {
	use \Core\Kernel as Kernel;
	class Converter {
		private $flag = NULL;
		private $url = NULL;
		private $path = NULL;
		private $htmlString = NULL;
		private $type = 0x1;
		public static $WKHTMLTOPDF = 0x1;
		public static $CHROME = 0x2;
		public static $fromURL = 0x1;
		public static $fromHTML = 0x2;
		public static $fromFILE = 0x3;
		private $options = NULL;
		public function __construct($flag = NULL, $extra = NULL, $type = NULL) {
			$options = array();
			if($flag == 0x1) {
				$this->flag = 0x1;
				$this->url = $extra;
			} else if($flag == 0x2) {
				$this->flag = 0x2;
				$this->htmlString = $extra;
			} else if($flag == 0x3) {
				$this->flag = 0x3;
				$this->path = $extra;
			} else {
				throw new HtmlToPDFException("Please select correct flag for HtmlToPDF Converter");
			}
			
			if($type == 0x2) $this->type = 0x2;
			
		}
		public function setUrl($url = NULL) {
			if(!$this->flagIs(self::$fromURL))
				throw new HtmlToPDFException("Invalid HtmlToPDF Mode!");

			$this->url = $url;
		}
		public function setOptions($option) {
			$this->options = $option;
		}
		public function convert() {
			if($this->type == self::$CHROME) $shell = new Chrome();
			else $shell = new Shell();
			
			if($this->flagIs(self::$fromURL)) {
				if($this->url == NULL) 
					throw new HtmlToPDFException("URL not specified.");

				return $shell->execute($this->url, $this->options);
			} else if($this->flagIs(self::$fromFILE)) {
				if($this->path == NULL) 
					throw new HtmlToPDFException("URL not specified.");

				return $shell->execute($this->path, $this->options);
			} else if($this->flagIs(self::$fromHTML)) {
				$tmpFileName = Kernel::getStorageConfig('cache') . '/misc/' . uniqid("BITPD_" . Kernel::getMicroTime()) . '.html';
				\Core\FileHandler::writeFile($tmpFileName, $this->htmlString);
				$output = $shell->execute($tmpFileName, $this->options);
				@unlink($tmpFileName);
				
				return $output;
			}
		}
		public function flagIs($flag) {
			if($this->flag == $flag) return true;
			else return false;
		}
	}
}