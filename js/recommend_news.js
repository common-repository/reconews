
window.onload = function(){
	
	var xmlHttp = new XMLHttp(recommend_news_data["url"], {action: "recommend_news_action", post_id: recommend_news_data["post_id"], nonce: recommend_news_data["nonce"]}, function(callback){
		
		
		
	});
	
	
}

var XMLHttp = function(url, data, callback){
	
	var xhr = this.requestCreate();
	xhr.onreadystatechange = function(){
		
		//console.log("XMLHttp readyState = " + xhr.readyState + " status = " + xhr.status);
		
		switch(xhr.readyState){
		
		case 4:
		
			if(xhr.status == 0){
			
				//console.log("XMLHttp その他の応答:" + xhr.status);
				callback({status: "ERROR"});
				
			}else{
				
				if((200 <= xhr.status && xhr.status < 300) || (xhr.status == 304)){
					
					//console.log("XMLHttp 受信:" + xhr.responseText);
					
				}else{
					
					//console.log("その他の応答:" + xhr.status);
					callback({status: "ERROR"});
					
				}
				
			}
				
			break;
					
		}
		
	};
	
	xhr.onload = function(e){
		
		var responseJson = JSON.parse(xhr.responseText);
		
		callback(responseJson);
		
	}
	
	xhr.open("POST", url);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(this.encodeHTMLForm(data));
	
}

XMLHttp.prototype.requestCreate = function(){
	
	try{
		
		return new XMLHttpRequest();
	
	}catch(e){}

	return null;

}

XMLHttp.prototype.encodeHTMLForm = function(data){
	
    var params = [];

    for( var name in data )
    {
        var value = data[ name ];
        var param = encodeURIComponent( name ) + '=' + encodeURIComponent( value );

        params.push( param );
    }

    return params.join( '&' ).replace( /%20/g, '+' );
	
}