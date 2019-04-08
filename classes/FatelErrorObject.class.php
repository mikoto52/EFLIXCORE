<?php
namespace Core;

class FatelErrorObject extends ResourceObject {
	public $file = '';
	public $line = 0;
	public $is_fatel = true;
	
	function __construct($code=-1, $message='', $file='', $line=0) {
		parent::__construct($code, $message);
		$this->file = $file;
		$this->line = $line;
	}
}