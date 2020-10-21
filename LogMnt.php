<?php
/*
ログ管理機能
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("LogMntController.php");


$request =new request();
$session =new session();
$result  =new result();

$controller =new LogMntController($request, $session, $result);

/*アクションの振り分け*/
if($request->get("doDelete") != ""){
	$request->add("action","delete");
}elseif($request->get("download") != ""){
	$request->add("action","download");
}

$controller->connectdb();
$controller->execute();
$controller->closedb();

switch($result->get("ResultStatus")) {
case "DenyAccess":
	$controller->denyaccess();
	break;
default:
	if($request->get("action")!="download"){
		if( $request->get("winMode") != "sub" ){
			include_once("view/LogMnt.html");
		}else{
			include_once("view/LogDel.html");
		}
		break;
	}
}

unset($request);
unset($session);
unset($result);
unset($controller);

?>