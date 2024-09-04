<?php
$reasons = array(
	1 => '<li><label><input type="radio" name="cwv_disable_reason" value="temporary"/>' . esc_html__('It is only temporary', 'cwvpsb') . '</label></li>',
	2 => '<li><label><input type="radio" name="cwv_disable_reason" value="stopped"/>' . esc_html__('I stopped using plugin on my site', 'cwvpsb') . '</label></li>',
	3 => '<li><label><input type="radio" name="cwv_disable_reason" value="missing"/>' . esc_html__('I miss a feature', 'cwvpsb') . '</label></li>
		<li><input type="text" class="mb-box missing" name="cwv_disable_text[]" value="" placeholder="' . esc_html__('Please describe the feature', 'cwvpsb') . '"/></li>',
	4 => '<li><label><input type="radio" name="cwv_disable_reason" value="technical"/>' . esc_html__('Technical Issue', 'cwvpsb') . '</label></li>
		<li><textarea class="mb-box technical" name="cwv_disable_text[]" placeholder="' . esc_html__('How Can we help? Please describe your problem', 'cwvpsb') . '"></textarea></li>',
	5 => '<li><label><input type="radio" name="cwv_disable_reason" value="another"/>' . esc_html__('I switched to another plugin', 'cwvpsb') . '</label></li>
		<li><input type="text" class="mb-box another" name="cwv_disable_text[]" value="" placeholder="' . esc_html__('Name of the plugin', 'cwvpsb') . '" /></li>',
	6 => '<li><label><input type="radio" name="cwv_disable_reason" value="other"/>' . esc_html__('Other reason', 'cwvpsb') . '</label></li>
		<li><textarea class="mb-box other" name="cwv_disable_text[]" placeholder="' . esc_html__('Please specify, if possible', 'cwvpsb') . '"></textarea></li>',
);
shuffle($reasons);
?>


<div id="cwv-reloaded-feedback-overlay" style="display: none;">
	<div id="cwv-reloaded-feedback-content">
		<form action="" method="post">
			<h3><strong><?php esc_html_e('If you have a moment, please let us know why you are deactivating:', 'cwvpsb'); ?></strong>
			</h3>
			<ul>
				<?php
				foreach ($reasons as $reason) {
					echo $reason; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason is already escaped in the array
				}
				?>
			</ul>
			<?php if ($email): ?>
				<input type="hidden" name="cwv_disable_from" value="<?php echo esc_attr($email); ?>" />
			<?php endif; ?>
			<input id="cwv-reloaded-feedback-submit" class="button button-primary" type="submit"
				name="cwv_disable_submit" value="<?php esc_html_e('Submit & Deactivate', 'cwvpsb'); ?>" />
			<a class="button"><?php esc_html_e('Only Deactivate', 'cwvpsb'); ?></a>
			<a class="cwv-feedback-not-deactivate" href="#"><?php esc_html_e('Don\'t deactivate', 'cwvpsb'); ?></a>
		</form>
	</div>
</div>