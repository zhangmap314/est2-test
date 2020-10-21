<?php
/*
ログイン機能クラス
*/
class CalledFromController extends basecontroller{
	
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
		$new_id = "";
		$new_name = "";
		if($this->result->get("action")=="regist"){
			//登録処理
			//入力チェック
			if(!$this->check()){
				//エラーあり
				$this->result->add("new_id",$this->request->get("c_id"));
				$this->result->add("new_name",$this->request->get("c_name"));
			}else{
				if($this->insert_caller()){
					$this->accesslog("呼出し元管理", "登録");
					$this->result->add("jsalert","呼出元を登録しました。");
				}else{
					$this->result->add("systemerror", "呼出元マスタにデータを登録できませんでした。");
					$this->showerrorpage();
					return false;
				}
			}
		}elseif($this->result->get("action")=="update"){
			//更新処理
			//入力チェック
			if(!$this->check()){
				//エラーあり
				$this->result->add("caller_id", $this->request->get("c_id_hidden"));
				$this->result->add("up_id", $this->request->get("c_id"));
				$this->result->add("up_name", $this->request->get("c_name"));
			}else{
				if($this->update_caller()){
					$this->accesslog("呼出し元管理", "更新");
					$this->result->add("jsalert","呼出元を更新しました。");
				}else{
					$this->result->add("systemerror", "呼出元マスタのデータの更新に失敗しました。");
					$this->showerrorpage();
					return false;
				}
			}
		}elseif($this->result->get("action")=="delete"){
			//削除処理
			//存在チェック
			if($this->extcheck()){
				if($this->delete_caller()){
					$this->accesslog("呼出し元管理", "削除");
					$this->result->add("jsalert","呼出元を削除しました。");
				}else{
					$this->result->add("systemerror", "呼出元マスタのデータの削除に失敗しました。");
					$this->showerrorpage();
					return false;
				}
			}else{
				$err_co2 = "そのデータは既に削除されています。<br>";
				$err_co2 = $this->request->get("row") . "行目の項目に次のエラーがあります。<br>" . $err_co2;
				$this->result->add("err_co2",$err);
			}
		}
		$this->getcaller();
	}
	
	//呼出元一覧作成
	private function getcaller(){
		$sql = "select caller_id, caller_name, to_char(regist_time,'YYYY/MM/DD') as regist_ymd, to_char(update_time,'YYYY/MM/DD') as update_ymd
		        from mst_caller order by regist_time";
		$callerdata = $this->db->selectdata($sql);
		$this->result->add("caller",$callerdata);
		//削除用アラート
		$del_alert_arr = array();
		for($i=0;$i<count($callerdata);$i++){
			$del_alert_arr += array($i => $this->javascript_escape($callerdata[$i]["caller_id"]));
		}
		$this->result->add("Delete_alert",$del_alert_arr);
	}
	
	//チェック処理
	//返り値：true :エラーなし
	//        false:エラーあり
	private function check(){
		$err="";
		//呼出元区分(呼出元ID)
		if($this->request->get("c_id") == ""){
			$err .="呼出元区分を入力してください。<br>";
		}elseif((strpos($this->request->get("c_id"),"&")!==false)){
			$err .="呼出元区分に\"&amp;\"は使用できません。<br>";
		}elseif(strlen($this->request->get("c_id")) > 10){
			$err .="呼出元区分の桁数がオーバーしています。<br>";
		}elseif(!$this->dup_check()){
			$err .="入力された呼出元区分はすでに存在しています。<br>";
		}elseif(strlen($this->request->get("c_id"))!=mb_strlen($this->request->get("c_id"))){
			$err .= "呼出元区分は半角英数字で入力してください。<br>";
		}
		
		//呼出元名
		if($this->request->get("c_name") == ""){
			$err .="呼出元名を入力してください。<br>";
		}elseif(mb_strlen($this->request->get("c_name")) > 50){
			$err .="呼出元名の桁数がオーバーしています。<br>";
		}
		
		//更新・削除対象レコードの存在チェック
		if($this->request->get("row","") != ""){
			if(!$this->extcheck()){
				$err .= "そのデータは既に削除されています。<br>";
			}
		}
		
		//エラーがなければ終了
		if($err == ""){
			return true;
		}
		//行番号があれば、行番号表示
		if($this->request->get("row","") != ""){
			$err = $this->request->get("row") . "行目の項目に次のエラーがあります。<br>" . $err;
		}else{
			$err = "登録データに次のエラーがあります。<br>" . $err;
		}
		$this->result->add("err",$err);
		return false;
	}
	
	//重複チェック
	//返り値：true :エラーなし
	//        false:エラーあり
	private function dup_check(){
		if($this->request->get("c_id") == $this->request->get("c_id_hidden") ){
			return true;
		}

		$sql = "select caller_id from mst_caller where caller_id = '" . common::sqlescape($this->request->get("c_id")) . "'";
		$this->result->add("dup",$this->db->selectdata($sql));
		if(count($this->result->get("dup")) == 0){
			return true;
		}
		return false;
	}

	//呼出元マスタへデータインサート
	//返り値：true :エラーなし
	//        false:エラーあり
	private function insert_caller(){
		$callerdata = array(
			"caller_id"   => common::sqlescape($this->request->get("c_id")),
			"caller_name" => common::sqlescape($this->request->get("c_name")),
			"regist_time" => date("Y/m/d H:i:s"),
			"update_time" => date("Y/m/d H:i:s"),
			"update_uid"  => $this->session->get("user_id")
			);
		
		return $this->db->insert("mst_caller", $callerdata);
	}

	//呼出元マスタへデータアップデート
	//返り値：true :エラーなし
	//        false:エラーあり
	private function update_caller(){
		$callerdata = array(
			"caller_id"   => common::sqlescape($this->request->get("c_id")),
			"caller_name" => common::sqlescape($this->request->get("c_name")),
			"update_time" => date("Y/m/d H:i:s"),
			"update_uid"  => $this->session->get("user_id")
			);
		$conditions ="caller_id = '" . common::sqlescape($this->request->get("c_id_hidden")) . "'";
		
		return $this->db->update("mst_caller", $callerdata,$conditions);
	}

	//呼出元マスタへデータデリート
	//返り値：true :エラーなし
	//        false:エラーあり
	private function delete_caller(){
		$sqldel = "delete from mst_caller where caller_id = '" . common::sqlescape($this->request->get("c_id_hidden")) . "'";
		return $this->db->executesql($sqldel);
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
		$selsql = "select caller_id from mst_caller where caller_id = '" . common::sqlescape($this->request->get("c_id_hidden")) . "'";
		$count = $this->db->selectdata($selsql);
		if(count($count)==0){
			return false;
		}
		return true;
	}

}
?>