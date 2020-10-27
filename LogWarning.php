<?php
/*
ログ管理規約機能
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("LogWarningController.php");


$request =new request();
$session =new session();
$result  =new result();

$controller =new LogWarningController($request, $session, $result);

if(strlen($request->get("ToLogMnt")) > 0){
	$request->add("action","logmnt");
}

$controller->connectdb();
$controller->execute();
$controller->closedb();

if($result->get("ResultStatus") == "Successful") {
		include_once("LogWarning.html");
}
elseif($result->get("ResultStatus") == "LogMnt") {
		header("Location: ./LogMnt.php");
}
else{ 
	$controller->denyaccess();
}


unset($request);
unset($session);
unset($result);
unset($controller);

?>