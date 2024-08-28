<?php 
$reasons = array(
    	1 => '<li><label><input type="radio" name="pn_disable_reason" value="temporary"/>' . esc_html__('It is only temporary', 'push-notification') . '</label></li>',
		2 => '<li><label><input type="radio" name="pn_disable_reason" value="stopped"/>' . esc_html__('I stopped using Push Notification on my site', 'push-notification') . '</label></li>',
		3 => '<li><label><input type="radio" name="pn_disable_reason" value="missing"/>' . esc_html__('I miss a feature', 'push-notification') . '</label></li>
		<li><input type="text" class="mb-box missing" name="pn_disable_text[]" value="" placeholder="'.esc_attr__( 'Please describe the feature', 'push-notification' ).'"/></li>',
		4 => '<li><label><input type="radio" name="pn_disable_reason" value="technical"/>' . esc_html__('Technical Issue', 'push-notification') . '</label></li>
		<li><textarea  class="mb-box technical" name="pn_disable_text[]" placeholder="' . esc_html__('How Can we help? Please describe your problem', 'push-notification') . '"></textarea></li>',
		5 => '<li><label><input type="radio" name="pn_disable_reason" value="another"/>' . esc_html__('I switched to another plugin', 'push-notification') .  '</label></li>
		<li><input type="text"  class="mb-box another" name="pn_disable_text[]" value="" placeholder="'.esc_attr__( 'Name of the plugin', 'push-notification' ).'"/></li>',
		6 => '<li><label><input type="radio" name="pn_disable_reason" value="other"/>' . esc_html__('Other reason', 'push-notification') . '</label></li>
		<li><textarea  class="mb-box other" name="pn_disable_text[]" placeholder="' . esc_attr__('Please specify, if possible', 'push-notification') . '"></textarea></li>',
    );
shuffle($reasons);
?>


<div id="pn-reloaded-feedback-overlay" style="display: none;">
    <div id="pn-reloaded-feedback-content">
	<form action="" method="post">
	    <h3><strong><?php echo esc_html__('If you have a moment, please let us know why you are deactivating:', 'push-notification'); ?></strong></h3>
	    <ul>
                <?php 
				if ( ! empty( $reasons ) ) {

					foreach ($reasons as $reason_escaped){
						/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped  */
						echo $reason_escaped;

					}

				}
                ?>
	    </ul>
	    <?php if ($email) : ?>
    		<input type="hidden" name="pn_disable_from" value="<?php echo esc_attr($email); ?>"/>
	    <?php endif; ?>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'push_notification_feedback' ) ); ?>">
			<input id="pn-reloaded-feedback-submit" class="button button-primary" type="submit" name="pn_disable_submit" value="<?php echo esc_html__('Submit & Deactivate', 'push-notification'); ?>"/>
			<a class="button"><?php echo esc_html__('Only Deactivate', 'push-notification'); ?></a>
			<a class="pn-for-wp-feedback-not-deactivate" href="#"><?php echo esc_html__('Don\'t deactivate', 'push-notification'); ?></a>
	</form>
    </div>
</div>