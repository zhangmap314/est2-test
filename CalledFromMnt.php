<?php
/*
ログインインターフェース機能
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("CalledFromMntController.php");


$request =new request();
$session =new session();
$result  =new result();

$controller =new CalledFromController($request, $session, $result);

if($request->get("update") != ""){
	$result->add("action","update");
}elseif($request->get("delete") != ""){
	$result->add("action","delete");
}elseif($request->get("regist") != ""){
	$result->add("action","regist");
}

$controller->connectdb();
$controller->execute();
$controller->closedb();

if($result->get("ResultStatus") == "Successful") {
	include_once("CalledFromMnt.html");
}
else{ 
	$controller->denyaccess();
}


unset($request);
unset($session);
unset($result);
unset($controller);

?>