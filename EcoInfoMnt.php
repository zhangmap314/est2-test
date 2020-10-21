<?PHP
/*
環境情報メンテナンス
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("EcoInfoMntController.php");


$request = new request();
$session = new session();
$result = new result();

$request->add("openner","menu");

$controller =new EcoInfoMntController($request, $session, $result);

/*アクションの振り分け*/
//ダウンロード
if($request->get("download") != ""){
	$request->add("action","download");
}
//デフォルトデータ登録
elseif($request->get("default") != ""){
	$request->add("action","default");
}
//電力会社登録
elseif($request->get("co2insert") != ""){
	$request->add("action","co2insert");
}
//電力会社削除
elseif($request->get("co2delete") != ""){
	$request->add("action","co2delete");
}
//電力会社更新
elseif($request->get("co2update") != ""){
	$request->add("action","co2update");
}
else{
	$request->add("action","getecoinfo");
}

//全角から半角へ変換
$request->add("electricity_cost",common::zen2han($request->get("electricity_cost")));
$request->add("co2_coefficient",common::zen2han($request->get("co2_coefficient")));
$request->add("co2_to_tree",common::zen2han($request->get("co2_to_tree")));

$controller->connectdb();
$controller->execute();
$controller->closedb();

if($result->get("ResultStatus") == "Successful") {
	if($request->get("action") == "download"){
		//DLなのでなにもしない
	}else{
		include_once("view/EcoInfoMnt.html");
	}
}
else{ 
	$controller->denyaccess();
}

unset($request);
unset($session);
unset($result);
unset($controller);

	
?>
