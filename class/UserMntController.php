<?php
/*
管理者メンテクラス
*/
class UserMntController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function execute(){
		if($this->session->get("user_lebel") <> USER_LEBEL_ADMIN){
			return;
		}
		$this->result->add("ResultStatus","Successful");

		//画面初期値
		$this->result->add("level","9");
		$this->result->add("ldap","1");
		$this->result->add("pass","");
		$this->result->add("status","1");

		switch($this->request->get("action")) {
			case "insert":
				$this->check(true);
				if($this->result->get("err") ==""){
					if($this->insertuser()){
						$this->accesslog("管理者メンテナンス", "登録");
						$this->result->add("jsalert","ユーザを登録しました。");
					}	
				}else{
					//画面のデータ保持
					$this->result->add("user_name" ,$this->request->get("user_name"));
					$this->result->add("level",$this->request->get("kanri_Lv"));
					$this->result->add("ldap",$this->request->get("ldap_auth"));
					$this->result->add("status",$this->request->get("status"));
				}
				break;
			case "update":
				$this->check(false);
				if($this->result->get("err") ==""){
					if($this->kousindata()){
						$this->accesslog("管理者メンテナンス", "更新");
						$this->result->add("jsalert","ユーザID「" . $this->javascript_escape($this->request->get("user_id")) . "」の\\nユーザを更新しました。");
					}	
				}else{
					//画面のデータ保持
					if($this->request->get("mine",false)=="true"){
						$this->result->add("mine",true);
					}
					$this->result->add("user_name" ,$this->request->get("user_name"));
					$this->result->add("level"     ,$this->request->get("kanri_Lv",USER_LEBEL_ADMIN));
					$this->result->add("ldap"      ,$this->request->get("ldap_auth"));
					$this->result->add("status"    ,$this->request->get("status","1"));
					$this->request->add("showone"  ,"dumy");
				}
				break;
			case "delete":
				if($this->deletedata()){
					$this->accesslog("管理者メンテナンス", "削除");
					$this->result->add("jsalert","ユーザID「" . $this->javascript_escape($this->request->get("user_id")) . "」の\\nユーザを削除しました。");
				}	
				break;
			case "showone":
				if($this->showone()==false){
					$this->request->add("user_id","");
					$this->request->add("showone","");
				}else{
					//管理者は、自分のデータで、更新できない箇所あり
					if( $this->request->get("user_id") == $this->session->get("user_id")
					and $this->session->get("user_lebel") == USER_LEBEL_ADMIN){
						$this->result->add("mine",true);
					}
				}
				break;
			default:
				break;
		}
		//一覧の表示
		$this->search();
	}
	
	//管理者一覧表の取得
	//戻り値 データあり：resultのUSERにデータをセット
	//       データなし：resultのUSERにfalseをセット
	private function search(){
		$strsql = "select 
					user_id,
					case user_lebel
						when '9' then '管理者'
						when '5' then 'ログ取得者'
						else 'エラー'
					end as user_lebel,
					case status
						when '0' then '無効'
						when '1' then '有効'
						else 'エラー'
					end as status,
					case ldap_auth_flg
						when '0' then '行わない'
						when '1' then '行う'
						else 'エラー'
					end as ldap_auth_flg,
					user_name,
					to_char(regist_time,'yyyy/mm/dd') as regist_time,
					to_char(update_time,'yyyy/mm/dd') as update_time
				from
					mst_user
				order by regist_time desc"; 
			
	
		$userdata = $this->db->selectdata($strsql);
		if(count($userdata) == 0){
			$this->result->add("USER",false);
		}else{
			$this->result->add("USER",$userdata);
			//削除時のalert用のデータを作成	
			$del_alert_arr = array();
			for($i=0;$i<count($userdata);$i++){
				$del_alert_arr += array($i => $this->javascript_escape($userdata[$i]["user_id"]));
			}
			$this->result->add("Delete_alert",$del_alert_arr);
		}

	}

	//チェック
	//エラーメッセージをエラー変数へ
	private function check($passindi){
		$err="";
		if($this->request->get("user_id") == ""){
			$err .= "ユーザーIDを入力してください。<br>";
		}elseif(mb_strlen($this->request->get("user_id"))>50){
			$err .= "ユーザーIDの桁数がオーバーしています。<br>";
		}elseif(strlen($this->request->get("user_id"))!=mb_strlen($this->request->get("user_id"))){
			$err .= "ユーザーIDは半角英数字で入力してください。<br>";
		}

		if(mb_strlen($this->request->get("user_name"))>50){
			$err .= "氏名の桁数がオーバーしています。<br>";
		}

		//パスワードチェック
		//LDAP認証を行わない時
		if($this->request->get("ldap_auth")=="0"){
			if($this->request->get("password_input")==""){
			//パスが入力されていない
				if($passindi){
				//パス必須(登録時)
					$err .= "パスワードを入力してください。<br>";
				}elseif(!$this->pass_null_check()){
				//Nullチェック
					$err .= "パスワードを入力してください。<br>";
				}
			}elseif(strlen($this->request->get("password_input"))>50){
				$err .= "パスワードの桁数がオーバーしています。<br>";
			}elseif(strlen($this->request->get("password_input"))!=mb_strlen($this->request->get("password_input"))){
				$err .= "パスワードは半角英数字で入力してください。<br>";
			}
		}

		//人数制限チェックs
		if($this->request->get("kanri_Lv",USER_LEBEL_ADMIN)==USER_LEBEL_ADMIN){
			//登録・更新するユーザ以外で管理者が4人以上なら、エラー
			$strsql = "select count(user_lebel) as count from mst_user
						where user_lebel ='" . USER_LEBEL_ADMIN . "'
						  and user_id != '" . common::sqlescape($this->request->get("user_id")) . "'";
			$userdata = $this->db->selectdata($strsql);
			if($userdata[0]["count"] >= 4){
				$err .= "管理者の登録は4人までです。<br>";
			}
		}
		$this->result->add("err",$err);
	}

	//パスワードのNullチェック
	//戻り値 パスワードが設定されている  ：true
	//       パスワードが設定されていない：false
	private function pass_null_check(){
		$passSQL = "select user_pw from mst_user
					 where user_id = '" . common::sqlescape($this->request->get("user_id")) . "'";
		$passdata = $this->db->selectdata($passSQL);
		if($passdata[0]["user_pw"] == ""){
			return false;
		}
		return true;
	}

	//ユーザーを登録
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function insertuser(){
		$strsql = "select user_id from
					 mst_user
				where
					user_id='" .common::sqlescape($this->request->get("user_id")) . "'";
		$userdata = $this->db->selectdata($strsql);
		if(count($userdata) > 0){
			$this->result->add("err","同じデータが既に登録されてます");
		}else{
			$userdata = array(
					"user_id"       => common::sqlescape( $this->request->get("user_id")),
					"user_pw"       => common::sqlescape($this->request->get("password_input")),
					"user_name"     => common::sqlescape( $this->request->get("user_name")),
					"user_lebel"    => common::sqlescape($this->request->get("kanri_Lv")),
					"ldap_auth_flg" => common::sqlescape($this->request->get("ldap_auth","1")),	//ログ取得者が選択されて、無効になっているときは、有効("1")
					"status"        => common::sqlescape($this->request->get("status")),
					"regist_time"   => date("Y/m/d H:i:s"),
					"update_time"   => date("Y/m/d H:i:s"),
					"update_uid"    => $this->session->get("user_id")
			);
	
			return $this->db->insert("mst_user", $userdata);
		}
		return false;
	}

	//更新ボタンの押されたデータを取得
	private function showone(){
		$strsql = "select user_id,user_name,user_lebel,ldap_auth_flg,status,user_pw
					 from mst_user
					where user_id='" .common::sqlescape($this->request->get("user_id")) . "'";
		$userdata = $this->db->selectdata($strsql);
		if(count($userdata) == 1){
			$this->result->add("user_id"   ,$userdata[0]["user_id"]);
			$this->result->add("user_name" ,$userdata[0]["user_name"]);
			$this->result->add("level"     ,$userdata[0]["user_lebel"]);
			$this->result->add("ldap"      ,$userdata[0]["ldap_auth_flg"]);
			$this->result->add("status"    ,$userdata[0]["status"]);
			//パスが登録してあれば、必須でない
			if(strlen($userdata[0]["user_pw"])>0){
				$this->result->add("pass_indi",false);
			}else{
				$this->result->add("pass_indi",true);
			}
		} elseif(count($userdata) == 0){
			$this->result->add("err","そのデータは既に削除されています。<br>");
			return false;
		}
		return true;
	}
	
	//更新処理
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function kousindata(){
		//必ず更新する項目
		$userdata = array(
				"user_name"     => common::sqlescape( $this->request->get("user_name")),
				"update_time"   => date("Y/m/d H:i:s"),
				"ldap_auth_flg" => common::sqlescape($this->request->get("ldap_auth","1")),	//ログ取得者が選択されて、無効になっているときは、有効("1")
				"update_uid"    => $this->session->get("user_id")
		);
		
		//管理者レベルが管理者のユーザは、自分自身の管理者レベルとステータスを変更できない。
		if($this->request->get("mine") != "true"){
			$userdata += array(
					"user_lebel"    => common::sqlescape($this->request->get("kanri_Lv")),
					"status"        => common::sqlescape($this->request->get("status"))
				);
		}
		//パスワードは入力があったときのみ変更
		if($this->request->get("password_input","") != ""){
			$userdata += array("user_pw" => common::sqlescape($this->request->get("password_input")));
		}
		$conditions =" user_id='" . common::sqlescape($this->request->get("user_id")) . "' ";
		

		//該当するデータがなければ、更新不可
		$err="";
		$selsql = "select user_id from mst_user where user_id='" .common::sqlescape($this->request->get("user_id")) . "' ";
		$count = $this->db->selectdata($selsql);
		if(count($count)==0){
			$err .= "そのデータは既に削除されています。<br>";
		}

		//更新
		$this->result->add("err",$err);
		if($this->result->get("err") ==""){
			return $this->db->update("mst_user", $userdata,$conditions);
		}
		return false;
	}

	//削除処理
	//戻り値：true :エラーなし
	//        false:エラーあり
	private function deletedata(){
		$err="";
		//自身のデータは削除不可
		if($this->request->get("user_id") == $this->session->get("user_id")){
			$err .= "そのデータは削除できません。<br>";
		}

		//該当するデータがなければ、削除不可
		$selsql = "select user_id from mst_user where user_id='" .common::sqlescape($this->request->get("user_id")) . "' ";
		$count = $this->db->selectdata($selsql);
		if(count($count)==0){
			$err .= "そのデータは既に削除されています。<br>";
		}

		//削除
		$this->result->add("err",$err);
		if($this->result->get("err") ==""){
			$strsql = "delete from mst_user where user_id='" .common::sqlescape($this->request->get("user_id")) . "' ";
			
			return $this->db->executesql($strsql);
		}
		return false;
	}
	
	public function javascript_escape($str){
		$str = str_replace("\\","\\\\",$str);
		$str = str_replace("'","\'",$str);
		$str = str_replace("\"","\\\"",$str);
		return $str;
	}

}
?>