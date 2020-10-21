<?php
/*
セッション管理クラス
*/
class session {
	
	/*****************************************************
	コンストラクタ
	*****************************************************/
	public function __construct(){
		session_start();  // セッションをスタートさせる。
	}
	
	/*****************************************************
	ペアデータをセッションに追加する
	*****************************************************/
	public function add($name, $data) {
		$_SESSION[$name] = $data;
	}
	
	/*****************************************************
	セッションより指定された名前のデータを取得する
	*****************************************************/
	public function get($name,$default="") {
		if(isset($_SESSION[$name])){
			return $_SESSION[$name];
		}else{
			return $default;
		}
	}
	
	public function printsession(){
		print("<pre>");
		print_r($_SESSION);
		print("</pre>");
	}
}

?>