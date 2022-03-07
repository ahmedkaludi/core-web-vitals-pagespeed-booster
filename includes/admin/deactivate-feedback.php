<?php 
$reasons = array(
    		1 => '<li><label><input type="radio" name="cwv_disable_reason" value="temporary"/>' . __('It is only temporary', 'cwvpsb') . '</label></li>',
		2 => '<li><label><input type="radio" name="cwv_disable_reason" value="stopped"/>' . __('I stopped using plugin on my site', 'cwvpsb') . '</label></li>',
		3 => '<li><label><input type="radio" name="cwv_disable_reason" value="missing"/>' . __('I miss a feature', 'cwvpsb') . '</label></li>
		<li><input type="text" class="mb-box missing" name="cwv_disable_text[]" value="" placeholder="Please describe the feature"/></li>',
		4 => '<li><label><input type="radio" name="cwv_disable_reason" value="technical"/>' . __('Technical Issue', 'cwvpsb') . '</label></li>
		<li><textarea class="mb-box technical" name="cwv_disable_text[]" placeholder="' . __('How Can we help? Please describe your problem', 'cwvpsb') . '"></textarea></li>',
		5 => '<li><label><input type="radio" name="cwv_disable_reason" value="another"/>' . __('I switched to another plugin', 'cwvpsb') .  '</label></li>
		<li><input type="text" class="mb-box another" name="cwv_disable_text[]" value="" placeholder="Name of the plugin"/></li>',
		6 => '<li><label><input type="radio" name="cwv_disable_reason" value="other"/>' . __('Other reason', 'cwvpsb') . '</label></li>
		<li><textarea class="mb-box other" name="cwv_disable_text[]" placeholder="' . __('Please specify, if possible', 'cwvpsb') . '"></textarea></li>',
    );
shuffle($reasons);
?>


<div id="cwv-reloaded-feedback-overlay" style="display: none;">
    <div id="cwv-reloaded-feedback-content">
	<form action="" method="post">
	    <h3><strong><?php _e('If you have a moment, please let us know why you are deactivating:', 'cwvpsb'); ?></strong></h3>
	    <ul>
                <?php 
                foreach ($reasons as $reason){
                    echo $reason;
                }
                ?>
	    </ul>
	    <?php if ($email) : ?>
    	    <input type="hidden" name="cwv_disable_from" value="<?php echo $email; ?>"/>
	    <?php endif; ?>
	    <input id="cwv-reloaded-feedback-submit" class="button button-primary" type="submit" name="cwv_disable_submit" value="<?php _e('Submit & Deactivate', 'cwvpsb'); ?>"/>
	    <a class="button"><?php _e('Only Deactivate', 'cwvpsb'); ?></a>
	    <a class="cwv-feedback-not-deactivate" href="#"><?php _e('Don\'t deactivate', 'cwvpsb'); ?></a>
	</form>
    </div>
</div>