<?php
date_default_timezone_set("Asia/Seoul");

if(!defined("__VERSION__")) {
	define("__VERSION__", "0.3");
}

if(!defined("__VCP__")) {
	define("__VCP__", true);
}

if(!defined("__EFLIX__")) {
	define("__EFLIX__", __VCP__);
}

if(!defined("_VCPDEBUG_")) {
	define("_VCPDEBUG_", true);
}

if(error_reporting() !== E_ALL & ~E_NOTICE) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

if(!defined("__VCPLOADED__")) {
	$docroot = str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']);
	$pathArr = str_replace("\\", "/", __DIR__);
	$pathArr = str_ireplace(sprintf("%s/", $docroot), "", $pathArr);
	$pathArr = explode("/", $pathArr);
	$depth = count($pathArr);
	$root_depth = $depth - 1;
	for($i = $root_depth; $i < $depth; $i++) {
		unset($pathArr[$i]);
	}
	$vcpPath = implode($pathArr, "/");
	if(!$vcpPath) $vcpPath = "/";
	$vcpHost = sprintf("http://%s/", $_SERVER['HTTP_HOST']);
	if($vcpPath != '/') $vcpHost .= $vcpPath . '/';
	$vcpRoot = $_SERVER['DOCUMENT_ROOT'] . '/';
	if($vcpPath != '/') $vcpRoot .= $vcpPath . '/';
	// _VCPROOT_ 절대경로
	if(!defined("_VCPROOT_")) {
		define("_VCPROOT_", $vcpRoot);
	}
	// _VCPPATH_ 상대경로
	if(!defined("_VCPPATH_")) {
		define("_VCPPATH_", $vcpPath);
	}
	if(!defined("_VCPHOST_")) {
		define("_VCPHOST_", $vcpHost);
	}

	if(!defined("_VIRTHOST_")) {
		define("_VIRTHOST_", _VCPHOST_);
	}
	if(!defined("_VIRTPATH_")) {
		define("_VIRTPATH_", _VCPPATH_);
	}
	if(!defined("_VIRTROOT_")) {
		define("_VIRTROOT_", _VCPROOT_);
	}
	
	include_once(_VIRTROOT_ . "/includes/func.inc.php");
	include_once(_VIRTROOT_ . "/includes/route.inc.php");
	
	define("__VCPLOADED__", true);

}
