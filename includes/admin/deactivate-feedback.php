<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$cwvpsb_reasons = array(
	1 => '<li><label><input type="radio" name="cwv_disable_reason" value="temporary"/>' . esc_html__('It is only temporary', 'core-web-vitals-pagespeed-booster') . '</label></li>',
	2 => '<li><label><input type="radio" name="cwv_disable_reason" value="stopped"/>' . esc_html__('I stopped using plugin on my site', 'core-web-vitals-pagespeed-booster') . '</label></li>',
	3 => '<li><label><input type="radio" name="cwv_disable_reason" value="missing"/>' . esc_html__('I miss a feature', 'core-web-vitals-pagespeed-booster') . '</label></li>
		<li><input type="text" class="mb-box missing" name="cwv_disable_text[]" value="" placeholder="' . esc_html__('Please describe the feature', 'core-web-vitals-pagespeed-booster') . '"/></li>',
	4 => '<li><label><input type="radio" name="cwv_disable_reason" value="technical"/>' . esc_html__('Technical Issue', 'core-web-vitals-pagespeed-booster') . '</label></li>
		<li><textarea class="mb-box technical" name="cwv_disable_text[]" placeholder="' . esc_html__('How Can we help? Please describe your problem', 'core-web-vitals-pagespeed-booster') . '"></textarea></li>',
	5 => '<li><label><input type="radio" name="cwv_disable_reason" value="another"/>' . esc_html__('I switched to another plugin', 'core-web-vitals-pagespeed-booster') . '</label></li>
		<li><input type="text" class="mb-box another" name="cwv_disable_text[]" value="" placeholder="' . esc_html__('Name of the plugin', 'core-web-vitals-pagespeed-booster') . '" /></li>',
	6 => '<li><label><input type="radio" name="cwv_disable_reason" value="other"/>' . esc_html__('Other reason', 'core-web-vitals-pagespeed-booster') . '</label></li>
		<li><textarea class="mb-box other" name="cwv_disable_text[]" placeholder="' . esc_html__('Please specify, if possible', 'core-web-vitals-pagespeed-booster') . '"></textarea></li>',
);
shuffle($cwvpsb_reasons);
?>


<div id="cwv-reloaded-feedback-overlay" style="display: none;">
	<div id="cwv-reloaded-feedback-content">
		<form action="" method="post">
			<h3><strong><?php esc_html_e('If you have a moment, please let us know why you are deactivating:', 'core-web-vitals-pagespeed-booster'); ?></strong>
			</h3>
			<ul>
				<?php
				foreach ($cwvpsb_reasons as $cwvpsb_reason) {
					echo $cwvpsb_reason; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason is already escaped in the array
				}
				?>
			</ul>
			<?php if ($email): ?>
				<input type="hidden" name="cwv_disable_from" value="<?php echo esc_attr($email); ?>" />
				<input type="hidden" name="deactivate_nonce" value="<?php echo esc_attr($email); ?>" />
			<?php endif; ?>
			<input id="cwv-reloaded-feedback-submit" class="button button-primary" type="submit"
				name="cwv_disable_submit" value="<?php esc_html_e('Submit & Deactivate', 'core-web-vitals-pagespeed-booster'); ?>" />
			<a class="button"><?php esc_html_e('Only Deactivate', 'core-web-vitals-pagespeed-booster'); ?></a>
			<a class="cwv-feedback-not-deactivate" href="#"><?php esc_html_e('Don\'t deactivate', 'core-web-vitals-pagespeed-booster'); ?></a>
		</form>
	</div>
</div>
