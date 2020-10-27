<?php
/*
管理者メンテナンス
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("UserMntController.php");


$request =new request();
$session =new session();
$result  =new result();

$controller =new UserMntController($request, $session, $result);

/*アクションの振り分け*/
if($request->get("toroku") != ""){
	$request->add("action","insert");
}elseif($request->get("kousin") != ""){
	$request->add("action","update");
}elseif($request->get("sakujo") != ""){
	$request->add("action","delete");
}elseif($request->get("showone") != ""){
	$request->add("action","showone");
}


$controller->connectdb();
$controller->execute();
$controller->closedb();

if($result->get("ResultStatus") == "Successful"){
	include_once("UserMnt.html");
}else{ 
	$controller->denyaccess();
}


unset($request);
unset($session);
unset($result);
unset($controller);

?>