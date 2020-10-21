<?php
/*
ログイン機能クラス
*/
class MenuController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}
	
	public function execute(){
		if($this->session->get("user_lebel") == USER_LEBEL_ADMIN){
			$this->result->add("ResultStatus","Successful");
			$this->result->add("Disabled","");
		}elseif($this->session->get("user_lebel") == USER_LEBEL_LOGGER){
			$this->result->add("ResultStatus","Successful");
			$this->result->add("Disabled","disabled");
		}else{
			return;
		}	
	}
}
?>