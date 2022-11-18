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
	 $(".child-opt-bulk").hide();
	 $('.image_optimization').change(function(){
        if($(this).is(':checked')){
            $(".child-opt").show();
        }else{
            $(".child-opt").hide();
            $(".child-opt-bulk").hide();
        }
    }).change();

 	$("select.webp_support").change(function(){
        var webp = $(this).children("option:selected").val();
        if(webp == 'manual'){
        	$(".child-opt-bulk").show();
        	$(".child-opt-bulk2").show();
        }else{
        	$(".child-opt-bulk").hide();
        	$(".child-opt-bulk2").hide();
        }
    });

    $('.js_optimization').change(function(){
        if($(this).is(':checked')){
            $(".child-opt").show();
        }else{
            $(".child-opt").hide();
        }
    }).change();
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



		
	jQuery("#table_page_cc_style_all").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "GET",
			url: ajaxurl+"?action=cwvpsb_showdetails_data&cwvpsb_security_nonce="+cwvpsb_localize_data.cwvpsb_security_nonce
		}
	});
	jQuery("#table_page_cc_style_completed").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "GET",
			url: ajaxurl+"?action=cwvpsb_showdetails_data_completed&cwvpsb_security_nonce="+cwvpsb_localize_data.cwvpsb_security_nonce
		}
	});
	jQuery("#table_page_cc_style_failed").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "GET",
			url: ajaxurl+"?action=cwvpsb_showdetails_data_failed&cwvpsb_security_nonce="+cwvpsb_localize_data.cwvpsb_security_nonce
		}
	});
	jQuery("#table_page_cc_style_queue").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "GET",
			url: ajaxurl+"?action=cwvpsb_showdetails_data_queue&cwvpsb_security_nonce="+cwvpsb_localize_data.cwvpsb_security_nonce
		}
	});
// tabs section for datatable starts here
	$('.cwvpb-global-container').hide();
	$('.cwvpb-global-container:first').show();
	$('#cwvpb-global-tabs a:first').addClass('cwvpb-global-selected');
	
	$('#cwvpb-global-tabs a').click(function(){
		var t = $(this).attr('data-id');
		
	  if(!$(this).hasClass('cwvpb-global-selected')){ 
		$('#cwvpb-global-tabs a').removeClass('cwvpb-global-selected');           
		$(this).addClass('cwvpb-global-selected');

		$('.cwvpb-global-container').hide();
		$('#'+t).show();
	 }
	});

// tabs section for datatable ends here

$(".cwvpbs-resend-urls").on("click", function(e){
	e.preventDefault();
	var current = $(this);
	current.addClass('updating-message');		
	$.ajax({
		url: ajaxurl,
		type:'post',
		dataType: 'json',
		data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce, 
				action: 'cwvpsb_resend_urls_for_cache'},
		success: function(response){
			current.removeClass('updating-message');		
			if(response.status){
				location.reload(true);
			}else{
				alert('something went wrong');
			}
		}
	})

})
	$(".cwvpbs-advance-toggle").on("click", function(e){
		e.preventDefault();
		$(".cwvpbs-advance-btn-div").toggleClass('cwvpb-display-none');		
	});

	$(document).on("click", ".cwvpb-resend-single-url", function(e) {
		e.preventDefault();
		
		var current = $(this);
		var url_id = $(this).attr('data-id');
		var d_section = $(this).attr('data-section');
		current.addClass('cwvpb-display-none');
		current.after('<span class="spinner is-active"></span>');		
		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce,
			action: 'cwvpsb_resend_single_url_for_cache',
			url_id: url_id
			},
			success: function(response){
				current.removeClass('updating-message');	
				if(response.status){
					
					if(d_section == 'all'){
						current.parent().parent().parent().find(".cwvpb-status-t").text('queue');						
						$(current).next('span').remove();
						current.remove();
					}
					if(d_section == 'failed'){
						current.parent().parent().parent().remove();
					}

				}else{
					current.removeClass('cwvpb-display-none');		
					alert('something went wrong');
				}
			}
		});

	}
	);

	function cwvpb_recheck_urls(current, page){
		var new_page = page;
		current.addClass('updating-message');		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce, 
					action: 'cwvpsb_recheck_urls_cache', page:new_page},
			success: function(response){
				current.removeClass('updating-message');	
				if(response.status){
					if(response.count > 0){
						new_page++;
						cwvpb_recheck_urls(current,new_page);
					}else{
						alert('Recheck is done');	
						location.reload(true);
					}										
				}else{
					alert('something went wrong');
				}
			}
		});
	}

	$(document).on("click", ".cwb-copy-urls-error", function(e){
		e.preventDefault();
		var element = $(this).parent().find(".cwb-copy-urls-text");
		var $temp = $("<input>");
		$("body").append($temp);
		$temp.val($(element).val()).select();
		document.execCommand("copy");
		$temp.remove();
		$('<div>Copied!</div>').insertBefore($(this)).delay(3000).fadeOut();
	});
	$(".cwvpsb-recheck-url-cache").on("click", function(e){
		e.preventDefault();
		if(!confirm('It will check all cached urls. if any one has issue will optimize it again. Proceed?')){
			return false;
		}	
		var current = $(this);		
		var page    = 0;
		cwvpb_recheck_urls(current, page);		
	});

	$(".cwvpsb-reset-url-cache").on("click", function(e){
		e.preventDefault();
		if(!confirm('Are you sure? It will start optimize process from beginning again.')){
			return false;
		}	
		var current = $(this);
		current.addClass('updating-message');		
		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce, action: 'cwvpsb_reset_urls_cache'},
			success: function(response){
				current.removeClass('updating-message');	
				if(response.status){
					location.reload(true);
				}else{
					alert('something went wrong');
				}
			}
		})

	});

	$(".cwvpsb-reset-url-cache").on("click", function(e){
		e.preventDefault();
		if(!confirm('Are you sure? It will start optimize process from beginning again.')){
			return false;
		}	
		var current = $(this);
		current.addClass('updating-message');		
		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce, action: 'cwvpsb_reset_urls_cache'},
			success: function(response){
				current.removeClass('updating-message');	
				if(response.status){
					location.reload(true);
				}else{
					alert('something went wrong');
				}
			}
		})

	});
});

var css_check_interval=setInterval(function(){
	jQuery.ajax({
		url: ajaxurl,
		type:'post',
		dataType: 'json',
		data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce, action: 'cwvpsb_update_critical_css_stat'},
		success: function(response){
		
			if(response.status=='success'){

				if(response.percentage )
				{
					jQuery('.cwvpsb_progress_bar_body').css('width',response.percentage+'%');
					jQuery('.cwvpsb_progress_bar_body').text(response.percentage+'%');
				}
				if(response.total_count)
				{
					jQuery('#cwvpsb_css_total_count').text(response.total_count+' URLs');
				}
				if(response.queue_count)
				{
					jQuery('#cwvpsb_css_queue_count').text(response.queue_count+' URLs');

					var hours=null;
					var estimate_time="NA";
					if( Math.floor(response.queue_count/300) >0 )
					{
						hours= Math.floor(response.queue_count/300)+' Hours';
					}
					if(hours){
						estimate_time = hours+ (response.queue_count % 60)+ ' Min';
					}else{
						
						if((response.queue_count % 60) > 0){
							estimate_time = (response.queue_count % 60)+ ' Min';
						}                
					}  
					if(estimate_time) 
					{
						jQuery('#cwvpsb_css_generate_time').text(estimate_time);
					}
					
					
					
				}
				if(response.cached_count)
				{
					jQuery('#cwvpsb_css_cached_count').text(response.cached_count+' URLs');
				}
				if(jQuery('#cwvpsb_css_failed_count').length && response.failed_count)
				{
					jQuery('#cwvpsb_css_failed_count').text(response.failed_count+' URLs');
				}

				if(response.total_count==Number(response.cached_count)+Number(response.failed_count))
				{
					clearInterval(css_check_interval);
				}
				
			}
		}
	})

}, 10000);