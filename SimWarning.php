<?php
/*
���O�C���C���^�[�t�F�[�X�@�\
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("SimWarningController.php");

$request =new request();
$session =new session();
$result  =new result();

$controller =new SimWarningController($request, $session, $result);

if(strlen($request->get("ToSimulation")) > 0){
	$request->add("action","ToSimulation");
}

$controller->connectdb();
$controller->execute();
$controller->closedb();

if($result->get("ResultStatus") == "Successful") {
		include_once("SimWarning.html");
}
elseif($result->get("ResultStatus") == "ToSimulation") {
		$result->add("redirect","redirect");
		include_once("SimWarning.html");
}
else{ 
	$controller->denyaccess();
}


unset($request);
unset($session);
unset($result);
unset($controller);

?>