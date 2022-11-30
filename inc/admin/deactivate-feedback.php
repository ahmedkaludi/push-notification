<?php 
$reasons = array(
    	1 => '<li><label><input type="radio" name="pn_disable_reason" value="temporary"/>' . esc_html__('It is only temporary', 'push-notification') . '</label></li>',
		2 => '<li><label><input type="radio" name="pn_disable_reason" value="stopped"/>' . esc_html__('I stopped using Push Notification on my site', 'push-notification') . '</label></li>',
		3 => '<li><label><input type="radio" name="pn_disable_reason" value="missing"/>' . esc_html__('I miss a feature', 'push-notification') . '</label></li>
		<li><input type="text" class="mb-box missing" name="pn_disable_text[]" value="" placeholder="Please describe the feature"/></li>',
		4 => '<li><label><input type="radio" name="pn_disable_reason" value="technical"/>' . esc_html__('Technical Issue', 'push-notification') . '</label></li>
		<li><textarea  class="mb-box technical" name="pn_disable_text[]" placeholder="' . esc_html__('How Can we help? Please describe your problem', 'push-notification') . '"></textarea></li>',
		5 => '<li><label><input type="radio" name="pn_disable_reason" value="another"/>' . esc_html__('I switched to another plugin', 'push-notification') .  '</label></li>
		<li><input type="text"  class="mb-box another" name="pn_disable_text[]" value="" placeholder="Name of the plugin"/></li>',
		6 => '<li><label><input type="radio" name="pn_disable_reason" value="other"/>' . esc_html__('Other reason', 'push-notification') . '</label></li>
		<li><textarea  class="mb-box other" name="pn_disable_text[]" placeholder="' . esc_html__('Please specify, if possible', 'push-notification') . '"></textarea></li>',
    );
shuffle($reasons);
?>


<div id="pn-reloaded-feedback-overlay" style="display: none;">
    <div id="pn-reloaded-feedback-content">
	<form action="" method="post">
	    <h3><strong><?php echo esc_html__('If you have a moment, please let us know why you are deactivating:', 'push-notification'); ?></strong></h3>
	    <ul>
                <?php 
                foreach ($reasons as $reason){
                    echo $reason;
                }
                ?>
	    </ul>
	    <?php if ($email) : ?>
    	    <input type="hidden" name="pn_disable_from" value="<?php echo $email; ?>"/>
	    <?php endif; ?>
	    <input id="pn-reloaded-feedback-submit" class="button button-primary" type="submit" name="pn_disable_submit" value="<?php echo esc_html__('Submit & Deactivate', 'push-notification'); ?>"/>
	    <a class="button"><?php echo esc_html__('Only Deactivate', 'push-notification'); ?></a>
	    <a class="pn-for-wp-feedback-not-deactivate" href="#"><?php echo esc_html__('Don\'t deactivate', 'push-notification'); ?></a>
	</form>
    </div>
</div>