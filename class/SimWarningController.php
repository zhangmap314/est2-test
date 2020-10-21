<?php
/*
ログイン機能クラス
*/
class SimWarningController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}

	public function execute(){
		//ボタンが押下されたか
		if ($this->request->get("action") == "ToSimulation"){
			//シミュレーションヘが押された
			//ユーザの管理レベルが"管理者"でも"ログ取得者"でもなかったら
			//ログインログを生成する。
			if($this->session->get("user_lebel") <> USER_LEBEL_ADMIN and
			   $this->session->get("user_lebel") <> USER_LEBEL_LOGGER){
				//すでにログがなければ
				if(!$this->selectloginlog()){
					//ログを生成する
					if(!$this->insertlog()){
						$this->result->add("systemerror", "ログデータを生成できませんでした。");
						$this->showerrorpage();
						return false;
					}
			   }
			}
			$this->result->add("ResultStatus","ToSimulation");
		}else{
			//初期画面
			//ワーニングのファイルの読み込み
			$this->getfile();
			$this->result->add("ResultStatus","Successful");
		}
	}

	//ファイルの読み込み
	//werningtextにファイルの中身を入れる。
	private function getfile(){
		$filename = DIR_NAME_TEMPLATE . FILE_NAME_SIM_WARNING;
		$filebuf = "";
		if(file_exists($filename)){
			if(is_readable($filename)){
				$filebuf = file_get_contents($filename);
				$filebuf = mb_convert_encoding($filebuf,"UTF-8", "UTF-8,sjis,euc-jp");
				$filebuf = str_replace("\"","\\\"",$filebuf);		// " ---> \" に変換
				
				$this->result->add("warningtext",$filebuf);
			}
		}
	}
	
	//ログインログが存在するかどうか
	//戻り値 存在する  :true
	//       存在しない:false
	private function selectloginlog(){
		$sql = "select save_no from tbl_login_log " .
			   " where unique_id = '" . common::sqlescape($this->session->get("user_unique_id")) . "'" .
			   "   and user_id   = '" . common::sqlescape($this->session->get("user_id")) . "'";
		$loginlog = $this->db->selectdata($sql);
		if(count($loginlog) > 0){
			return true;
		}
		return false;
	}
}
?>