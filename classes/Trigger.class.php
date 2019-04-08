<?php
namespace Core;

class Trigger {
	public static $triggers = array();
	public static function insert($point, $callable) {
		if(self::$triggers === NULL) {
			self::$triggers = array();
		}
		
		self::$triggers[] = new TriggerObject($point, $callable);
	}
	
	public static function execute($point, $args = NULL) {
		try {
			foreach(self::$triggers as $val) {
				if($val->is($point)) {
					$output = $val->execute($args);
					
					if($output->isFatel()) {
						return $output;
					}
				}
			}
		} catch (Exception $e) {
			throw $e;
		}
		
		return new ResourceObject();
	}

	public static function setTriggers($triggers) {
		self::$triggers = $triggers;
	}

	public static function getTriggers() {
		return self::$triggers;
	}
}