<?php
	require_once("basecontroller.php");
	require_once("request.php");
	require_once("session.php");
	require_once("result.php");

	$request =new request();
	$session =new session();
	$result  =new result();
	
	$controller =new basecontroller($request, $session, $result);
	
	
	
	if($request->get("downloadhowto") != ""){
		//ご利用に当たって
		$filepath = DIR_NAME_TEMPLATE . FILE_NAME_HOWTO;
		$showname = "ご使用にあたって.pdf";
		$ftype = "application/pdf";
		$disposition = "inline";
	}else if($request->get("downloadmanual") != ""){
		//操作方法
		$filepath = DIR_NAME_TEMPLATE . FILE_NAME_MANUAL;
		$showname = "操作説明.pdf";
		$ftype = "application/pdf";
		$disposition = "inline";
	}else if($request->get("downloadidea") != ""){
		//提案書テンプレート
		$filepath = DIR_NAME_IDEA . $request->get("ideafile");
		$showname = $request->get("ideafile");
		$ftype = "application/vnd.ms-excel";
		$disposition = "attachment";
	}else{
		header("HTTP/1.0 404 Not Found");
		exit;
	}
	
	/*日本語ファイル名の文字化け対策*/
	$showname = mb_convert_encoding($showname, "SJIS", "UTF-8");
		
	// ファイルの存在確認 
	if (!file_exists($filepath)) {
		header("HTTP/1.0 404 Not Found");
		exit;
	}
	
	// ファイルサイズの確認 
	if (filesize($filepath) == 0) {
		header("HTTP/1.0 404 Not Found");
		exit;
	}

	// ダウンロード用HTTPヘッダ 
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Pragma: Cache");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: Cache"); 
	header("Content-Description: File Transfer");
	//application/msword
	//application/vnd.ms-excel
	//application/vnd.ms-powerpoint
	//application/pdf
	header("Content-Type: $ftype");
	header("Content-Disposition: $disposition; filename=\"" . $showname . "\"");
	header("Content-length: " . filesize($filepath));
	
	/* ファイルを読んで出力 */
	if (!readfile($filepath)) {
		die("Cannot read the file(".$filepath.")");
	}
	
	if($request->get("downloadhowto") != ""){
		//ご利用に当たって
		$controller->connectdb();
		$controller->accesslog("シミュレーション", "ご利用に当たって");
		$controller->closedb();
	}else if($request->get("downloadmanual") != ""){
		//操作方法
		$controller->connectdb();
		$controller->accesslog("シミュレーション", "操作方法");
		$controller->closedb();
	}else if($request->get("downloadidea") != ""){
		//提案書テンプレート
		$controller->connectdb();
		$controller->accesslog("シミュレーション", "提案書テンプレート");
		$controller->closedb();
	}

?>
