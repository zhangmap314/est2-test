<?php
/*
ログイン機能クラス
*/
class LoginController extends basecontroller{

    public function __construct($_request_, $_session_, $_result_){
        parent::__construct($_request_, $_session_, $_result_);
    }
    public function __destruct(){
        parent::__destruct();
    }

	public function execute(){
		if($this->request->get("clear") != ""){
			$this->request->add("userid", "");
			$this->result->add("ResultStatus","DoLogin");
			return false;
		}
		//呼出し元のチェック
		if(!$this->checkcaller()){
			$this->result->add("ResultStatus","DenyAccess");
			return false;
		}
		
		$this->session->add("caller", $this->request->get("caller") );
		
		if($this->session->get("loginSAML")=='0'){
			/*ユーザIDまたはパスワードがない場合はログイン画面を表示*/
			if( $this->request->get("userid") == "" ||
				$this->request->get("userpw") == ""){
				
				$this->result->add("ResultStatus","DoLogin");
				return false;
			}
			
			/*パスワードが暗号化されている場合(ログイン画面より遷移の場合は復号処理しない)*/
			if( $this->request->get("systemloginparam") != "noenc"){
				$this->pwdecoding();
			}
			//ユーザ情報取得
			$this->getuserinfo();
			
			if($this->session->get("status") != "1" ){
				$this->result->add("errmsg","ユーザIDは無効になっています。");
				$this->result->add("ResultStatus","DoLogin");
				return false;
			}
			if($this->session->get("ldap_auth_flg") == '1'){
				/*LDAP認証*/
				if(!$this->ldapauth()){
					$this->result->add("errmsg","LDAP認証失敗しました。");
					$this->result->add("ResultStatus","DoLogin");
					return false;
				}
			}else{
				if($this->request->get("userpw") != $this->result->get("user_pw")){
					$this->result->add("errmsg","ユーザIDまたはパスワードが違います。");
					$this->result->add("ResultStatus","DoLogin");
					return false;
				}
			}

		} else {
			if( $this->request->get("userid") == "") {
				$this->saml();
				return false;
			}
			//ユーザ情報取得
			$this->getuserinfo();
			if($this->session->get("status") != "1" ){
				$this->result->add("errmsg","ユーザIDは無効になっています。");
				$this->result->add("ResultStatus","DoLogin");
				return false;
			}
			if ( $this->request->get("hash_v") == "" ||
				 $this->request->get("hash_v") != common::make_hash_v($this->request->get("userid").$this->request->get("caller"))) {
				if($this->session->get("ldap_auth_flg") == '1'){
					$this->saml();
					return false;
				}else{
					if($this->request->get("userpw") != $this->result->get("user_pw")){
						if ($this->request->get("userpw") != "") {
							$this->result->add("errmsg","ユーザIDまたはパスワードが違います。");
						}
						$this->result->add("ResultStatus","DoLogin");
						return false;
					}
				}
			}
		}

		$this->result->add("ResultStatus","LoginSuccessful");	
		$this->session->add("user_unique_id" , md5(microtime()) . md5(microtime() . $this->request->get("userid")));

		if($this->session->get("user_lebel") == USER_LEBEL_ADMIN || 
			$this->session->get("user_lebel") == USER_LEBEL_LOGGER){//管理者またはログ取得者の場合はログインログを生成
			
			if(!$this->insertlog()){
				$this->result->add("systemerror", "ログデータを生成できませんでした。");
				$this->showerrorpage();
				return false;
			}
		}
	}

    protected function getuserinfo(){
        //ユーザマスタからユーザ情報を検索
        $strsql = "select 
						user_id,user_name,user_lebel,ldap_auth_flg,status,user_pw
					from
						mst_user 
					where  ";
        $strsql .= "user_id = '" . common::sqlescape($this->request->get("userid")) . "'";

        $data = $this->db->selectdata($strsql);

        if( count($data) == 1 ){
            $this->session->add("user_id", $data[0]["user_id"]);
            $this->session->add("user_name", $data[0]["user_name"]);
            $this->session->add("user_lebel", $data[0]["user_lebel"]);
            $this->session->add("ldap_auth_flg", $data[0]["ldap_auth_flg"]);
            $this->session->add("status", $data[0]["status"]);
            $this->result->add("user_pw", $data[0]["user_pw"]);

        }else{
            $this->session->add("user_id", $this->request->get("userid"));
            $this->session->add("user_name", "");
            $this->session->add("user_lebel", '0');			//一般ユーザ
            $this->session->add("ldap_auth_flg", '1');		//要認証
            $this->session->add("status", '1');				//有効

            $this->result->add("user_pw", "");

        }
    }

    /*呼出し元マスタを検索し、渡されたデータがマスタに存在するかをチェックする*/
    private function checkcaller(){

        $strsql = "select caller_name  from mst_caller where ";
        $strsql .= "caller_id = '" . common::sqlescape($this->request->get("caller")) . "'";

        $data = $this->db->selectdata($strsql);

        if(count($data) == 0){
            return false;
        };
        if(substr($data[0]["caller_name"],0,4)=="LDAP"){
            $this->session->add("loginSAML", "0");
            return  true;
        }else{

            $this->session->add("loginSAML", "1");
            return  true;
        }
        return false;
    }


    private function pwdecoding(){
        /*パスワード復号化処理*/
        /*****************暗号化手順********************/
        /*文字列を１文字ずつAsciiコードの数値に変換し、*/
        /*結果に７を乗じて、"%" で連結する*/
        /*例:RICOH --> 82 73 67 79 72 --> 574 511 469 553 504 --> 574%511%469%553%504 */

        /*****************復号化手順********************/
        /*受取った文字列を "%" で分割し、各数値を文字に戻してから連結する*/

        $ar = split( "%", $this->request->get("userpw") );
        $pw = "";
        for($i = 0; $i < count($ar); $i++){
            $pw .= chr( $ar[$i] / 7 );
        }

        $this->request->add("userpw", $pw);
    }
    /*
    LDAP認証を行う
    詳細：
        LDAPサーバに接続して、アプリDNでバインドする
        ユーザidでエントリを検索
        検索結果からユーザDNを取出す
        ユーザDNでバインドする
        エントリから会社名を取得
    エントリから取出せるユーザ属性：
        名前(ローマ字)：		cn
        名前(漢字)：			cn;lang-ja
        名前(フリガナ)：		cn;lang-ja;phonetic
        姓(ローマ字)：			sn
        姓(漢字)：				sn;lang-ja
        姓(フリガナ)：			sn;lang-ja;phonetic
        名(ローマ字)：			givenname
        名(漢字)：				givenname;lang-ja
        名(フリガナ)：			givenname;lang-ja;phonetic
        電話番号：				telephonenumber
        FAX番号：				facsimiletelephonenumber
        役職コード：			title
        役職：					title;lang-ja
        所属名：				ou(ou;lang-ja)
        所属コード：			departmentNumber
        社員番号：				employeenumber
        社員区分：				employeetype
        メールアドレス：		Mail
        ユーザID：				uid
        秘書DN：				secretary
        勤務状況区分：			ricohEmployeementStatus
        会社名：				o(o;lang-ja)
        会社コード：			ricohcompanynumber
        NotesID：				ricohnotsaid
        Notesアドレス上の役職：	ricohNotesTitle
        派遣元会社名：			ricohdispatchedcompany;lang-ja
        勤務地：				ricohlocation
    */
    private function ldapauth(){

        if( $_SERVER["SERVER_ADDR"] == "10.250.125.190" ){//開発環境
            //会社コード
            $this->session->add("corp_cd", "test" );
            //会社名
            $this->session->add("corp_name", "test" );
            //所属コード
            $this->session->add("shozoku_cd", "test" );
            //所属名
            $this->session->add("shozoku_name", "test" );
            return true;
        }

        /*LDAP認証処理*/
        $rtn = false;

        //アプリ接続
        if(! ($ldapconn = ldap_connect($this->ldapserver, $this->ldapport ) ) ){
            $this->result->add("systemerror", "LDAPサーバーに接続できませんでした。");
            $this->showerrorpage();
            return false;
        }

        //アプリDNでバインド
        if(! ($appbind = ldap_bind($ldapconn, $this->appdn, $this->apppw)) ){
            $extended_error='';
            ldap_get_option($ldapconn, 0x0032, $extended_error);
            ldap_close($ldapconn);
            $this->result->add("systemerror", "バインドエラー。".$extended_error);
            $this->showerrorpage();
            return false;
        }
        //エントリ検索
        $result = ldap_search($ldapconn, $this->basedn, "(uid=".$this->request->get("userid").")");

        //結果の有無
        if( ldap_count_entries($ldapconn,$result) <= 0 ){
            $rtn = false;
        }else{
            //検索結果からエントリを取出す
            $entry = ldap_first_entry($ldapconn, $result);
            //エントリからユーザDNを取出す
            if( !($usrdn = ldap_get_dn($ldapconn, $entry)) ){
                $rtn = false;
            }else{

                //ユーザ接続
                if(! ($ldapuserconn = ldap_connect($this->ldapserver, $this->ldapport ) ) ){
                    //検索結果を開放
                    ldap_free_result($result);
                    ldap_close($ldapconn);

                    $this->result->add("systemerror", "LDAPサーバーに接続できませんでした。");
                    $this->showerrorpage();
                    return false;
                }


                //ユーザDNでバインドを試みる
                if( !ldap_bind($ldapuserconn, $usrdn, $this->request->get("userpw")) ){
                    $rtn = false;
                }else{
                    //エントリからユーザ情報を取得
                    $attrs = ldap_get_attributes($ldapconn, $entry);

                    //会社コード
                    if (isset($attrs["ricohcompanynumber"]))
                        $this->session->add("corp_cd", $attrs["ricohcompanynumber"][0] );
                    //会社名
                    $this->session->add("corp_name", $attrs["o;lang-ja"][0] );
                    //所属コード
                    if (isset($attrs["departmentNumber"]))
                        $this->session->add("shozoku_cd", $attrs["departmentNumber"][0] );
                    //所属名
                    $this->session->add("shozoku_name", $attrs["ou;lang-ja"][0] );

                    $rtn = true;
                }
                ldap_close($ldapuserconn);
            }
        }
        //検索結果を開放
        ldap_free_result($result);
        ldap_close($ldapconn);

        return $rtn;
    }

    private function saml(){
        header("Location: ./saml/index.php?sso");

    }
}
?>