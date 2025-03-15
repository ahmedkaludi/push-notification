jQuery(document).ready(function ($) {
	if (jQuery("#pn_send_notification_on_post").length > 0) {
		$('#pn_send_notification_on_post').on('change', function () {
			var metaValue = $(this).is(':checked') ? '1' : '0';
			var postId = $('#post_ID').val();
			var nonce = $('#set_send_push_notification_nonce').val();
			$.ajax({
				type: 'POST',
				url: pnAjax.ajax_url,
				data: {
					action: 'update_pn_meta',
					post_id: postId,
					meta_key: 'pn_send_notification_on_post',
					meta_value: metaValue,
					set_send_push_notification_nonce: nonce
				},
				success: function (response) {
					if (response.success) {
						console.log('Meta updated successfully.');
					} else {
						console.log('Error updating meta:', response.data);
					}
				},
				error: function () {
					console.log('AJAX error occurred.');
				}
			});
		});
	}
});