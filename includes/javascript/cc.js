jQuery(document).ready(function(){
	if(cwvpb_ccdata.grab_cc_check!=1){
		jQuery.ajax({
			method:'post',
			url:cwvpb_ccdata.ajaxurl,
	        dataType: "json",
	        data:{
	        	action:"cc_call", 
	        	security_nonce:cwvpb_ccdata.cc_nonce,
	        	current_url:cwvpb_ccdata.current_url

	    		},
	        success:function(response){
	            console.log('ajaxurl called');  
	        }  
		})
	}
})