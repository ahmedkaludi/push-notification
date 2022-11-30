var strict;

jQuery(document).ready(function ($) {
    /**
     * DEACTIVATION FEEDBACK FORM
     */
    // show overlay when clicked on "deactivate"
    pn_deactivate_link = $('.wp-admin.plugins-php tr[data-slug="push-notification"] .row-actions .deactivate a');
    pn_deactivate_link_url = pn_deactivate_link.attr('href');

    pn_deactivate_link.click(function (e) {
        e.preventDefault();
        
        // only show feedback form once per 30 days
        var c_value = pn_admin_get_cookie("pn_hide_deactivate_feedback");

        if (c_value === undefined) {
            $('#pn-reloaded-feedback-overlay').show();
        } else {
            // click on the link
            window.location.href = pn_deactivate_link_url;
        }
    });
    // show text fields
    $('#pn-reloaded-feedback-content input[type="radio"]').click(function () {
        // show text field if there is one
        var input_value = $(this).attr("value");
        var target_box = $("." + input_value);
        $(".mb-box").not(target_box).hide();
        $(target_box).show();
    });
    // send form or close it
    $('#pn-reloaded-feedback-content .button').click(function (e) {
        e.preventDefault();
        // set cookie for 30 days
        var exdate = new Date();
        exdate.setSeconds(exdate.getSeconds() + 2592000);
        document.cookie = "pn_hide_deactivate_feedback=1; expires=" + exdate.toUTCString() + "; path=/";

        $('#pn-reloaded-feedback-overlay').hide();
        if ('pn-reloaded-feedback-submit' === this.id) {
            // Send form data
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'pn_send_feedback',
                    data: $('#pn-reloaded-feedback-content form').serialize()
                },
                complete: function (MLHttpRequest, textStatus, errorThrown) {
                    // deactivate the plugin and close the popup
                    $('#pn-reloaded-feedback-overlay').remove();
                    window.location.href = pn_deactivate_link_url;

                }
            });
        } else {
            $('#pn-reloaded-feedback-overlay').remove();
            window.location.href = pn_deactivate_link_url;
        }
    });
    // close form without doing anything
    $('.pn-for-wp-feedback-not-deactivate').click(function (e) {
        $('#pn-reloaded-feedback-overlay').hide();
    });
    
    function pn_admin_get_cookie (name) {
	var i, x, y, pn_cookies = document.cookie.split( ";" );
	for (i = 0; i < pn_cookies.length; i++)
	{
		x = pn_cookies[i].substr( 0, pn_cookies[i].indexOf( "=" ) );
		y = pn_cookies[i].substr( pn_cookies[i].indexOf( "=" ) + 1 );
		x = x.replace( /^\s+|\s+$/g, "" );
		if (x === name)
		{
			return unescape( y );
		}
	}
}

}); // document ready