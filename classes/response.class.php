<?php
namespace Core;

class response {
	public static $__instance;
	public $status = 200;
	public $charset = "UTF-8";
	public $headers = array();
	public $headerstrings = array();
	public $contentType = 'text/html';
	public $use_view = false;
	public $view_path = "";
	public $view_file = "";
	public $use_layout = false;
	public $layout_path = "";
	public $layout_file = "";
	public $responseBody = "";
	public $htmlheaders = array();
	public $title = "";
	public $core_errors = array();
	public $css_files = array();
	public $js_files = array();
	
	public function __construct() {
		// Remove X-Powered-By Headers
		header_remove("X-Powered-By");
		
		$this->addHeader("Server", "EFlixCore/1.0");
	}
	
	public static function &getInstance() {
		if(!self::$__instance) {
			self::$__instance = new self;
		}
		
		return self::$__instance;
	}
	
	public function addCoreError($msg) {
		// deprecated functions
		return;
		$self = self::getInstance();
		$self->core_errors[] = $msg;
	}
	
	public function setHeaderString($str) {
		$this->headerstrings[] = $str;
	}
	
	public function getLayout() {
		return $this->layout_file;
	}
	
	public function getLayoutPath() {
		return $this->layout_path;
	}
	
	public function setLayout($file) {
		$this->layout_file = $file;
		if(!$this->layout_file && !$this->layout_path) $this->use_layout = false;
		else $this->use_layout = true;
	}
	
	public function setLayoutPath($path) {
		$this->layout_path = $path;
		if(!$this->layout_file && !$this->layout_path) $this->use_layout = false;
		else $this->use_layout = true;
	}
	
	public function getView() {
		return $this->view_file;
	}
	
	public function getViewPath() {
		return $this->view_path;
		if(!$this->view_file && !$this->view_path) $this->use_view = false;
		else $this->use_view = true;
	}
	
	public function setView($file) {
		$this->view_file = $file;
		if(!$this->view_file && !$this->view_path) $this->use_view = false;
		else $this->use_view = true;
	}
	
	public function setViewPath($path) {
		$this->view_path = $path;
	}
	
	public function useLayout() {
		return $this->use_layout;
	}
	
	public function useView() {
		return $this->use_view;
	}

	public function addHTMLHeader($data) {
		$this->htmlheaders[] = $data;
	}

	public function removeAllCSS() {
		$this->css_files = array();
	}

	public function removeAllJS() {
		$this->js_files = array();
	}

	public function removeAllAssets() {
		$this->removeAllCSS();
		$this->removeAllJS();
	}
	
	public function addJS($js_path, $priority = 10) {
		// POSIX Calibration
		$js_path = str_replace("\\", "/", $js_path);
		if($priority == 0) {
			array_unshift($this->js_files, $js_path);
			return;
		}
		$this->js_files[] = $js_path;
	}
	
	public function addCSS($css_path, $priority = 10) {
		// POSIX Calibration
		$css_path = str_replace("\\", "/", $css_path);
		if($priority == 0) {
			array_unshift($this->css_files, $css_path);
			return;
		}
		$this->css_files[] = $css_path;
	}
	
	public function sendRedirect($url) {
		header("Location: " . $url);
		exit;
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
	
}