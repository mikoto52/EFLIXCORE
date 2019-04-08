<?php
namespace Core;

class TriggerObject {
	public $callable = NULL; 
	public $point = "";
	
	public function __construct($point = "", $callable = NULL) {
		$this->callable = $callable;
		$this->point = $point;
	}
	
	public function is($point) {
		if($this->point == $point) {
			return true;
		}
		
		return false;
	}
	
	public function execute($args) {
		$output = call_user_func($this->callable, $args);
		if(!$output) $output = new ResourceObject();
		if(!is_object($output)) $output = new FatelErrorObject(0, "Invalid Data Type returned: ResourceObject expected.");
		
		return $output;
	}
}