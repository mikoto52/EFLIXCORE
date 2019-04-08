<?php
date_default_timezone_set("Asia/Seoul");

$version = '0.4.1'; // version tag
$debug = true; // debug mode

if(!defined("__EFLIX_LOADED__")) {
	if(!defined("__VERSION__")) define("__VERSION__", $version);
	if(!defined("__EFLIX__")) define("__EFLIX__", true);
	if(!defined("_EFLIXDEBUG_")) define("_EFLIXDEBUG_", $debug);
	if(error_reporting() !== E_ALL & ~E_NOTICE) {
		ini_set('display_errors', true);
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
	}
	
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
	if(!defined("_EFLIXROOT_")) define("_EFLIXROOT_", $vcpRoot); 
	// _VCPPATH_ 상대경로
	if(!defined("_EFLIXPATH_")) define("_EFLIXPATH_", $vcpPath); 
	if(!defined("_EFLIXHOST_")) define("_EFLIXHOST_", $vcpHost); 
	
	// 호환성 변수
	if(!defined("__VCP__")) define("__VCP__", true);
	if(!defined("_VCPDEBUG_")) define("_VCPDEBUG_", _EFLIXDEBUG_);
	if(!defined("_VCPROOT_")) define("_VCPROOT_", _EFLIXROOT_);
	if(!defined("_VIRTROOT_")) define("_VIRTROOT_", _VCPROOT_);
	if(!defined("_VCPPATH_")) define("_VCPPATH_", _EFLIXPATH_);
	if(!defined("_VIRTPATH_")) define("_VIRTPATH_", _VCPPATH_);
	if(!defined("_VCPHOST_")) define("_VCPHOST_", _EFLIXHOST_);
	if(!defined("_VIRTHOST_")) define("_VIRTHOST_", _VCPHOST_);
	
	include_once(_VIRTROOT_ . "/includes/func.inc.php");
	include_once(_VIRTROOT_ . "/includes/route.inc.php");
	
	define("__EFLIX_LOADED__", true);

}
