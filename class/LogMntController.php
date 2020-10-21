<?php
/*
ログ管理機能クラス
*/
class LogMntController extends basecontroller{
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}
	
	public function execute(){
		//ログ閲覧者(user_lebel="5")または管理者(user_lebel="9")以外は接続を拒否する
		if($this->session->get("user_lebel") <> USER_LEBEL_ADMIN and
		   $this->session->get("user_lebel") <> USER_LEBEL_LOGGER){
			$this->result->add("ResultStatus","DenyAccess");
			return false;
		}
		//actionに処理が指定されている場合は、その処理を行う
		switch($this->request->get("action")) {
			case "delete":
				if($this->datecheck()){
					if($this->session->get("user_lebel") == USER_LEBEL_ADMIN){
						//システム仕様ログにチェックが入っている場合
						if($this->request->get("target_log") == "0"){
							$this->deleteSysLog();
						} elseif($this->request->get("target_log") == "1"){
							$this->deleteDlLog();
						} elseif($this->request->get("target_log") == "2"){
							$this->deleteAccessLog();
						}
					}
				}
				$this->getyearlist();
				break;
			case "download":
				if($this->datecheck()){
					//管理者かつログダウンロード履歴にチェックが入っている場合
					if($this->session->get("user_lebel") == USER_LEBEL_ADMIN and
					         $this->request->get("target_log") == "1"){
						if(!$this->downloadDlLog()){
							$this->getyearlist();
							$this->request->add("action","");
						} else
							$this->accesslog("ログ管理", "ダウンロード履歴のダウンロード");

					//システム仕様ログにチェックが入っている場合
					} elseif ($this->request->get("target_log") == "2") {
						if(!$this->downloadAccessLog()){
							$this->getyearlist();
							$this->request->add("action","");
						} else
							$this->accesslog("ログ管理", "アクセスログのダウンロード");

					//システム仕様ログにチェックが入っている場合
					} else {
						if(!$this->downloadSysLog()){
							$this->getyearlist();
							$this->request->add("action","");
						} else
							$this->accesslog("ログ管理", "システムログのダウンロード");
					}
				}else{
					$this->getyearlist();
					$this->request->add("action","");
				}
				break;
			default:
				$this->getyearlist();
		}
	}
	
	
	private function getyearlist(){
		//システム使用ログ用の年リスト取得
		$strsql = "select 
						distinct to_char(login_time,'yyyy') as log_year 
					from 
						tbl_login_log 
					order by 
						log_year desc";

		$data = $this->db->selectdata($strsql);
		if(count($data) == 0){
			$this->result->add("YEARLIST", false);
		}else{
			$this->result->add("YEARLIST", $data);
		}
		
		//ログダウンロード履歴用の年リスト取得
		$strsql_dl ="select 
						distinct to_char(download_time,'yyyy') as dl_year 
					from 
						tbl_download_hist 
					order by 
						dl_year desc";

		$dldata = $this->db->selectdata($strsql_dl);
		if(count($dldata) == 0){
			$this->result->add("DLYEARLIST", false);
		}else{
			$this->result->add("DLYEARLIST", $dldata);
		}

		//アクセスログ用の年リスト取得
		$strsql_acc ="select 
						distinct to_char(access_time,'yyyy') as log_year 
					from 
						tbl_access_log 
					order by 
						log_year desc";

		$accdata = $this->db->selectdata($strsql_acc);
		if(count($accdata) == 0){
			$this->result->add("ACCYEARLIST", false);
		}else{
			$this->result->add("ACCYEARLIST", $accdata);
		}
	}
	
	private function downloadSysLog(){
		//編集用日付の作成
		$from_ymd = $this->request->get("fromyear") . "/" . 
					$this->request->get("frommonth") . "/" .
					$this->request->get("fromday");
		$to_ymd = $this->request->get("toyear") . "/" . 
					$this->request->get("tomonth") . "/" .
					$this->request->get("today");
		$data = Array("user_id" => common::sqlescape($this->session->get("user_id")),
					"unique_id" => common::sqlescape($this->session->get("user_unique_id")),
					"log_from" => common::sqlescape($from_ymd),
					"log_to" => common::sqlescape($to_ymd));
		if(!$this->db->insert("tbl_download_hist", $data)){
			$err = $this->db->getlasterror();
			$err = "登録エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			return false;
		}
		//ファイル取得用SQLを作成
		$strsql = "	 select  ";
		$strsql .= "		action.save_no as save_no , ";										//連番(アクション用連番)
		$strsql .= "		log.user_id,  ";													//ユーザID
		$strsql .= "		log.user_name,  ";													//ユーザ名
		$strsql .= "		log.corp_name,  ";													//会社名
		$strsql .= "		log.shozoku_name,  ";												//所属名
		$strsql .= "		caller.caller_name,  ";												//呼出し元
		$strsql .= "		to_char(log.login_time,'yyyy/mm/dd hh24:mi:ss') as login_time,  ";	//ログイン日時
		$strsql .= "		action.cust_cd,  ";													//企業コード
		$strsql .= "		action.cust_name,  ";												//お客様名
		$strsql .= "		case  ";
		$strsql .= "			when action.action_kbn = '0' then '提案書テンプレートデータ'  ";
		$strsql .= "			when action.action_kbn = '1' then 'エクセルファイル'  ";
		$strsql .= "			when action.action_kbn = '2' then '印刷'  ";
		$strsql .= "		end as action_kbn,  ";												//アクション区分
		$strsql .= "		action.sim_years,  ";												//シミュレーション年数
		$strsql .= "		prd.crnt_maker_name,  ";											//使用機種メーカー名
		$strsql .= "		prd.crnt_product_name,  ";											//使用機種名
		$strsql .= "		prd.crnt_prod_type_name,  ";										//使用機製品種別
		$strsql .= "		case  ";
		$strsql .= "			when prd.crnt_color_kbn = '0' then 'モノクロ'  ";
		$strsql .= "			when prd.crnt_color_kbn = '1' then 'カラー'  ";
		$strsql .= "		end as crnt_color_kbn,  ";											//使用機モノクロ/カラー
		$strsql .= "		prd.crnt_printer_cnt,  ";											//使用機プリンター台数
		$strsql .= "		prd.crnt_adv,  ";													//使用機月の使用紙枚数
		$strsql .= "		case  ";
		$strsql .= "			when prd.crnt_eco_mode = '0' then 'OFF'   ";
		$strsql .= "			when prd.crnt_eco_mode = '1' then 'ON'  ";
		$strsql .= "		end as crnt_eco_mode,  ";											//使用機省エネフラグ
		$strsql .= "		case  ";
		$strsql .= "			when prd.crnt_power_mode = '0' then 'OUT'   ";
		$strsql .= "			when prd.crnt_power_mode = '1' then 'IN'  ";
		$strsql .= "		end as crnt_power_mode,  ";											//使用機プラグIN/OUT
		$strsql .= "		prd.crnt_denryoku,  ";												//使用機消費電力
		$strsql .= "		prd.crnt_co2,  ";													//使用機CO2排出量
		$strsql .= "		prd.new_product_name,  ";											//提案機種名
		$strsql .= "		prd.new_prod_type_name,  ";											//提案機製品種別
		$strsql .= "		case  ";
		$strsql .= "			when prd.new_color_kbn = '0' then 'モノクロ'  ";
		$strsql .= "			when prd.new_color_kbn = '1' then 'カラー'  ";
		$strsql .= "		end as new_color_kbn,  ";											//提案機モノクロ/カラー
		$strsql .= "		prd.new_printer_cnt,  ";											//提案機プリンタ台数
		$strsql .= "		prd.new_adv,  ";													//提案機月の使用紙枚数
		$strsql .= "		case  ";
		$strsql .= "			when prd.new_eco_mode = '0' then 'OFF'   ";
		$strsql .= "			when prd.new_eco_mode = '1' then 'ON'  ";
		$strsql .= "		end as new_eco_mode,  ";											//提案機省エネフラグ
		$strsql .= "		case  ";
		$strsql .= "			when prd.new_power_mode = '0' then 'OUT'   ";
		$strsql .= "			when prd.new_power_mode = '1' then 'IN'  ";
		$strsql .= "		end as new_power_mode,  ";											//提案機プラグIN/OUT
		$strsql .= "		prd.new_denryoku, ";												//提案機消費電力
		$strsql .= "		prd.new_co2,  ";													//提案機CO2排出量
		$strsql .= "		action.crnt_denryoku_ttl,  ";										//使用機種合計消費電力
		$strsql .= "		action.crnt_co2_ttl,  ";											//使用機種合計CO2排出量
		$strsql .= "		action.crnt_denkiryo_ttl,  ";										//使用機種合計電気料金
		$strsql .= "		action.new_denryoku_ttl,  ";										//提案機種合計消費電力
		$strsql .= "		action.new_co2_ttl,  ";												//提案機種合計CO2排出量
		$strsql .= "		action.new_denkiryo_ttl,  ";										//提案機種合計電気料金
		$strsql .= "		action.denryoku_ttl,  ";											//消費電力削減効果
		$strsql .= "		action.co2_ttl,  ";													//CO2削減効果
		$strsql .= "		action.denkiryo_ttl,  ";											//電気料金削減効果
		$strsql .= "		action.tree_ttl,  ";												//立木換算本数
		$strsql .= "		prd.sequence_no ";													//枝番(ソート用)
		$strsql .= "	from ";
		$strsql .= "		tbl_login_log log  ";
		$strsql .= "	left join  ";
		$strsql .= "		mst_caller caller  ";
		$strsql .= "	on  ";
		$strsql .= "		log.caller_id = caller.caller_id  ";
		$strsql .= "	inner join  ";
		$strsql .= "		tbl_action_log action ";
		$strsql .= "	on  ";
		$strsql .= "		log.unique_id = action.unique_id ";
		$strsql .= "	left join  ";
		$strsql .= "		tbl_prd_log prd ";
		$strsql .= "	on ";
		$strsql .= "		action.save_no = prd.save_no  ";

		$wheresql = "where log.login_time between '" .
					$this->request->get("fromyear") . "/" . 
					$this->request->get("frommonth") . "/" .
					$this->request->get("fromday") . " 00:00:00' and '" .
					$this->request->get("toyear") . "/" .
					$this->request->get("tomonth") . "/" .
					$this->request->get("today") . " 23:59:59' ";
					
		//ユーザレベルが管理者以外の場合、条件に会社コードを追加
		//if($this->session->get("user_lebel") == USER_LEBEL_LOGGER){
		if($this->session->get("user_lebel") != USER_LEBEL_ADMIN){
			$wheresql .= " and log.corp_name = '" . $this->session->get("corp_name") . "' ";
		}
		
		$strsql .= $wheresql;
		
		$strsql .= " union select 
						log.save_no as save_no, 
						log.user_id, 
						log.user_name, 
						log.corp_name, 
						log.shozoku_name, 
						caller.caller_name, 
						to_char(log.login_time,'yyyy/mm/dd hh24:mi:ss') as login_time, 

						NULL as cust_cd, 
						NULL as cust_name, 
						'ログイン' as action_kbn, 
						NULL as  sim_years, 
						
						NULL as crnt_maker_name, 
						NULL as crnt_product_name, 
						NULL as crnt_prod_type_name, 
						NULL as crnt_color_kbn, 
						NULL as crnt_printer_cnt, 
						NULL as crnt_adv, 
						NULL as crnt_eco_mode, 
						NULL as crnt_power_mode, 
						NULL as crnt_denryoku, 
						NULL as crnt_co2, 
						
						NULL as new_product_name, 
						NULL as new_prod_type_name, 
						NULL as new_color_kbn, 
						NULL as new_printer_cnt, 
						NULL as new_adv, 
						NULL as new_eco_mode, 
						NULL as new_power_mode, 
						NULL as new_denryoku, 
						NULL as new_co2, 
						
						NULL as crnt_denryoku_ttl, 
						NULL as crnt_co2_ttl, 
						NULL as crnt_denkiryo_ttl, 
						NULL as new_denryoku_ttl, 
						NULL as new_co2_ttl, 
						NULL as new_denkiryo_ttl, 
						NULL as denryoku_ttl, 
						NULL as co2_ttl, 
						NULL as denkiryo_ttl, 
						NULL as tree_ttl,
						0 as sequence_no 
						
					from
						tbl_login_log log 
					left join 
						mst_caller caller 
					on 
						log.caller_id = caller.caller_id 
					";


		$strsql .= $wheresql;
		
		$strsql .= " order by
						save_no asc , sequence_no asc ";

		$data = $this->db->selectdata($strsql);

		if(count($data) == 0 ){
			$this->result->add("JSAlert", "対象期間内のデータがありません。");
			return false;
		}
		
		$fname ="SysLog".
				$this->request->get("fromyear") .
				$this->request->get("frommonth") . $this->request->get("fromday") . "_" .
				$this->request->get("toyear") . $this->request->get("tomonth") .
				$this->request->get("today") . ".csv";
		
		/*ヘッダを生成する*/
		$contents  = "\"SaveNO\"";
		$contents .= ",\"ユーザID\"";
		$contents .= ",\"ユーザ名\"";
		$contents .= ",\"会社名\"";
		$contents .= ",\"所属\"";
		$contents .= ",\"呼出し元\"";
		$contents .= ",\"ログイン日時\"";
		$contents .= ",\"企業コード\"";
		$contents .= ",\"お客様名\"";
		$contents .= ",\"操作名\"";
		$contents .= ",\"シミュレーション年数\"";
		
		$prd_culmun = array("メーカー名", 
							"使用機種名", 
							"使用機種製品種別", 
							"使用機種モノクロ/カラー", 
							"台数", 
							"ADV", 
							"省エネON/OFF", 
							"プラグイン/アウト", 
							"消費電", 
							"CO2排出量", 
							"提案機種名", 
							"提案機種製品種別", 
							"提案機種モノクロ/カラー", 
							"台数", 
							"ADV", 
							"省エネON/OFF１", 
							"プラグイン/アウト", 
							"消費電力", 
							"CO2排出量"
					);
		for ($i = 1; $i <= $this->simulation_max_rows; $i++) 
		{
			for ($j = 0; $j < count($prd_culmun); $j++) {
				$contents .= ",\"" . $prd_culmun[$j] . mb_convert_kana($i,"N" ,"UTF-8") . "\"" ;
			}
		}
		
		$contents .= ",\"使用機合計消費電力量\"";
		$contents .= ",\"使用機合計CO2排出量\"";
		$contents .= ",\"使用機合計電気料金\"";
		$contents .= ",\"提案機合計消費電力量\"";
		$contents .= ",\"提案機合計CO2排出量\"";
		$contents .= ",\"提案機合計電気料金\"";
		$contents .= ",\"削減消費電力量\"";
		$contents .= ",\"削減CO2排出量\"";
		$contents .= ",\"削減電気料金\"";
		$contents .= ",\"削減立木本数\"";
		
		$contents .= "\r\n";
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Type: application/x-csv");
		header("Content-Disposition: attachment; filename=\"" . $fname . "\"");
		
		$contents = mb_convert_encoding($contents, "SJIS-win", "UTF-8");
		print $contents;
                $contents = "";
		$c = 0;
		while ($c < count($data)) 
		{
			$contents = "\"" . $data[$c]["save_no"] . "\",";							//Save_No
			$contents .= "\"" . $data[$c]["user_id"] . "\",";							//ユーザID
			$contents .= "\"" . $data[$c]["user_name"] . "\",";							//ユーザ名
			$contents .= "\"" . $data[$c]["corp_name"] . "\",";							//会社名
			$contents .= "\"" . $data[$c]["shozoku_name"] . "\",";						//所属名
			$contents .= "\"" . $data[$c]["caller_name"] . "\",";						//呼出し元
			$contents .= "\"" . $data[$c]["login_time"] . "\",";						//ログイン日時
			$contents .= "\"" . $data[$c]["cust_cd"] . "\",";							//企業コード
			$contents .= "\"" . $data[$c]["cust_name"] . "\",";							//お客様名
			$contents .= "\"" . $data[$c]["action_kbn"] . "\",";						//アクション区分
			$contents .= "\"" . $data[$c]["sim_years"] . "\",";							//シミュレーション年数
			
			$comp_save_no = $data[$c]["save_no"];
			
			//機種データ内容を20回分繰り返しデータ生成を行う
			for($d = 0; $d < $this->simulation_max_rows; $d++)
			{
				if($c < count($data) && $comp_save_no == $data[$c]["save_no"])
				{
					$contents .= "\"" . $data[$c]["crnt_maker_name"] . "\",";			//使用機種メーカー名
					$contents .= "\"" . $data[$c]["crnt_product_name"] . "\",";			//使用機種名
					$contents .= "\"" . $data[$c]["crnt_prod_type_name"] . "\",";		//使用機製品種別
					$contents .= "\"" . $data[$c]["crnt_color_kbn"] . "\",";			//使用機モノクロ/カラー
					$contents .= "\"" . $data[$c]["crnt_printer_cnt"] . "\",";			//使用機プリンター台数
					$contents .= "\"" . $data[$c]["crnt_adv"] . "\",";					//使用機月の使用紙枚数
					$contents .= "\"" . $data[$c]["crnt_eco_mode"] . "\",";				//使用機省エネフラグ
					$contents .= "\"" . $data[$c]["crnt_power_mode"] . "\",";			//使用機プラグIN/OUT
					$contents .= "\"" . $data[$c]["crnt_denryoku"] . "\",";				//使用機消費電力
					$contents .= "\"" . $data[$c]["crnt_co2"] . "\",";					//使用機CO2排出量
					$contents .= "\"" . $data[$c]["new_product_name"] . "\",";			//提案機種名
					$contents .= "\"" . $data[$c]["new_prod_type_name"] . "\",";		//提案機製品種別
					$contents .= "\"" . $data[$c]["new_color_kbn"] . "\",";				//提案機モノクロ/カラー
					$contents .= "\"" . $data[$c]["new_printer_cnt"] . "\",";			//提案機プリンタ台数
					$contents .= "\"" . $data[$c]["new_adv"] . "\",";					//提案機月の使用紙枚数
					$contents .= "\"" . $data[$c]["new_eco_mode"] . "\",";				//提案機省エネフラグ
					$contents .= "\"" . $data[$c]["new_power_mode"] . "\",";			//提案機プラグIN/OUT
					$contents .= "\"" . $data[$c]["new_denryoku"] . "\",";				//提案機消費電力
					$contents .= "\"" . $data[$c]["new_co2"] . "\",";					//提案機CO2排出量
					$c++;
					
				}
				else
				{
					//次レコードの連番が異なる場合
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					$contents .= "\"\",";
					
				}
			}
			
			$c--;	//前レコードを参照するために、一時的にカウント変数を戻す。
			$contents .= "\"" . $data[$c]["crnt_denryoku_ttl"] . "\",";					//使用機合計消費電力量
			$contents .= "\"" . $data[$c]["crnt_co2_ttl"] . "\",";						//使用機合計CO2排出量
			$contents .= "\"" . $data[$c]["crnt_denkiryo_ttl"] . "\",";					//使用機合計電気料金
			$contents .= "\"" . $data[$c]["new_denryoku_ttl"] . "\",";					//提案機合計消費電力量
			$contents .= "\"" . $data[$c]["new_co2_ttl"] . "\",";						//提案機合計CO2排出量
			$contents .= "\"" . $data[$c]["new_denkiryo_ttl"] . "\",";					//提案機合計電気料金
			$contents .= "\"" . $data[$c]["denryoku_ttl"] . "\",";						//削減消費電力量
			$contents .= "\"" . $data[$c]["co2_ttl"] . "\",";							//削減CO2排出量
			$contents .= "\"" . $data[$c]["denkiryo_ttl"] . "\",";						//削減電気料金
			$contents .= "\"" . $data[$c]["tree_ttl"] . "\"";							//削減立木本数
			$contents .= "\r\n";
			$c++;

		        $contents = mb_convert_encoding($contents, "SJIS-win", "UTF-8");
		        print $contents;
		}
		
		return true;
	}
	
	private function downloadDlLog(){
		$strsql = "select 
					dlhist.user_id as user_id,
					log.corp_cd as corp_cd,
					log.corp_name as corp_name,
					log.shozoku_cd as shozoku_cd,
					log.shozoku_name as shozoku_name,
					to_char(dlhist.log_from,'yyyy/mm/dd') as log_from,
					to_char(dlhist.log_to,'yyyy/mm/dd') as log_to,
					to_char(dlhist.download_time,'yyyy/mm/dd hh24:mi:ss') as download_time
					from 
						tbl_download_hist dlhist 
					left join 
						tbl_login_log log 
					on 
						dlhist.unique_id = log.unique_id ";
		$wheresql = "where dlhist.download_time between '" .
					$this->request->get("fromyear") . "/" . 
					$this->request->get("frommonth") . "/" .
					$this->request->get("fromday") . " 00:00:00' and '" .
					$this->request->get("toyear") . "/" .
					$this->request->get("tomonth") . "/" .
					$this->request->get("today") . " 23:59:59' ";

		$strsql .= $wheresql;
		$strsql .= " order by 
						dlhist.serial_no asc ";

		$data = $this->db->selectdata($strsql);

		if(count($data) == 0 ){
			$this->result->add("JSAlert", "対象期間内のデータがありません。");
			return false;
		}
		
		$fname ="DownloadHist".
				$this->request->get("fromyear") .
				$this->request->get("frommonth") . $this->request->get("fromday") . "_" .
				$this->request->get("toyear") . $this->request->get("tomonth") .
				$this->request->get("today") . ".csv";
		
		/*ヘッダを生成する*/
		$contents  = "\"ユーザID\"";
		$contents .= ",\"会社コード\"";
		$contents .= ",\"会社名\"";
		$contents .= ",\"所属コード\"";
		$contents .= ",\"所属名\"";
		$contents .= ",\"ダウンロードログ期間FROM\"";
		$contents .= ",\"ダウンロードログ期間TO\"";
		$contents .= ",\"ダウンロード日時\"";
		
		$contents .= "\r\n";
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Type: application/x-csv");
		header("Content-Disposition: attachment; filename=\"" . $fname . "\"");
		
		$contents = mb_convert_encoding($contents, "SJIS-win", "UTF-8");
		print $contents;

		$c = 0;
		while ($c < count($data)) {
			$contents = "\"" . $data[$c]["user_id"] . "\",";
			$contents .= "\"" . $data[$c]["corp_cd"] . "\",";
			$contents .= "\"" . $data[$c]["corp_name"] . "\",";
			$contents .= "\"" . $data[$c]["shozoku_cd"] . "\",";
			$contents .= "\"" . $data[$c]["shozoku_name"] . "\",";
			$contents .= "\"" . $data[$c]["log_from"] . "\",";
			$contents .= "\"" . $data[$c]["log_to"] . "\",";
			$contents .= "\"" . $data[$c]["download_time"] . "\"";
			
			$contents .= "\r\n";
			$c++;

		        $contents = mb_convert_encoding($contents, "SJIS-win", "UTF-8");
        		print $contents;
		}
		
		return true;
	}

	private function downloadAccessLog(){
		$strsql = "select 
					alog.save_no as save_no,
					alog.user_id as user_id,
					alog.user_name, 
					alog.corp_cd as corp_cd,
					alog.corp_name as corp_name,
					alog.shozoku_cd as shozoku_cd,
					alog.shozoku_name as shozoku_name,
					alog.menu_name as menu_name,
					alog.button_name as button_name,
					to_char(alog.access_time,'yyyy/mm/dd hh24:mi:ss') as access_time
					from 
						tbl_access_log alog ";
		$wheresql = "where alog.access_time between '" .
					$this->request->get("fromyear") . "/" . 
					$this->request->get("frommonth") . "/" .
					$this->request->get("fromday") . " 00:00:00' and '" .
					$this->request->get("toyear") . "/" .
					$this->request->get("tomonth") . "/" .
					$this->request->get("today") . " 23:59:59' ";
		
		if($this->session->get("user_lebel") != USER_LEBEL_ADMIN){
			$wheresql .= " and alog.corp_name = '" . $this->session->get("corp_name") . "' ";
		}
		
		$strsql .= $wheresql;

		$strsql .= " union select 
					alog.save_no as save_no,
					alog.user_id as user_id,
					alog.user_name, 
					alog.corp_cd as corp_cd,
					alog.corp_name as corp_name,
					alog.shozoku_cd as shozoku_cd,
					alog.shozoku_name as shozoku_name,
					'ログイン' as menu_name,
					null as button_name,
					to_char(alog.login_time,'yyyy/mm/dd hh24:mi:ss') as access_time
					from tbl_login_log alog ";
		$wheresql = "where alog.login_time between '" .
					$this->request->get("fromyear") . "/" . 
					$this->request->get("frommonth") . "/" .
					$this->request->get("fromday") . " 00:00:00' and '" .
					$this->request->get("toyear") . "/" .
					$this->request->get("tomonth") . "/" .
					$this->request->get("today") . " 23:59:59' ";
		
		if($this->session->get("user_lebel") != USER_LEBEL_ADMIN){
			$wheresql .= " and alog.corp_name = '" . $this->session->get("corp_name") . "' ";
		}
		
		$strsql .= $wheresql;

		$strsql .= " order by save_no asc ";

		$data = $this->db->selectdata($strsql);
		if(count($data) == 0 ){
			$this->result->add("JSAlert", "対象期間内のデータがありません。");
			return false;
		}
		
		$fname ="Accesslog".
				$this->request->get("fromyear") .
				$this->request->get("frommonth") . $this->request->get("fromday") . "_" .
				$this->request->get("toyear") . $this->request->get("tomonth") .
				$this->request->get("today") . ".csv";
		
		/*ヘッダを生成する*/
		$contents  = "\"SaveNO\"";
		$contents .= ",\"ユーザID\"";
		$contents .= ",\"ユーザ名\"";
		$contents .= ",\"会社コード\"";
		$contents .= ",\"会社名\"";
		$contents .= ",\"所属コード\"";
		$contents .= ",\"所属名\"";
		$contents .= ",\"メニュー名\"";
		$contents .= ",\"ボタン名\"";
		$contents .= ",\"アクセス日時\"";
		
		$contents .= "\r\n";
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Type: application/x-csv");
		header("Content-Disposition: attachment; filename=\"" . $fname . "\"");
		
		$contents = mb_convert_encoding($contents, "SJIS-win", "UTF-8");
		print $contents;

		$c = 0;
		while ($c < count($data)) {
			$contents = "\"" . $data[$c]["save_no"] . "\",";
			$contents .= "\"" . $data[$c]["user_id"] . "\",";
			$contents .= "\"" . $data[$c]["user_name"] . "\",";
			$contents .= "\"" . $data[$c]["corp_cd"] . "\",";
			$contents .= "\"" . $data[$c]["corp_name"] . "\",";
			$contents .= "\"" . $data[$c]["shozoku_cd"] . "\",";
			$contents .= "\"" . $data[$c]["shozoku_name"] . "\",";
			$contents .= "\"" . $data[$c]["menu_name"] . "\",";
			$contents .= "\"" . $data[$c]["button_name"] . "\",";
			$contents .= "\"" . $data[$c]["access_time"] . "\"";
			
			$contents .= "\r\n";
			$c++;

		        $contents = mb_convert_encoding($contents, "SJIS-win", "UTF-8");
        		print $contents;
		}
		
		return true;
	}

	private function getfieldval($ar, $fieldname){
		if(!isset($ar[$fieldname])){
			return "";
		}elseif(is_null($ar[$fieldname])){
			return "";
		}else{
			return $ar[$fieldname];
		}
	}

	
	//指定された日付のチェックを行う
	private function datecheck(){
		$errmsg = "";
		if(!is_numeric($this->request->get("fromyear")) || 
			!is_numeric($this->request->get("frommonth")) || 
			!is_numeric($this->request->get("fromday"))){
			$errmsg .= "期間開始日付が不正です。<br>";
		}elseif(!checkdate($this->request->get("frommonth"), 
						$this->request->get("fromday"), 
						$this->request->get("fromyear"))){
			$errmsg .= "期間開始日付が不正です。<br>";
		}
		
		if(!is_numeric($this->request->get("toyear")) || 
			!is_numeric($this->request->get("tomonth")) || 
			!is_numeric($this->request->get("today"))){
			$errmsg .= "期間終了日付が不正です。<br>";
		}elseif(!checkdate($this->request->get("tomonth"), 
						$this->request->get("today"), 
						$this->request->get("toyear"))){
			$errmsg .= "期間終了日付が不正です。<br>";
		}
		
		if($errmsg == ""){
			$frTime = mktime (0, 0, 0, $this->request->get("frommonth"),
                                                $this->request->get("fromday"), $this->request->get("fromyear"));
                        $toTime = mktime (0, 0, 0, $this->request->get("tomonth"),
                                                $this->request->get("today"), $this->request->get("toyear"));
			if($frTime - $toTime > 0){
				$errmsg = "日付大小関係が不正です。";
			} elseif ($toTime - $frTime > 365*24*60*60){
				$errmsg = "１年以内の期間で入力してください。";
			}
		}
		
		$this->result->add("errmsg", $errmsg);
		if($errmsg == ""){
			return true;
		}else{
			return false;
		}
	}
	
	private function deleteSysLog(){
		$this->db->begintrans();
		
		//表示用削除件数の取得
		$strsql = "select 
						count(A.dummy) as delnum 
					from ( ";
		$strsql .= "select 1 as dummy ";
		$strsql .= "from tbl_action_log where unique_id in(";
		$strsql .= "select distinct unique_id from 
						tbl_login_log 
					where login_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59') ";
		$strsql .= "union all select 1 as dummy ";
		$strsql .= "from tbl_login_log 
					where login_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59' ";
		$strsql .= ") A";
						

		$data = $this->db->selectdata($strsql);
		$delNum = $data[0]["delnum"];
		
		//機種ログの削除
		$strsql = "delete from tbl_prd_log where save_no in(";
		$strsql .= "select distinct save_no from tbl_action_log where unique_id in(";
		$strsql .= "select distinct unique_id from 
						tbl_login_log 
					where login_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59')) ";
		if(!$this->db->executesql($strsql, false)){
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			return false;
		}
		
		//アクションログの削除
		$strsql = "delete from tbl_action_log where unique_id in(";
		$strsql .= "select distinct unique_id from 
						tbl_login_log 
					where login_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59') ";
		if(!$this->db->executesql($strsql, false)){
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			return false;
		}
		
		//ログインログの削除
		$strsql = "delete from 
						tbl_login_log 
					where login_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59' ";
		if($this->db->executesql($strsql)){
			$this->accesslog("ログ管理", "システムログの削除");
			$this->result->add("JSAlert",$delNum."件を削除しました。");
			$this->result->add("delDone", "delDone");
		}else{
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			$this->result->add("delDone", "delDone");
			return false;
		}
		$this->db->commit();
		return true;
	}

	private function deleteDlLog(){
		$this->db->begintrans();
		
		//表示用削除件数の取得
		$strsql = "select 
						count(1) as delnum
					from 
						tbl_download_hist 
					where download_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59' ";

		$data = $this->db->selectdata($strsql);
		$delNum = $data[0]["delnum"];

		//ダウンロード履歴の削除
		$strsql = "delete from 
						tbl_download_hist 
					where download_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59' ";
		if($this->db->executesql($strsql)){
			$this->accesslog("ログ管理", "ダウンロード履歴の削除");
			$this->result->add("JSAlert",$delNum."件を削除しました。");
			$this->result->add("delDone", "delDone");
		}else{
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			$this->result->add("delDone", "delDone");
			return false;
		}
		$this->db->commit();
		return true;
	}

	private function deleteAccessLog(){
		$this->db->begintrans();
		
		//表示用削除件数の取得
		$strsql = "select 
						count(1) as delnum
					from 
						tbl_access_log 
					where access_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59' ";

		$data = $this->db->selectdata($strsql);
		$delNum = $data[0]["delnum"];

		//ダウンロード履歴の削除
		$strsql = "delete from 
						tbl_access_log 
					where access_time between '" .
						$this->request->get("fromyear") . "/" . 
						$this->request->get("frommonth") . "/" .
						$this->request->get("fromday") . " 00:00:00' and '" .
						$this->request->get("toyear") . "/" .
						$this->request->get("tomonth") . "/" .
						$this->request->get("today") . " 23:59:59' ";
		if($this->db->executesql($strsql)){
			$this->accesslog("ログ管理", "アクセスログの削除");
			$this->result->add("JSAlert",$delNum."件を削除しました。");
			$this->result->add("delDone", "delDone");
		}else{
			$this->db->rollback();
			$err = $this->db->getlasterror();
			$err = "削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
					"エラーメッセージ:" . $err["message"];
			$this->result->add("JSAlert", $err);
			$this->result->add("delDone", "delDone");
			return false;
		}
		$this->db->commit();
		return true;
	}
}
?>
