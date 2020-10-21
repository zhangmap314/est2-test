<?php
/*
機種メンテ機能クラス
*/
class ProductListController extends basecontroller{
	private $RPP = 20;	//1頁20件表示する
	
	public function __construct($_request_, $_session_, $_result_){
		parent::__construct($_request_, $_session_, $_result_);
	}
	public function __destruct(){
		parent::__destruct();
	}
	
	public function execute(){
		switch($this->request->get("action")) {
			case "search":
				$this->savesearchkey(true);
				$this->createsrchsql();
				$this->showpage();
				$this->accesslog("機種メンテナンス", "検索");
				break;
			case "research":
				$this->showpage();
				$this->accesslog("機種メンテナンス", "再表示");
				break;
			case "downloadcsv":
				$this->downloadcsv();
				$this->accesslog("機種メンテナンス", "CSVダウンロード");
				break;
			case "delete":
				$this->deletedata();
				$this->accesslog("機種メンテナンス", "削除");
				$this->showpage(true);
				break;
			case "moveprevious":		//前頁へ
				$this->request->add("currentpage", $this->request->get("currentpage", 2) - 1);
				$this->showpage();
				$this->accesslog("機種メンテナンス", "前頁へ");
				break;
			case "movenext":			//次頁へ
				$this->request->add("currentpage", $this->request->get("currentpage", 0) + 1);
				$this->showpage();
				$this->accesslog("機種メンテナンス", "次頁へ");
				break;
			case "doupload":
				$this->updatemst();
				$this->accesslog("機種メンテナンス", "機種情報一括更新");
				break;
			default:
				$this->savesearchkey(false);
				
		}
		
		$sql = "select maker_cd,maker_name from mst_maker order by sort_key ";
		$this->result->add("makerlist", $this->db->selectdata($sql));
	
		$sql = "select prod_type_cd,prod_type_name from mst_prod_type order by sort_key ";
		$this->result->add("prodtypelist", $this->db->selectdata($sql));
	}
	
	private function savesearchkey($withrequest){
		$this->session->add("maker_cd", "");
		$this->session->add("prod_type_cd", "");
		$this->session->add("speed_kbn", "");
		$this->session->add("product_name", "");
		//$this->session->add("product_kbn", "");
		$this->session->add("color_kbn", "");
		$this->session->add("sort_key1", "");
		$this->session->add("sort_kbn1", "");
		$this->session->add("sort_key2", "");
		$this->session->add("sort_kbn2", "");
		if($withrequest){
			$this->session->add("maker_cd", $this->request->get("maker_cd"));
			$this->session->add("prod_type_cd", $this->request->get("prod_type_cd"));
			$this->session->add("speed_kbn", $this->request->get("speed_kbn"));
			$this->session->add("product_name", $this->request->get("product_name"));
			//$this->session->add("product_kbn", $this->request->get("product_kbn"));
			$this->session->add("color_kbn", $this->request->get("color_kbn"));
			$this->session->add("sort_key1", $this->request->get("sort_key1"));
			$this->session->add("sort_kbn1", $this->request->get("sort_kbn1"));
			$this->session->add("sort_key2", $this->request->get("sort_key2"));
			$this->session->add("sort_kbn2", $this->request->get("sort_kbn2"));
		}
	}
	
	private function updatemst(){
		//$colscnt = 22;	//横の項目数
		$colscnt = 21;	//横の項目数
		/*
		メーカーコード
		製品種別コード
		モノクロ／カラー
		機種区分	←使用機種と提案機種を統合のため、「機種区分」を廃止したが、プログラム変更箇所を抑えるため、とりあえず残す。CSVデータにはダミー項目がある
		機種名
		省エネOFF可
		プラグOFF可
		発売年月
		製品速度分類
		推定スリープ電力の有無
		JOB当り面数
		省エネONJOB単位電力量
		省エネOFFJOB単位電力量
		省エネON週間最終時間
		省エネOFF週間最終時間
		省エネON週間最終電力量
		省エネOFF週間最終電力量
		スリープ電力
		省エネON時最小ADV
		省エネON時最大ADV
		省エネOFF時最小ADV
		省エネOFF時最大ADV
		*/

		
		//mb_strrpos($_FILES['productdata']['name'],".")
		
		//application/vnd.ms-excel
		//text/plain
		
		$this->result->add("err","");
		
		if( trim($_FILES['productdata']['name']) == "" || 
			!isset($_FILES['productdata']['tmp_name'])){
			$this->result->add("err", "アップロードファイルを指定してください。");
			return false;
		}
		
		//アップロードされたファイルがcsvかをチェック
		if(strtoupper(substr($_FILES['productdata']['name'],-4,4)) != ".CSV"){
			
			$this->result->add("err", "機種データファイルを指定してください。");
			return false;
		}
		
		if(!file_exists($_FILES["productdata"]["tmp_name"]) || !is_readable($_FILES["productdata"]["tmp_name"])){
			$this->result->add("err", "機種データファイルがアップロード出来ませんでした。");
			return false;
		}
		
		//アップロードされたファイルを読み込む
		$filebuf = file_get_contents($_FILES["productdata"]["tmp_name"]);
		//文字コードをUTF-8に変換する
		$filebuf = mb_convert_encoding($filebuf,"UTF-8", "UTF-8,sjis,euc-jp");
		//コード変換したデータを再度書き戻す
		file_put_contents($_FILES["productdata"]["tmp_name"], $filebuf);
		
		
		$datarows = array();
		$rowidx = 1;
		$handle = fopen($_FILES["productdata"]["tmp_name"], "r");
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		    if(count($data) != $colscnt){
				$this->result->add("err", $this->result->get("err") . $rowidx . "行目の項目数が異なります。<br>" );
			}
			$datarows[] = $data;
			$rowidx++;
		}
		fclose($handle);
		
		if($this->result->get("err") != ""){
			return false;
		}
		
		$fields = array(
						"area_cd" => "01",
						"maker_cd" => "",
						"prod_type_cd" => "",
						"color_kbn" => "",
						//"product_kbn" => "",
						"product_name" => "",
						"eco_off_flg" => "",
						"plug_off_flg" => "",
						"release_month" => "",
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
						"regist_time" => "",
						"update_time" => date("Y/m/d H:i:s"),
						"update_uid" => $this->session->get("user_id"),
						"eco_on_minadv" => "",
						"eco_on_maxadv" => "",
						"eco_off_minadv" => "",
						"eco_off_maxadv" => ""
				);
		for($rowidx = 0; $rowidx < count($datarows); $rowidx++){
			$this->checkdata($datarows[$rowidx], $rowidx + 1);
		}
		if($this->result->get("err") != ""){
			return false;
		}
		
		$this->db->begintrans();
		for($rowidx = 0; $rowidx < count($datarows); $rowidx++){
			$onerow = $datarows[$rowidx];
			//if(!$this->get_regist_time($onerow[0], $onerow[1], $onerow[2], $onerow[3], $onerow[4])){
			if(!$this->get_regist_time($onerow[0], $onerow[1], $onerow[2], $onerow[3])){
				$this->result->add("err", $rowidx + 1 . "行目で更新エラーが発生しました。<br>エラー内容：" . $this->db->getlasterror() );
				$this->db->rollback();
				return false;
			}
			$fields["maker_cd"] = $onerow[0];
			$fields["prod_type_cd"] = $onerow[1];
			$fields["color_kbn"] = $onerow[2];
			//$fields["product_kbn"] = $onerow[3];
			$fields["product_name"] = $onerow[3];
			$fields["eco_off_flg"] = $onerow[4];
			$fields["plug_off_flg"] = $onerow[5];
			$fields["release_month"] = $onerow[6];
			$fields["speed_kbn"] = $onerow[7];
			$fields["power_flg"] = $onerow[8];
			$fields["power_per_paper"] = $onerow[9];
			$fields["eco_on_pwr_p_job"] = $onerow[10];
			$fields["eco_off_pwr_p_job"] = $onerow[11];
			$fields["eco_on_last_time"] = $onerow[12];
			$fields["eco_off_last_time"] = $onerow[13];
			$fields["eco_on_last_power"] = $onerow[14];
			$fields["eco_off_last_power"] = $onerow[15];
			$fields["sleep_power"] = $onerow[16];
			$fields["eco_on_minadv"] = $onerow[17];
			$fields["eco_on_maxadv"] = $onerow[18];
			$fields["eco_off_minadv"] = $onerow[19];
			$fields["eco_off_maxadv"] = $onerow[20];
			
			
			if($this->result->get("regist_time") == ""){
				$fields["regist_time"] = $fields["update_time"];
			}else{
				$fields["regist_time"] = $this->result->get("regist_time");
			}
			
			if(!$this->db->insert("mst_product", $fields , false)){
				$this->result->add("err", $rowidx + 1 . "行目で更新エラーが発生しました。<br>エラー内容：" . $this->db->getlasterror() );
				$this->db->rollback();
				return false;
			}
		}
		$this->db->commit();
		$this->result->add("JSAlert", "機種データ一括更新処理が正常終了しました。");
	}
	
	//private function get_regist_time($maker_cd, $prod_type_cd, $color_kbn, $product_kbn, $product_name, $area_cd = "01"){
	private function get_regist_time($maker_cd, $prod_type_cd, $color_kbn, $product_name, $area_cd = "01"){
		/*
		$where = "  area_cd = '" . common::sqlescape($area_cd) . "' 
					and maker_cd = '" . common::sqlescape($maker_cd) . "'
					and prod_type_cd = '" . common::sqlescape($prod_type_cd) . "'
					and color_kbn = '" . common::sqlescape($color_kbn) . "'
					and product_kbn = '" . common::sqlescape($product_kbn) . "'
					and product_name = '" . common::sqlescape($product_name) . "' ";
		*/
		$where = "  area_cd = '" . common::sqlescape($area_cd) . "' 
					and maker_cd = '" . common::sqlescape($maker_cd) . "'
					and prod_type_cd = '" . common::sqlescape($prod_type_cd) . "'
					and color_kbn = '" . common::sqlescape($color_kbn) . "'
					and product_name = '" . common::sqlescape($product_name) . "' ";
		
		$sql = "select 
					to_char(regist_time, 'YYYY/MM/DD HH24:MI:SS') as regist_time
				from
					mst_product
				where " . $where;
		
		$data = $this->db->selectdata($sql);
		
		$this->result->add("regist_time", "");
		
		if(count($data) == 0){
			return true;
		}
		$sql = "delete from mst_product where " . $where;
		
		if($this->db->executesql($sql, false)){
			$this->result->add("regist_time", $data[0]["regist_time"]);
			return true;
		}
		return false;
	}
	
	private function checkdata($proddata, $rowidx){
		/*
			0	：	maker_cd
			1	：	prod_type_cd
			2	：	color_kbn
			3	：	product_kbn		←使用機種と提案機種を統合のため、廃止したが改修範囲を抑えるため、CSVレイアウトは変更しない
			3	：	product_name
			4	：	eco_off_flg
			5	：	plug_off_flg
			6	：	release_month
			7	：	speed_kbn
			8	：	power_flg
			9	：	power_per_paper
			10	：	eco_on_pwr_p_job
			11	：	eco_off_pwr_p_job
			12	：	eco_on_last_time
			13	：	eco_off_last_time
			14	：	eco_on_last_power
			15	：	eco_off_last_power
			16	：	sleep_power
			17	:	eco_on_minadv
			18	:	eco_on_maxadv
			19	:	eco_off_minadv
			20	:	eco_off_maxadv
		*/
		$proddata[0] = trim($proddata[0]);
		if($proddata[0] == "" || !is_numeric($proddata[0]) ){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目のメーカーコードが不正です。<br>");
		}else{
			$data = $this->db->selectdata("select count(1) as cnt from mst_maker where maker_cd ='" . common::sqlescape($proddata[0]) . "'");
			if($data[0]["cnt"] == 0 ){
				$this->result->add("err", $this->result->get("err") . $rowidx . "行目のメーカーコードがマスタに存在しません。<br>");
			}
		}
		$proddata[1] = trim($proddata[1]);
		if($proddata[1] == "" || !is_numeric($proddata[1]) ){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の製品種別コードが不正です。<br>");
		}else{
			$data = $this->db->selectdata("select count(1) as cnt from mst_prod_type where prod_type_cd ='" . common::sqlescape($proddata[1]) . "'");
			if($data[0]["cnt"] == 0 ){
				$this->result->add("err", $this->result->get("err") . $rowidx . "行目の製品種別コードがマスタに存在しません。<br>");
			}
		}
		if($proddata[2] != "0" && $proddata[2] != "1"){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目のモノクロ／カラー区分が不正です。<br>");
		}
		
		/*
		if($proddata[3] != "0" && $proddata[3] != "1"){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の機種区分が不正です。<br>");
		}
		*/
		
		if($proddata[3] == "" || mb_strlen($proddata[3]) > 50){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の機種名が不正です。<br>");
		}
		if($proddata[4] != "0" && $proddata[4] != "1"){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネOFF可が不正です。<br>");
		}
		if($proddata[5] != "0" && $proddata[5] != "1"){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目のプラグOFF可が不正です。<br>");
		}
		if($proddata[6] != "" ){
			if(mb_strlen($proddata[6]) != "6" || !is_numeric($proddata[6]) ){
				$this->result->add("err", $this->result->get("err") . $rowidx . "行目の発売年月が不正です。<br>");
			}else if(!checkdate( mb_substr($proddata[6], 4, 2), 1, mb_substr($proddata[6], 0, 4) ) ){
				$this->result->add("err", $this->result->get("err") . $rowidx . "行目の発売年月が不正です。<br>");
			}
		}
		if($proddata[7] != "1" && $proddata[7] != "2" && $proddata[7] != "3"){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の速度分類が不正です。<br>");
		}
		if($proddata[8] != "0" && $proddata[8] != "1"){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の推定スリープ電力の有無が不正です。<br>");
		}
		if(!is_numeric($proddata[9]) || mb_strlen($proddata[9]) > 4){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目のジョブ当り面数が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[10])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネONジョブ単位電力量が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[11])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネOFFジョブ単位電力量が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[12])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネON週間最終時間が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[13])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネOFF週間最終時間が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[14])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネON週間最終電力量が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[15])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネOFF週間最終電力量が不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[16])){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目のスリープ電力が不正です。<br>");
		}
		
		if(!$this->my_is_numeric($proddata[17], 8)){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネON時最小ADVが不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[18], 8)){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネON時最大ADVが不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[19], 8)){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネOFF時最小ADVが不正です。<br>");
		}
		if(!$this->my_is_numeric($proddata[20], 8)){
			$this->result->add("err", $this->result->get("err") . $rowidx . "行目の省エネOFF時最大ADVが不正です。<br>");
		}
		
	}
	
	private function my_is_numeric($num, $keta = 5){
		if($num == "" ||  !is_numeric($num)){
			return false;
		}
		$ar = mb_split("\.", $num);

		if(mb_strlen($ar[0]) > $keta){
			return false;
		}
		
		if(mb_strpos($num,".")===false){
			//小数点がない場合
			
		}else{
			if(mb_strlen($ar[1]) > 5){
				return false;
			}
		}
		return true;
	}
	
	/*検索*/
	private function search($offset = 0, $limit = 0){
		if($limit == 0){
			$limit = $this->RPP;
		}
		$sql = $this->session->get("search_select") . 
				" where " . $this->session->get("search_cond") .
				" order by " . $this->session->get("search_orderby");
		
		$sql .= " offset " .$offset . " limit " . $limit;

		$productdata = $this->db->selectdata($sql);
		if(count($productdata) == 0){
			$this->result->add("productdata",false);
			$this->result->add("JSAlert", "対象データがありません。");
		}else{
			$this->result->add("productdata",$productdata);
		}
	}
	

	/*データ削除*/
	private function deletedata(){
		//該当するデータがなければ、削除不可
		$selsql = "select seq_no from mst_product where seq_no = '" . common::sqlescape($this->request->get("seq_no")) . "' ";
		$count = $this->db->selectdata($selsql);
		if(count($count)==0){
			$this->result->add("JSAlert","そのデータは既に削除されています。");
		}else{
			$strsql = "delete from 
							mst_product 
						where 
							seq_no = '" . common::sqlescape($this->request->get("seq_no")) . "' "; 
							
			if($this->db->executesql($strsql)){
				$this->result->add("JSAlert","削除しました。");
			}else{
				$err = $this->db->getlasterror();
				$err = "削除エラーが発生しました。\\nエラーコード:" . $err["code"] . "\\n" .
						"エラーメッセージ:" . $err["message"];
				$this->result->add("JSAlert", $err);
			}
		}
	}
	
	/*ダウンロード*/
	private function downloadcsv(){
		
		$sql = "select
					prod.maker_cd || ':' || maker.maker_name as maker_name,
					prod.prod_type_cd || ':' || prod_type.prod_type_name as prod_type_name,
					case when color_kbn = '0' then '0:モノクロ' else '1:カラー' end as color_kbn_name,
					prod.product_name,
					case when prod.eco_off_flg = '1' then '1:可' else '0:不可' end as eco_off_flg,
					case when prod.plug_off_flg = '1' then '1:可' else '0:不可' end as plug_off_flg,
					prod.release_month,
					case
					when prod.speed_kbn = '1' then '1:25以下'
					when prod.speed_kbn = '2' then '2:26～40' 
					when prod.speed_kbn = '3' then '3:41以上' end as speed_kbn_name,
					case when prod.power_flg = '1' then '1:有' else '0:無' end as power_flg,
					prod.power_per_paper,
					prod.eco_on_pwr_p_job,
					prod.eco_off_pwr_p_job,
					prod.eco_on_last_time,
					prod.eco_off_last_time,
					prod.eco_on_last_power,
					prod.eco_off_last_power,
					prod.sleep_power,
					prod.eco_on_minadv,
					prod.eco_on_maxadv,
					prod.eco_off_minadv,
					prod.eco_off_maxadv,
					to_char(prod.regist_time, 'YYYY/MM/DD HH24:MI:SS') as regist_time
				from
					mst_product prod
				left join
					mst_maker maker
				on
					prod.maker_cd = to_char(maker.maker_cd,'FM99')
				left join
					mst_prod_type prod_type
				on
					prod.prod_type_cd = to_char(prod_type.prod_type_cd,'FM9') ";
		$sql .= " where " . $this->session->get("search_cond");
		$sql .= " order by " . $this->session->get("search_orderby");
		
		$data = $this->db->selectdata($sql);
		
		if(count($data) == 0 ){
			$this->result->add("JSAlert", "ダウンロードデータがありません。");
			return false;
		}
		
		
		//$contents = "\"メーカー名\",\"製品種別\",\"モノクロ/カラー\",\"機種区分\",\"機種名\",\"省エネOFF可\",\"プラグOFF可\",\"発売年月\",\"製品速度分類\",\"推定スリープ電力の有無\",\"JOB当り面数\",\"省エネONJOB単位電力量\",\"省エネOFFJOB単位電力量\",\"省エネON週間最終時間\",\"省エネOFF週間最終時間\",\"省エネON週間最終電力量\",\"省エネOFF週間最終電力量\",\"スリープ電力\",\"省エネON最小ADV\",\"省エネON最大ADV\",\"省エネOFF最小ADV\",\"省エネOFF最大ADV\",\"登録日時\"\r\n";
		$contents = "\"メーカー名\",\"製品種別\",\"モノクロ/カラー\",\"機種名\",\"省エネOFF可\",\"プラグOFF可\",\"発売年月\",\"製品速度分類\",\"推定スリープ電力の有無\",\"JOB当り面数\",\"省エネONJOB単位電力量\",\"省エネOFFJOB単位電力量\",\"省エネON週間最終時間\",\"省エネOFF週間最終時間\",\"省エネON週間最終電力量\",\"省エネOFF週間最終電力量\",\"スリープ電力\",\"省エネON最小ADV\",\"省エネON最大ADV\",\"省エネOFF最小ADV\",\"省エネOFF最大ADV\",\"登録日時\"\r\n";
		for($c = 0; $c < count($data); $c++){
			$contents .= "\"" . $data[$c]["maker_name"] . "\",";
			$contents .= "\"" . $data[$c]["prod_type_name"] . "\",";
			$contents .= "\"" . $data[$c]["color_kbn_name"] . "\",";
			//$contents .= "\"" . $data[$c]["product_kbn_name"] . "\",";
			$contents .= "\"" . $data[$c]["product_name"] . "\",";
			$contents .= "\"" . $data[$c]["eco_off_flg"] . "\",";
			$contents .= "\"" . $data[$c]["plug_off_flg"] . "\",";
			$contents .= "\"" . $data[$c]["release_month"] . "\",";
			$contents .= "\"" . $data[$c]["speed_kbn_name"] . "\",";
			$contents .= "\"" . $data[$c]["power_flg"] . "\",";
			$contents .= "\"" . $data[$c]["power_per_paper"] . "\",";
			$contents .= "\"" . $data[$c]["eco_on_pwr_p_job"] . "\",";
			$contents .= "\"" . $data[$c]["eco_off_pwr_p_job"] . "\",";
			$contents .= "\"" . $data[$c]["eco_on_last_time"] . "\",";
			$contents .= "\"" . $data[$c]["eco_off_last_time"] . "\",";
			$contents .= "\"" . $data[$c]["eco_on_last_power"] . "\",";
			$contents .= "\"" . $data[$c]["eco_off_last_power"] . "\",";
			$contents .= "\"" . $data[$c]["sleep_power"] . "\",";
			$contents .= "\"" . $data[$c]["eco_on_minadv"] . "\",";
			$contents .= "\"" . $data[$c]["eco_on_maxadv"] . "\",";
			$contents .= "\"" . $data[$c]["eco_off_minadv"] . "\",";
			$contents .= "\"" . $data[$c]["eco_off_maxadv"] . "\",";
			
			$contents .= "\"" . $data[$c]["regist_time"] . "\"\r\n";
			
		}
		$contents = mb_convert_encoding($contents,"SJIS", "UTF-8");
		
		
		// ダウンロード用HTTPヘッダ 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: Cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: Cache"); 
		header("Content-Description: File Transfer");
		
		header("Content-Type: application/x-csv");
		header("Content-Disposition: attachment; filename=\"ProductData.csv\"");
		header("Content-length: " . strlen($contents));
		
		print $contents;
	}
	
	private function showpage($afterdel = false){
		$strsql = "select
						count(1) as rows
					from
						mst_product prod
					where " . $this->session->get("search_cond");
		
		$data = $this->db->selectdata($strsql);
		
		$this->result->add("maxpage", (int)($data[0]["rows"]/$this->RPP));
		if($data[0]["rows"] % $this->RPP > 0){
			$this->result->add("maxpage", $this->result->get("maxpage") + 1);
		}
		//削除の場合(頁に1件しか表示されていない場合、削除後は1頁前を表示)
		if($afterdel){
			if( $this->request->get("currentpage", 1) > $this->result->get("maxpage") ){
				$this->request->add("currentpage", $this->result->get("maxpage") );
			}
		}
		
		$this->search(($this->request->get("currentpage", 1) -1) * $this->RPP);
	}
	
	
	
	
	private function createsrchsql(){
		$sql = "";
		
		$sql = "select
					prod.seq_no,
					prod.product_name,
					prod.maker_cd,
					maker.maker_name,
					prod.prod_type_cd,
					prod_type.prod_type_name,
					prod.color_kbn,
					case when color_kbn = '0' then 'モノクロ' else 'カラー' end as color_kbn_name,
					prod.speed_kbn,
					case
					when prod.speed_kbn = '1' then '25以下'
					when prod.speed_kbn = '2' then '26～40' 
					when prod.speed_kbn = '3' then '41以上' end as speed_kbn_name,
					to_char(prod.regist_time, 'yyyy/mm/dd') as regist_time,
					to_char(prod.update_time, 'yyyy/mm/dd') as update_time
				from
					mst_product prod
				left join
					mst_maker maker
				on
					prod.maker_cd = to_char(maker.maker_cd,'FM99')
				left join
					mst_prod_type prod_type
				on
					prod.prod_type_cd = to_char(prod_type.prod_type_cd,'FM9') ";
		
		$this->session->add("search_select", $sql);
		
		$sql = "";
		
		
		if($this->request->get("maker_cd") != ""){
			$sql .= " prod.maker_cd = '" . common::sqlescape($this->request->get("maker_cd")) ."' ";
			$sql .= " and ";
		}
		if($this->request->get("prod_type_cd") != ""){
			$sql .= " prod.prod_type_cd = '" . common::sqlescape($this->request->get("prod_type_cd")) ."' ";
			$sql .= " and ";
		}
		if($this->request->get("speed_kbn") != ""){
			$sql .= " prod.speed_kbn = '" . common::sqlescape($this->request->get("speed_kbn")) ."' ";
			$sql .= " and ";
		}
		/*
		if($this->request->get("product_kbn") != ""){
			$sql .= " prod.product_kbn = '" . common::sqlescape($this->request->get("product_kbn")) ."' ";
			$sql .= " and ";
		}
		*/
		
		if($this->request->get("color_kbn") != ""){
			$sql .= " prod.color_kbn = '" . common::sqlescape($this->request->get("color_kbn")) ."' ";
			$sql .= " and ";
		}
		
		$sql .= " upper(prod.product_name) like '%" . common::sqlescape(mb_strtoupper($this->request->get("product_name")), true) ."%' ";
		
		$this->session->add("search_cond", $sql);
		
		$sql = "";
		
		switch($this->request->get("sort_key1")) {
			case "2":
				$sql .= "prod.maker_cd";
				break;
			case "3":
				$sql .= "prod.prod_type_cd";
				break;
			
			case "5":
				$sql .= "color_kbn";
				break;
			case "6":
				$sql .= "speed_kbn";
				break;
			case "7":
				$sql .= "regist_time";
				break;
			case "8":
				$sql .= "update_time";
				break;
			default:
				$sql .= "upper(product_name) ";
				break;
		}
		if($this->request->get("sort_kbn1") == "2"){
			$sql .= " desc, ";
		}else{
			$sql .= " asc, ";
		}
		
		switch($this->request->get("sort_key2")) {
			case "2":
				$sql .= "maker_cd";
				break;
			case "3":
				$sql .= "prod_type_cd";
				break;
			
			case "5":
				$sql .= "color_kbn";
				break;
			case "6":
				$sql .= "speed_kbn";
				break;
			case "7":
				$sql .= "regist_time";
				break;
			case "8":
				$sql .= "update_time";
				break;
			default:
				$sql .= "upper(product_name) ";
				break;
		}
		if($this->request->get("sort_kbn2") == "2"){
			$sql .= " desc ";
		}else{
			$sql .= " asc ";
		}
		
		$this->session->add("search_orderby", $sql);
	}
}
?>