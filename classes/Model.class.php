<?php
namespace Core {
	class Model {
	
		public function __construct($approot) {
			$this->approot = $approot;
			$this->init();
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
	}
}