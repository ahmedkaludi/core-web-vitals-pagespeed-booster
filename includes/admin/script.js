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
			        'nonce_verify' : nonce,
					'filename':currentFile,

			    }
		if (typeof currentFile == 'undefined'){
			currentFile = 'All images are already WEBP';
		}	    
		$.ajax({url: ajaxurl, type:'post', dataType: 'json', data: data,
			success: function(response){
				if(response.status==200){
					$('.log_convert_info').html("Image '"+currentFile + "' converted ( "+(current_conversion_number+1)+" out of total "+need_convertFiles.length+" files) <br/>" );
					current_conversion_number += 1;
					if(current_conversion_number<need_convertFiles.length){
						startConversion(nonce);
					}else{
						$('.log_convert_info').append("-------------------------------- <br/>" );
						$('.log_convert_info').append("Conversion completed Total "+need_convertFiles.length+" files are converted <br/>" );
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
			type: "POST",
			url: ajaxurl,
			data:{
				'action': 'cwvpsb_showdetails_data',
				'cwvpsb_security_nonce' : cwvpsb_localize_data.cwvpsb_security_nonce,
				'cwvpsb_type':'all'
			},
		}
	});
	jQuery("#table_page_cc_style_completed").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "POST",
			url: ajaxurl,
			data:{
				'action': 'cwvpsb_showdetails_data',
				'cwvpsb_security_nonce' : cwvpsb_localize_data.cwvpsb_security_nonce,
				'cwvpsb_type':'cached'
			},
		}
	});
	jQuery("#table_page_cc_style_failed").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "POST",
			url: ajaxurl,
			data:{
				'action': 'cwvpsb_showdetails_data',
				'cwvpsb_security_nonce' : cwvpsb_localize_data.cwvpsb_security_nonce,
				'cwvpsb_type':'failed'
			},
		}
	});
	jQuery("#table_page_cc_style_queue").DataTable({
		serverSide: true,
		processing: true,
        fixedColumns: true,		
		ajax: {
			type: "POST",
			url: ajaxurl,
			data:{
				'action': 'cwvpsb_showdetails_data',
				'cwvpsb_security_nonce' : cwvpsb_localize_data.cwvpsb_security_nonce,
				'cwvpsb_type':'queue'
			},
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
		current.addClass('updating-message');		
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'cwvpsb_security_nonce': cwvpsb_localize_data.cwvpsb_security_nonce, 
					action: 'cwvpsb_recheck_urls_cache', page:page},
			success: function(response){
				current.removeClass('updating-message');	
				if(response.status){
					if(response.count > 0){
						page=page+1;
						cwvpb_recheck_urls(current,page);
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

	$(".nav-tab").on("click", function(e){
		if($(".cwvpsb-support").is(":visible"))
		{
			$('#submit').hide();
		}
		else
		{
			$('#submit').show();
		}
	
	});

	$(".cwvpsb-send-query").on("click", function(e){
		e.preventDefault();   
		$(".cwvpsb-query-error").hide();
		$(".cwvpsb-query-success").hide(); 
		var message     = $("#cwvpsb_query_message").val();  
		var email       = $("#cwvpsb_query_email").val(); 
		
		if($.trim(message) !='' && $.trim(email) !='' && cwvpsbIsEmail(email) == true){
			$(".cwvpsb-send-query").text('Sending ...');
		 $.ajax({
						type: "POST",    
						url:ajaxurl,                    
						dataType: "json",
						data:{action:"cwvpsb_send_query_message",message:message,email:email,cwvpsb_wpnonce:cwvpsb_script_vars.nonce},
						success:function(response){                       
						  if(response['status'] =='t'){
							$(".cwvpsb-query-success").show();
							$(".cwvpsb-query-error").hide();
							$("#cwvpsb_query_message").val('');
							$("#cwvpsb_query_email").val(''); 
							$(".cwvpsb-send-query").text('Send Support Request');
						  }else{                                  
							$(".cwvpsb-query-success").hide();  
							$(".cwvpsb-query-error").show();
							$(".cwvpsb-send-query").text('Send Support Request');
						  }
						},
						error: function(response){                    
						console.log(response);
						}
						});   
		}else{
			
			if($.trim(message) =='' && $.trim(email) ==''){
				alert('Please enter the message, email');
			}else{
			
			if($.trim(message) == ''){
				alert('Please enter the message');
			}
			if($.trim(email) == ''){
				alert('Please enter the email');
			}
			if(cwvpsbIsEmail(email) == false){
				alert('Please enter a valid email');
			}
				
			}
			
		}                        
	
	});
});

function cwvpsbIsEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}

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

jQuery(document).ready(function($) {

    /* Newletters js starts here */      
        if(cwvpsb_localize_data.do_tour){
                    
          var  content = '<h3>Thanks For Core Web Vitals & PageSpeed Booster!</h3>';
              content += '<p>Do you want the latest updates before others on Core Web Vitals & PageSpeed Booster ? - Free just for users of Core Web Vitals & PageSpeed Booster!</p>';
              content += '<style type="text/css">';
              content += '.wp-pointer-buttons{ padding:0; overflow: hidden; }';
              content += '.wp-pointer-content .button-secondary{  left: -25px;background: transparent;top: 5px; border: 0;position: relative; padding: 0; box-shadow: none;margin: 0;color: #0085ba;} .wp-pointer-content .button-primary{ display:none}  #cwvpsb_mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }';
              content += '</style>';                        
              content += '<div id="cwvpsb_mc_embed_signup">';
              content += '<form method="POST" accept-charset="utf-8" id="cwvpsb-news-letter-form">';
              content += '<div id="cwvpsb_mc_embed_signup_scroll">';
              content += '<div class="cwvpsb-mc-field-group" style="    margin-left: 15px;    width: 195px;    float: left;">';
              content += '<input type="text" name="cwvpsb_subscriber_name" class="form-control" placeholder="Name" hidden value="'+cwvpsb_localize_data.current_user_name+'" style="display:none">';
              content += '<input type="text" value="'+cwvpsb_localize_data.current_user_email+'" name="cwvpsb_subscriber_email" class="form-control" placeholder="Email*"  style="      width: 180px;    padding: 6px 5px;">';                        
              content += '<input type="text" name="cwvpsb_subscriber_website" class="form-control" placeholder="Website" hidden style=" display:none; width: 168px; padding: 6px 5px;" value="'+cwvpsb_localize_data.get_home_url+'">';
              content += '<input type="hidden" name="ml-submit" value="1" />';
              content += '</div>';
              content += '<div id="mce-responses">';                                                
              content += '</div>';
              content += '<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_a631df13442f19caede5a5baf_c9a71edce6" tabindex="-1" value=""></div>';
              content += '<input type="submit" value="Subscribe" name="subscribe" id="pointer-close" class="button mc-newsletter-sent" style=" background: #0085ba; border-color: #006799; padding: 0px 16px; text-shadow: 0 -1px 1px #006799,1px 0 1px #006799,0 1px 1px #006799,-1px 0 1px #006799; height: 30px; margin-top: 1px; color: #fff; box-shadow: 0 1px 0 #006799;">';
              content += '<p id="cwvpsb-news-letter-status"></p>';
              content += '</div>';
              content += '</form>';
              content += '</div>';

              $(document).on("submit", "#cwvpsb-news-letter-form", function(e){
                e.preventDefault(); 
                
                var $form = $(this),
                name = $form.find('input[name="cwvpsb_subscriber_name"]').val(),
                email = $form.find('input[name="cwvpsb_subscriber_email"]').val();
                website = $form.find('input[name="cwvpsb_subscriber_website"]').val();                          
                
                $.post(cwvpsb_localize_data.ajax_url,
                            {action:'cwvpsb_subscribe_to_news_letter',
                            cwvpsb_security_nonce:cwvpsb_localize_data.cwvpsb_security_nonce,
                            name:name, email:email, website:website },
                  function(data) {
                    
                      if(data)
                      {
                        if(data=="Some fields are missing.")
                        {
                          $("#cwvpsb-news-letter-status").text("");
                          $("#cwvpsb-news-letter-status").css("color", "red");
                        }
                        else if(data=="Invalid email address.")
                        {
                          $("#cwvpsb-news-letter-status").text("");
                          $("#cwvpsb-news-letter-status").css("color", "red");
                        }
                        else if(data=="Invalid list ID.")
                        {
                          $("#cwvpsb-news-letter-status").text("");
                          $("#cwvpsb-news-letter-status").css("color", "red");
                        }
                        else if(data=="Already subscribed.")
                        {
                          $("#cwvpsb-news-letter-status").text("");
                          $("#cwvpsb-news-letter-status").css("color", "red");
                        }
                        else
                        {
                          $("#cwvpsb-news-letter-status").text("You're subscribed!");
                          $("#cwvpsb-news-letter-status").css("color", "green");
                        }
                      }
                      else
                      {
                        alert("Sorry, unable to subscribe. Please try again later!");
                      }
                  }
                );
              });      
      
      var setup;                
      var wp_pointers_tour_opts = {
          content:content,
          position:{
              edge:"top",
              align:"left"
          }
      };

                      
      wp_pointers_tour_opts = $.extend (wp_pointers_tour_opts, {
              buttons: function (event, t) {
                      button= jQuery ('<a id="pointer-close" class="button-secondary">' + cwvpsb_localize_data.button1 + '</a>');
                      button_2= jQuery ('#pointer-close.button');
                      button.bind ('click.pointer', function () {
                              t.element.pointer ('close');
                      });
                      button_2.on('click', function() {
                        setTimeout(function(){ 
                            t.element.pointer ('close');
                        }, 3000);
                            
                      } );
                      return button;
              },
              close: function () {
                      $.post (cwvpsb_localize_data.ajax_url, {
                              pointer: 'cwvpsb_subscribe_pointer',
                              action: 'dismiss-wp-pointer'
                      });
              },
              show: function(event, t){
                t.pointer.css({'left':'170px', 'top':'160px'});
            }                                               
      });
      
      setup = function () {
              $(cwvpsb_localize_data.displayID).pointer(wp_pointers_tour_opts).pointer('open');
                if (cwvpsb_localize_data.button2) {
                      jQuery ('#pointer-close').after ('<a id="pointer-primary" class="button-primary">' + cwvpsb_localize_data.button2+ '</a>');
                      jQuery ('#pointer-primary').click (function () {
                              cwvpsb_localize_data.function_name;
                      });
                      jQuery ('#pointer-close').click (function () {
                              $.post (cwvpsb_localize_data.ajax_url, {
                                      pointer: 'cwvpsb_subscribe_pointer',
                                      action: 'dismiss-wp-pointer'
                              });
                      });
                }
      };

      if (wp_pointers_tour_opts.position && wp_pointers_tour_opts.position.defer_loading) {
              $(window).bind('load.wp-pointers', setup);
      }
      else {
              setup ();
      }
      
    }
      
    /* Newletters js ends here */ 

});
