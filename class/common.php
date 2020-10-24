<?php
class common{
	/***************************************************************************
	入力されたHTML要素などの特殊文字を単なる文字に変換(画面表示)
	****************************************************************************/	
	static function disp_encode($str){
		$checkarray = array("&" => "&amp;", "<" => "&lt;",">" => "&gt;"," " => "&nbsp;");
		
		foreach($checkarray as $key => $value){
			$str = str_replace($key,$value,$str);
		}
		return $str;
	}

	/***************************************************************************
	入力されたHTML要素などの特殊文字を単なる文字に変換(value表示)
	***************************************************************************/	
	static function value_encode($str){
		$checkarray=array("&"=>"&amp;","\""=>"&quot;","\\"=>"&#92;");
	
		foreach($checkarray as $key => $value){
			$str = str_replace($key,$value,$str);
		}
		return $str;
	}
	
	/***************************************************************************
	全角英数字を半角に変換する
	***************************************************************************/
	static function zen2han($str){
		return mb_convert_kana($str,"rn");
	}
	
	/***************************************************************************
	半角英数字を全角に変換する
	***************************************************************************/
	static function han2zen($str){
		return mb_convert_kana($str,"RN");
	}
	
	/***************************************************************************
	sqlのエスケープを行う
	***************************************************************************/
	static function sqlescape($str, $like = false){
		$str = str_replace("'", "''", $str);
		if($like){
			//like でSQLを発行する際は'%'と'_'もエスケープする
			$str = str_replace("\\", "\\\\\\\\", $str);	//like時：\ ---> \\\\
			$str = str_replace("%", "\\\\%", $str);
			$str = str_replace("_", "\\\\_", $str);
		}else{
			$str = str_replace("\\", "\\\\", $str);		//likeでない時:\ ---> \\
		}
		return $str;
		
	}
	
	static function dbfield($val, $nulldef=""){
		if(is_null($val)){
			return $nulldef;
		}else{
			return $val;
		}
	}
	
	static function createoptions($ar, $val, $disp, $def = ""){
		$rtn = "";
		if(!is_array($ar)){ return ""; }
		for($i=0; $i<count($ar); $i++){
			if($ar[$i][$val] == $def){
				$rtn .= "<option value=\"" . $ar[$i][$val] . "\" selected>" . $ar[$i][$disp] . "</option>\r\n";
			}else{
				$rtn .= "<option value=\"" . $ar[$i][$val] . "\">" . $ar[$i][$disp] . "</option>\r\n";
			}
		}
		return $rtn;
	}
	
	static function make_hash_v($val){
		$hash_v_key = $val.'8086';
		return base64_encode(md5($hash_v_key));
	}
}
?>