<?php
defined('ABSPATH') || exit;

class Webvital_font_frontend_functions
{
	

	/** @var bool $do_optimize */
	private $do_optimize;

	/**
	 * constructor.
	 */
	public function __construct()
	{
		$this->do_optimize = true;

		add_action('wp_head', [$this, 'add_preloads'], 3);
		add_action('wp_print_styles', [$this, 'process_fonts'], PHP_INT_MAX - 1000);
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	/**
	 * TODO: When setting all preloads at once (different stylesheet handles) combined with unloads, not all URLs are rewritten with their cache keys properly.
	 *       When configured handle by handle, it works fine. PHP multi-threading issues?
	 */
	public function add_preloads()
	{
		static $preloaded_fonts = [];

		if (empty($preloaded_fonts)) {
			$preloaded_fonts = get_option('web_vital_preload_fonts', []);
		}

		$preloaded_fonts = apply_filters('web_vital_frontend_preloaded_fonts', $preloaded_fonts);

		if (!$preloaded_fonts) {
			return;
		}

		static $optimized_fonts = [];

		if (empty($optimized_fonts)) {
			$optimized_fonts = get_option('web_vital_optimized_fonts', []);
		}

		$stylesheets = apply_filters('web_vital_frontend_optimized_fonts', $optimized_fonts);

		$i = 0;

		foreach ($stylesheets as $stylesheet => $fonts) {
			foreach ($fonts as $font) {
				$preloads_stylesheet = $preloaded_fonts[$stylesheet] ?? [];

				if (!in_array($font->id, array_keys($preloads_stylesheet))) {
					continue;
				}

				$font_id          = $font->id;
				$preload_variants = array_filter(
					$font->variants,
					function ($variant) use ($preloads_stylesheet, $font_id) {
						return in_array($variant->id, $preloads_stylesheet[$font_id]);
					}
				);

				foreach ($preload_variants as $variant) {
					$url = $variant->woff2;
					echo "<link id='omgf-preload-$i' rel='preload' href='$url' as='font' type='font/woff2' crossorigin />\n";
					$i++;
				}
			}
		}
	}

	/**
	 * Check if the Remove Google Fonts option is enabled.
	 */
	public function process_fonts()
	{
		if (!$this->do_optimize) {
			return;
		}

		if (is_admin()) {
			return;
		}

		if (apply_filters('web_vital_advanced_processing_enabled', false)) {
			return;
		}
		$processing_option = 'replace';//remove

		switch ($processing_option) {
			case 'remove':
				add_action('wp_print_styles', [$this, 'remove_registered_fonts'], PHP_INT_MAX - 500);
				break;
			default:
				add_action('wp_print_styles', [$this, 'replace_registered_fonts'], PHP_INT_MAX - 500);
		}
	}

	/**
	 * This function contains a nice little hack, to avoid messing with potential dependency issues. We simply set the source to an empty string!
	 */
	public function remove_registered_fonts()
	{
		global $wp_styles;

		$registered = $wp_styles->registered;
		$fonts      = apply_filters('web_vital_auto_remove', $this->detect_registered_google_fonts($registered));

		foreach ($fonts as $handle => $font) {
			$wp_styles->registered[$handle]->src = '';
		}
	}

	/**
	 * Retrieve stylesheets from Google Fonts' API and modify the stylesheet for local storage.
	 */
	public function replace_registered_fonts()
	{
		global $wp_styles;

		$registered           = $wp_styles->registered;
		$fonts                = apply_filters('web_vital_auto_replace', $this->detect_registered_google_fonts($registered));
		$unloaded_stylesheets = self::unloaded_stylesheets();
		$unloaded_fonts       = self::unloaded_fonts();

		foreach ($fonts as $handle => $font) {
			// If this stylesheet has been marked for unload, empty the src and skip out early.
			if (in_array($handle, $unloaded_stylesheets)) {
				$wp_styles->registered[$handle]->src = '';

				continue;
			}

			$updated_handle = $handle;

			if ($unloaded_fonts) {
				$updated_handle = self::get_cache_key($handle);
			}

			$cached_file = '/uploads/web-vital-fonts/' . $updated_handle . "/$updated_handle.css";

			if (file_exists(WP_CONTENT_DIR . $cached_file)) {
				$wp_styles->registered[$handle]->src = content_url($cached_file);

				continue;
			}
			$modetype = 'auto';
			if ( $modetype == 'auto' ) {
				$api_url  = str_replace(['http:', 'https:'], '', home_url('/wp-json/wvpsbf/v1/download/'));
				$protocol = '';

				if (substr($font->src, 0, 2) == '//') {
					$protocol = 'https:';
				}

				$wp_styles->registered[$handle]->src = $protocol . str_replace('//fonts.googleapis.com/', $api_url, $font->src) . "&handle=$updated_handle&original_handle=$handle";
			}
		}
	}

	/**
	 * @param $registered_styles
	 *
	 * @return array
	 */
	private function detect_registered_google_fonts($registered_styles)
	{
		return array_filter(
			$registered_styles,
			function ($contents) {
				return strpos($contents->src, 'fonts.googleapis.com/css') !== false
					|| strpos($contents->src, 'fonts.gstatic.com') !== false;
			}
		);
	}

	/**
	 * @return array
	 */
	public static function unloaded_stylesheets()
	{
		static $unloaded_stylesheets = [];

		if (empty($unloaded_stylesheets)) {
			$unloaded_stylesheets = explode(',', get_option('webvital_unload_stylesheets', ''));
		}

		return array_filter($unloaded_stylesheets);
	}
	/**
	 * @return array
	 */
	public static function unloaded_fonts()
	{
		static $unloaded_fonts = [];

		if (empty($unloaded_fonts)) {
			$unloaded_fonts = get_option('webvital_unload_fonts', []);
		}

		return $unloaded_fonts;
	}
	/**
	 * @param $handle
	 *
	 * @return string
	 */
	public static function get_cache_key($handle)
	{
		static $cache_keys = [];
		if (empty($cache_keys)) {
			$cache_keys = explode(',', get_option('webvital_cache_keys', ''));
		}
		$cache_keys = array_filter($cache_keys);

		//$cache_keys = self::cache_keys();

		foreach ($cache_keys as $index => $key) {
			if (strpos($key, $handle) !== false) {
				return $key;
			}
		}

		return '';
	}

	public function register_routes()
	{
		require_once WEB_VITALS_PAGESPEED_BOOSTER_DIR."/inc/load-css-download.php";
		$proxy = new Webvita_Fonts_API_Download();
		$proxy->register_routes();
	}
}
$webvital_font = new Webvital_font_frontend_functions();