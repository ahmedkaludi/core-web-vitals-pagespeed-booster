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
});