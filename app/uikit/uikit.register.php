<?php
use Core\Router as Router;
use Core\Kernel as Kernel;
use Core\Trigger as Trigger;

Trigger::insert("Router.Before.Process", function(){
	$res = Kernel::getResponse();
	
	// Register EZFlex Core JS
	$res->addJS(__DIR__ . "/asset/js/uikit.js");
	
	$res->addCSS(__DIR__ . "/asset/css/uikit.css");
	
});