function KeyChk(){
	//Enterキー押下時
	//IE対応
	if(event.keyCode == 13){
	
		if (!document.activeElement.type) {//activeElementがない場合は、Enterキーは無効
			return(false);
		}
		
		//フォーカスがボタンにあるときは、Enterキー有効
		if(document.activeElement.type == "submit" || document.activeElement.type == "button"){
			return(true);
		}else{
			//Enterキーによる送信を止める
			return(false);
		}
	} else {
		return(true);
	}
}

function checkCTRLKey(e)
{
	var keycode,ctrl,alt;
	
	// Mozilla(Firefox, NN) and Opera 
	if (e != null) { 
		keycode = e.which; 
		ctrl    = typeof e.modifiers == 'undefined' ? e.ctrlKey : e.modifiers & Event.CONTROL_MASK; 
		alt     = typeof e.modifiers == 'undefined' ? e.altKey  : e.modifiers & Event.ALT_MASK; 
		if (ctrl || alt){
			return false;
		} else if(keycode == 122 || keycode == 123 || keycode == 116){
			//F11 F12 F5 禁止
			keycode = 0;
			return false;
		} else if(keycode == 13){
			//Enterキー押下時
			if (document.activeElement.type==null) {//activeElementがない場合は、Enterキーは無効
				keycode = 0;
				return false;
			}
			//フォーカスがボタンにあるときは、Enterキー有効
			if(document.activeElement.type == "submit" || document.activeElement.type == "button"){
				return true;
			}else{
				//Enterキーによる送信を止める
				keycode = 0;
				return false;
			}
		} else {
			return(true);
		}
	// Internet Explorer 
	} else { 
		if (event.ctrlKey || event.altKey){
			return false;
		}else if(event.keyCode == 122 || event.keyCode == 123 || event.keyCode == 116){
			//F11 F12 F5 禁止
			event.keyCode = 0;
			return false;
		}
	} 
}
window.document.onkeydown = checkCTRLKey;

if (document.all) {
	document.onkeypress = KeyChk;
}
