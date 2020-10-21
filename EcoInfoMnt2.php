<?PHP
/*
環境情報メンテナンス
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("EcoInfoMnt2Controller.php");

$request = new request();
$session = new session();
$result = new result();

$controller =new EcoInfoMnt2Controller($request, $session, $result);

/*アクションの振り分け*/
//個人データ登録
if($request->get("kojintoroku") != ""){
	$request->add("action","kojintoroku");
	$request->add("Type_radio1",$request->get("Type_radio1"));

}
//初期値
elseif($request->get("showdefault") != ""){
	$request->add("action","showdefault");
	$request->add("Type_radio1",'1');

}
else{
//	$request->get("Type_radio1");
//	$request->get("denryoku_keisu");
}


//全角から半角へ変換
$request->add("electricity_cost",common::zen2han($request->get("electricity_cost")));
$request->add("co2_coefficient",common::zen2han($request->get("co2_coefficient")));
$request->add("denryoku_keisu",common::zen2han($request->get("denryoku_keisu")));
$request->add("co2_to_tree",common::zen2han($request->get("co2_to_tree")));
$request->add("Type_radio1",common::zen2han($request->get("Type_radio1")));

$controller->connectdb();
$controller->execute();
$controller->closedb();

switch($result->get("ResultStatus")) {
	case "DenyAccess":		//エラー画面遷移
		$controller->denyaccess();
		break;
	default:
		if($request->get("action") != "download"){
			include_once("view/EcoInfoMnt2.html");
			break;
		}
}

unset($request);
unset($session);
unset($result);
unset($controller);


?>
