<?php
namespace Core;

class hashTable {
	public function set($k, $v) {
		$this->{$k} = $v;
	}
	
	public function get($k) {
		if(isset($this->{$k})) {
			return $this->{$k};
		}
		
		return null;
	}
}