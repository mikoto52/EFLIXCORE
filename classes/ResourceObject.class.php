<?php
namespace Core;

/**
 * #brief	Can only be extended to HTTPError or FatelError
 * #brief	ResourceObject means Successfully processed or GeneralError
 * #brief	returning FatelErrorObject or HTTPErrorObject cause Kernel Panic
 */
class ResourceObject {
	public $code = 0;
	public $message = 'success';
	public $is_fatel = false;
	public $is_exception = false;
	public $is_http_error = false;
	public $terminate = false;
	
	public function __construct($code = 0, $message = 'success') {
		$this->code = $code;
		$this->message = $message;
	}
	public function terminate() {
        $this->terminate = true;
    }
    public function terminated() {
        return $this->terminate;
    }
	public function isError() {
		if($this->code == -1) 
			return true;
			
		if($this->isFatel()) 
			return true;
		
		if($this->isException())
			return true;
		
		return false;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	public function getType() {
		return get_class($this);
	}
	
	public function is($type = 'ResourceObject') {
		if($this->getType() === $type) {
			return true;
		}
		return false;
	}
	
	public function isFatel() {
		return $this->is_fatel;
	}
	
	public function isException() {
		return $this->is_exception;
	}
	
	public function isHTTPError() {
		return $this->is_http_error;
	}
}