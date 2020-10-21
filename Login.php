<?php
/*
ログインインターフェース機能
*/


require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("LoginController.php");

$request =new request();
$session =new session();
$result  =new result();

$controller =new LoginController($request, $session, $result);



/**********他システムとの連携パラメーターを受け取る**********/
//ユーザID
if( $request->get("LOGIN_ID") != "" ){
	$request->add( "userid", $request->get("LOGIN_ID") );
}

//hash_v
if( $request->get("HASH_V") != "" ){
    $request->add( "hash_v", $request->get("HASH_V") );
}
//パスワード
if( $request->get("PASS_ID") != "" ){

	if( is_numeric(str_replace("%", "", $request->get("PASS_ID") ) ) ){
		$request->add( "userpw", $request->get("PASS_ID") );
	}else{
		$request->add( "userpw", "" );

	}

}
//呼び出し元
if( $request->get("CALLER", "") != "" ){
	$request->add("caller", $request->get("CALLER") );
}

//企業コード(セッションに格納)
if( $request->get("CUST_CD", "") != "" ){
	$session->add("cust_cd", $request->get("CUST_CD") );
}
//お客様名(セッションに格納)
if( $request->get("CUST_NAME", "") != "" ){
	$session->add("cust_name", $request->get("CUST_NAME") );
}


/**********他システムとの連携パラメーターを受け取る**********/




$controller->connectdb();
$controller->execute();
$controller->closedb();

switch($result->get("ResultStatus")) {
case "DenyAccess":
//	$controller->denyaccess();
	header("Location: ./Denied.html");
	break;
case "DoLogin":
	include_once("view/Login.html");
	break;
case "LoginSuccessful":
	if($session->get("user_lebel") == USER_LEBEL_ADMIN || 
		$session->get("user_lebel") == USER_LEBEL_LOGGER){//管理者またはログ取得者の場合はメニュー画面へ
		header("Location: ./Menu.php");
	}else{
		header("Location: ./SimWarning.php");
	}
	break;
default:
	
}

unset($request);
unset($session);
unset($result);
unset($controller);

?>