<?php
/*
ログインインターフェース機能
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");

require_once("ProductListController.php");


$request =new request();
$session =new session();
$result  =new result();

$controller =new ProductListController($request, $session, $result);

/*アクションの振り分け*/
if($request->get("search") != ""){
	//検索
	$request->add("action","search");
}elseif($request->get("research") != ""){
	//再表示
	$request->add("action","research");
}elseif($request->get("download") != ""){
	//ダウンロード
	$request->add("action","downloadcsv");
}elseif($request->get("dodelete") != ""){
	//削除
	$request->add("action","delete");
}elseif($request->get("MovePreviousPage") != ""){
	//前のページへ
	$request->add("action","moveprevious");
}elseif($request->get("MoveNextPage") != ""){
	//次のページへ
	$request->add("action","movenext");
}elseif($request->get("doupload") != ""){
	//アップロード(一括更新)
	$request->add("action","doupload");
}


$controller->connectdb();
$controller->execute();
$controller->closedb();

if($request->get("action") != "downloadcsv"){
	include_once("view/ProductList.html");
}

unset($request);
unset($session);
unset($result);
unset($controller);

?>