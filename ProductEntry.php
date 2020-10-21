<?php
/*
ログインインターフェース機能
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("ProductEntryController.php");

$request =new request();
$session =new session();
$result  =new result();

$controller =new ProductEntryController($request, $session, $result);

/*アクションの振り分け*/
if($request->get("execute") != ""){
	if($request->get("seq_no") == ""){
		$request->add("action","insert");
	}else{
		$request->add("action","update");
	}
}else{
	if($request->get("seq_no") == ""){
		$request->add("action","clearform");
	}else{
		$request->add("action","setdefault");
	}
}


$controller->connectdb();
$controller->execute();
$controller->closedb();


switch($result->get("ResultStatus")) {
case "DenyAccess":
	$controller->denyaccess();
	break;
default:
	include_once("view/ProductEntry.html");
	break;
}

unset($request);
unset($session);
unset($result);
unset($controller);

?>