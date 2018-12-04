<?php
include_once("./includes/config.inc.php");

$oKernel = Core\Kernel::getInstance();
Core\DisplayHandler::display($oKernel->proc());

