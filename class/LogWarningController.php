<?php
/*
ログ管理規約クラス
*/
class LogWarningController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}

	public function execute(){
		//ログ閲覧者(user_lebel="5")またはフル権限管理者(user_lebel="9")以外は接続を拒否する
		if($this->session->get("user_lebel") <> USER_LEBEL_ADMIN and
		   $this->session->get("user_lebel") <> USER_LEBEL_LOGGER){
			$this->result->add("ResultStatus","DenyAccess");
			return false;
		}
		//ボタンが押下されたか
		if ($this->request->get("action") == "logmnt"){
			$this->result->add("ResultStatus","LogMnt");
		}else{
			//初期画面
			//ワーニングのファイルの読み込み
			$this->getfile();
			$this->result->add("ResultStatus","Successful");
		}
	}

	private function getfile(){
		$filename = DIR_NAME_TEMPLATE . FILE_NAME_LOG_WARNING;
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
}
?>