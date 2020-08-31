<?php
class webVitalAdmin{
	function __construct(){
		$this-> init();
	}

	function init(){
		add_action( 'admin_menu', array($this,'add_menu_links'));
		add_action('admin_init',array($this, 'dashboard_section'));
		add_action('wp_ajax_parse_clear_cached_css', array($this, 'parse_clear_cached_css'));
		add_action('wp_ajax_list_files_to_convert', array($this, 'get_list_convert_files'));
		add_action('wp_ajax_webvital_webp_convert_file', array($this, 'webp_convert_file'));
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
		add_thickbox();
		wp_enqueue_script( 'web-vital-admin-script', WEBVITAL_PAGESPEED_BOOSTER_URL . 'assets/admin-script.js', array('jquery'), WEBVITAL_PAGESPEED_BOOSTER_VERSION."&test", true );
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
		add_settings_field(
			'web_vital_setting_7',								// ID
			esc_html__('Bulk Image convert to webp','web-vitals-page-speed-booster'),			// Title
			array($this, 'image_convert_webp_bulk'),					// Callback
			'webvital_dashboard_section',							// Page slug
			'webvital_dashboard_section'							// Settings Section ID
		);
	}

	function image_convert_webp_bulk(){
		$webp_nonce = wp_create_nonce('web-vital-security-nonce');
		echo "<button type='button' class='bulk_convert_webp' data-nonce='".$webp_nonce."'>".esc_html__('Bulk convert to webp','web-vitals-page-speed-booster')."</button><span id='bulk_convert_message'></span>
			<div style='display:none;'><div id='bulkconverUpload-wrap'><div class='bulkconverUpload'>".esc_html__('Please wait...','web-vitals-page-speed-booster')."</div></div></div>
			";
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
					<input type="input" name="webvital_settings[list_of_urls][]" class="" value="'.$url_enter.'" placeholder="'.esc_html__('Ads script url','web-vitals-page-speed-booster').'">
					<span style="cursor: pointer;" class="remove_url_row"><span class="dashicons dashicons-no-alt"></span></span>
				</div>';
			}
		}else{
			$rows .= '<div class="ads_uri_row">
					<input type="input" name="webvital_settings[list_of_urls][]" class="" value="" placeholder="'.esc_html__('Ads script url','web-vitals-page-speed-booster').'">
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

	function get_list_convert_files(){
		if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'web-vital-security-nonce')){
			echo json_encode(array('status'=>500 ,"msg"=>'Request Security not verified'));die;
		}
		$listOpt = array();
		$upload = wp_upload_dir();
		$listOpt['root'] = $upload['basedir'];
		$listOpt['filter'] = [
                'only-converted' => false,
                'only-unconverted' => true,
                'image' => 3,
                '_regexPattern'=> '#\.(jpe?g|png)$#'
            ];
       	$years = array(date("Y"),date("Y",strtotime("-1 year")),date("Y",strtotime("-2 year")),date("Y",strtotime("-3 year")),date("Y",strtotime("-4 year")),date("Y",strtotime("-6 year")) );

       	$fileArray = array();
       	foreach ($years as $key => $year) {
       		$images = $this->getFilesListRecursively($year, $listOpt);
       		$fileArray = array_merge($fileArray, $images);
       	}
		$sort = array();
		foreach($fileArray as $keys=>$file){
			$sort[$file[1]][] = $file[0];
		}
		krsort($sort);
		$files = array();
		foreach($sort as $asort){
			foreach($asort as $file){
				$files[] = $file;
			}
		}
        $response['files'] = array_filter($files);

        $response['status'] = 200;
        $response['message'] = ($response['files'])? 'Files are available to convert': 'All files are converted';
        $response['count'] = count($response['files']);
        echo json_encode($response);die;
	}

	function getFilesListRecursively($currentDir, &$listOpt){
		$dir = $listOpt['root'] . '/' . $currentDir;
		$dir = $this->canonicalize($dir);
		if (!@file_exists($dir) || !@is_dir($dir)) {
            return [];
        }
        $fileIterator = new \FilesystemIterator($dir);
        $results = [];
        $filter = &$listOpt['filter'];
        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();
			$filedate = $fileIterator->getCTime();
            if (($filename != ".") && ($filename != "..")) {
                if (@is_dir($dir . "/" . $filename)) {
                    $results = array_merge($results, $this->getFilesListRecursively($currentDir . "/" . $filename, $listOpt));
                } else {
                    // its a file - check if its a jpeg or png
					if (preg_match('#\.(jpe?g|png)$#', $filename)) {
                        $addThis = true;

                        if (($filter['only-converted']) || ($filter['only-unconverted'])) {

                            $destinationPath = $listOpt['root']."/web-vital-webp";
                            if(!is_dir($destinationPath)) { wp_mkdir_p($destinationPath); }
                            
                            $destination = str_replace($listOpt['root'], $destinationPath, $dir);
                            $destination .= "/".$filename.'.webp';
                            $webpExists = @file_exists($destination);
                            

                            if (!$webpExists && ($filter['only-converted'])) {
                                $addThis = false;
                            }
                            if ($webpExists && ($filter['only-unconverted'])) {
                                $addThis = false;
                            }
                        } else {
                            $addThis = true;
                        }
                        if ($addThis) {
                            $results[] = array($currentDir . "/" . $filename, $filedate);      // (we cut the leading "./" off with substr)
                            //$results[] = substr($currentDir . "/", 2) . $filename;      // (we cut the leading "./" off with substr)
                        }
                    }
                }
            }
            $fileIterator->next();
        }
        return $results;
	}

	function canonicalize($path){
		$parts = explode('/', $path);

	    // Remove parts containing just '.' (and the empty holes afterwards)
	    $parts = array_values(array_filter($parts, function($var) {
	        return ($var != '.');
	    }));

	      // Remove parts containing '..' and the preceding
	      $keys = array_keys($parts, '..');
	      foreach($keys as $keypos => $key) {
	        array_splice($parts, $key - ($keypos * 2 + 1), 2);
	      }
	      return implode('/', $parts);
	    
	}

	function webp_convert_file(){
		if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'web-vital-security-nonce')){
			echo json_encode(array('status'=>500 ,"msg"=>'Request Security not verified'));die;
		}
		$filename = sanitize_text_field(stripslashes($_POST['filename']));
		$filename = wp_unslash($_POST['filename']);

		$upload = wp_upload_dir();
		$destinationPath = $upload['basedir']."/web-vital-webp";
		if(!is_dir($destinationPath)) { wp_mkdir_p($destinationPath); }
		$destination = $destinationPath.'/'. $filename.".webp";

		$source = $upload['basedir']."/".$filename;

		try {
			require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/vendor/autoload.php";
			$convertOptions = [];
			\WebPConvert\WebPConvert::convert($source, $destination, $convertOptions);
		} catch (\WebpConvert\Exceptions\WebPConvertException $e) {
            if(function_exists('error_log')){ error_log($e->getMessage()); }
        } catch (\Exception $e) {
        	$message = 'An exception was thrown!';
            if(function_exists('error_log')){ error_log($e->getMessage()); }
        }
        echo json_encode(array('status'=>200 ,"msg"=>'File converted successfully'));die;
	}
}
new webVitalAdmin();