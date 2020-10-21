<?php
	/*******************************
	Postgresデータベースクラス
	*******************************/
class dbclass{
	private $ConnStr;
	private $Conn = false;
	
	/*******************************************************
	コンストラクタ
	*******************************************************/
	public function __construct($ConnectionString){
		//"host=*** port=5432 dbname=*** user=*** password=***"
		$this->ConnStr = $ConnectionString;
	}
	
	/*******************************************************
	デストラクタ
	*******************************************************/
	public function __destruct(){
		//接続状態を調べて、DBが開いている場合は、クローズする
		if(pg_connection_status($this->Conn) === PGSQL_CONNECTION_OK){
			pg_close($this->Conn);
		}
	}
	
	/*******************************************************
	データベース接続をオープンする
	*******************************************************/
	public function connect(){
		$this->Conn = pg_connect($this->ConnStr);
		if(!$this->Conn){
			return false;
		}else{
			return true;
		}
	}
	
	/*******************************************************
	データベース接続を閉じる
	*******************************************************/
	public function close(){
		pg_close($this->Conn);
		$this->Conn = false;
	}
	
	/*******************************************************
	直近のエラーメッセージをで取得
	*******************************************************/
	public function getlasterror(){
		return pg_last_error($this->Conn);
	}
	
	/*******************************************************
	SQLを実行する
	$strsql : 実行対象SQL
	$autocommit : true - オートコミット;false - 手動コミット
	*******************************************************/
	public function executesql($strsql, $autocommit = true){
		if($autocommit){
			$this->begintrans();
		}
		$result = pg_query($this->Conn, $strsql);
		if ($result) {
			if($autocommit){
				$this->commit();
			}
			return true;
		}else{
			if($autocommit){
				$this->rollback();
			}
			return false;
		}
	}
	
	/*******************************************************
	トランザクショを開始する
	*******************************************************/
	public function begintrans(){
		pg_query($this->Conn, "begin");
	}
	
	/*******************************************************
	トランザクションのコミットを行う
	*******************************************************/
	public function commit(){
		pg_query($this->Conn, "commit");
	}
	
	/*******************************************************
	トランザクションのロールバックを行う
	*******************************************************/
	public function rollback(){
		pg_query($this->Conn, "rollback");
	}
	
	/*******************************************************
	DBよりデータを取得し、連想配列にセットして返す
	$strsql : 実行対象SQL(SELECT *** FROM *** WHERE ***)
	*******************************************************/
	public function selectdata($strsql){
		$result = pg_query($this->Conn, $strsql);
		$rtn = array();
		if (pg_num_rows($result) > 0) {
			$rtn = pg_fetch_all($result);
			pg_free_result($result);
		}
		return $rtn;
	}
	
	/*******************************************************
	データのインサートを行う
	$tablename : テーブル名
	$ardata : 連想配列データ("COLNAME1" => "DATA1","COLNAME2" => "DATA2")
	$autocommit : true - オートコミット;false - 手動コミット
	*******************************************************/
	public function insert($tablename, $ardata, $autocommit = true){
		$strsql = "insert into " . $tablename . " (";
		
		$arkey = array_keys($ardata);
		for ($i = 0; $i < count($arkey); $i++) {
			if($i > 0){
				$strsql .= ",";
			}
			$strsql .= $arkey[$i];
		}
		$strsql .= ") values(";
		
		$arval = array_values($ardata);
		for ($i = 0; $i < count($arval); $i++) {
			if($i > 0){
				$strsql .= ",";
			}
			if($arval[$i] != ""){
				$strsql .= "'" . $arval[$i] . "'";
			}else{
				$strsql .= "null";
			}
		}
		$strsql .= ")";
		
		return $this->executesql($strsql, $autocommit);
	}
	
	/*******************************************************
	データの更新を行う
	$tablename : テーブル名
	$ardata : 連想配列データ("COLNAME1" => "DATA1","COLNAME2" => "DATA2")
	$condition : 更新条件
	$autocommit : true - オートコミット;false - 手動コミット
	*******************************************************/
	public function update($tablename, $ardata, $condition, $autocommit = true){
		$strsql = "update " . $tablename . " set ";
		
		foreach($ardata as $key => $value){
			if($value != ""){
				$strsql .= $key . "='" . $value . "',";
			}else{
				$strsql .= $key . " = null,";
			}
		}
		$strsql = mb_substr($strsql, 0, mb_strlen($strsql) - 1);
		$strsql .= " where " . $condition;
		return $this->executesql($strsql,$autocommit);
	}
}
?>