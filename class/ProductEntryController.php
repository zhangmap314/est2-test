<?php
/*
機種新規登録・更新機能クラス
*/
class ProductEntryController extends basecontroller{

	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}
	
	public function execute(){
		if( $this->session->get("user_lebel") <> USER_LEBEL_ADMIN){
			$this->result->add("ResultStatus","DenyAccess");
			return false;
		}
		
		switch($this->request->get("action")) {
			case "clearform":			//登録・更新画面
				$this->defaultdata(0);
				break;
			case "setdefault":			//登録・更新画面
				$this->defaultdata(1);
				break;
			case "insert":				//登録・更新画面
				if($this->checkproductdata()){
					$this->insertproduct();
				}
				$this->defaultdata(2);
				break;
			case "update":				//登録・更新画面
				if($this->checkproductdata()){
					$this->updateproduct();
				}
				$this->defaultdata(2);
				break;
		}
	}
	
	/**************************************************************
	機種データをセットする
	$setflg : 0:クリア；1:DBデータ；2:リクエストデータ
	**************************************************************/
	private function defaultdata($setflg){
		//メーカー名の取得
		$maker_name = Array();
		$strsql = "select maker_cd,
						  maker_name 
					from 
						mst_maker 
					order by sort_key ";
		$data = $this->db->selectdata($strsql);
		//DBよりデータをセット
		foreach($data as $key => $value){
			$maker_name[$key] = $value;
		}
		
		//製品種別の取得
		$prod_type_name = Array();
		$strsql = "select prod_type_cd,
						  prod_type_name 
					from 
						mst_prod_type 
					order by sort_key ";
		$data = $this->db->selectdata($strsql);
		
		//DBよりデータをセット
		foreach($data as $key => $value){
			$prod_type_name[$key] = $value;
		}
		
		$productdata = Array("maker_cd" => "",
							"maker_name" => $maker_name,
							"prod_type_cd" => "",
							"prod_type_name" => $prod_type_name,
							"color_kbn" => "",
							"product_name" => "",
							//"product_kbn" => "",
							"plug_off_flg" => "",
							"eco_off_flg" => "",
							"release_month_yy" => "",
							"release_month_mm" => "",
							"speed_kbn" => "",
							"power_flg" => "",
							"power_per_paper" => "",
							"eco_on_pwr_p_job" => "",
							"eco_off_pwr_p_job" => "",
							"eco_on_last_time" => "",
							"eco_off_last_time" => "",
							"eco_on_last_power" => "",
							"eco_off_last_power" => "",
							"sleep_power" => "",
							"eco_on_minadv" => "",
							"eco_on_maxadv" => "",
							"eco_off_minadv" => "",
							"eco_off_maxadv" => ""
							);
							
		if($setflg == 1){
			$strsql = "select 
							maker_cd,
							prod_type_cd,
							color_kbn,
							product_name,
							eco_off_flg,
							plug_off_flg,
							substr(release_month, 1, 4) as release_month_yy,
							to_number(substr(release_month, 5, 2),'99') as release_month_mm,
							speed_kbn,
							power_flg,
							power_per_paper,
							eco_on_pwr_p_job,
							eco_off_pwr_p_job,
							eco_on_last_time,
							eco_off_last_time,
							eco_on_last_power,
							eco_off_last_power,
							sleep_power ,
							eco_on_minadv,
							eco_on_maxadv,
							eco_off_minadv,
							eco_off_maxadv
						from 
							mst_product 
					 	where 
								seq_no = '" . common::sqlescape($this->request->get("seq_no")) . "'";
			$data = $this->db->selectdata($strsql);
			if(count($data) == 0){
				$this->result->add("JSAlert","更新対象データが存在しません。");
				$this->result->add("WindowClose",true);
				$this->result->add("productdata",$productdata);
				return false;
			}
			//DBよりデータをセット
			foreach($data[0] as $key => $value){
				$productdata[$key] = $value;
			}
		}elseif($setflg == 2){
			//リクエストよりデータをセット
			foreach($productdata as $key => $value){
				if(is_array($productdata[$key])==false){
					$productdata[$key] = $this->request->get($key);
				}
			}
		}
		$this->result->add("productdata",$productdata);
	}
	
	//登録データチェック
	private function checkproductdata(){
		$errmsg = "";
		//メーカー名チェック
		if($this->request->get("maker_cd") == ""){
			$errmsg .= "メーカー名を入力してください。<br>";
		}elseif(strlen($this->request->get("maker_cd")) > 2){
			$errmsg .= "メーカー名の桁数がオーバーしています。<br>";
		}
		//製品種別チェック
		if($this->request->get("prod_type_cd") == ""){
			$errmsg .= "製品種別を入力してください。<br>";
		}elseif(strlen($this->request->get("prod_type_cd")) > 1){
			$errmsg .= "製品種別の桁数がオーバーしています。<br>";
		}
		//カラー/モノクロチェック
		if($this->request->get("color_kbn") == ""){
			$errmsg .= "カラー/モノクロを入力してください。<br>";
		}elseif(strlen($this->request->get("color_kbn")) > 1){
			$errmsg .= "カラー/モノクロの桁数がオーバーしています。<br>";
		}
		//機種名チェック
		if($this->request->get("product_name") == ""){
			$errmsg .= "機種名を入力してください。<br>";
		}elseif(mb_strlen($this->request->get("product_name")) > 50){
			$errmsg .= "機種名の桁数がオーバーしています。<br>";
		}
		/*
		//機種区分チェック
		if($this->request->get("product_kbn") == ""){
			$errmsg .= "機種区分を入力してください。<br>";
		}elseif(strlen($this->request->get("product_kbn")) > 1){
			$errmsg .= "機種区分の桁数がオーバーしています。<br>";
		}
		*/
		//プラグOFF可チェック
		if($this->request->get("plug_off_flg") == ""){
			$errmsg .= "プラグOFF可を入力してください。<br>";
		}elseif(strlen($this->request->get("plug_off_flg")) > 1){
			$errmsg .= "プラグOFF可の桁数がオーバーしています。<br>";
		}
		//省エネOFF可チェック
		if($this->request->get("eco_off_flg") == ""){
			$errmsg .= "省エネOFF可を入力してください。<br>";
		}elseif(strlen($this->request->get("eco_off_flg")) > 1){
			$errmsg .= "省エネOFF可の桁数がオーバーしています。<br>";
		}
		//発売年月チェック
		$this->request->add("release_month_yy", common::zen2han($this->request->get("release_month_yy")));
		$this->request->add("release_month_mm", common::zen2han($this->request->get("release_month_mm")));
		//発売年月の年または月いずれかに入力がある場合はチェック
		if($this->request->get("release_month_yy") <> "" || $this->request->get("release_month_mm") <> ""){
			//発売年月（年）チェック
			if($this->request->get("release_month_yy") == ""){
				$errmsg .= "発売年月（年）を入力してください。<br>";
			}elseif(!is_numeric($this->request->get("release_month_yy"))){
				$errmsg .= "発売年月（年）を半角数字入力してください。<br>";
			}elseif( !preg_match("/^[0-9]{4}$/", $this->request->get("release_month_yy"))){
				$errmsg .= "発売年月（年）の桁数が誤っています。<br>";
			}
			//発売年月（月）チェック
			if($this->request->get("release_month_mm") == ""){
				$errmsg .= "発売年月（月）を入力してください。<br>";
			}elseif(!is_numeric($this->request->get("release_month_mm"))){
				$errmsg .= "発売年月（月）を半角数字入力してください。<br>";
			}elseif( !preg_match("/^[0-9]{1,2}$/", $this->request->get("release_month_mm"))){
				$errmsg .= "発売年月（月）の桁数が誤っています。<br>";
			}
			//発売年月関係でエラーが存在しない場合、発売年月整合性チェック
			if(!preg_match("/発売年月/", $errmsg)){
				if(checkdate($this->request->get("release_month_mm"), 1, $this->request->get("release_month_yy"))==false){
					$errmsg .= "発売年月の年月が不正です<br>";
				}
			}
		}
		//製品速度チェック
		if($this->request->get("speed_kbn") == ""){
			$errmsg .= "製品速度を入力してください。<br>";
		}elseif(strlen($this->request->get("speed_kbn")) > 1){
			$errmsg .= "製品速度の桁数がオーバーしています。<br>";
		}
		//推定スリープ電力の有無チェック
		if($this->request->get("power_flg") == ""){
			$errmsg .= "推定スリープ電力の有無を入力してください。<br>";
		}elseif(strlen($this->request->get("power_flg")) > 1){
			$errmsg .= "推定スリープ電力の有無の桁数がオーバーしています。<br>";
		}
		//ジョブ当たり面数チェック
		if($this->request->get("power_per_paper") == ""){
			$errmsg .= "ジョブ当たり面数を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("power_per_paper"))){
			$errmsg .= "ジョブ当たり面数を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,4}$/", $this->request->get("power_per_paper"))){
			$errmsg .= "ジョブ当たり面数の桁数がオーバーしています。<br>";
		}
		//省エネONジョブ単位電力量チェック
		$this->request->add("eco_on_pwr_p_job", common::zen2han($this->request->get("eco_on_pwr_p_job")));
		if($this->request->get("eco_on_pwr_p_job") == ""){
			$errmsg .= "省エネONジョブ単位電力量を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_on_pwr_p_job"))){
			$errmsg .= "省エネONジョブ単位電力量を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("eco_on_pwr_p_job")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("eco_on_pwr_p_job"))){
			$errmsg .= "省エネONジョブ単位電力量の桁数がオーバーしています。<br>";
		}
		//省エネOFFジョブ単位電力量チェック
		$this->request->add("eco_off_pwr_p_job", common::zen2han($this->request->get("eco_off_pwr_p_job")));
		if($this->request->get("eco_off_pwr_p_job") == ""){
			$errmsg .= "省エネOFFジョブ単位電力量を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_off_pwr_p_job"))){
			$errmsg .= "省エネOFFジョブ単位電力量を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("eco_off_pwr_p_job")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("eco_off_pwr_p_job"))){
			$errmsg .= "省エネOFFジョブ単位電力量の桁数がオーバーしています。<br>";
		}
		//省エネON週間最終時間チェック
		$this->request->add("eco_on_last_time", common::zen2han($this->request->get("eco_on_last_time")));
		if($this->request->get("eco_on_last_time") == ""){
			$errmsg .= "省エネON週間最終時間を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_on_last_time"))){
			$errmsg .= "省エネON週間最終時間を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("eco_on_last_time")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("eco_on_last_time"))){
			$errmsg .= "省エネON週間最終時間の桁数がオーバーしています。<br>";
		}
		//省エネOFF週間最終時間チェック
		$this->request->add("eco_off_last_time", common::zen2han($this->request->get("eco_off_last_time")));
		if($this->request->get("eco_off_last_time") == ""){
			$errmsg .= "省エネOFF週間最終時間を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_off_last_time"))){
			$errmsg .= "省エネOFF週間最終時間を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("eco_off_last_time")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("eco_off_last_time"))){
			$errmsg .= "省エネOFF週間最終時間の桁数がオーバーしています。<br>";
		}
		//省エネON週間最終電力量チェック
		$this->request->add("eco_on_last_power", common::zen2han($this->request->get("eco_on_last_power")));
		if($this->request->get("eco_on_last_power") == ""){
			$errmsg .= "省エネON週間最終電力量を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_on_last_power"))){
			$errmsg .= "省エネON週間最終電力量を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("eco_on_last_power")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("eco_on_last_power"))){
			$errmsg .= "省エネON週間最終電力量の桁数がオーバーしています。<br>";
		}
		//省エネOFF週間最終電力量チェック
		$this->request->add("eco_off_last_power", common::zen2han($this->request->get("eco_off_last_power")));
		if($this->request->get("eco_off_last_power") == ""){
			$errmsg .= "省エネOFF週間最終電力量を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_off_last_power"))){
			$errmsg .= "省エネOFF週間最終電力量を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("eco_off_last_power")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("eco_off_last_power"))){
			$errmsg .= "省エネOFF週間最終電力量の桁数がオーバーしています。<br>";
		}
		//スリープ電力チェック
		$this->request->add("sleep_power", common::zen2han($this->request->get("sleep_power")));
		if($this->request->get("sleep_power") == ""){
			$errmsg .= "スリープ電力を入力してください。<br>";
		}elseif(!is_numeric($this->request->get("sleep_power"))){
			$errmsg .= "スリープ電力を半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,5}$/", $this->request->get("sleep_power")) && 
				 !preg_match("/^[0-9]{1,5}[\.]{1}[0-9]{1,5}$/", $this->request->get("sleep_power"))){
			$errmsg .= "スリープ電力の桁数がオーバーしています。<br>";
		}
		
		
		
		
		
		//省エネON時最小ADVチェック
		if($this->request->get("eco_on_minadv") == ""){
			$errmsg .= "省エネON時最小ADVを入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_on_minadv"))){
			$errmsg .= "省エネON時最小ADVを半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,8}$/", $this->request->get("eco_on_minadv"))){
			$errmsg .= "省エネON時最小ADVの桁数がオーバーしています。<br>";
		}
		//省エネON時最大ADVチェック
		if($this->request->get("eco_on_maxadv") == ""){
			$errmsg .= "省エネON時最大ADVを入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_on_maxadv"))){
			$errmsg .= "省エネON時最大ADVを半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,8}$/", $this->request->get("eco_on_maxadv"))){
			$errmsg .= "省エネON時最大ADVの桁数がオーバーしています。<br>";
		}
		
		
		//省エネOFF時最小ADVチェック
		if($this->request->get("eco_off_minadv") == ""){
			$errmsg .= "省エネOFF時最小ADVを入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_off_minadv"))){
			$errmsg .= "省エネOFF時最小ADVを半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,8}$/", $this->request->get("eco_off_minadv"))){
			$errmsg .= "省エネOFF時最小ADVの桁数がオーバーしています。<br>";
		}
		//省エネOFF時最大ADVチェック
		if($this->request->get("eco_off_maxadv") == ""){
			$errmsg .= "省エネOFF時最大ADVを入力してください。<br>";
		}elseif(!is_numeric($this->request->get("eco_off_maxadv"))){
			$errmsg .= "省エネOFF時最大ADVを半角数字入力してください。<br>";
		}elseif( !preg_match("/^[0-9]{1,8}$/", $this->request->get("eco_off_maxadv"))){
			$errmsg .= "省エネOFF時最大ADVの桁数がオーバーしています。<br>";
		}
		
		
		
		
		//seq_noチェック
		if($this->request->get("action") == "update" && $this->request->get("seq_no") == ""){
			$errmsg .= "更新対象データが存在しません。<br>";
		}
		
		$this->result->add("errmsg", $errmsg);
		if($errmsg == ""){
			return true;
		}else{
			return false;
		}
	}
	
	private function insertproduct(){
		$this->db->begintrans();
		$strsql = "select 
						count(1) as cnt 
					from 
						mst_product 
					where 
						area_cd = '01' and 
						maker_cd = '" . common::sqlescape($this->request->get("maker_cd")) . "' and 
						prod_type_cd = '" . common::sqlescape($this->request->get("prod_type_cd")) . "' and 
						color_kbn = '" . common::sqlescape($this->request->get("color_kbn")) . "' and  
						product_name = '" . common::sqlescape($this->request->get("product_name")) . "'";
		$data = $this->db->selectdata($strsql);
		//同一プロダクトが既に存在する場合
		if($data[0]["cnt"] > 0){
			//既存プロダクトの削除
			$strsql = "delete 
						from 
							mst_product 
						where 
							area_cd = '01' and 
							maker_cd = '" . common::sqlescape($this->request->get("maker_cd")) . "' and 
							prod_type_cd = '" . common::sqlescape($this->request->get("prod_type_cd")) . "' and 
							color_kbn = '" . common::sqlescape($this->request->get("color_kbn")) . "' and  
							product_name = '" . common::sqlescape($this->request->get("product_name")) . "'";
			if(!$this->db->executesql($strsql, false)){
				$this->db->rollback();
				$err = $this->db->getlasterror();
				$err = "登録前削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
						"エラーメッセージ:" . $err["message"];
				$this->result->add("JSAlert", $err);
				return false;
			}
		}
		//登録データのセット
		if($this->request->get("release_month_yy") <> ""){
			$release_month = $this->request->get("release_month_yy");
			if($this->request->get("release_month_mm") < 10 && strlen($this->request->get("release_month_mm")) < 2){
				$release_month .= '0'. $this->request->get("release_month_mm");
			} else {
				$release_month .= $this->request->get("release_month_mm");
			}
		} else {
			$release_month = null;
		}
		$data = Array("area_cd" => "01",
					"maker_cd" => common::sqlescape($this->request->get("maker_cd")),
					"prod_type_cd" => common::sqlescape($this->request->get("prod_type_cd")),
					"color_kbn" => common::sqlescape($this->request->get("color_kbn")),
					//"product_kbn" => common::sqlescape($this->request->get("product_kbn")),
					"product_name" => common::sqlescape($this->request->get("product_name")),
					"eco_off_flg" => common::sqlescape($this->request->get("eco_off_flg")),
					"plug_off_flg" => common::sqlescape($this->request->get("plug_off_flg")),
					"release_month" => common::sqlescape($release_month),
					"speed_kbn" => common::sqlescape($this->request->get("speed_kbn")),
					"power_flg" => common::sqlescape($this->request->get("power_flg")),
					"power_per_paper" => common::sqlescape($this->request->get("power_per_paper")),
					"eco_on_pwr_p_job" => common::sqlescape($this->request->get("eco_on_pwr_p_job")),
					"eco_off_pwr_p_job" => common::sqlescape($this->request->get("eco_off_pwr_p_job")),
					"eco_on_last_time" => common::sqlescape($this->request->get("eco_on_last_time")),
					"eco_off_last_time" => common::sqlescape($this->request->get("eco_off_last_time")),
					"eco_on_last_power" => common::sqlescape($this->request->get("eco_on_last_power")),
					"eco_off_last_power" => common::sqlescape($this->request->get("eco_off_last_power")),
					"sleep_power" => common::sqlescape($this->request->get("sleep_power")),
					"regist_time" => date("Y/m/d H:i:s"),
					"update_time" => date("Y/m/d H:i:s"),
					"update_uid" => common::sqlescape($this->session->get("user_id")),
					"eco_on_minadv" => common::sqlescape($this->request->get("eco_on_minadv")),
					"eco_on_maxadv" => common::sqlescape($this->request->get("eco_on_maxadv")),
					"eco_off_minadv" => common::sqlescape($this->request->get("eco_off_minadv")),
					"eco_off_maxadv" => common::sqlescape($this->request->get("eco_off_maxadv"))
					);
		//登録処理の実行
		if($this->db->insert("mst_product", $data, false)){
			$this->db->commit();
			$this->accesslog("機種メンテナンス", "登録");
			$this->result->add("JSAlert","登録しました。");
			$this->result->add("WindowClose",true);
			return true;
		}else{
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "登録エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			return false;
		}
	}
	
	private function updateproduct(){
		//更新データ存在チェック
		$strsql = "select 
						count(1) as cnt 
					from 
						mst_product 
					where 
						seq_no = '" . common::sqlescape($this->request->get("seq_no")) . "'";
		$data = $this->db->selectdata($strsql);
		if($data[0]["cnt"] == 0){
			$this->result->add("JSAlert","更新対象データが存在しません。\\n他管理者に削除された可能性があります。");
			return false;
		}
		
		$this->db->begintrans();
		
		//同一データ存在チェック
		$strsql = "select 
						count(1) as cnt 
					from 
						mst_product 
					where 
						seq_no <> '" . common::sqlescape($this->request->get("seq_no")) . "' and 
						area_cd = '01' and 
						maker_cd = '" . common::sqlescape($this->request->get("maker_cd")) . "' and 
						prod_type_cd = '" . common::sqlescape($this->request->get("prod_type_cd")) . "' and 
						color_kbn = '" . common::sqlescape($this->request->get("color_kbn")) . "' and 
						product_name = '" . common::sqlescape($this->request->get("product_name")) . "'";
		$data = $this->db->selectdata($strsql);
		//同一プロダクトが既に存在する場合
		if($data[0]["cnt"] > 0){
			//既存プロダクトの削除
			$strsql = "delete 
						from 
							mst_product 
						where 
							seq_no <> '" . common::sqlescape($this->request->get("seq_no")) . "' and 
							area_cd = '01' and 
							maker_cd = '" . common::sqlescape($this->request->get("maker_cd")) . "' and 
							prod_type_cd = '" . common::sqlescape($this->request->get("prod_type_cd")) . "' and 
							color_kbn = '" . common::sqlescape($this->request->get("color_kbn")) . "' and  
							product_name = '" . common::sqlescape($this->request->get("product_name")) . "'";
			if(!$this->db->executesql($strsql, false)){
				$this->db->rollback();
				$err = $this->db->getlasterror();
				$err = "更新前削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
						"エラーメッセージ:" . $err["message"];
				$this->result->add("JSAlert", $err);
				return false;
			}
		}
		//更新データのセット
		if($this->request->get("release_month_yy") <> ""){
			$release_month = $this->request->get("release_month_yy");
			if($this->request->get("release_month_mm") < 10 && strlen($this->request->get("release_month_mm")) < 2){
				$release_month .= '0'. $this->request->get("release_month_mm");
			} else {
				$release_month .= $this->request->get("release_month_mm");
			}
		} else {
			$release_month = null;
		}
		$data = Array("area_cd" => "01",
					"maker_cd" => common::sqlescape($this->request->get("maker_cd")),
					"prod_type_cd" => common::sqlescape($this->request->get("prod_type_cd")),
					"color_kbn" => common::sqlescape($this->request->get("color_kbn")),
					//"product_kbn" => common::sqlescape($this->request->get("product_kbn")),
					"product_name" => common::sqlescape($this->request->get("product_name")),
					"eco_off_flg" => common::sqlescape($this->request->get("eco_off_flg")),
					"plug_off_flg" => common::sqlescape($this->request->get("plug_off_flg")),
					"release_month" => common::sqlescape($release_month),
					"speed_kbn" => common::sqlescape($this->request->get("speed_kbn")),
					"power_flg" => common::sqlescape($this->request->get("power_flg")),
					"power_per_paper" => common::sqlescape($this->request->get("power_per_paper")),
					"eco_on_pwr_p_job" => common::sqlescape($this->request->get("eco_on_pwr_p_job")),
					"eco_off_pwr_p_job" => common::sqlescape($this->request->get("eco_off_pwr_p_job")),
					"eco_on_last_time" => common::sqlescape($this->request->get("eco_on_last_time")),
					"eco_off_last_time" => common::sqlescape($this->request->get("eco_off_last_time")),
					"eco_on_last_power" => common::sqlescape($this->request->get("eco_on_last_power")),
					"eco_off_last_power" => common::sqlescape($this->request->get("eco_off_last_power")),
					"sleep_power" => common::sqlescape($this->request->get("sleep_power")),
					"update_time" => date("Y/m/d H:i:s"),
					"update_uid" => common::sqlescape($this->session->get("user_id")),
					"eco_on_minadv" => common::sqlescape($this->request->get("eco_on_minadv")),
					"eco_on_maxadv" => common::sqlescape($this->request->get("eco_on_maxadv")),
					"eco_off_minadv" => common::sqlescape($this->request->get("eco_off_minadv")),
					"eco_off_maxadv" => common::sqlescape($this->request->get("eco_off_maxadv"))
					);
		
		$cond = " seq_no ='" . common::sqlescape($this->request->get("seq_no")) . "' ";

		//登録処理の実行
		if($this->db->update("mst_product", $data, $cond, false)){
			$this->db->commit();
			$this->accesslog("機種メンテナンス", "更新");
			$this->result->add("JSAlert","更新しました。");
			$this->result->add("WindowClose",true);
			return true;
		}else{
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "更新エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			return false;
		}
	}
}
?>