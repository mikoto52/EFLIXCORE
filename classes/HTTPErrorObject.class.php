<?php
namespace Core;

class HTTPErrorObject extends ResourceObject {
	public $is_http_error = true;
	
	/* Error Code Definition */
	public $code_table = array(
		'404'=>'Not Found',
		'400'=>'Bad Request',
		'401'=>'Unauthorized',
		'403'=>'Forbidden',
		'500'=>'Internal Server Error'
	);
	public $desc_table = array(
		'404'=>'The requested page does not exist.',
		'403'=>'Access to this resource on the server is denied!',
		'400'=>'We could not process your request due to a system error.',
		'500'=>'We could not process your request due to a system error.',
	);
}