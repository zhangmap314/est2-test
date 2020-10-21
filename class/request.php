<?php
/******************************
リクエストデータ管理クラス
******************************/
class request{
	private $params = Array();

	/*****************************************************
	コンストラクタ
	*****************************************************/
	function __construct() {
		// 通常のリクエストパラメータも同様に扱えるように
		if( is_array($_REQUEST) ){
			foreach( $_REQUEST as $name => $value) {
				//リクエストデータをTRIM、半角カタカナを全角に変換
				$this->add($name, mb_convert_kana(preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', $value) ,"KV") );
				
				//SQLインジェクション対策
				//テーブル操作SQLが存在するようなリクエストは受付けない
				if (eregi("(select|insert|update|delete|truncate|drop)", $value)){
					if(eregi("(mst_caller|mst_ecoinfo|mst_product|mst_user|tbl_log|tbl_prd_log)", $value)){
						header("HTTP/1.0 403 Access Denied");
						exit;
					}
				}
			}
		}
	}
	
	/*****************************************************
	データを追加する
	$name : データペアのキー
	$data : 追加データ
	*****************************************************/
	function add($name, $data) {
		$this->params[$name] = $data;
	}

	/*****************************************************
	キー指定でデータを取得
	*****************************************************/
	function get($name, $def = "") {
		if (array_key_exists($name, $this->params)){
			return $this->params[$name];
		}else{
			return $def;
		}
	}
	
	/*****************************************************
	リクエストデータのクリア
	*****************************************************/
	function clear(){
		unset($this->params);
		$this->params = Array();
	}
	
	
	/*****************************************************
	デストラクタ
	*****************************************************/
	function __destruct(){
		unset($this->params);
	}
}
?>