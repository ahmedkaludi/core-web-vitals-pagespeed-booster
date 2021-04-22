(function($){
	$(".add_new_row_url").click(function(){
		var html = '<div class="ads_uri_row"><input type="input" name="webvitals_settings[list_of_urls][]" class="" value=""placeholder="Ads script url"><span style="cursor: pointer;" class="remove_url_row"><span class="dashicons dashicons-no-alt"></span></span></div>';
		$("#ads_url_wrapper").append(html);
		loadremover();
	});

	function loadremover(){
		$(".remove_url_row").click(function(){
			$(this).parents('.ads_uri_row').remove();
		})
	}
	loadremover();

	$("#web-vital-clear-cache, #clear-css-cache").click(function(event){
		var selfobj = $(this);
		var cleantype = selfobj.attr('data-cleaningtype');
		if(!confirm('Are you sure to delete the "'+cleantype+' cache"?')){
			return false;
		}
		selfobj.parent('div').find('.clear-cache-msg').text("please wait...")
		var nonce = $(this).attr('data-security');		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'nonce': nonce, action: 'parse_clear_cached_css', 'cleaning': cleantype},
			success: function(response){
				if(response.status==200){
					selfobj.parent('div').find('.clear-cache-msg').text("Cache cleared")
				}else{
					selfobj.parent('div').find('.clear-cache-msg').text(response.msg)
				}
			}
		})
	});
	var need_convertFiles = {};
	var current_conversion_number = 0;
	$(".bulk_convert_webp").click(function(event){
		//$('#bulk_convert_message').text("please wait...");
		tb_show('Bulk webp Conversion', '#TB_inline?inlineId=bulkconverUpload-wrap');

		var data = {
			'action': 'list_files_to_convert',
	        'nonce' : $(this).attr('data-nonce'),
		};
		$.ajax({url: ajaxurl, type:'post', dataType: 'json', data: data,
			success: function(response){
				if(response.status==200){
					need_convertFiles = response.files
					html = 'This tool will automatically convert your images in webp format<br/><br/><button id="startListconversion" class="button button-primary" type="button">Start conversion</button>'+
						'<div class="log_convert_info"></div>';
					$('.bulkconverUpload').html( html );
					addconverter();
				}else{
					$('.clear-cache-msg').text(response.msg)
				}
			}
		})
	});

	function addconverter(){
		$('#startListconversion').click(function(){
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
			        'nonce_verify' : nonce,
			        'filename': currentFile
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
})(jQuery);