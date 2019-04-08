<?php
namespace Core;

class RouteObject {
	public $pattern = NULL;
	public $method = NULL;
	public $callable = NULL;
	
	public function __construct($pattern, $method, $callable) {
		$this->pattern = $pattern;
		$this->method = $method;
		$this->callable = $callable;
	}
	
	public function equals($pattern, $method)
	{
		if($this->pattern === $pattern && $this->method === $method) 
			return true;
			
		return false;
	}
}