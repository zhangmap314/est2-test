<?php

/*
環境情報メンテナンスクラス
*/
class EcoInfoMntController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}

	public function execute(){
		if($this->session->get("user_lebel") <> USER_LEBEL_ADMIN){
			return false;
		}
		$this->result->add("ResultStatus","Successful");
		
		$Suc_flg = false;
		switch($this->request->get("action")){
			case "download":
				//全ユーザ環境情報のダウンロード
				$this->downloadcsv();
				$this->accesslog("環境情報メンテナンス", "ユーザー環境情報ダウンロード");
				return;
				break;
			case "default":
				//デフォルトデータ更新
				if($this->ecoinfo_check()){
					$Suc_flg = $this->ecoinfo_update();
				}
				if($Suc_flg){
					$this->accesslog("環境情報メンテナンス", "デフォルトデータ登録");
					$this->result->add("jsalert","デフォルトデータを登録しました。");
				}else{
					//更新失敗のため、画面の値の再取得
					$this->result->add("electricity_cost",$this->request->get("electricity_cost"));
					$this->result->add("co2_coefficient",$this->request->get("co2_coefficient"));
					$this->result->add("co2_to_tree",$this->request->get("co2_to_tree"));
					$this->result->add("weeks",$this->request->get("weeks"));
				}
				break;
			case "co2insert":
				//電力会社一覧登録
				if($this->co2_check()){
					$Suc_flg = $this->co2_insert();
				}
				if($Suc_flg){
					$this->accesslog("環境情報メンテナンス", "電力会社登録");
					$this->result->add("jsalert","電力会社を登録しました。");
				}else{
					//エラーのとき、データの保持
					$this->result->add("new_corp_name",$this->request->get("corp_name"));
					$this->result->add("new_co2_coefficient",$this->request->get("co2_coefficient"));
				}
				break;
			case "co2update":
				//電力会社一覧更新
				if($this->co2_check()){
					$Suc_flg = $this->co2_update();
				}
				if($Suc_flg){
					$this->accesslog("環境情報メンテナンス", "電力会社更新");
					$this->result->add("jsalert","電力会社を更新しました。");
				}else{
					//エラーのとき、データの保持
					$this->result->add("up_corp_cd",$this->request->get("corp_cd"));
					$this->result->add("up_corp_name",$this->request->get("corp_name"));
					$this->result->add("up_co2_coefficient",$this->request->get("co2_coefficient"));
				}
				break;
			case "co2delete":
				//電力会社一覧削除
				if($this->extcheck()){
					if($this->co2_delete()){
						$this->accesslog("環境情報メンテナンス", "電力会社削除");
						$this->result->add("jsalert","電力会社を削除しました。");
					}
				}else{
					$err_co2 = "<br>" . $this->request->get("row") . "行目の項目に次のエラーがあります。";
					$err_co2 .="<br>そのデータは既に削除されています。";
					$this->result->add("err_co2", $err_co2);
				}
				break;
		}
		//画面の再表示
		$this->getdefaultecoinfo();
		$this->getco2list();
	}

	//入力チェック
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function ecoinfo_check(){
		$err="";
		//電気代
		if($this->request->get("electricity_cost") == ""){
			$err .="電気料金単価を入力してください<br>";
		}elseif(!ereg("^[0-9]+\.?[0-9]*$",$this->request->get("electricity_cost"))){
			$err .="電気料金単価を半角数字で入力してください<br>";
		}elseif(strlen(sprintf("%d",floor($this->request->get("electricity_cost")))) > 5){
			$err .="電気料金単価の桁数がオーバーしています<br>";
		}
		//CO2係数
		if($this->request->get("co2_coefficient") == ""){
			$err .="CO2係数を入力してください<br>";
		}elseif(!ereg("^[0-9]+\.?[0-9]*$",$this->request->get("co2_coefficient"))){
			$err .="CO2係数を半角数字で入力してください<br>";
		}elseif(strlen(sprintf("%d",floor($this->request->get("co2_coefficient")))) > 5){
			$err .="CO2係数の桁数がオーバーしています<br>";
		}
		//立木換算
		if($this->request->get("co2_to_tree") == ""){
			$err .="立木換算を入力してください<br>";
		}elseif(!ereg("^[0-9]+\.?[0-9]*$",$this->request->get("co2_to_tree"))){
			$err .="立木換算を半角数字で入力してください<br>";
		}elseif(strlen(sprintf("%d",floor($this->request->get("co2_to_tree")))) > 5){
			$err .="立木換算の桁数がオーバーしています<br>";
		}
		//年間週間数
		if($this->request->get("weeks") == ""){
			$err .="年間週間数を入力してください<br>";
		}elseif(!ereg("^[0-9]+$",$this->request->get("weeks"))){
			$err .="年間週間数を半角数字で入力してください<br>";
		}elseif( $this->request->get("weeks") <= 0 ){
			$err .="年間週間数を入力してください<br>";
		}elseif(strlen($this->request->get("weeks")) > 2){
			$err .="年間週間数の桁数がオーバーしています<br>";
		}
		
		if($err == ""){
			return true;
		}else{
			$this->result->add("err_ecoinfo",$err);
			return false;
		}
	}
	
	//co2係数マスタ登録・更新チェック
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function co2_check(){
		$err_co2="";
		//電力会社
		if($this->request->get("corp_name") == ""){
			$err_co2 .="<br>電力会社を入力してください";
		}elseif(mb_strlen($this->request->get("corp_name")) > 50 ){
			$err_co2 .="<br>電力会社の桁数がオーバーしています";
		}
		//電力係数
		if($this->request->get("co2_coefficient") == ""){
			$err_co2 .="<br>CO2排出係数を入力してください";
		}elseif(!ereg("^[0-9]+\.?[0-9]*$",$this->request->get("co2_coefficient"))){
			$err_co2 .="<br>CO2排出係数を半角数字で入力してください";
		}elseif(strlen(sprintf("%d",floor($this->request->get("co2_coefficient")))) > 5){
			$err_co2 .="<br>CO2排出係数の桁数がオーバーしています";
		}
		//更新時に対象レコードが存在しているか確認
		if($this->request->get("row","")!=""){
			if(!$this->extcheck()){
				$err_co2 .="<br>そのデータは既に削除されています。";
			}
		}
		
		if($err_co2 == ""){
			return true;
		}
		if($this->request->get("row","")!=""){
			$err_co2 = "<br>" . $this->request->get("row") . "行目の項目に次のエラーがあります。" . $err_co2;
		}else{
			$err_co2 = "<br>登録データに次のエラーがあります。" . $err_co2;
		}
		$this->result->add("err_co2" ,$err_co2);
		return false;
	}

	//デフォルトデータ取得
	private function getdefaultecoinfo(){
		$strsql = "select 
						electricity_cost, 
						co2_coefficient, 
						co2_to_tree,
						weeks 
					from 
					mst_ecoinfo ";
					
		$strsql .= " where user_id = '00000000000000000000' and area_cd= '01'";
		$data = $this->db->selectdata($strsql);
		$this->result->add("ecoinfo",$data[0]);
	}
	//電力会社名、CO2排出係数データ取得
	private function getco2list(){
		$strsql = "select corp_cd, corp_name, co2_coefficient, sort_key from mst_co2 order by sort_key";
		$co2data = $this->db->selectdata($strsql);
		//一覧データ
		$this->result->add("co2list",$co2data);
		//削除用アラート
		$del_alert_arr = array();
		for($i=0;$i<count($co2data);$i++){
			$del_alert_arr += array($i => $this->javascript_escape($co2data[$i]["corp_name"]));
		}
		$this->result->add("Delete_alert",$del_alert_arr);
	}

	//データ更新
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function ecoinfo_update(){
		$updata = array(
			"electricity_cost"	=> common::sqlescape($this->request->get("electricity_cost")),
			"co2_coefficient"	=> common::sqlescape($this->request->get("co2_coefficient")),
			"co2_to_tree"		=> common::sqlescape($this->request->get("co2_to_tree")),
			"update_time"		=> date("Y/m/d H:i:s"),
			"update_uid"		=> $this->session->get("user_id"),
			"weeks"				=> common::sqlescape($this->request->get("weeks"))
		);
		$conditions ="area_cd= '01' and user_id = '00000000000000000000' ";
	
		if(!$this->db->update("mst_ecoinfo", $updata,$conditions)){
			$this->result->add("err_ecoinfo", "データ更新失敗しました。");
			return false;
		}
		return true;
	}

	//co2係数マスタ登録
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function co2_insert(){
		$strsql = "select
					coalesce((select max(corp_cd)  from mst_co2),0) + 1 as corp_cd,
					coalesce((select max(sort_key) from mst_co2),0) + 1 as sort_key";
		$no = $this->db->selectdata($strsql);
		$insert = array(
			"corp_cd"         => $no[0]["corp_cd"],                     //カンパニーコード
			"corp_name"       => common::sqlescape($this->request->get("corp_name")),      //電力会社
			"sort_key"        => $no[0]["sort_key"],                    //ソートキー
			"co2_coefficient" => common::sqlescape($this->request->get("co2_coefficient")),//CO2排出係数
			"regist_time"     => date("Y/m/d H:i:s"),                   //登録日付
			"update_time"     => date("Y/m/d H:i:s"),                   //更新日付
			"update_uid"      => $this->session->get("user_id")         //更新ユーザID
			);
		if(!$this->db->insert("mst_co2", $insert)){
			$this->result->add("err", "電力会社データの登録に失敗しました。");
			return false;
		}
		return true;
	}

	//co2係数マスタ更新
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function co2_update(){
		$updata = array(
				"corp_name"       => common::sqlescape($this->request->get("corp_name")),
				"co2_coefficient" => common::sqlescape($this->request->get("co2_coefficient")),
				"update_time"     => date("Y/m/d H:i:s"),                   //更新日付
				"update_uid"      => $this->session->get("user_id")         //更新ユーザID
			);
		$conditions ="corp_cd='" .common::sqlescape($this->request->get("corp_cd")) . "' ";

		if(!$this->db->update("mst_co2", $updata,$conditions)){
			$this->result->add("err", "データ更新失敗しました。");
			return false;
		}
		return true;
	}

	//co2係数マスタ削除
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function co2_delete(){
		$strsql = "select corp_name from mst_co2 where corp_cd='" .common::sqlescape($this->request->get("corp_cd")) . "' ";
		$corp = $this->db->selectdata($strsql);
		if(count($corp) == 0){
			$this->result->add("jsalert", "データがありません。");
			$this->request->add("action", "");
			return false;
		}
		$this->result->add("del_corp_name",$corp[0]["corp_name"]);
		$strsql = "delete from mst_co2 where corp_cd='" .common::sqlescape($this->request->get("corp_cd")) . "' ";
		return $this->db->executesql($strsql);
	}
	
	
	//csvダウンロード
	private function downloadcsv(){
		//ユーザ用データをダウンロード
		$strsql = "select
						user_id, 
						currency, 
						electricity_cost, 
						co2_coefficient, 
						co2_to_tree,
						weeks,
						to_char(regist_time,'yyyy/mm/dd hh24:mi:ss') as regist_time,
						to_char(update_time,'yyyy/mm/dd hh24:mi:ss') as update_time
					from 
						mst_ecoinfo 
					where 
						user_id <> '00000000000000000000' and area_cd='01' 
					order by 
						update_time";
		$data = $this->db->selectdata($strsql);
		if(count($data) == 0){
			$this->result->add("jsalert", "データがありません。");
			$this->request->add("action", "");
			return false;
		}
		
		$contents = "\"ユーザーID\",\"通貨単位\",\"電気料金単価\",\"CO2係数\",\"立木換算\",\"年間週間数\",\"登録時刻\",\"更新時刻\"\r\n";
		for($c = 0; $c < count($data); $c++){
			$contents .= "\""
				.$data[$c]["user_id"]          . "\",\""
				.$data[$c]["currency"]         . "\",\""
				.$data[$c]["electricity_cost"] . "\",\""
				.$data[$c]["co2_coefficient"]  . "\",\""
				.$data[$c]["co2_to_tree"]      . "\",\""
				.$data[$c]["weeks"]            . "\",\""
				.$data[$c]["regist_time"]      . "\",\""
				.$data[$c]["update_time"]      . "\"\r\n";
		}
		
		$contents = mb_convert_encoding($contents, "SJIS", "UTF-8");
		$content_length = strlen($contents);
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Disposition: attachment; filename=\"EcoInfo.csv\"");
		header("Content-Type: application/x-csv");
		header('Content-Transfer-Encoding: binary');
		header("Content-length: " . $content_length);

		print $contents;
	}
	
	public function javascript_escape($str){
		$str = str_replace("\\","\\\\",$str);
		$str = str_replace("'","\'",$str);
		$str = str_replace("\"","\\\"",$str);
		return $str;
	}

	//mst_caller存在チェック
	//返り値：true :存在する
	//        false:存在しない
	private function extcheck(){
		$selsql = "select corp_cd from mst_co2 where corp_cd='" .common::sqlescape($this->request->get("corp_cd")) . "' ";
		$count = $this->db->selectdata($selsql);
		if(count($count)==0){
			return false;
		}
		return true;
	}

}
?>