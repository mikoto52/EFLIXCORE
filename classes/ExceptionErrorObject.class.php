<?php
namespace Core;

class ExceptionErrorObject extends ResourceObject {
	public $file = '';
	public $line = 0;
	public $e = NULL;
	public $is_fatel = true;
	public $is_exception = true;
	
	function __construct($code=-1, $message = "", $e = NULL) {
		parent::__construct($code, $message);
		$this->file = $file;
		$this->line = $line;
		$this->e = $e;
	}
}