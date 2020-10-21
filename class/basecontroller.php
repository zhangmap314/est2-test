<?php
/*
各コントローラーのベースクラス
*/

require_once("dbclass.php");
require_once("common.php");

class basecontroller{
	/*セッティング情報*/
	private $debug = false;
	
	/*画面右クリック有効・無効*/
	public $enablecontextmenu = false;
	
	/*db接続文字列*/
	private $connstr		= "host=127.0.0.1 port=5433 dbname=estweb2_test user=estweb2_test password=zhangwei";
	
	//public $simulation_max_rows = 4;		//シミュレーション画面の機種行数
	public $simulation_max_rows = 99;		//表示行数用ドロップダウンリスト上限
	
	public $ricoh_maker_cd = "10";			//リコーのメーカーコード
	
	
	/*
	protected $ldapserver = "192.168.242.181";		//LDAPサーバーアドレス(テスト環境)
	protected $ldapport = 389;						//LDAPポート番号
	protected $appdn = "cn=ESTWEB,ou=Applications,dc=ricoh,dc=com";	//アプリバインド情報
	protected $apppw = "bewtse2008";								//アプリパスワード
	protected $basedn = "dc=ricoh,dc=com";							//ベースDN
	*/
	
	// 2013/11/16 LDAP server replace modify START
	//protected $ldapserver = "192.168.242.180";		//LDAPサーバーアドレス(本番環境)
	protected $ldapserver = "127.0.0.1";		//LDAPサーバーアドレス(本番環境)
	// 2013/11/16 LDAP server replace modify END
	protected $ldapport = 389;						//LDAPポート番号
	protected $appdn = "cn=ESTWEB,ou=Applications,dc=ricoh,dc=com";	//アプリバインド情報
	protected $apppw = "bewtse2008+1";								//アプリパスワード
	protected $basedn = "dc=ricoh,dc=com";							//ベースDN
	
	
	
	/*クラス変数*/
	protected $request;
	protected $session;
	protected $result;
	protected $db = false;
	
	
	
	
	
	/*******************************************************
	コンストラクタ
	*******************************************************/
	public function __construct($_request_,$_session_,$_result_){
		/*定数*/
		define("USER_LEBEL_ADMIN",	"9");									//管理者ユーザレベル
		define("USER_LEBEL_LOGGER",	"5");									//ログ取得者ユーザレベル
		
		define("DIR_NAME_TEMPLATE",				"./template/");				//テンプレートディレクトリ
		define("DIR_NAME_IDEA",					"./template/idea/");			//テンプレートディレクトリ
		
		define("FILE_NAME_SIM_WARNING",			"sim_warning.txt");			//シミュレーションワーニングファイル名
		define("FILE_NAME_LOG_WARNING",			"log_warning.txt");			//ログ管理ワーニング
		define("FILE_NAME_TELOP",				"telop.txt");				//テロップファイル
		define("FILE_NAME_SIM_RESULT_WARNING",	"sim_result_warning.txt");	//シミュレーション注意事項
		define("FILE_NAME_HOWTO",				"howto.pdf");				//ご使用に当たって
		define("FILE_NAME_MANUAL",				"manual.pdf");				//操作方法

		
		$this->request = $_request_;
		$this->session = $_session_;
		$this->result = $_result_;
		
		if( mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) != "Login.php"){
			//セッションからユーザID取れない場合はセッションタイムアウトと判断する。
			if( $this->session->get("user_id") == ""){
				$this->result->add('err_kbn', '999');	//エラーページタイトル設定用
				$this->result->add('systemerror','セッションがタイムしました。再度ログインして下さい。'); 
				$this->showerrorpage();
				exit;
			}
			//一般ユーザはシミュレーションと環境メンテ以外は見れない
			if($this->session->get("user_lebel")!= USER_LEBEL_ADMIN && $this->session->get("user_lebel")!= USER_LEBEL_LOGGER){
				if(mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) != "Simulation.php" &&
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) != "SimWarning.php" &&
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) != "EcoInfoMnt2.php" &&
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) != "Help.php"){
				
					$this->denyaccess();
					exit;
				}
			}
			
			if($this->session->get("user_lebel")!= USER_LEBEL_ADMIN){
				if(mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) == "UserMnt.php" ||
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) == "Upload.php" ||
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) == "ProductEntry.php" ||
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) == "ProductList.php" ||
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) == "EcoInfoMnt.php" ||
					mb_substr($_SERVER['SCRIPT_NAME'], mb_strrpos($_SERVER['SCRIPT_NAME'],"/")+1) == "CalledFromMnt.php"){
					$this->denyaccess();
					exit;
				}
			}
		}
		
		
		if($this->debug){
			print "リクエストパラメーター：<br>";
			print "<pre>";
			print_r($_request_);
			print "</pre>";
			
			print "<br>セッションデータ：<br>";
			$this->session->printsession();
			
			print("<br><br>");
			print ("<br>定数：<br>");
		
			print("USER_LEBEL_ADMIN&nbsp;&nbsp;=>&nbsp;&nbsp;".USER_LEBEL_ADMIN."<br>");
			print("USER_LEBEL_LOGGER&nbsp;&nbsp;=>&nbsp;&nbsp;".USER_LEBEL_LOGGER."<br>");
			print("DIR_NAME_TEMPLATE&nbsp;&nbsp;=>&nbsp;&nbsp;".DIR_NAME_TEMPLATE."<br>");
			print("DIR_NAME_IDEA&nbsp;&nbsp;=>&nbsp;&nbsp;".DIR_NAME_IDEA."<br>");
			print("FILE_NAME_SIM_WARNING&nbsp;&nbsp;=>&nbsp;&nbsp;".FILE_NAME_SIM_WARNING."<br>");
			print("FILE_NAME_LOG_WARNING&nbsp;&nbsp;=>&nbsp;&nbsp;".FILE_NAME_LOG_WARNING."<br>");
			print("FILE_NAME_TELOP&nbsp;&nbsp;=>&nbsp;&nbsp;".FILE_NAME_TELOP."<br>");
			print("FILE_NAME_SIM_RESULT_WARNING&nbsp;&nbsp;=>&nbsp;&nbsp;".FILE_NAME_SIM_RESULT_WARNING."<br>");
			print("FILE_NAME_HOWTO&nbsp;&nbsp;=>&nbsp;&nbsp;".FILE_NAME_HOWTO."<br>");
			print("FILE_NAME_MANUAL&nbsp;&nbsp;=>&nbsp;&nbsp;".FILE_NAME_MANUAL."<br>");

			
		}
	}
	
	/*******************************************************
	アクセス拒否ヘッダをセット
	*******************************************************/
	public function denyaccess() {
		if($this->debug){
			print "<br><br>不正なパラメーターが送信されました。<br>";
			print "****************************************************<br>";
			print("<pre>");
			print_r($this->request);
			print("<pre>");
			print "<br>****************************************************<br>";
		}else{
			header("HTTP/1.0 403 Access Denied");
		}
	}
	
	/*******************************************************
	エラー画面を表示する
	*******************************************************/
	public function showerrorpage(){
		include_once("SystemError.html");
		$this->closedb();
		exit;
	}
	
	/*******************************************************
	データベースに接続を代行する
	*******************************************************/
	public function connectdb(){
		$this->db = new dbclass($this->connstr);
		if(!$this->db->connect()){
			$this->result->add("systemerror", "データベースに接続できませんでした。");
			$this->showerrorpage();
			$this->db = false;
		}
	}
	
	/*******************************************************
	データベースクローズを代行する
	*******************************************************/
	public function closedb(){
		if( $this->db ){
			$this->db->close();
			$this->db = false;
		}
	}
	
	
	
	protected function getsaveno(){
		$data = $this->db->selectdata("select  nextval('saveno')  as saveno ");
		
		return $data[0]["saveno"];
	}
	
	public function insertlog(){
		$logdata = array("save_no" => $this->getsaveno(),
				 "unique_id" => common::sqlescape($this->session->get("user_unique_id")),
				 "user_id" => common::sqlescape($this->session->get("user_id")),
				 "user_name" => common::sqlescape($this->session->get("user_name")),
				 "corp_cd" => common::sqlescape($this->session->get("corp_cd")),
				 "corp_name" => common::sqlescape($this->session->get("corp_name")),
				 "shozoku_cd" => common::sqlescape($this->session->get("shozoku_cd")),
				 "shozoku_name" => common::sqlescape($this->session->get("shozoku_name")),
				 "user_lebel" => $this->session->get("user_lebel"),
				 "caller_id" => $this->session->get("caller"),
				 "login_time" => date("Y/m/d H:i:s")
				 );
		
		return $this->db->insert("tbl_login_log", $logdata);
	}
	
	public function accesslog($menu_name = "", $button_name = ""){
		$save_no = $this->getsaveno();
		$accessdata = array("save_no" => $save_no,
				 "unique_id" => common::sqlescape($this->session->get("user_unique_id")),
				 "user_id" => common::sqlescape($this->session->get("user_id")),
				 "user_name" => common::sqlescape($this->session->get("user_name")),
				 "corp_cd" => common::sqlescape($this->session->get("corp_cd")),
				 "corp_name" => common::sqlescape($this->session->get("corp_name")),
				 "shozoku_cd" => common::sqlescape($this->session->get("shozoku_cd")),
				 "shozoku_name" => common::sqlescape($this->session->get("shozoku_name")),
				 "user_lebel" => $this->session->get("user_lebel"),
				 "menu_name" => $menu_name,
				 "button_name" => $button_name,
				 "access_time" => date("Y/m/d H:i:s")
				 );
		$this->db->insert("tbl_access_log", $accessdata);
		return $save_no;
	}
	
	
	/*******************************************************
	デストラクタ
	*******************************************************/
	public function __destruct(){
		unset($this->request);
		unset($this->session);
		unset($this->result);
		
		$this->closedb();
	}
}
?>
