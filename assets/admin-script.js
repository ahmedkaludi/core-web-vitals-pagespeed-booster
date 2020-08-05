(function($){
	$(".add_new_row_url").click(function(){
		var html = '<div class="ads_uri_row"><input type="input" name="webvital_settings[list_of_urls][]" class="" value=""placeholder="Ads script url"><span style="cursor: pointer;" class="remove_url_row"><span class="dashicons dashicons-no-alt"></span></span></div>';
		$("#ads_url_wrapper").append(html);
		loadremover();
	});

	function loadremover(){
		$(".remove_url_row").click(function(){
			$(this).parents('.ads_uri_row').remove();
		})
	}
	loadremover();

	$("#web-vital-clear-cache").click(function(event){
		console.log("web-vital-clear-cache");
		$('.clear-cache-msg').text("please wait...")
		var nonce = $(this).attr('data-security');
		$.ajax({
			url: ajaxurl,
			type:'post',
			dataType: 'json',
			data: {'nonce': nonce, action: 'parse_clear_cached_css'},
			success: function(response){
				if(response.status==200){
					$('.clear-cache-msg').text("Cache cleared")
				}else{
					$('.clear-cache-msg').text(response.msg)
				}
			}
		})
	});
})(jQuery);