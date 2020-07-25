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
})(jQuery);