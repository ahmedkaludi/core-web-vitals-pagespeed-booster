var strict;

jQuery(document).ready(function ($) {
    /**
     * DEACTIVATION FEEDBACK FORM
     */
    // show overlay when clicked on "deactivate"
    cwv_deactivate_link = $('.wp-admin.plugins-php tr[data-slug="core-web-vitals-pagespeed-booster"] .row-actions .deactivate a');
    cwv_deactivate_link_url = cwv_deactivate_link.attr('href');

    cwv_deactivate_link.click(function (e) {
        e.preventDefault();

        // only show feedback form once per 30 days
        var c_value = cwv_admin_get_cookie("cwv_hide_deactivate_feedback");

        if (c_value === undefined) {
            $('#cwv-reloaded-feedback-overlay').show();
        } else {
            // click on the link
            window.location.href = cwv_deactivate_link_url;
        }
    });
    // show text fields
    $('#cwv-reloaded-feedback-content input[type="radio"]').click(function () {
        // show text field if there is one
        var inputValue = $(this).attr("value");
        var targetBox = $("." + inputValue);
        $(".mb-box").not(targetBox).hide();
        $(targetBox).show();
    });
    // send form or close it
    $('#cwv-reloaded-feedback-content .button').click(function (e) {
        e.preventDefault();
        // set cookie for 30 days
        var exdate = new Date();
        exdate.setSeconds(exdate.getSeconds() + 2592000);
        document.cookie = "cwv_hide_deactivate_feedback=1; expires=" + exdate.toUTCString() + "; path=/";

        $('#cwv-reloaded-feedback-overlay').hide();
        if ('cwv-reloaded-feedback-submit' === this.id) {
            // Send form data
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'cwv_send_feedback',
                    data: $('#cwv-reloaded-feedback-content form').serialize()
                },
                complete: function (MLHttpRequest, textStatus, errorThrown) {
                    // deactivate the plugin and close the popup
                    $('#cwv-reloaded-feedback-overlay').remove();
                    window.location.href = cwv_deactivate_link_url;

                }
            });
        } else {
            $('#cwv-reloaded-feedback-overlay').remove();
            window.location.href = cwv_deactivate_link_url;
        }
    });
    // close form without doing anything
    $('.cwv-feedback-not-deactivate').click(function (e) {
        $('#cwv-reloaded-feedback-overlay').hide();
    });
    
    function cwv_admin_get_cookie (name) {
	var i, x, y, cwv_cookies = document.cookie.split( ";" );
	for (i = 0; i < cwv_cookies.length; i++)
	{
		x = cwv_cookies[i].substr( 0, cwv_cookies[i].indexOf( "=" ) );
		y = cwv_cookies[i].substr( cwv_cookies[i].indexOf( "=" ) + 1 );
		x = x.replace( /^\s+|\s+$/g, "" );
		if (x === name)
		{
			return unescape( y );
		}
	}
}

}); // document ready