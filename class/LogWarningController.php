<?php
/*
���O�Ǘ��K��N���X
*/
class LogWarningController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}

	public function execute(){
		//���O�{����(user_lebel="5")�܂��̓t�������Ǘ���(user_lebel="9")�ȊO�͐ڑ������ۂ���
		if($this->session->get("user_lebel") <> USER_LEBEL_ADMIN and
		   $this->session->get("user_lebel") <> USER_LEBEL_LOGGER){
			$this->result->add("ResultStatus","DenyAccess");
			return false;
		}
		//�{�^�����������ꂽ��
		if ($this->request->get("action") == "logmnt"){
			$this->result->add("ResultStatus","LogMnt");
		}else{
			//�������
			//���[�j���O�̃t�@�C���̓ǂݍ���
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
				$filebuf = str_replace("\"","\\\"",$filebuf);		// " ---> \" �ɕϊ�
				
				$this->result->add("warningtext",$filebuf);
			}
		}
	}
}
?>