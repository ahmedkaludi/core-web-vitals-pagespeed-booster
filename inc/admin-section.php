<?php
class webVitalAdmin{
	function __construct(){
		$this-> init();
	}

	function init(){
		add_action( 'admin_menu', array($this,'add_menu_links'));
		add_action('admin_init',array($this, 'dashboard_section'));
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
		submit_button( esc_html__('Save Settings', 'web-vitals-page-speed-booster') );
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
			'Ads Optimization',			// Title
			array($this, 'load_on_scroll'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
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
}
new webVitalAdmin();