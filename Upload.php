<?PHP
/*
ファイルアップロード
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("UploadController.php");

$request = new request();
$session = new session();
$result = new result();

$controller =new UploadController($request, $session, $result);

/*アクションの振り分け*/

if($request->get("upload") != ""){
	$request->add("action","upload");
}elseif($request->get("delete") != ""){
	$request->add("action","delete");
}

$controller->connectdb();
$controller->execute();
$controller->closedb();


switch($result->get("ResultStatus")) {
case "DenyAccess":
	$controller->denyaccess();
	break;
default:
	include_once("Upload.html");
	break;
}

unset($request);
unset($session);
unset($result);
unset($controller);

	
?>
