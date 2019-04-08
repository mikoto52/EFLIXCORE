<?php
namespace Core;

class Controller {
	public $approot = "";
	
	public function __construct($approot) {
		$this->approot = $approot;
		$this->init();
	}
	
	public function __methodExists($method) {
		$methodFile = sprintf("%s/controller/%s.method.php", $this->approot, $method);
		if(!is_file($methodFile)) return false;
		else return true;
	}
	
	public function __call($method, $args) {
		$methodFile = sprintf("%s/controller/%s.method.php", $this->approot, $method);
		if(!is_file($methodFile)) return; 
		$func = include_once($methodFile);
		call_user_func_array($func, $args);
	}
	
	public function getAppRoot() {
		return $this->approot;
	}
	
	public function init() {
		
	}
	
	public function before() {
		
	}
	
	public function after() {
		
	}
	
	public static function extend($name, $closure) {
		$classname = get_called_class();
		$appname = str_replace("Controller", "", get_called_class());
		$controller = getController($appname);
		if(isset(self::$__registeredMethod[$name]) || method_exists($classname, $name) || method_exists(self, $name)) {
			throw new Exception(sprintf("Method '%s' already registered or defined!", $name));
		}
		
		self::$__registeredMethod[$name] = $closure;
	}
}