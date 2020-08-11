<?php
class webVitalAdmin{
	function __construct(){
		$this-> init();
	}

	function init(){
		add_action( 'admin_menu', array($this,'add_menu_links'));
		add_action('admin_init',array($this, 'dashboard_section'));
		add_action('wp_ajax_parse_clear_cached_css', array($this, 'parse_clear_cached_css'));
	}

	function add_menu_links() {	
		// Main menu page
		add_menu_page( esc_html__( 'Web vital', 'web-vitals-page-speed-booster' ), 
	                esc_html__( 'Web Vitals & PageSpeed Booster', 'web-vitals-page-speed-booster' ), 
	                'manage_options',
	                'web-vitals-page-speed-booster',
	                array( $this, 'admin_interface_render'),
	                '', 100 );
		
		// Settings page - Same as main menu page
		add_submenu_page( 'web-vitals-page-speed-booster',
	                esc_html__( 'Web Vitals & PageSpeed Booster', 'web-vitals-page-speed-booster' ),
	                esc_html__( 'Settings', 'web-vitals-page-speed-booster' ),
	                'manage_options',
	                'web-vitals-page-speed-booster',
	                array( $this, 'admin_interface_render'));
		add_action('admin_enqueue_scripts', array($this, 'admin_script'));
	}
	function admin_script($hook){
		if($hook!='toplevel_page_web-vitals-page-speed-booster'){return ;}
		wp_enqueue_script( 'web-vital-admin-script', WEBVITAL_PAGESPEED_BOOSTER_URL . 'assets/admin-script.js', array('jquery'), WEBVITAL_PAGESPEED_BOOSTER_VERSION, true );
	}

	function admin_interface_render(){
	 	// Authentication
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Handing save settings
		if ( isset( $_GET['settings-updated'] ) ) {
			settings_errors();
		}
		echo '<form action="options.php" method="post" enctype="multipart/form-data" class="web-vitals-settings-form">';
		settings_fields( 'webvital_setting_dashboard_group' );
		do_settings_sections( 'webvital_dashboard_section' );	// Page slug
		echo "<div style='display:inline-block'><div style='float:left;'>";
		submit_button( esc_html__('Save Settings', 'web-vitals-page-speed-booster') );
		echo "</div><p><button type='button' id='web-vital-clear-cache' data-security='".wp_create_nonce('web-vital-cache-clear')."'>".esc_html__('Clear cache', 'web-vitals-page-speed-booster')."</button><span class='clear-cache-msg'></span></p>";
		echo '</form>';
	}

	function dashboard_section(){
		register_setting( 'webvital_setting_dashboard_group', 'webvital_settings' );

		add_settings_section('webvital_dashboard_section', esc_html__('Web Vitals & PageSpeed Booster','web-vitals-page-speed-booster'), '__return_false', 'webvital_dashboard_section');
		
		/* add_settings_field(
			'web_vital_setting_1',								// ID
			'Ads lazyload',			// Title
			array($this, 'lazy_load_callback'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		); */
		add_settings_field(
			'web_vital_setting_2',								// ID
			esc_html__('Ads Optimization','web-vitals-page-speed-booster'),			// Title
			array($this, 'load_on_scroll'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
		add_settings_field(
			'web_vital_setting_3',								// ID
			esc_html__('Script URLs','web-vitals-page-speed-booster'),			// Title
			array($this, 'list_of_urls'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
		add_settings_field(
			'web_vital_setting_4',								// ID
			esc_html__('Remove Unused css','web-vitals-page-speed-booster'),			// Title
			array($this, 'remove_unused_css'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
		
		add_settings_field(
			'web_vital_setting_5',								// ID
			esc_html__('Image Native Lazy Load','web-vitals-page-speed-booster'),			// Title
			array($this, 'native_lazyload_image'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
		add_settings_field(
			'web_vital_setting_6',								// ID
			esc_html__('Image convert to webp','web-vitals-page-speed-booster'),			// Title
			array($this, 'image_convert_webp'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
	}

	function image_convert_webp(){
		// Get Settings
		$settings = web_vital_defaultSettings(); 
		?>
		<input type="checkbox" name="webvital_settings[image_convert_webp]" id="webvital_settings[image_convert_webp]" class="" <?php echo (isset( $settings['image_convert_webp'] ) &&  $settings['image_convert_webp'] == 1 ? 'checked="checked"' : ''); ?> value="1">
	               
		<?php
	}

	function native_lazyload_image(){
		// Get Settings
		$settings = web_vital_defaultSettings(); 
		?>
		<input type="checkbox" name="webvital_settings[native_lazyload_image]" id="webvital_settings[native_lazyload_image]" class="" <?php echo (isset( $settings['native_lazyload_image'] ) &&  $settings['native_lazyload_image'] == 1 ? 'checked="checked"' : ''); ?> value="1">
	               
		<?php
	}
	function remove_unused_css(){
		// Get Settings
		$settings = web_vital_defaultSettings(); 
		?>
		<input type="checkbox" name="webvital_settings[remove_unused_css]" id="webvital_settings[remove_unused_css]" class="" <?php echo (isset( $settings['remove_unused_css'] ) &&  $settings['remove_unused_css'] == 1 ? 'checked="checked"' : ''); ?> value="1">
	               
		<?php
	}
	function lazy_load_callback(){
		// Get Settings
		$settings = web_vital_defaultSettings(); 
		?>
		<input type="checkbox" name="webvital_settings[lazy_load]" id="webvital_settings[lazy_load]" class="" <?php echo (isset( $settings['lazy_load'] ) &&  $settings['lazy_load'] == 1 ? 'checked="checked"' : ''); ?> value="1">
	               
		<?php
	}

	function load_on_scroll(){
		// Get Settings
		$settings = web_vital_defaultSettings(); 
		?>
		<input type="checkbox" name="webvital_settings[load_on_scroll]" id="webvital_settings[load_on_scroll]" class="" <?php echo (isset( $settings['load_on_scroll'] ) &&  $settings['load_on_scroll'] == 1 ? 'checked="checked"' : ''); ?> value="1">
	               
		<?php
	}

	function list_of_urls(){
		// Get Settings
		$settings = web_vital_defaultSettings(); 
		$rows = '';
		if(isset($settings['list_of_urls'])){
			foreach ($settings['list_of_urls'] as $key => $url_enter) {
				$rows .= '<div class="ads_uri_row">
					<input type="input" name="webvital_settings[list_of_urls][]" class="" value="'.$url_enter.'" placeholder="Ads script url">
					<span style="cursor: pointer;" class="remove_url_row"><span class="dashicons dashicons-no-alt"></span></span>
				</div>';
			}
		}else{
			$rows .= '<div class="ads_uri_row">
					<input type="input" name="webvital_settings[list_of_urls][]" class="" value="" placeholder="Ads script url">
					<span style="cursor: pointer;" class="remove_url_row"><span class="dashicons dashicons-no-alt"></span></span>
				</div>';
		}
		?>
		<style type="text/css">.ads_uri_row {margin-top: 10px;}</style>
		<div id="ads_url_wrapper">
			<?php echo $rows; ?>
	     </div>  
	     <br/>    
			<input type="button" class="add_new_row_url" value="Add">
		<?php
	}

	public function parse_clear_cached_css(){
		if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'web-vital-security-nonce')){
			echo json_encode(array("status"=> 400, "msg"=>"Security verification failed, Refresh the page"));die;
		}

		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
		$dir_handle = opendir($user_dirname);
		if (!$dir_handle){
          echo json_encode(array("status"=> 400, "msg"=>"cache not found"));die;
		}
		while($file = readdir($dir_handle)) {
			if (strpos($file, '.css')!==false){
				unlink($user_dirname."/".$file);
			}
		}
		closedir($dir_handle);
		echo json_encode(array("status"=> 200, "msg"=>"CSS cleared"));die;
	}

	function parse_style_css(){
		if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'web-vital-security-nonce')){
			return json_encode(array());
		}
		$url = $_POST['url'];
		$request = wp_remote_get($url);
		if( is_wp_error( $request ) ) {
			return false; // Bail early
		}
		$html = wp_remote_retrieve_body( $request );
		require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/style-sanitizer.php";
		$tmpDoc = new DOMDocument();
		libxml_use_internal_errors(true);
		$tmpDoc->loadHTML($html);
		$arg['allow_dirty_styles'] = false;
		$obj = new webvital_Style_TreeShaking($tmpDoc, $arg);
		$datatrack = $obj->sanitize();
		return $html;
	}
}
new webVitalAdmin();