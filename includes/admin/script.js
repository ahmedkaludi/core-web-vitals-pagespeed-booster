function cwvpsbGetParamByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
jQuery( document ).ready(function($) {
$("#clear-css-cache").click(function(event){
		var selfobj = $(this);
		var cleantype = selfobj.attr('data-cleaningtype');
		if(!confirm('Are you sure to delete the "'+cleantype+' cache"?')){
			return false;
		}
		selfobj.parent('div').find('.clear-cache-msg').text(" Please wait...")
		var nonce = $(this).attr('data-security');		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'nonce': nonce, action: 'cwvpsb_clear_cached_css', 'cleaning': cleantype},
			success: function(response){
				if(response.status==200){
					selfobj.parent('div').find('.clear-cache-msg').text(" Cache Cleared")
				}else{
					selfobj.parent('div').find('.clear-cache-msg').text(response.msg)
				}
			}
		})
});	

$(".cwvpsb-tabs a").click(function(e){
		var href = $(this).attr("href");
		var currentTab = cwvpsbGetParamByName("tab",href);
		if(!currentTab){
			currentTab = "images";
		}                                            
		$(this).siblings().removeClass("nav-tab-active");
		$(this).addClass("nav-tab-active");
		$(".form-wrap").find(".cwvpsb-"+currentTab).siblings().hide();
		$(".form-wrap .cwvpsb-"+currentTab).show();
		window.history.pushState("", "", href);
		return false;
	});
	var need_convertFiles = {};
	var current_conversion_number = 0;
	 
		var data = {
			'action': 'list_files_to_convert',
	        'nonce' : $(this).attr('data-nonce'),
		};
		$.ajax({url: ajaxurl, type:'post', dataType: 'json', data: data,
			success: function(response){
					need_convertFiles = response.files
					html = '<button id="startListconversion" class="button button-primary" type="button">Bulk Convert to WebP</button><br/><br/>This tool will automatically convert your images in webp format and it will take some mintues please do not close this window or click the back button until all images converted<br/><br/>'+
						'<div class="log_convert_info"></div>';
					$('.bulkconverUpload').html( html );
					addconverter();
				
			}
		});
	 

	function addconverter(){
		$('#startListconversion').on('click', function(){
			$(this).attr("disabled", true);
			var total = need_convertFiles.length
			var start = 0; 
			var nonce = $(".bulk_convert_webp").attr('data-nonce')
			startConversion(nonce);
		})
	}
	function startConversion(nonce){
		var currentFile = need_convertFiles[current_conversion_number];
		var data = {'action': 'webvital_webp_convert_file',
			        'nonce_verify' : nonce
			    }
		if (typeof currentFile == 'undefined'){
			currentFile = 'All images are already WEBP';
		}	    
		$.ajax({url: ajaxurl, type:'post', dataType: 'json', data: data,
			success: function(response){
				if(response.status==200){
					$('.log_convert_info').append("File: "+currentFile + " => Converted <br/>" );
					current_conversion_number += 1;
					if(current_conversion_number<need_convertFiles.length){
						startConversion(nonce);
					}else{
						$('.log_convert_info').append("-------------------------------- <br/>" );
						$('.log_convert_info').append("Conversion completed Total "+total+" files are converted <br/>" );
					}
					var element = document.getElementsByClassName("bulkconverUpload")[0].parentNode;
        			element.scrollTop = element.scrollHeight;
				}else{
					$('.log_convert_info').append("File: "+need_convertFile + " => Failed <br/>" );
				}
			}
		})
	}
});