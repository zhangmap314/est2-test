<?php
/*
シミュレーション画面
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("SimulationController.php");


$request =new request();
$session =new session();
$result  =new result();

$controller =new SimulationController($request, $session, $result);

if($request->get("calculate") != ""){
	$request->add("action", "calculate");	//計算
	$session->add("cust_name", $request->get("cust_name"));
}elseif($request->get("clearall") != ""){
	$request->clear();
}elseif($request->get("printout") != ""){
	$request->add("action", "printout");	//印刷
}elseif($request->get("download") != ""){
	$request->add("action", "download");	//計算して結果をダウンロード
}elseif($request->get("dlformacro") != ""){
	$request->add("action", "dlformacro");	//提案書マクロ用計算結果をダウンロード
}
if($request->get("cust_name") != ""){
	$session->add("cust_name", $request->get("cust_name"));
}

$controller->connectdb();
$controller->execute();
$controller->closedb();

if($result->get("nolog", false) === true){
	header("Location: ./SimWarning.php");
}else{
	if($request->get("action") == "printout" ){
		include_once("SimPrint.html");
	}elseif($request->get("action") != "download" &&
			$request->get("action") != "dlformacro"){
			
		include_once("Simulation.html");
	}
}

unset($request);
unset($session);
unset($result);
unset($controller);

?>