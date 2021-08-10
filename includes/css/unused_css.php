<?php 
add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_unused_css');
function cwvpsb_unused_css($html){
	require_once CWVPSB_PLUGIN_DIR."/includes/style-sanitizer.php";
	$tmpDoc = new DOMDocument();
	libxml_use_internal_errors(true);
	$tmpDoc->loadHTML($html);
	$error_codes = [];
	$args        = [
		'validation_error_callback' => static function( $error ) use ( &$error_codes ) {
					$error_codes[] = $error['code'];
		},
		'should_locate_sources'=>true,
		'use_document_element'=>true,
		'include_manifest_comment'=>false,
	];
	$parser = new cwvpsb_treeshaking($tmpDoc,$args);
	$sanitize = $parser->sanitize();
	$custom_style_element = $tmpDoc->createElement( 'style' );
	$tmpDoc->head->appendChild( $custom_style_element );
	$html = $tmpDoc->saveHTML();
	return $html;
}
