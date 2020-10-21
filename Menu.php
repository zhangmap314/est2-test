<?php
/*
ログインインターフェース機能
*/

require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("MenuController.php");

$request =new request();
$session =new session();
$result  =new result();

$controller =new MenuController($request, $session, $result);

$controller->connectdb();
$controller->execute();
$controller->closedb();
if($session->get("saml")=="true"){
    $controller->insertlog();
}
if($result->get("ResultStatus") == "Successful") {
	include_once("view/Menu.html");
}
else{ 
	$controller->denyaccess();
}


unset($request);
unset($session);
unset($result);
unset($controller);

?>