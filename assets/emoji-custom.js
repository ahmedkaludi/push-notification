(() => {
    new MeteorEmoji()
})()

jQuery("#notification-title").parent('div').find('.faces > a').click(function(){
    // console.log(jQuery(this).html());
    // title = jQuery("#notification-title").val();
    // // jQuery("#notification-title").val(title);
    // $('#notification-title').focus().val('').val(title);
    // jQuery('#notification-title').focus();
    $('#notification-title').val($('#notification-title').val() + '').focus();
})
jQuery("#notification-title").parent('div').click(function(){
    title = jQuery("#notification-title").val();
    jQuery(".notification-title").html(title);
    jQuery('#notification-title').focus();
})
jQuery("#notification-message").parent('div').click(function(){
    title = jQuery("#notification-message").val();
    jQuery(".notification-description").html(title);
    jQuery('#notification-message').focus();
})
jQuery('.js_pn_custom').width( 475 )
// jQuery('#notification-message, #notification-title').next().next('div').width( 350 )
jQuery('#notification-title').next('a').css('top',5)