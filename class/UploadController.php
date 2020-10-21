<?php

/*
アップロードクラス
*/
class UploadController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}

	public function execute(){
		//フル権限管理者(user_lebel="9")以外は接続を拒否する
		if($this->session->get("user_lebel") != USER_LEBEL_ADMIN){
			$this->result->add("ResultStatus","DenyAccess");
			return false;
		}
		
		//actionに処理が指定されている場合は、その処理を行う
		switch($this->request->get("action")) {
			case "upload":
				$this->upload();
				break;
			case "delete":
				$this->delete();
				break;
		}
		
		// ファイル情報の取得
		$ar = scandir( DIR_NAME_IDEA );
		$dirArr = array();
		for( $i=0; $i < count($ar); $i++ ){
			if( $ar[$i] != "." && $ar[$i] != ".." 
				&& substr($ar[$i],-3,3) == "xls"){
				array_push($dirArr,mb_convert_encoding($ar[$i],"UTF-8","auto"));
			}
		}
		
		for( $i=0; $i < count($ar); $i++ ){
			if( $ar[$i] != "." && $ar[$i] != ".." 
				&& substr($ar[$i],-4,4) == "xlsm"){
				array_push($dirArr,mb_convert_encoding($ar[$i],"UTF-8","auto"));
			}
		}
		//requestに結果を出力
		$this->result->add("upl_idea", $dirArr);
		
	}
	
	private function upload(){
		/*
		howto：ご使用にあたって（pdf）
		manual：操作説明（pdf）
		telop：テロップ情報アップロード（txt）
		sim_warning：シミュレーションワーニング文書（txt）
		log_warning：ログダウンロードワーニング文書（txt）
		sim_result_warning：シミュレーション注意事項（txt）
		idea1：提案書テンプレート1（xls）
		idea2：提案書テンプレート2（xls）
		idea3：提案書テンプレート3（xls）
		*/
	
		//application/vnd.ms-powerpoint
		//application/msword
		//application/vnd.ms-excel
		//application/pdf
		//text/plain
		
		/*
		print "<pre>";
		print_r($_FILES['help1']);
		print_r($_FILES['help2']);
		print "</pre>";
		*/
		
		$errArr = array();
		//全項目にアップロードファイルが指定されていない場合はエラー
		if( trim($_FILES['howto']['name']) == "" && 
			trim($_FILES['manual']['name']) == "" && 
			trim($_FILES['telop']['name']) == "" && 
			trim($_FILES['sim_warning']['name']) == "" && 
			trim($_FILES['log_warning']['name']) == "" && 
			trim($_FILES['sim_result_warning']['name']) == "" && 
			trim($_FILES['idea1']['name']) == "" && 
			trim($_FILES['idea2']['name']) == "" && 
			trim($_FILES['idea3']['name']) == ""){
			array_push($errArr, "アップロードファイルを指定してください。");
			$this->result->add("err",$errArr);
			return false;
		}
		
		//ファイルを保存
		if(isset($_FILES['howto']['tmp_name'])){
			if($_FILES['howto']['tmp_name'] != ""){
				
				//アップロードされたファイルがpdfかをチェック
				if( trim($_FILES["howto"]["type"]) == "application/pdf" ){
					if(!move_uploaded_file($_FILES['howto']['tmp_name'], DIR_NAME_TEMPLATE.FILE_NAME_HOWTO) ){
						array_push($errArr,"\"ご使用にあたって\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"ご使用にあたって\"はpdfファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['manual']['tmp_name'])){
			if($_FILES['manual']['tmp_name'] != ""){
				
				//アップロードされたファイルがpdfかをチェック
				if( trim($_FILES["manual"]["type"]) == "application/pdf" ){
					if(!move_uploaded_file($_FILES['manual']['tmp_name'], DIR_NAME_TEMPLATE.FILE_NAME_MANUAL ) ){
						array_push($errArr,"\"操作説明\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"操作説明\"はpdfファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['telop']['tmp_name'])){
			if($_FILES['telop']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( trim($_FILES["telop"]["type"]) == "text/plain" ){
					if(!move_uploaded_file($_FILES['telop']['tmp_name'], DIR_NAME_TEMPLATE.FILE_NAME_TELOP ) ){
						array_push($errArr,"\"テロップ情報アップロード\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"テロップ情報アップロード\"はtxtファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['sim_warning']['tmp_name'])){
			if($_FILES['sim_warning']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( trim($_FILES["sim_warning"]["type"]) == "text/plain" ){
					if(!move_uploaded_file($_FILES['sim_warning']['tmp_name'], DIR_NAME_TEMPLATE.FILE_NAME_SIM_WARNING ) ){
						array_push($errArr,"\"シミュレーションワーニング文書\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"シミュレーションワーニング文書\"はtxtファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['log_warning']['tmp_name'])){
			if($_FILES['log_warning']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( trim($_FILES["log_warning"]["type"]) == "text/plain" ){
					if(!move_uploaded_file($_FILES['log_warning']['tmp_name'], DIR_NAME_TEMPLATE.FILE_NAME_LOG_WARNING ) ){
						array_push($errArr,"\"ログダウンロードワーニング文書\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"ログダウンロードワーニング文書\"はtxtファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['sim_result_warning']['tmp_name'])){
			if($_FILES['sim_result_warning']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( trim($_FILES["sim_result_warning"]["type"]) == "text/plain" ){
					if(!move_uploaded_file($_FILES['sim_result_warning']['tmp_name'], DIR_NAME_TEMPLATE.FILE_NAME_SIM_RESULT_WARNING ) ){
						array_push($errArr,"\"シミュレーション注意事項\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"シミュレーション注意事項\"はtxtファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['idea1']['tmp_name'])){
			if($_FILES['idea1']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( substr(trim($_FILES["idea1"]["type"]),0,24) == "application/vnd.ms-excel" ){
					if(!move_uploaded_file($_FILES['idea1']['tmp_name'], DIR_NAME_IDEA.$this->xlsNmLow($_FILES['idea1']['name']))){
						array_push($errArr,"\"提案書テンプレート1\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"提案書テンプレート1\"はxlsファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['idea2']['tmp_name'])){
			if($_FILES['idea2']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( substr(trim($_FILES["idea2"]["type"]),0,24) == "application/vnd.ms-excel" ){
					if(!move_uploaded_file($_FILES['idea2']['tmp_name'], DIR_NAME_IDEA.$this->xlsNmLow($_FILES['idea2']['name']))){
						array_push($errArr,"\"提案書テンプレート2\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"提案書テンプレート2\"はxlsファイルを指定して下さい。");
				}
			}
		}
		if(isset($_FILES['idea3']['tmp_name'])){
			if($_FILES['idea3']['tmp_name'] != ""){
				
				//アップロードされたファイルがtxtかをチェック
				if( substr(trim($_FILES["idea3"]["type"]),0,24) == "application/vnd.ms-excel" ){
					if(!move_uploaded_file($_FILES['idea3']['tmp_name'], DIR_NAME_IDEA.$this->xlsNmLow($_FILES['idea3']['name']))){
						array_push($errArr,"\"提案書テンプレート3\"がアップロード出来ませんでした。");
					}
				}else{
					array_push($errArr,"\"提案書テンプレート3\"はxlsファイルを指定して下さい。");
				}
			}
		}
		
		//アップロード処理後、エラー配列をresultに出力
		$this->result->add("err",$errArr);
		
		if(sizeof($errArr)==0){
			$this->accesslog("ファイルアップロード", "アップロード");
			$this->result->add("JSAlert", "ファイルをアップロードしました。");
			
		}
	}
	
	private function delete(){
		$errArr = array();
		//削除するファイルの名前をhiddenから取得
		$delfile = $this->request->get("del_fname");
		
		//削除処理実行
		if(!file_exists(DIR_NAME_IDEA.$delfile)){
			array_push($errArr,"\"$delfile\"は存在しません。");
		}elseif(!unlink(DIR_NAME_IDEA.$delfile)){
			array_push($errArr,"\"$delfile\"が削除出来ませんでした。");
		}
		
		//削除処理後、エラー配列をresultに出力
		$this->result->add("err",$errArr);
		
		//エラーが発生していない場合、削除完了アラートを表示
		if(sizeof($errArr)==0){
			$this->accesslog("ファイルアップロード", "削除");
			$this->result->add("JSAlert", "ファイルを削除しました。");
			
		}
	}
	
	private function xlsNmLow($fname){
		//excelファイルの最後の拡張子を.xlsに変更する
		if(strtoupper(substr($fname,-4,4)) == ".XLS"){
			$fname = substr($fname, 0, strrpos($fname, '.')).".xls";
		} elseif (strtoupper(substr($fname,-5,5)) == ".XLSM"){
			$fname = substr($fname, 0, strrpos($fname, '.')).".xlsm";
		}
		return($fname);
	}
	
}
?>