<?php
use Core\Kernel as Kernel;
use Core\DisplayHandler as DisplayHandler;
use Core\FatelErrorObject as FatelErrorObject;
use Core\HTTPErrorObject as HTTPErrorObject;
use Core\ResourceObject as ResourceObject;
use Core\response as response;
use Core\ExceptionErrorObject as ExceptionErrorObject;

if(!defined("__VCP__")) exit();

/* 
 * #define  Global Functions
 * #brief   Alias Functions
 */
function setView($s) {
	$response = response::getInstance();
	return $response->setView($s);
}

function setViewPath($s) {
	$response = response::getInstance();
	return $response->setViewPath($s);
}

function getView() {
	$response = response::getInstance();
	return $response->getView();
}

function getViewPath() {
	$response = response::getInstance();
	return $response->getViewPath();
}

function setLayout($s = "") {
	$response = response::getInstance();
	return $response->setLayout($s);
}

function setLayoutPath($s = "") {
	$response = response::getInstance();
	return $response->setLayoutPath($s);
}

function getLayout() {
	$response = response::getInstance();
	return $response->getLayout();
}

function getLayoutPath() {
	$response = response::getInstance();
	return $response->getLayoutPath();
}

function addHtmlHeader($html) {
	Kernel::addHtmlHeader($html);
}

function setTitle($title) {
	Kernel::setTitle($title);
}

function sendRedirect($url) {
	Kernel::sendRedirect($url);
}

function getModel($app) {
	$class_name = sprintf("App\%s\Model", $app);
	if(!class_exists($class_name)) {
		$appRoot = sprintf("%sapp/%s", _VCPROOT_, $app);
		$modelFile = sprintf("%s/%s.model.php", $appRoot, $app);
		
		if(is_file($modelFile)) {
			include_once($modelFile);
		}		
		
		if(!class_exists($class_name)) {
			$output = new ExceptionErrorObject(-1, sprintf("Unable to load '%s' class", $class_name), new Exception(sprintf("Unable to load '%s' class", $class_name)));
			DisplayHandler::display($output);
			exit;
		}
		
		$GLOBALS['__'.$class_name] = new $class_name($appRoot);
	}
	
	return $GLOBALS['__'.$class_name];
}

function getController($app) {
	$class_name = sprintf("App\%s\Controller", $app);
	if(!class_exists($class_name)) {
		$appRoot = sprintf("%sapp/%s", _VCPROOT_, $app);
		$controllerFile = sprintf("%s/%s.controller.php", $appRoot, $app);
		
		if(is_file($controllerFile)) {
			include_once($controllerFile);
		}		
		
		if(!class_exists($class_name)) {
			$output = new ExceptionErrorObject(-1, sprintf("Unable to load '%s' class", $class_name), new Exception(sprintf("Unable to load '%s' class", $class_name)));
			DisplayHandler::display($output);
			exit;
		}
		
		$GLOBALS['__'.$class_name] = new $class_name($appRoot);
	}
	
	return $GLOBALS['__'.$class_name];
}

function getURL() {
	$args = func_get_args();
	if(!$args) 
	{
		return Kernel::getURI();
	}
	
	$baseUrl = _VCPPATH_ == '/'? '/':'/' . _VCPPATH_ . '/';
	$returnUrl = sprintf("%s", $baseUrl);
	foreach($args as $k=>$v) {
		$returnUrl .= sprintf("%s/", $v);
	}
	$returnUrl = substr($returnUrl, 0, -1);
	
	return $returnUrl;
}

function getHTTPUrl() {
	$args = func_get_args();
	
	$baseUrl = _VCPHOST_ . "";
	$returnUrl = sprintf("%s", $baseUrl);
	foreach($args as $k=>$v) {
		$returnUrl .= sprintf("%s/", $v);
	}
	$returnUrl = substr($returnUrl, 0, -1);
	
	return $returnUrl;
}

function getRequest() {
	return Kernel::getRequest();
}

function getResponse() {
	return Kernel::getResponse();
}

function getDocumentRoot() {
	$docRoot = $_SERVER['DOCUMENT_ROOT'];
	$docRoot = str_replace("\\", "/", $docRoot);
	return	$docRoot; 
}

function getExtraConfig($key) {
	return Kernel::getExtraConfig($key);
}

function get($k) {
	$req = Kernel::getRequest();
	return $req->getRequest($k);
}

function post($k) {
	$req = Kernel::getRequest();
	return $req->getForm($k);
}

function getDBConn() {
	return Kernel::getDBConn();
}
function __EFLIXCORE_Autoload($class_name) {
	if($class_name == 'self') return;
	$class_file = NULL;

	if(!class_exists($class_name)) {
		$clR = explode("\\", $class_name);
		if($clR[0] == 'Core') {
			// $class_name = preg_replace("#^Core\\\\#", "", $class_name, 1);
			$clR2 = $clR;
			unset($clR2[0]);
			$class_name = implode($clR2, '\\');

			$fname = sprintf('%sclasses/%s.class.php', _VIRTROOT_, $class_name);
			if(is_file($fname))
				$class_file = $fname;

			$fname = sprintf('%sclasses/%s.php', _VIRTROOT_, $class_name);
			if(is_file($fname))
				$class_file = $fname;

		} else if($clR[0] == 'App') {
			$clR2 = $clR;
			unset($clR2[0]);
			$class_name = implode($clR2, '\\');
			$app_name = strtolower($clR2[1]);
			unset($clR2[1]);
			$class_name = implode($clR2, '\\');
			if($class_name != 'Controller') {
				$fname = sprintf('%sapp/%s/classes/%s.class.php', _VIRTROOT_, $app_name, $class_name);
				if(is_file($fname))
					$class_file = $fname;

				$fname = sprintf('%sapp/%s/classes/%s.php', _VIRTROOT_, $app_name, $class_name);
				if(is_file($fname))
					$class_file = $fname;
			} else {
				$fname = sprintf('%sclasses/%s.class.php', _VIRTROOT_, $class_name);
				if(is_file($fname))
					$class_file = $fname;

				$fname = sprintf('%sclasses/%s.php', _VIRTROOT_, $class_name);
				if(is_file($fname))
					$class_file = $fname;
			}
		} else {
			$class_name = str_replace("\\", "/", $class_name);

			$fname = sprintf('%sclasses/%s.php', _VIRTROOT_, $class_name);
			if(is_file($fname))
				$class_file = $fname;

			$fname = sprintf('%sclasses/%s.class.php', _VIRTROOT_, $class_name);
			if(is_file($fname))
				$class_file = $fname;
		}

		if($class_file)
			include_once($class_file);
	}
}

function __EFLIXCORE_ErrorHandler($errno, $errstr, $errfile, $errline) {
	return \Core\Kernel::callErrorHandler($errno, $errstr, $errfile, $errline);
}

function __EFLIXCORE_Shutdown() {
	return \Core\Kernel::callShutdownFunction();
}
spl_autoload_register("__EFLIXCORE_Autoload");
set_error_handler("__EFLIXCORE_ErrorHandler");
register_shutdown_function("__EFLIXCORE_Shutdown");