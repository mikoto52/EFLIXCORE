<?php
namespace Core;

class request {
	
	public $postvars = array();
	public $getvars = array();
	public static $__instance = NULL;
	
	public static function getInstance() 
	{
		if(!self::$__instance) {
			self::$__instance = new self;
		}
		
		return self::$__instance;
	}
	
	public function __construct() 
	{
		// start session
		session_start();
		
		$this->postvars = array();
		$this->getvars = array();
		
		// register GET Variables
		foreach($_GET as $k=>$v) 
		{
			$this->getvars[$k] = $v;
			
			// register as kernel variable
			$GLOBALS['__VARIABLES']->{$k} = $v;
		}
		
		// if content-type 'JSON' parse JSON to _POST
		if(strtolower($_SERVER['CONTENT_TYPE']) == 'application/json') {
			$str = file_get_contents("php://input");
			$_POST = (array)json_decode($str);
			if(!$_POST) 
				parse_str($str, $_POST);
		} else {
			if(!$_POST && $_SERVER['REQUEST_METHOD'] != 'GET') {
				$str = file_get_contents("php://input");
				$jsonObj = (array)json_decode($str);
				if($jsonObj)
					$_POST = $jsonObj;
				else
					parse_str($str, $_POST);
			}
		}
		
		// register POST Variables
		foreach($_POST as $k=>$v) 
		{
			$this->postvars[$k] = $v;
			
			// register as kernel variable
			$GLOBALS['__VARIABLES']->{$k} = $v;
		}
	}
	
	public function getSession($k) 
	{
		return $_SESSION[$k];
	}
	
	public function setSession($k, $v) 
	{
		$_SESSION[$k] = $v;
	}
	
	public function getForm($k) 
	{
		return $this->postvars[$k];
	}
	
	public function getRequest($k)
	{
		return $this->getvars[$k];
	}
	
	public function getFormArgs() {
		return $this->postvars;
	}
	
	public function getRequestArgs() {
		return $this->getvars;
	}
	
	public function getXMLRequest() {
		$reqStr = file_get_contents("php://input");
		$xml = simplexml_load_string($reqStr);
		if(!$xml) {
			throw new Exception("Request XML is malformed!");
		}

		return json_decode(json_encode($xml), false);
	}
}
