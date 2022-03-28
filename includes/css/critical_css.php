<?php

class criticalCss{
	public function __construct(){
		$this->init();
	}

	public function init(){
		if(!is_admin()){
			add_action( 'wp_enqueue_scripts', array($this, 'scripts_styles') );
			add_action('wp_head', array($this, 'print_style_cc'), 100);
		}
		add_action("wp_ajax_cc_call", array($this, 'grab_cc_css'));
			add_action("wp_ajax_nopriv_cc_call", array($this, 'grab_cc_css'));
	}

	function scripts_styles(){
		wp_register_script('corewvps-cc', CWVPSB_PLUGIN_DIR_URI.'/includes/javascript/cc.js', array('jquery'), CWVPSB_VERSION, true);

		wp_enqueue_script('corewvps-cc');
		global $wp;
		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb';

		$data = array('ajaxurl'=>admin_url( 'admin-ajax.php' ),
					'cc_nonce'   => wp_create_nonce('cc_ajax_check_nonce'),
					'current_url' => home_url( $wp->request ),
					'grab_cc_check'=> (file_exists($user_dirname."/".md5(home_url( $wp->request )).".css")? 1: 2), 
					'test'=>$user_dirname."/".md5(home_url( $wp->request )).".css"
					);
		wp_localize_script('corewvps-cc', 'cwvpb_ccdata', $data);
	}

	function grab_cc_css(){
		if ( ! isset( $_POST['security_nonce'] ) ){
	       echo json_encode( array("message"=>"security nonce not found") );die;  
	    }
	    if ( !wp_verify_nonce( $_POST['security_nonce'], 'cc_ajax_check_nonce' ) ){
	       echo json_encode( array("message"=>"security nonce wrong") );die;  
	    }
	    $targetUrl = $_POST['current_url'];
	    $URL = 'http://45.32.112.172/?url='.$targetUrl;
	    $response = wp_remote_get($URL, array());
	    $resStatuscode = wp_remote_retrieve_response_code( $response );
	    if($resStatuscode==200){
	    	$response = wp_remote_retrieve_body($response);
	    	$responseArr = json_decode($response, true);
	    	if($responseArr["status"] != 200){
	    		echo json_encode( array("status"=>$responseArr["status"]) );die;
	    	}
	    	$upload_dir = wp_upload_dir(); 
			$user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb';
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);

			$content = $responseArr['critical_css'];
			$new_file = $user_dirname."/".md5($URL).".css";
			$ifp = @fopen( $new_file, 'w+' );
			if ( ! $ifp ) {
	          echo json_encode(  array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) ));die;
	        }
	        $result = @fwrite( $ifp, $content );
		    fclose( $ifp );

	    	echo json_encode(array("status"=>200));die;
	    }else{
	    	echo json_encode(array("status"=>$resStatuscode, 'message'=> 'return from server'));die;
	    }





	}


	function print_style_cc(){
		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb';

		global $wp;
		$url = home_url( $wp->request );
		if(file_exists($user_dirname.'/'.md5($url).'.css')){
			$css =  file_get_contents($user_dirname.'/'.md5($url).'.css');
		 	echo "<style>$css</style>";
		}
	}
}
$cwvpbCriticalCss = new criticalCss();