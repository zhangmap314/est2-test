<?php
/*
シミュレーションクラス
*/
class SimulationController extends basecontroller{
	private $stdsimresultfile = "./template/simulationresult.xls";
	private $curdatafile = "./template/cur_data.xls";
	private $newdatafile = "./template/new_data.xls";
	
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}
	private $simresultwarning2 = "スリープモード時の消費電力が確認できなかった機種 ";
	private $simresultwarning3 = " については、当該機種が発売された年度の平均的な消費電力の値を使用しています。";
	
	public function execute(){
		if(!$this->checkloginlog()){
			$this->result->add("nolog", true);
			return false;
		}
		//シミュレーション注意事項(画面の下部に表示するメッセージ)
		$filename = DIR_NAME_TEMPLATE . FILE_NAME_SIM_RESULT_WARNING;
		$filebuf = "";
		
		if(file_exists($filename)){
			if(is_readable($filename)){
				//テンプレートを読み込み
				$filebuf = file_get_contents($filename);
				$filebuf = mb_convert_encoding($filebuf,"UTF-8", "UTF-8,sjis,euc-jp");
				$filebuf = str_replace("\"","\\\"",$filebuf);// " ---> \" に変換
				//$filebuf = preg_replace("/(\r\n|\n|\r)/", "", $filebuf);//改行コード削除
				$filebuf = preg_replace("/(\t)/", "    ", $filebuf);//タブ文字を半角スペース４つに変換
				$this->result->add("simresultwarning", $filebuf);
			}
		}
		
		
		switch($this->request->get("action")){
			case "calculate":
				$this->calculate("計算");
				break;
			case "printout":
				$this->calculate("画面印刷", true, "2");
				break;
			case "download":
				if($this->calculate("エクセルファイル", true, "1")){
					if(!$this->downloadstdresult()){	//単体で表示するための結果をダウンロード
						//ダウンロード失敗時に計算とみなしてエラーメッセージを表示する
						$this->request->add("action", "calculate");
					}
				}else{
					//計算処理失敗時に計算とみなしてエラーメッセージを表示する
					$this->request->add("action", "calculate");
				}
				break;
			case "dlformacro":
				if($this->calculate("提案書テンプレートデータ", true, "0")){
					$this->dodownload();	//マクロで提案書に展開用データをダウンロード
				}else{
					//計算処理失敗時に計算とみなしてエラーメッセージを表示する
					$this->request->add("action", "calculate");
				}
				break;
			default:
				
				break;
		}
		
		//印刷時の表示データ取得
		if($this->request->get("action") == "printout") {
			//メーカーリスト取得
			$this->result->add("cur_makerlist", $this->db->selectdata("select maker_cd,maker_name from mst_maker order by sort_key"));
			//製品種別リスト取得
			$this->result->add("both_prod_type_cd", $this->db->selectdata("select prod_type_cd,prod_type_name from mst_prod_type order by sort_key"));
			//機種名取得
			//for($i=1; $i<= $this->simulation_max_rows; $i++){
			for($i=1; $i<= $this->request->get("hid_showline"); $i++){
				$this->result->add("cur_prod_name".$i,$this->get_prod_name($this->request->get('cur_seq_no'.$i)));
				$this->result->add("new_prod_name".$i,$this->get_prod_name($this->request->get('new_seq_no'.$i)));
			}
		}
		
		if($this->request->get("action") != "printout" &&
			$this->request->get("action") != "download" &&
			$this->request->get("action") != "dlformacro"){
		
			//テロップ
			$filename = DIR_NAME_TEMPLATE . FILE_NAME_TELOP;
			$filebuf = "";
			
			if(file_exists($filename)){
				if(is_readable($filename)){
					//テンプレートを読み込み
					$filebuf = file_get_contents($filename);
					$filebuf = mb_convert_encoding($filebuf,"UTF-8", "UTF-8,sjis,euc-jp");
					$filebuf = str_replace("\"","\\\"",$filebuf);// " ---> \" に変換
					$filebuf = preg_replace("/(\r\n|\n|\r)/", "", $filebuf);//改行コード削除
					
					$this->result->add("telopmsg", $filebuf);
				}
			}
			
			//メーカーリスト取得
			$this->result->add("cur_makerlist", $this->db->selectdata("select maker_cd,maker_name from mst_maker order by sort_key"));
			//製品種別リスト取得
			$this->result->add("both_prod_type_cd", $this->db->selectdata("select prod_type_cd,prod_type_name from mst_prod_type order by sort_key"));
			
			
			//提案書リスト
			$ar = scandir( DIR_NAME_IDEA );
			$filelist = array();
			for( $i=0; $i < count($ar); $i++ ){
				if( $ar[$i] != "." && $ar[$i] != ".." 
				&& substr($ar[$i],-3,3) == "xls"){
					$filelist[] = array("filename" => $ar[$i], "dispname" => str_replace(".xls", "", $ar[$i]));
				}
			}
			for( $i=0; $i < count($ar); $i++ ){
				if( $ar[$i] != "." && $ar[$i] != ".." 
				&& substr($ar[$i],-4,4) == "xlsm"){
					$filelist[] = array("filename" => $ar[$i], "dispname" => str_replace(".xlsm", "", $ar[$i]));
				}
			}
			$this->result->add("filelist", $filelist);
			
			//機種の各種情報を取得
			$this->getproductsinfo();
			
			
		}
	}
	
	private function checkloginlog(){
		$sql = "select
					save_no
				from
					tbl_login_log
				where
					unique_id = '" . common::sqlescape($this->session->get("user_unique_id")) . "' 
				and user_id = '". common::sqlescape($this->session->get("user_id")) . "' ";
		$log = $this->db->selectdata($sql);
		if(count($log) > 0){
			return true;
		}else{
			return false;
		}
	}
	
	private function calculate($menu_name, $docreatelog = false, $action_kbn = ""){
		if(!$this->checkinput()){ return false;}
		
		//環境情報取得
		$sql = "select
					currency,
					electricity_cost,
					co2_coefficient,
					co2_to_tree,
					weeks
				from
					mst_ecoinfo
				where
					area_cd = '01'
				and
					user_id = '*****'";
		$ecoinfo = $this->db->selectdata(str_replace("*****", $this->session->get("user_id"), $sql));
		if(count($ecoinfo)==0){//ユーザー用環境情報がない場合は、管理者用を取得
			$ecoinfo = $this->db->selectdata(str_replace("*****", "00000000000000000000", $sql));
		}
		
		//=====換算係数(Excelダウンロード用)    2012/9/15改修=====
		$this->result->add("base_denki_tanka", $ecoinfo[0]["electricity_cost"]);
		$this->result->add("base_co2keisu",    $ecoinfo[0]["co2_coefficient"]);
		$this->result->add("base_tree_kyushu", $ecoinfo[0]["co2_to_tree"]);
		$this->result->add("base_weeks",       $ecoinfo[0]["weeks"]);
		//==========
		
		
		
		
		//シミュレーション計算
		
		//使用機種
		//for($i=1; $i<= $this->simulation_max_rows; $i++){
		for($i=1; $i<= $this->request->get("hid_showline"); $i++){
			//台数とADVが入力されていれば、計算を行う
			if(is_numeric($this->request->get("cur_printers" . $i)) && is_numeric($this->request->get("cur_papers" . $i))){
				$proddata = $this->getproductdata($this->request->get("cur_seq_no" . $i));//使用機種情報取得
				//機種情報取得できた場合
				if(count($proddata) == 1){
					$this->calcone($proddata[0], $ecoinfo[0], "0", $i);
					/*機種名格納*/
					$this->result->add("cur_product_name" . $i, $proddata[0]["product_name"]);
					
					//推定スリープ電力の有無がありの機種名
					if($proddata[0]["power_flg"] == "1"){
						if($this->result->get("suiteiprodlist") != ""){
							$this->result->add("suiteiprodlist", $this->result->get("suiteiprodlist") . "、");
						}
						$this->result->add("suiteiprodlist", $this->result->get("suiteiprodlist") . "「". $proddata[0]["product_name"] . "」");
					}
					
					//数字をカンマで整形
					$this->result->add("cur_yearpower" . $i, number_format($this->result->get("cur_yearpower" . $i, 0)));
					$this->result->add("cur_yearco2" . $i, number_format($this->result->get("cur_yearco2" . $i, 0)));
					$this->result->add("cur_yearcost" . $i, number_format($this->result->get("cur_yearcost" . $i, 0)));
					$this->result->add("cur_yeartrees" . $i, number_format($this->result->get("cur_yeartrees" . $i, 0)));
				}
			}
		}
		
		
		//提案機種
		//for($i=1; $i<= $this->simulation_max_rows; $i++){
		for($i=1; $i<= $this->request->get("hid_showline"); $i++){
			//台数とADVが入力されていれば、計算を行う
			if(is_numeric($this->request->get("new_printers" . $i)) && is_numeric($this->request->get("new_papers" . $i))){
				//リストは機種名であるが、valueは機種のキーであるseq_no
				$proddata = $this->getproductdata($this->request->get("new_seq_no" . $i));//提案機種情報取得
				//機種情報取得できた場合
				if(count($proddata) == 1){
					$this->calcone($proddata[0], $ecoinfo[0], "1", $i);
					/*機種名格納*/
					$this->result->add("new_product_name" . $i, $proddata[0]["product_name"]);
					
					//推定スリープ電力の有無がありの機種名
					if($proddata[0]["power_flg"] == "1"){
						if($this->result->get("suiteiprodlist") != ""){
							$this->result->add("suiteiprodlist", $this->result->get("suiteiprodlist") . "、");
						}
						$this->result->add("suiteiprodlist", $this->result->get("suiteiprodlist") . "「". $proddata[0]["product_name"] . "」");
					}
					
					//数字をカンマで整形
					$this->result->add("new_yearpower" . $i, number_format($this->result->get("new_yearpower" . $i, 0)));
					$this->result->add("new_yearco2" . $i, number_format($this->result->get("new_yearco2" . $i, 0)));
					$this->result->add("new_yearcost" . $i, number_format($this->result->get("new_yearcost" . $i, 0)));
					$this->result->add("new_yeartrees" . $i, number_format($this->result->get("new_yeartrees" . $i, 0)));
				}
			}
		}
		if($this->result->get("suiteiprodlist") != ""){
			$this->result->add("suiteimsg", $this->simresultwarning2 . $this->result->get("suiteiprodlist") . $this->simresultwarning3);
		}
		
		//削減効果
		$this->result->add("totalpower", number_format( $this->result->get("cur_totalpower", 0) - $this->result->get("new_totalpower", 0)));
		if($this->result->get("totalpower") == 0){
			$this->result->add("totalpower", "");
		}
		
		$this->result->add("totalco2", number_format( $this->result->get("cur_totalco2", 0) - $this->result->get("new_totalco2", 0)));
		if($this->result->get("totalco2") == 0){
			$this->result->add("totalco2", "");
		}
		
		$this->result->add("totalcost", number_format( $this->result->get("cur_totalcost", 0) - $this->result->get("new_totalcost", 0)));
		if($this->result->get("totalcost") == 0){
			$this->result->add("totalcost", "");
		}
		
		$this->result->add("totaltrees", number_format(round( $this->result->get("cur_totaltrees", 0) - $this->result->get("new_totaltrees", 0))));
		if($this->result->get("totaltrees") == 0){
			$this->result->add("totaltrees", "");
		}
		
		//使用機種結果合計数字をカンマで整形
		$this->result->add("cur_totalpower", number_format($this->result->get("cur_totalpower", 0)));
		$this->result->add("cur_totalco2", number_format($this->result->get("cur_totalco2", 0)));
		$this->result->add("cur_totalcost", number_format($this->result->get("cur_totalcost", 0)));
		$this->result->add("cur_totaltrees", number_format($this->result->get("cur_totaltrees", 0)));
		
		//提案機種結果合計数字をカンマで整形
		$this->result->add("new_totalpower", number_format($this->result->get("new_totalpower", 0)));
		$this->result->add("new_totalco2", number_format($this->result->get("new_totalco2", 0)));
		$this->result->add("new_totalcost", number_format($this->result->get("new_totalcost", 0)));
		$this->result->add("new_totaltrees", number_format($this->result->get("new_totaltrees", 0)));
		
		
		//クリップボードコピー用データ生成
		$copydata = "";
		//使用機種の合計消費電力量、電気料金、CO2排出量
		$copydata = "\"" . $this->result->get("cur_totalpower") . "\",\"" . $this->result->get("cur_totalcost") . "\",\"" . $this->result->get("cur_totalco2") . "\",";
		//提案機種の合計消費電力量、電気料金、CO2排出量
		$copydata .= "\"" . $this->result->get("new_totalpower") . "\",\"" . $this->result->get("new_totalcost") . "\",\"" . $this->result->get("new_totalco2") . "\",";
		//削減効果の消費電力量、電気料金、CO2排出量、立木換算
		$copydata .= "\"" . $this->result->get("totalpower") . "\",\"" . $this->result->get("totalcost") . "\",\"" . $this->result->get("totalco2") . "\",\"" . $this->result->get("totaltrees") . "\",";
		//留意事項
		$copydata .= "\"" . str_replace("'", "\\'", preg_replace("/(\r\n|\n|\r)/", "\\n", $this->result->get("simresultwarning") )) . "\","; //改行 -> \n, ' -> \' に変換
		//スリープ電力の留意事項
		$copydata .= "\"" . str_replace("'", "\\'", preg_replace("/(\r\n|\n|\r)/", "\\n", $this->result->get("suiteimsg") )) . "\","; //改行 -> \n, ' -> \' に変換
		//お客様名
		$copydata .= "\"" . str_replace("'", "\\'", preg_replace("/(\r\n|\n|\r)/", "\\n", $this->session->get("cust_name") )) . "\",";
		//シミュレーション年数
		$copydata .= "\"" . $this->request->get("foryears") . "\",";
		
		for($i=1; $i<= $this->simulation_max_rows; $i++){
			//使用機種
			$copydata .= "\"";
			//機種メーカー名(台数とADVが入力されている場合のみ)
			if(is_numeric($this->request->get("cur_printers" . $i)) && is_numeric($this->request->get("cur_papers" . $i))){
				$copydata .= str_replace("'", "\\'", $this->get_maker_name($this->request->get("cur_maker_cd" . $i)));
			}
			$copydata .= "\",";
			//製品種別
			$copydata .= "\"" . $this->get_prod_type_name($this->request->get("cur_prod_type_cd" . $i)) . "\",";
			//モノクロ・カラー
			if( $this->request->get("cur_color_kbn" . $i) == "1" ){
				$copydata .= "\"カラー\",";
			}elseif( $this->request->get("cur_color_kbn" . $i) == "0" ){
				$copydata .= "\"モノクロ\",";
			}else{
				$copydata .= "\"\",";		//数合わせのため、データがなくてもブランクをセット
			}
			//機種名
			$copydata .= "\"" . str_replace("'", "\\'", $this->result->get("cur_product_name" . $i)) . "\",";
			//台数
			$copydata .= "\"" . $this->request->get("cur_printers" . $i) . "\",";
			//ADV
			$copydata .= "\"" . $this->request->get("cur_papers" . $i) . "\",";
			//消費電力量、CO2排出量
			$copydata .= "\"" . $this->result->get("cur_yearpower" . $i) . "\",\"" . $this->result->get("cur_yearco2" . $i) . "\",";
			
			//提案機種
			$copydata .= "\"";
			//機種メーカー名(台数とADVが入力されている場合のみ)
			if(is_numeric($this->request->get("new_printers" . $i)) && is_numeric($this->request->get("new_papers" . $i))){
				$copydata .= str_replace("'", "\\'", $this->get_maker_name($this->request->get("new_maker_cd" . $i)));
			}
			$copydata .= "\",";
			//製品種別
			$copydata .= "\"" . $this->get_prod_type_name($this->request->get("new_prod_type_cd" . $i)) . "\",";
			//モノクロ・カラー
			if( $this->request->get("new_color_kbn" . $i) == "1" ){
				$copydata .= "\"カラー\",";
			}elseif( $this->request->get("new_color_kbn" . $i) == "0" ){
				$copydata .= "\"モノクロ\",";
			}else{
				$copydata .= "\"\",";		//数合わせのため、データがなくてもブランクをセット
			}
			//機種名
			$copydata .= "\"" . str_replace("'", "\\'", $this->result->get("new_product_name" . $i)) . "\",";
			//台数
			$copydata .= "\"" . $this->request->get("new_printers" . $i) . "\",";
			//ADV
			$copydata .= "\"" . $this->request->get("new_papers" . $i) . "\",";
			//消費電力量、CO2排出量
			$copydata .= "\"" . $this->result->get("new_yearpower" . $i) ."\",\"" . $this->result->get("new_yearco2" . $i) . "\"";
			
			if( $i != $this->simulation_max_rows ){
				$copydata .= ",";
			}
		}
		$this->result->add("clipboardData", $copydata);
		
		
		$actionsaveno = $this->accesslog("シミュレーション", $menu_name);
		
		if(!$docreatelog){
			return true;
		}
		
		//アクションログと機種ログを生成
		$this->db->begintrans();
		
		$actionlog = array("save_no" => $actionsaveno,
							 "unique_id" => common::sqlescape($this->session->get("user_unique_id")),
							 "action_kbn" => $action_kbn,
							 "cust_cd" => common::sqlescape($this->session->get("cust_cd")),
							 "cust_name" => common::sqlescape($this->session->get("cust_name")),
							 "sim_years" => common::sqlescape($this->request->get("foryears")),
							 "crnt_denryoku_ttl" => str_replace(",", "", $this->result->get("cur_totalpower", "0")),
							 "crnt_co2_ttl" => str_replace(",", "", $this->result->get("cur_totalco2", "0")),
							 "crnt_denkiryo_ttl" => str_replace(",", "", $this->result->get("cur_totalcost", "0")),
							 "crnt_tree_ttl" => str_replace(",", "", $this->result->get("cur_totaltrees", "0")),
							 "new_denryoku_ttl" => str_replace(",", "", $this->result->get("new_totalpower", "0")),
							 "new_co2_ttl" => str_replace(",", "", $this->result->get("new_totalco2", "0")),
							 "new_denkiryo_ttl" => str_replace(",", "", $this->result->get("new_totalcost", "0")),
							 "new_tree_ttl" => str_replace(",", "", $this->result->get("new_totaltrees", "0")),
							 "denryoku_ttl" => str_replace(",", "", $this->result->get("totalpower", "0")),
							 "co2_ttl" => str_replace(",", "", $this->result->get("totalco2", "0")),
							 "denkiryo_ttl" => str_replace(",", "", $this->result->get("totalcost", "0")),
							 "tree_ttl" => str_replace(",", "", $this->result->get("totaltrees", "0")),
							 "create_time" => date("Y/m/d H:i:s")
						 );
		
		$this->result->add("err", "");
		if(!$this->db->insert("tbl_action_log", $actionlog, false)){
			$this->db->rollback();
			$this->result->add("err", "印刷のアクションログ生成が失敗しました。");
			return false;
		}
		
		$seq = 1;
		for($i=1; $i<= $this->simulation_max_rows; $i++){
			//提案機種ＯＲ使用機種の台数・ADVが入力されている
			if(is_numeric($this->request->get("new_printers" . $i)) && 
				is_numeric($this->request->get("new_papers" . $i)) || 
				is_numeric($this->request->get("cur_printers" . $i)) && 
				is_numeric($this->request->get("cur_papers" . $i))){
				//使用機種ＯＲ提案機種が有る場合
			
				$ar = Array(
						"save_no" => $actionsaveno,
						"sequence_no" => $seq,
						"new_maker_name" => $this->get_maker_name($this->request->get("new_maker_cd" . $i)),
						"new_prod_type_name" => $this->get_prod_type_name($this->request->get("new_prod_type_cd" . $i)),
						"new_color_kbn" => common::sqlescape($this->request->get("new_color_kbn" . $i)),
						"new_product_name" => common::sqlescape($this->result->get("new_product_name" . $i)),
						"new_eco_mode" => common::sqlescape($this->request->get("new_ecomode" . $i)),
						"new_power_mode" => common::sqlescape($this->request->get("new_plugmode" . $i)),
						"new_adv" => common::sqlescape($this->request->get("new_papers" . $i)),
						"new_printer_cnt" => common::sqlescape($this->request->get("new_printers" . $i)),
						"new_denryoku" => str_replace(",", "", common::sqlescape($this->result->get("new_yearpower" . $i))),
						"new_co2" => str_replace(",", "", common::sqlescape($this->result->get("new_yearco2" . $i))),
						"new_denkiryo" => str_replace(",", "", common::sqlescape($this->result->get("new_yearcost" . $i))),
						"new_tree" => str_replace(",", "", common::sqlescape($this->result->get("new_yeartrees" . $i))),
						"crnt_maker_name" => $this->get_maker_name($this->request->get("cur_maker_cd" . $i)),
						"crnt_prod_type_name" => $this->get_prod_type_name($this->request->get("cur_prod_type_cd" . $i)),
						"crnt_color_kbn" => common::sqlescape($this->request->get("cur_color_kbn" . $i)),
						"crnt_product_name" => common::sqlescape($this->result->get("cur_product_name" . $i)),
						"crnt_eco_mode" => common::sqlescape($this->request->get("cur_ecomode" . $i)),
						"crnt_power_mode" => common::sqlescape($this->request->get("cur_plugmode" . $i)),
						"crnt_adv" => common::sqlescape($this->request->get("cur_papers" . $i)),
						"crnt_printer_cnt" => common::sqlescape($this->request->get("cur_printers" . $i)),
						"crnt_denryoku" => str_replace(",", "", common::sqlescape($this->result->get("cur_yearpower" . $i))),
						"crnt_co2" => str_replace(",", "", common::sqlescape($this->result->get("cur_yearco2" . $i))),
						"crnt_denkiryo" => str_replace(",", "", common::sqlescape($this->result->get("cur_yearcost" . $i))),
						"crnt_tree" => str_replace(",", "", common::sqlescape($this->result->get("cur_yeartrees" . $i)))
					);
				//省エネモードはグレーダウンの場合はデータが送信されないため、ONとみなす
				if($ar["crnt_eco_mode"] == ""){
					$ar["crnt_eco_mode"] = "1";
				}
				if($ar["new_eco_mode"] == ""){
					$ar["new_eco_mode"] = "1";
				}
				
				
				
				if(!$this->db->insert("tbl_prd_log", $ar, false)){
					$this->db->rollback();
					$this->result->add("err", "シミュレーション機種ログの生成が失敗しました。");
					return false;
				}
				$seq++;
			
			}
		}
		$this->db->commit();
		
		return true;
	}
	
	private function get_prod_name($seq_no){
		if($seq_no == "" || !is_numeric($seq_no)){
			return "";
		}
		
		$sql = "select
					product_name
				from
					mst_product
				where
					seq_no = '" . common::sqlescape($seq_no) . "'";
		$prod = $this->db->selectdata($sql);
		if(count($prod) == 1){
			return $prod[0]["product_name"];
		}else{
			return "";
		}
	}
	
	private function get_maker_name($maker_cd){
		if($maker_cd == "" || !is_numeric($maker_cd)){
			return "";
		}
		
		$sql = "select
					maker_name
				from
					mst_maker
				where
					maker_cd = '" . common::sqlescape($maker_cd) . "'";
		$maker = $this->db->selectdata($sql);
		if(count($maker) == 1){
			return $maker[0]["maker_name"];
		}else{
			return "";
		}
	}
	private function get_prod_type_name($prod_type_cd){
		if($prod_type_cd == "" || !is_numeric($prod_type_cd)){
			return "";
		}
		
		$sql = "select
					prod_type_name
				from
					mst_prod_type
				where
					prod_type_cd = '" . common::sqlescape($prod_type_cd) . "'";
		$prod_type = $this->db->selectdata($sql);
		if(count($prod_type) == 1){
			return $prod_type[0]["prod_type_name"];
		}else{
			return "";
		}
	}
	
	
	/*ダウンロード*/
	private function dodownload(){
		$buf = "";
		
		$cur_totalpapers = 0;
		$new_totalpapers = 0;
		
		//$buf = $this->session->get("cust_cd") . "\t" . $this->session->get("cust_name") . "\t" . $this->request->get("foryears") . "\t" . $this->simulation_max_rows . "\r\n";
		$buf = $this->session->get("cust_cd") . "\t" . $this->session->get("cust_name") . "\t" . $this->request->get("foryears") . "\t" . $this->request->get("hid_showline") . "\r\n";
		
		
		//for($i=1; $i<= $this->simulation_max_rows; $i++){
		for($i=1; $i<= $this->request->get("hid_showline"); $i++){
		
			$buf .= $this->result->get("cur_product_name" . $i) . "\t" .
					$this->request->get("cur_papers" . $i) . "\t" .
					$this->result->get("cur_yearpower" . $i) . "\t" .
					$this->result->get("cur_yearcost" . $i) . "\t" .
					$this->result->get("cur_yearco2" . $i) . "\t" .
					$this->result->get("new_product_name" . $i) . "\t" .
					$this->request->get("new_papers" . $i) . "\t" .
					$this->result->get("new_yearpower" . $i) . "\t" .
					$this->result->get("new_yearcost" . $i) . "\t" .
					$this->result->get("new_yearco2" . $i) . "\r\n";
			
			if(is_numeric($this->request->get("cur_papers" . $i))){
				$cur_totalpapers += $this->request->get("cur_papers" . $i);
			}
			if(is_numeric($this->request->get("new_papers" . $i))){
				$new_totalpapers += $this->request->get("new_papers" . $i);
			}
		}
		$buf .= $cur_totalpapers . "\t" .
				$this->result->get("cur_totalpower") . "\t" .
				$this->result->get("cur_totalcost") . "\t" .
				$this->result->get("cur_totalco2") . "\t" .
				$new_totalpapers . "\t" .
				$this->result->get("new_totalpower") . "\t" .
				$this->result->get("new_totalcost") . "\t" .
				$this->result->get("new_totalco2") . "\r\n";
				
		$buf .= $this->result->get("totalpower") . "\t" .
				$this->result->get("totalcost") . "\t" .
				$this->result->get("totalco2") . "\t" .
				$this->result->get("totaltrees") . "\r\n";
		
		$buf .= $this->result->get("simresultwarning") . "\r\n";
		$buf .= $this->result->get("suiteimsg") . "\r\n";
		
		$buf = mb_convert_encoding($buf, "SJIS", "UTF-8");
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Type: application/x-csv");
		header("Content-Disposition: attachment; filename=\"simulationresult.csv\"");
		header("Content-length: " . strlen($buf));
		
		print $buf;
	}
	
	private function translatcolor($colorkbn){
		if($colorkbn == "1"){
			return "カラー";
		}else if($colorkbn == "0"){
			return "モノクロ";
		}else{
			return "";
		}
	}
	private function translatecomode($ecomode){
		if($ecomode == "0"){
			return "OFF";
		}else{
			return "ON";
		}
	}
	private function translatplug($plugmode){
		if($plugmode == "1"){
			return "イン";
		}else if($plugmode == "0"){
			return "アウト";
		}else{
			return "";
		}
	}
	
	
	/*単体で表示するシミュレーション結果をダウンロードする*/
	private function downloadstdresult(){
		
		if(!file_exists($this->stdsimresultfile)){
			$this->result->add("err", "テンプレートファイルが存在しません。");
			return false;
		}
		if(!is_readable($this->stdsimresultfile)){
			$this->result->add("err", "テンプレートファイルを読込めませんでした。");
			return false;
		}
		//テンプレートを読み込み
		$tmpcurdata = file_get_contents($this->curdatafile);
		$curdata = "";
		
		$tmpnewdata = file_get_contents($this->newdatafile);
		$newdata = "";
		
		for($i = 1; $i <= $this->request->get("hid_showline"); $i++) {
			//使用機種
			//No
			$curdata .= str_replace("<!--cur_no-->", $i, $tmpcurdata);
			//モノクロ／カラー
			$curdata = str_replace("<!--cur_color-->", $this->translatcolor( $this->request->get("cur_color_kbn".$i)), $curdata);
			//使用機種名
			$curdata = str_replace("<!--cur_prod_name-->", $this->result->get("cur_product_name".$i), $curdata);
			//使用台数
			$curdata = str_replace("<!--cur_printers-->", $this->request->get("cur_printers".$i), $curdata);
			//使用ADV
			$curdata = str_replace("<!--cur_papers-->", $this->request->get("cur_papers".$i), $curdata);
	
			//台数がなければ省エネモード、プラグはブランクとする
			if($this->request->get("cur_printers".$i) != ""){
				//省エネモード
				$curdata = str_replace("<!--cur_ecomode-->", $this->translatecomode( $this->request->get("cur_ecomode".$i)), $curdata);
				//プラグモード
				$curdata = str_replace("<!--cur_plugmode-->", $this->translatplug( $this->request->get("cur_plugmode".$i)), $curdata);
			}else{
				//省エネモード
				$curdata = str_replace("<!--cur_ecomode-->", "", $curdata);
				//プラグモード
				$curdata = str_replace("<!--cur_plugmode-->", "", $curdata);
			}

			//使用機種の個々の計算結果
			$curdata = str_replace("<!--cur_yearpower-->", $this->result->get("cur_yearpower".$i), $curdata);
			$curdata = str_replace("<!--cur_yearcost-->", $this->result->get("cur_yearcost".$i), $curdata);
			$curdata = str_replace("<!--cur_yearco2-->", $this->result->get("cur_yearco2".$i), $curdata);
			$curdata = str_replace("<!--cur_yeartrees-->", $this->result->get("cur_yeartrees".$i), $curdata);
			
			
			

			//提案機種
			//No
			$newdata .= str_replace("<!--new_no-->", $i, $tmpnewdata);
			//モノクロ／カラー
			$newdata = str_replace("<!--new_color-->", $this->translatcolor( $this->request->get("new_color_kbn".$i)), $newdata);
			//使用機種名
			$newdata = str_replace("<!--new_prod_name-->", $this->result->get("new_product_name".$i), $newdata);
			//使用台数
			$newdata = str_replace("<!--new_printers-->", $this->request->get("new_printers".$i), $newdata);
			//使用ADV
			$newdata = str_replace("<!--new_papers-->", $this->request->get("new_papers".$i), $newdata);

			//台数がなければ省エネモード、プラグはブランクとする
			if($this->request->get("new_printers".$i) != ""){
				//省エネモード
				$newdata = str_replace("<!--new_ecomode-->", $this->translatecomode( $this->request->get("new_ecomode".$i)), $newdata);
				//プラグモード
				$newdata = str_replace("<!--new_plugmode-->", $this->translatplug( $this->request->get("new_plugmode".$i)), $newdata);
			}else{
				//省エネモード
				$newdata = str_replace("<!--new_ecomode-->", "", $newdata);
				//プラグモード
				$newdata = str_replace("<!--new_plugmode-->", "", $newdata);
			}
			//提案機種の個々の計算結果
			$newdata = str_replace("<!--new_yearpower-->", $this->result->get("new_yearpower".$i), $newdata);
			$newdata = str_replace("<!--new_yearcost-->", $this->result->get("new_yearcost".$i), $newdata);
			$newdata = str_replace("<!--new_yearco2-->", $this->result->get("new_yearco2".$i), $newdata);
			$newdata = str_replace("<!--new_yeartrees-->", $this->result->get("new_yeartrees".$i), $newdata);
			
			
			
		}
		
		$filebuf = file_get_contents($this->stdsimresultfile);
		
		$filebuf = str_replace("<!--cust_name-->", $this->session->get("cust_name"), $filebuf);
		$filebuf = str_replace("<!--foryears-->", $this->request->get("foryears"), $filebuf);

		$filebuf = str_replace("<!--cur_data-->", $curdata, $filebuf);
		$filebuf = str_replace("<!--new_data-->", $newdata, $filebuf);
		
		//計算結果を置換する
		/*
		cur_color
		cur_prod_name
		cur_printers
		cur_papers
		cur_ecomode
		cur_plugmode
		cur_yearpower
		cur_yearcost
		cur_yearco2
		cur_yeartrees
		*/
		/*
		for($i=1; $i<= $this->simulation_max_rows; $i++){
			//モノクロ／カラー
			$filebuf = str_replace("<!--cur_color".$i."-->", $this->translatcolor( $this->request->get("cur_color_kbn".$i)), $filebuf);
			//使用機種名
			$filebuf = str_replace("<!--cur_prod_name".$i."-->", $this->result->get("cur_product_name".$i), $filebuf);
			//使用台数
			$filebuf = str_replace("<!--cur_printers".$i."-->", $this->request->get("cur_printers".$i), $filebuf);
			//使用ADV
			$filebuf = str_replace("<!--cur_papers".$i."-->", $this->request->get("cur_papers".$i), $filebuf);
			
			
			//台数がなければ省エネモード、プラグはブランクとする
			if($this->request->get("cur_printers".$i) != ""){
				//省エネモード
				$filebuf = str_replace("<!--cur_ecomode".$i."-->", $this->translatecomode( $this->request->get("cur_ecomode".$i)), $filebuf);
				//プラグモード
				$filebuf = str_replace("<!--cur_plugmode".$i."-->", $this->translatplug( $this->request->get("cur_plugmode".$i)), $filebuf);
			}else{
				//省エネモード
				$filebuf = str_replace("<!--cur_ecomode".$i."-->", "", $filebuf);
				//プラグモード
				$filebuf = str_replace("<!--cur_plugmode".$i."-->", "", $filebuf);
			}
			//使用機種の個々の計算結果
			$filebuf = str_replace("<!--cur_yearpower".$i."-->", $this->result->get("cur_yearpower".$i), $filebuf);
			$filebuf = str_replace("<!--cur_yearcost".$i."-->", $this->result->get("cur_yearcost".$i), $filebuf);
			$filebuf = str_replace("<!--cur_yearco2".$i."-->", $this->result->get("cur_yearco2".$i), $filebuf);
			$filebuf = str_replace("<!--cur_yeartrees".$i."-->", $this->result->get("cur_yeartrees".$i), $filebuf);
			
			
			
			
			//モノクロ／カラー
			$filebuf = str_replace("<!--new_color".$i."-->", $this->translatcolor( $this->request->get("new_color_kbn".$i)), $filebuf);
			//使用機種名
			$filebuf = str_replace("<!--new_prod_name".$i."-->", $this->result->get("new_product_name".$i), $filebuf);
			//使用台数
			$filebuf = str_replace("<!--new_printers".$i."-->", $this->request->get("new_printers".$i), $filebuf);
			//使用ADV
			$filebuf = str_replace("<!--new_papers".$i."-->", $this->request->get("new_papers".$i), $filebuf);
			
			//台数がなければ省エネモード、プラグはブランクとする
			if($this->request->get("new_printers".$i) != ""){
				//省エネモード
				$filebuf = str_replace("<!--new_ecomode".$i."-->", $this->translatecomode( $this->request->get("new_ecomode".$i)), $filebuf);
				//プラグモード
				$filebuf = str_replace("<!--new_plugmode".$i."-->", $this->translatplug( $this->request->get("new_plugmode".$i)), $filebuf);
			}else{
				//省エネモード
				$filebuf = str_replace("<!--new_ecomode".$i."-->", "", $filebuf);
				//プラグモード
				$filebuf = str_replace("<!--new_plugmode".$i."-->", "", $filebuf);
			}
			//提案機種の個々の計算結果
			$filebuf = str_replace("<!--new_yearpower".$i."-->", $this->result->get("new_yearpower".$i), $filebuf);
			$filebuf = str_replace("<!--new_yearcost".$i."-->", $this->result->get("new_yearcost".$i), $filebuf);
			$filebuf = str_replace("<!--new_yearco2".$i."-->", $this->result->get("new_yearco2".$i), $filebuf);
			$filebuf = str_replace("<!--new_yeartrees".$i."-->", $this->result->get("new_yeartrees".$i), $filebuf);
		}
		*/
		
		/*
		cur_totalpower
		cur_totalcost
		cur_totalco2
		cur_totaltrees
		*/
		//使用機種の計算結果合計
		$filebuf = str_replace("<!--cur_totalpower-->", $this->result->get("cur_totalpower"), $filebuf);
		$filebuf = str_replace("<!--cur_totalcost-->", $this->result->get("cur_totalcost"), $filebuf);
		$filebuf = str_replace("<!--cur_totalco2-->", $this->result->get("cur_totalco2"), $filebuf);
		$filebuf = str_replace("<!--cur_totaltrees-->", $this->result->get("cur_totaltrees"), $filebuf);
		
		//提案機種の計算結果合計
		$filebuf = str_replace("<!--new_totalpower-->", $this->result->get("new_totalpower"), $filebuf);
		$filebuf = str_replace("<!--new_totalcost-->", $this->result->get("new_totalcost"), $filebuf);
		$filebuf = str_replace("<!--new_totalco2-->", $this->result->get("new_totalco2"), $filebuf);
		$filebuf = str_replace("<!--new_totaltrees-->", $this->result->get("new_totaltrees"), $filebuf);

		/*
		totalpower
		totalcost
		totalco2
		totaltrees
		*/
		//差異
		$filebuf = str_replace("<!--totalpower-->", $this->result->get("totalpower"), $filebuf);
		$filebuf = str_replace("<!--totalco2-->", $this->result->get("totalco2"), $filebuf);
		$filebuf = str_replace("<!--totalcost-->", $this->result->get("totalcost"), $filebuf);
		$filebuf = str_replace("<!--totaltrees-->", $this->result->get("totaltrees"), $filebuf);
		
		//画面下部の表示メッセージ
		$filebuf = str_replace("<!--simresultwarning-->", preg_replace("/(\r\n|\n|\r)/", "<br>", $this->result->get("simresultwarning")), $filebuf);
		$filebuf = str_replace("<!--suiteimsg-->", $this->result->get("suiteimsg"), $filebuf);
		
		
		//=====換算係数追加表示 2012/9/15改修=====
		$filebuf = str_replace("<!--base_denki_tanka-->", $this->result->get("base_denki_tanka"), $filebuf);
		$filebuf = str_replace("<!--base_co2keisu-->", $this->result->get("base_co2keisu"), $filebuf);
		$filebuf = str_replace("<!--base_tree_kyushu-->", $this->result->get("base_tree_kyushu"), $filebuf);
		$filebuf = str_replace("<!--base_weeks-->", $this->result->get("base_weeks"), $filebuf);
		//==========
		
		
		
		$filebuf = mb_convert_encoding($filebuf, "SJIS", "UTF-8");
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Type: application/x-csv");
		header("Content-Disposition: attachment; filename=\"simulationresult.xls\"");
		header("Content-length: " . strlen($filebuf));
		
		print $filebuf;
		return true;
	}
	
	
	
	
	
	
	
	
	
	
	
	/*
	$proddata : 機種情報
	$ecoinfo  : 環境情報
	$model    : 0 = 使用機種；1 = 提案機種
	$rowno    : 行番号
	*/
	private function calcone($proddata, $ecoinfo, $model, $rowno){
		/*
		推定スリープ電力の有無		power_flg
		JOB当り面数					power_per_paper
		省エネONJOB単位電力量		eco_on_pwr_p_job
		省エネOFFJOB単位電力量		eco_off_pwr_p_job
		省エネON週間最終時間		eco_on_last_time
		省エネOFF週間最終時間		eco_off_last_time
		省エネON週間最終電力量		eco_on_last_power
		省エネOFF週間最終電力量		eco_off_last_power
		スリープ電力				sleep_power
		*/
		$prefix = "";
		
		if($model == "0"){//使用機種
			$prefix = "cur_";
		}else{//提案機種
			$prefix = "new_";
		}
		//週間ADV = 月間ADV(画面入力値) / (年間週間数 / 12) 
		$week_adv = $this->request->get($prefix . "papers" . $rowno) / ($ecoinfo["weeks"] / 12);
		
		//週間ジョブ数 = 週間ADV / ジョブ当り面数(小数以下切り上げ)
		$week_job = 0;
		if($proddata["power_per_paper"] > 0){
			$week_job = ceil($week_adv / $proddata["power_per_paper"]);
		}
		
		//週間ジョブ電力量 = 週間ジョブ数 × ジョブ単位電力量
		if($this->request->get($prefix . "ecomode" . $rowno) != "0"){//省エネモードON(OFFでなければONとする)
			$week_job_power = $week_job * $proddata["eco_on_pwr_p_job"];
		}else{//省エネモードOFF
			$week_job_power = $week_job * $proddata["eco_off_pwr_p_job"];
		}
		//週間ジョブ時間 = 週間ジョブ数×15分(0.25時間)
		$week_job_time = $week_job * 0.25;
		
		//週間スリープ時間 = 週間総時間(168H) - 週間最終時間 - 週間ジョブ時間
		if($this->request->get($prefix . "ecomode" . $rowno) != "0"){//省エネモードON(OFFでなければONとする)
			$week_sleep_time = 168 - $proddata["eco_on_last_time"] - $week_job_time;
		}else{
			$week_sleep_time = 168 - $proddata["eco_off_last_time"] - $week_job_time;
		}
		
		//週間スリープ電力量 = 週間スリープ時間×スリープ電力
		$week_sllep_power = $week_sleep_time * $proddata["sleep_power"];
		
		//週間消費電力量 = 週間ジョブ電力量＋週間スリープ電力量＋週間最終電力量
		//プラグアウト時は、週間ｽﾘｰﾌﾟ電力と週間最終電力量を、０とする
		if($this->request->get($prefix . "plugmode" . $rowno) == "0"){//プラグアウト時
			$week_sllep_power = 0;					//週間ｽﾘｰﾌﾟ電力
			$proddata["eco_on_last_power"] = 0;		//週間最終電力量
			$proddata["eco_off_last_power"] = 0;	//週間最終電力量
		}
		if($this->request->get($prefix . "ecomode" . $rowno) != "0"){//省エネモードON(OFFでなければONとする)
			$week_total_power = $week_job_power + $week_sllep_power + $proddata["eco_on_last_power"];
		}else{
			$week_total_power = $week_job_power + $week_sllep_power + $proddata["eco_off_last_power"];
		}
		$week_total_power = $week_total_power / 1000;	//単位を Wh -> Kwh に揃える
		
		//機種ごとの結果
		//消費電力量 = 週間消費電力量 × 年間週間数 × 台数 ※消費電力量計算結果を四捨五入とする
		$this->result->add($prefix . "yearpower" . $rowno,round( $week_total_power * $ecoinfo["weeks"] * $this->request->get($prefix . "printers" . $rowno)));
		//CO2排出量 = 消費電力量 × CO2係数
		$this->result->add($prefix . "yearco2" . $rowno, $this->result->get($prefix . "yearpower" . $rowno) * $ecoinfo["co2_coefficient"]);
		//電気料金コスト = 消費電力量 * 電気代
		$this->result->add($prefix . "yearcost" . $rowno, $this->result->get($prefix . "yearpower" . $rowno) * $ecoinfo["electricity_cost"]);
		//立木換算本数 = CO2排出量 / 立木換算係数
		$this->result->add($prefix . "yeartrees" . $rowno, $this->result->get($prefix . "yearco2" . $rowno) / $ecoinfo["co2_to_tree"]);
		
		
		//計算結果にシミュレーション年数を乗じる(立木換算は例外として、シミュレーション年数乗算はしない)
		//消費電力量
		$this->result->add($prefix . "yearpower" . $rowno, $this->result->get($prefix . "yearpower" . $rowno) * $this->request->get("foryears"));
		//CO2排出量
		$this->result->add($prefix . "yearco2" . $rowno, $this->result->get($prefix . "yearco2" . $rowno) * $this->request->get("foryears"));
		//電気料金コスト
		$this->result->add($prefix . "yearcost" . $rowno, $this->result->get($prefix . "yearcost" . $rowno) * $this->request->get("foryears"));
		
		
		//機種区分(使用・提案)ごとの総合計
		$this->result->add($prefix . "totalpower", $this->result->get($prefix . "totalpower", 0) + $this->result->get($prefix . "yearpower" . $rowno, 0));
		$this->result->add($prefix . "totalco2", $this->result->get($prefix . "totalco2", 0) + $this->result->get($prefix . "yearco2" . $rowno, 0));
		$this->result->add($prefix . "totalcost", $this->result->get($prefix . "totalcost", 0) + $this->result->get($prefix . "yearcost" . $rowno, 0));
		$this->result->add($prefix . "totaltrees", $this->result->get($prefix . "totaltrees", 0) + $this->result->get($prefix . "yeartrees" . $rowno, 0));
		
	}
	/*機種の最小、最大ADVを取得*/
	private function get_productadv($prodseqno){
		$sql = "select eco_on_minadv,
						eco_on_maxadv,
						eco_off_minadv,
						eco_off_maxadv
				from
					mst_product
				where
					seq_no = '" . common::sqlescape($prodseqno) . "'";
		
		return $this->db->selectdata($sql);
	}
	
	private function checkinput(){
		$errmsg = "";
		
		if(strlen($this->request->get("cust_name")) > 100){
			$errmsg .= "お客様名の桁数がオーバーしています。<br>";
		}
		
		for($i=1; $i<= $this->simulation_max_rows; $i++){
			//使用機種側チェック
			$this->request->add("cur_printers" . $i,common::zen2han($this->request->get("cur_printers" . $i)));
			$this->request->add("cur_papers" . $i,common::zen2han($this->request->get("cur_papers" . $i)));
			
			//台数とADVの何れか入力されていれば、チェックを行う
			if($this->request->get("cur_printers" . $i) != "" || $this->request->get("cur_papers" . $i) != ""){
				if($this->request->get("cur_seq_no" . $i) == ""){
					$errmsg .= $i . "行目の使用機種が選択されていません。<br>";
				}else{
					if(!preg_match("/^[0-9]{1,}$/", $this->request->get("cur_printers" . $i))){
						$errmsg .= "使用機種" . $i .  "機種目の台数は半角数字で入力して下さい。<br>";
					}elseif(strlen($this->request->get("cur_printers" . $i)) > 4){
						$errmsg .= "使用機種" . $i .  "機種目の台数の桁数がオーバーしています。<br>";
					}
					if(!preg_match("/^[0-9]{1,}$/", $this->request->get("cur_papers" . $i))){
						$errmsg .= "使用機種" . $i .  "機種目のADVは半角数字で入力して下さい。<br>";
					}else{
						//機種IDで機種の最大、最小ADVを取得
						$prodadv = $this->get_productadv($this->request->get("cur_seq_no" . $i));
						
						if( $this->request->get("cur_ecomode" . $i) == "0" ){
							//省エネモードがOFFの場合
							if( $this->request->get("cur_papers" . $i) < $prodadv[0]["eco_off_minadv"] || 
								$this->request->get("cur_papers" . $i) > $prodadv[0]["eco_off_maxadv"] ){
								
								$errmsg .= "使用機種" . $i . "機種目のADVは" . number_format($prodadv[0]["eco_off_minadv"]) . " ～ " . number_format($prodadv[0]["eco_off_maxadv"]) . "枚の範囲で入力して下さい。<br>";
							}
						}else{
							//省エネモードがONの場合
							if( $this->request->get("cur_papers" . $i) < $prodadv[0]["eco_on_minadv"] || 
								$this->request->get("cur_papers" . $i) > $prodadv[0]["eco_on_maxadv"] ){
								
								$errmsg .= "使用機種" . $i . "機種目のADVは" . number_format($prodadv[0]["eco_on_minadv"]) . " ～ " . number_format($prodadv[0]["eco_on_maxadv"]) . "枚の範囲で入力して下さい。<br>";
							}
						}
						
					}
				}
			}
			
			
			//提案機種側チェック
			$this->request->add("new_printers" . $i, common::zen2han($this->request->get("new_printers" . $i)));
			$this->request->add("new_papers" . $i, common::zen2han($this->request->get("new_papers" . $i)));
		
			//台数とADVの何れか入力されていれば、チェックを行う
			if($this->request->get("new_printers" . $i) != "" || $this->request->get("new_papers" . $i) != ""){
				if($this->request->get("new_seq_no" . $i)==""){
					$errmsg .= $i . "行目の提案機種が選択されていません。<br>";
				}else{
					if(!preg_match("/^[0-9]{1,}$/", $this->request->get("new_printers" . $i))){
						$errmsg .= "提案機種" . $i . "機種目の台数は半角数字で入力して下さい。<br>";
					}elseif(strlen($this->request->get("new_printers" . $i)) > 4){
						$errmsg .= "提案機種" . $i .  "機種目の台数の桁数がオーバーしています。<br>";
					}
					
					if(!preg_match("/^[0-9]{1,}$/", $this->request->get("new_papers" . $i))){
						$errmsg .= "提案機種" . $i . "機種目のADVは半角数字で入力して下さい。<br>";
					}else{
						
						//機種IDで機種の最大、最小ADVを取得
						$prodadv = $this->get_productadv($this->request->get("new_seq_no" . $i));
						
						if( $this->request->get("new_ecomode" . $i) == "0" ){
							//省エネモードがOFFの場合
							if( $this->request->get("new_papers" . $i) < $prodadv[0]["eco_off_minadv"] || 
								$this->request->get("new_papers" . $i) > $prodadv[0]["eco_off_maxadv"] ){
								
								$errmsg .= "提案機種" . $i . "機種目のADVは" . number_format($prodadv[0]["eco_off_minadv"]) . " ～ " . number_format($prodadv[0]["eco_off_maxadv"]) . "枚の範囲で入力して下さい。<br>";
							}
						}else{
							//省エネモードがONの場合
							if( $this->request->get("new_papers" . $i) < $prodadv[0]["eco_on_minadv"] || 
								$this->request->get("new_papers" . $i) > $prodadv[0]["eco_on_maxadv"] ){
								
								$errmsg .= "提案機種" . $i . "機種目のADVは" . number_format($prodadv[0]["eco_on_minadv"]) . " ～ " . number_format($prodadv[0]["eco_on_maxadv"]) . "枚の範囲で入力して下さい。<br>";
							}
						}
					}
					
					
					
				}
			}
			
		}
		$this->result->add("err", $errmsg);
		if($errmsg != ""){
			return false;
		}else{
			return true;
		}
	}
	
	
	private function getproductdata($seq_no){
		$sql = "select
					product_name,
					power_flg,
					power_per_paper,
					eco_on_pwr_p_job,
					eco_off_pwr_p_job,
					eco_on_last_time,
					eco_off_last_time,
					eco_on_last_power,
					eco_off_last_power,
					sleep_power
				from
					mst_product
				where
					seq_no = '" . common::sqlescape($seq_no) . "'";

		return $this->db->selectdata($sql);
	}
	
	
	
	
	private function getproductsinfo(){
		//使用機種と提案機種を統合のため、product_kbn(機種区分)をなくしたが、
		//画面の修正箇所を抑えるため、ダミーで機種区分を設定して、同じ機種データを2件取得する
		/*
		$sql = "select
					seq_no,product_name,
					'index' || maker_cd || prod_type_cd || color_kbn || product_kbn as list_name
				from
					mst_product
				order by
					area_cd,maker_cd,prod_type_cd,color_kbn,product_kbn,upper(product_name)";
		*/
		
		$sql = "select
					area_cd,maker_cd,prod_type_cd,color_kbn,'0' as product_kbn,upper(product_name) as name_for_sort,
					seq_no,product_name,
					'index' || maker_cd || prod_type_cd || color_kbn || '0' as list_name
				from
					mst_product
				union
				select
					area_cd,maker_cd,prod_type_cd,color_kbn,'1' as product_kbn,upper(product_name) as name_for_sort,
					seq_no,product_name,
					'index' || maker_cd || prod_type_cd || color_kbn || '1' as list_name
				from
					mst_product
				order by
					area_cd,maker_cd,prod_type_cd,color_kbn,product_kbn,name_for_sort";
		
		
		
		$prod_data = $this->db->selectdata($sql);
		
		$list_name = "";
		$prodlist = "";
		$index = 0;
		
		for($i = 0; $i < count($prod_data); $i++){
			if($list_name <> $prod_data[$i]["list_name"]){
				if($prodlist <> ""){
					$this->result->add("prodlistname" . $index, $list_name);
					$this->result->add("prodlist" . $index, $prodlist);
				}
				$prodlist = "";
				$index++;
				$list_name = $prod_data[$i]["list_name"];
			}
			if($prodlist <> ""){
				$prodlist .= "\t";
			}
			$prodlist .= $prod_data[$i]["seq_no"] . "\t" . str_replace("'", "\\'", $prod_data[$i]["product_name"]);
		}

		if($prodlist <> ""){
			$this->result->add("prodlistname" . $index, $list_name);
			$this->result->add("prodlist" . $index, $prodlist);
		}
		
		
		$sql = "select
					seq_no,eco_off_flg,plug_off_flg
				from
					mst_product
				order by
					seq_no";
		$this->result->add("offflglist", $this->db->selectdata($sql));
	}
}
?>