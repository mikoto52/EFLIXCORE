<?php
use Core\Router as Router;
use Core\Kernel as Kernel;
use Core\Trigger as Trigger;
use Core\AppInstaller as Installer;

Router::register("/member/register", array("GET", 'POST'), "member.register");
Router::register("/member/login", array("GET", 'POST'), "member.login");
Router::register("/member/loginProc", 'POST', "member.loginProc");
Router::register("/member/logout", 'GET', "member.logout");

// if SimpleSAMLphp plugin installed load it
$pluginPath = _VCPROOT_ . "appdata/member/plugin/";
$SimpleSAMLphpPath = $pluginPath . 'SAML/';
if(is_dir($SimpleSAMLphpPath)) {
	include_once($pluginPath . 'SAML.register.php');
}

Trigger::insert("Kernel.Boot.After", function($args) {
	$request = Kernel::getRequest();
	$ss_usr_srl = $request->getSession("ss_user_srl");
	$output = \Core\QueryBuilder::table('users')->where('user_srl', '=', 'ss_user_srl')->first();
	
	// Register 'member_info' to Kernel
	Kernel::set("member_info", $output);
});

/* register Static Method to Kernel */
Kernel::registerMethod("isLogged", function(){
	$oMember = getController("member");
	
	return $oMember->isLogged();
});
Kernel::registerMethod("getMemberInfo", function($user_srl = NULL){
	$oMember = getController("member");
	
	return $oMember->getMemberInfo($user_srl);
});
Kernel::registerMethod("getMember", function($user_srl = NULL){
	$oMember = getController("member");
	
	return $oMember->getMemberInfo($user_srl);
});
Kernel::registerMethod("isAdmin", function(){
	$oMember = getController("member");
	
	return $oMember->isAdmin();
});
