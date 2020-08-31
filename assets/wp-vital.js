(function(){
	setTimeout(function(){
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function(){
		    if (xhr.readyState === 4){
		        var responseData = JSON.parse(xhr.responseText);
		        if(responseData.status==200){
		        	console.log("CSS optimization successful");
		        }
		    }
		};

		xhr.open('GET', webvital.ajax_url+'?action=parse_style_css&nonce_verify='+webvital.security_nonce);
		xhr.send();
	},5000);
})