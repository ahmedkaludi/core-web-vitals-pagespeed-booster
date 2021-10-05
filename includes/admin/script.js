(function($){

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
})(jQuery);