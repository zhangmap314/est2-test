<?php
/*
ワーニング画面
*/
require_once("basecontroller.php");
require_once("request.php");
require_once("session.php");
require_once("result.php");
require_once("common.php");
require_once("WarnningController.php");

$request =new request();
$session =new session();
$result  =new result();

$controller =new WarnningController($request, $session, $result);

$controller->connectdb();
//$controller->execute();
$controller->closedb();


		//ワーニングメッセージ
		$filename = "./template/warnning.txt";
		$filebuf = "";
		
		if(file_exists($filename)){
			if(is_readable($filename)){
				//テンプレートを読み込み
				$filebuf = file_get_contents($filename);
				$filebuf = mb_convert_encoding($filebuf,"UTF-8", "UTF-8,sjis,euc-jp");
				$filebuf = str_replace("\"","\\\"",$filebuf);		// " ---> \" に変換
				//$filebuf = preg_replace("/(\r\n|\n|\r)/", "", $filebuf);//改行コード削除
				
				$result->add("warnningmsg", $filebuf);		
			}
		}
				include_once("view/Warnning.html");

unset($request);
unset($session);
unset($result);
unset($controller);

?>