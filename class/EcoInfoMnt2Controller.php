<?php

/*
環境情報メンテナンスクラス
*/
class EcoInfoMnt2Controller extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}

	public function execute(){
		
		switch($this->request->get("action")){
			case "kojintoroku":				//個人データ登録

				if( $this->check() ){
					if( $this->entryecodata() ){
						$this->accesslog("シミュレーション", "換算係数登録");
						$this->result->add("jsalert","環境情報を登録しました。");
					}else{
						$this->result->add("err", $this->db->getlasterror());
					}
				}
				
				$data["electricity_cost"] = $this->request->get("electricity_cost");
				$data["co2_coefficient"] = $this->request->get("co2_coefficient");
				$data["co2_to_tree"] = $this->request->get("co2_to_tree");
				$data["weeks"] = $this->request->get("weeks");
				$this->result->add("ecoinfo", $data);
				
				break;
			case "showdefault":				//初期値
				$this->getadminecoinfo();
				break;
			case "":
				//初期画面
				$sql = "select 
							electricity_cost,
							co2_coefficient,
							co2_to_tree,
							weeks
						from 
							mst_ecoinfo 
						where 
							area_cd= '01' 
							and user_id = '" . $this->session->get("user_id") . "'";
				$ecodata = $this->db->selectdata($sql);
			
				if(count($ecodata) == 1){
					$this->result->add("ecoinfo", $ecodata[0]);
				}else{
					$this->getadminecoinfo();
				}
				$this->accesslog("シミュレーション", "換算係数表示");
				break;
		}
		$this->co2list();
	}	

	//ユーザーデータ取得
	private function getecoinfo(){
		$sql = "select 
					electricity_cost,
					co2_coefficient,
					co2_to_tree,
					weeks
				from 
					mst_ecoinfo 
				where 
					area_cd= '01' and user_id = '" . $this->session->get("user_id") . "'";
		$ecodata = $this->db->selectdata($sql);
		$this->result->add("ecoinfo", $ecodata[0]);
	}

	//管理者データ取得
	private function getadminecoinfo(){
		$sql = "select 
					electricity_cost,
					co2_coefficient,
					co2_to_tree,
					weeks
				from 
					mst_ecoinfo 
				where 
					user_id = '00000000000000000000' and area_cd= '01'";
		
		$ecodata = $this->db->selectdata($sql);
		$this->result->add("ecoinfo", $ecodata[0]);
	}

	//CO2係数リスト
	private function co2list(){
		$sql = "select 
					corp_name || '：' || co2_coefficient as corp_name,
					co2_coefficient
				from 
					mst_co2";
		$this->result->add("co2keisulist", $this->db->selectdata($sql));
	}

//////入力チェック
	private function check(){
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
		switch($this->request->get("Type_radio1")){
			case "0":
				if($this->request->get("denryoku_keisu") == ""){
					$err .="CO2排出係数を選択してください<br>";
				}
				break;

			case "1":
				if($this->request->get("co2_coefficient") == ""){
					$err .="CO2排出係数を入力してください<br>";
				}elseif(!ereg("^[0-9]+\.?[0-9]*$",$this->request->get("co2_coefficient"))){
					$err .="CO2排出係数を半角数字で入力してください<br>";
				}elseif(strlen(sprintf("%d",floor($this->request->get("co2_coefficient")))) > 5){
					$err .="CO2排出係数の桁数がオーバーしています<br>";
				}
				break;
		}
		
		//立木換算
		if($this->request->get("co2_to_tree") == ""){
			$err .="立木換算を入力してください<br>";
		}elseif(!ereg("^[0-9]+\.?[0-9]*$",$this->request->get("co2_to_tree"))){
			$err .="立木換算を半角数字で入力してください<br>";
		}elseif(strlen(sprintf("%d",floor($this->request->get("co2_to_tree")))) > 5){
			$err .="立木換算の桁数がオーバーしています<br>";
		}elseif(!($this->request->get("co2_to_tree") > 0)){
			$err .="立木換算を０より大きい値で入力してください<br>";
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
			$this->result->add("err",$err);
			return false;
		}
	}

	private function entryecodata(){
		$sql = "select 
					user_id 
				from 
					mst_ecoinfo 
				where 
					user_id = '" . $this->session->get("user_id") . "'";
		
		$ecodata = array(
						"electricity_cost" => $this->request->get("electricity_cost"),
						"co2_coefficient" => "",
						"co2_to_tree" => $this->request->get("co2_to_tree"),
						"weeks" => $this->request->get("weeks"),
						"update_time" => date("Y/m/d H:i:s"),
						"update_uid" => $this->session->get("user_id")
					);
		if( $this->request->get("Type_radio1") == "1" ){
			//ラジオボタン＝１（テキストボックスを選択）
			$ecodata["co2_coefficient"] = $this->request->get("co2_coefficient");
		}else{
			$ecodata["co2_coefficient"] = $this->request->get("denryoku_keisu");
		}
		
		$conditions ="area_cd= '01' and user_id = '". $this->session->get("user_id") . "'";
		
		if(count($this->db->selectdata($sql)) == 1){
			//ユーザデータが既に存在していれば、更新
			return $this->db->update("mst_ecoinfo", $ecodata, $conditions);
		}else{
			//ユーザデータが存在しなければ、登録
			$ecodata["user_id"] = $this->session->get("user_id");
			$ecodata["area_cd"] = "01";
			$ecodata["currency"] = "\\\\";
			$ecodata["regist_time"] = date("Y/m/d H:i:s");
			
			return $this->db->insert("mst_ecoinfo", $ecodata);
		}
	}
}
?>