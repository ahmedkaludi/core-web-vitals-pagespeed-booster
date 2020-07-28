<?php
require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/vendor/autoload.php";

use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\CSSList\CSSList;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\RuleSet\RuleSet;
use Sabberworm\CSS\Property\AtRule;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\CSSList\KeyFrame;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Property\Import;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\Value\URL;
use Sabberworm\CSS\Value\Value;
use Sabberworm\CSS\CSSList\Document as CSSDocument;

class webvital_Style_TreeShaking{
	const TREE_SHAKING_ERROR_CODE = 'removed_unused_css_rules';
	const ILLEGAL_AT_RULE_ERROR_CODE = 'illegal_css_at_rule';
	const INLINE_SPECIFICITY_MULTIPLIER = 5;
	protected $args;
	private $dom;
	private $pending_stylesheets = array();
	private $stylesheets = array();
	private $style_custom_cdata_spec;
	private $custom_style_element;
	private $style_keyframes_cdata_spec;
	private $allowed_font_src_regex;
	private $base_url;
	private $content_url;
	private $used_class_names = array();
	private $used_tag_names = array();
	private $xpath;
	private $parse_css_duration = 0.0;
	private $head;
	private $current_node;
	private $current_sources;
	private $processed_imported_stylesheet_urls = array();
	private $imported_font_urls = array();
	private $selector_mappings = array();
	public static function has_required_php_css_parser() {
		$has_required_methods = (
			method_exists( 'Sabberworm\CSS\CSSList\Document', 'splice' )
			&&
			method_exists( 'Sabberworm\CSS\CSSList\Document', 'replace' )
		);
		if ( ! $has_required_methods ) {
			return false;
		}

		$reflection = new ReflectionClass( 'Sabberworm\CSS\OutputFormat' );

		$has_output_format_extensions = (
			$reflection->hasProperty( 'sBeforeAtRuleBlock' )
			&&
			$reflection->hasProperty( 'sAfterAtRuleBlock' )
			&&
			$reflection->hasProperty( 'sBeforeDeclarationBlock' )
			&&
			$reflection->hasProperty( 'sAfterDeclarationBlockSelectors' )
			&&
			$reflection->hasProperty( 'sAfterDeclarationBlock' )
		);
		if ( ! $has_output_format_extensions ) {
			return false;
		}

		return true;
	}
	public function __construct( DOMDocument $dom, array $args = array() ) {
		$this->dom = $dom;
		/* foreach ( AMP_PB_Allowed_Tags_Generated::get_allowed_tag( 'style' ) as $spec_rule ) {
			if ( ! isset( $spec_rule[ AMP_PB_Rule_Spec::TAG_SPEC ]['spec_name'] ) ) {
				continue;
			}
			if ( 'style[amp-keyframes]' === $spec_rule[ AMP_PB_Rule_Spec::TAG_SPEC ]['spec_name'] ) {
				$this->style_keyframes_cdata_spec = $spec_rule[ AMP_PB_Rule_Spec::CDATA ];
			} elseif ( 'style amp-custom' === $spec_rule[ AMP_PB_Rule_Spec::TAG_SPEC ]['spec_name'] ) {
				$this->style_custom_cdata_spec = $spec_rule[ AMP_PB_Rule_Spec::CDATA ];
			}
		} */

		/* $spec_name = 'link rel=stylesheet for fonts'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		foreach ( AMP_PB_Allowed_Tags_Generated::get_allowed_tag( 'link' ) as $spec_rule ) {
			if ( isset( $spec_rule[ AMP_PB_Rule_Spec::TAG_SPEC ]['spec_name'] ) && $spec_name === $spec_rule[ AMP_PB_Rule_Spec::TAG_SPEC ]['spec_name'] ) {
				$this->allowed_font_src_regex = '@^(' . $spec_rule[ AMP_PB_Rule_Spec::ATTR_SPEC_LIST ]['href']['value_regex'] . ')$@';
				break;
			}
		} */

		$guessurl = site_url();
		if ( ! $guessurl ) {
			$guessurl = wp_guess_url();
		} 
		$this->base_url    = $guessurl;
		$this->content_url = WP_CONTENT_URL;
		$this->xpath       = new DOMXPath( $dom );
	}
	public function get_styles() {
		return array();
	}
	public function get_stylesheets() {
		return $this->stylesheets;
	}
	private function get_used_class_names() {
		if ( empty( $this->used_class_names ) ) {
			$classes = ' ';
			foreach ( $this->xpath->query( '//*/@class' ) as $class_attribute ) {
				$classes .= ' ' . $class_attribute->nodeValue;
			}
			$customClassPrefix = 'cdd-bind-%s-'.md5( rand() );
			foreach ( $this->xpath->query( '//*/@' . $customClassPrefix . 'class' ) as $bound_class_attribute ) {
				if ( preg_match_all( '/([\'"])([^\1]*?)\1/', $bound_class_attribute->nodeValue, $matches ) ) {
					$classes .= ' ' . implode( ' ', $matches[2] );
				}
			}
			$this->used_class_names = array_unique( array_filter( preg_split( '/\s+/', trim( $classes ) ) ) );
		}
		return $this->used_class_names;
	}
	private function get_used_tag_names() {
		if ( empty( $this->used_tag_names ) ) {
			$used_tag_names = array();
			foreach ( $this->dom->getElementsByTagName( '*' ) as $el ) {
				$used_tag_names[ $el->tagName ] = true;
			}
			$this->used_tag_names = array_keys( $used_tag_names );
		}
		return $this->used_tag_names;
	}
	/* public function init( $sanitizers ) {
		parent::init( $sanitizers );

		foreach ( $sanitizers as $sanitizer ) {
			foreach ( $sanitizer->get_selector_conversion_mapping() as $html_selectors => $amp_selectors ) {
				if ( ! isset( $this->selector_mappings[ $html_selectors ] ) ) {
					$this->selector_mappings[ $html_selectors ] = $amp_selectors;
				} else {
					$this->selector_mappings[ $html_selectors ] = array_unique(
						array_merge( $this->selector_mappings[ $html_selectors ], $amp_selectors )
					);
				}

				// Prevent selectors like `amp-img img` getting deleted since `img` does not occur in the DOM.
				$this->args['dynamic_element_selectors'] = array_merge(
					$this->args['dynamic_element_selectors'],
					$this->selector_mappings[ $html_selectors ]
				);
			}
		}
	} */
	public function sanitize() {
		$elements = array();
		$this->head = $this->dom->getElementsByTagName( 'head' )->item( 0 );
		if ( ! $this->head ) {
			$this->head = $this->dom->createElement( 'head' );
			$this->dom->documentElement->insertBefore( $this->head, $this->dom->documentElement->firstChild );
		}

		$this->parse_css_duration = 0.0;
		$xpath = $this->xpath;
		$lower_case = 'translate( %s, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz" )'; // In XPath 2.0 this is lower-case().
		$predicates = array(
			sprintf( '( self::style and ( not( @type ) or %s = "text/css" ) )', sprintf( $lower_case, '@type' ) ),
			sprintf( '( self::link and @href and %s = "stylesheet" )', sprintf( $lower_case, '@rel' ) ),
			'( self::style and  @amp-custom )' ,
		);

		foreach ( $xpath->query( '//*[ ' . implode( ' or ', $predicates ) . ' ]' ) as $element ) {
			$elements[] = $element;
		}
		foreach ( $this->dom->getElementsByTagName( 'col' ) as $col ) {
			$width_attr = $col->getAttribute( 'width' );
			if ( ! empty( $width_attr ) && ( false === strpos( $width_attr, '*' ) ) ) {
				$width_style = 'width: ' . $width_attr;
				if ( is_numeric( $width_attr ) ) {
					$width_style .= 'px';
				}
				if ( $col->hasAttribute( 'style' ) ) {
					$col->setAttribute( 'style', $width_style . ';' . $col->getAttribute( 'style' ) );
				} else {
					$col->setAttribute( 'style', $width_style );
				}
				$col->removeAttribute( 'width' );
			}
		}
		foreach ( $elements as $element ) {
			$node_name = strtolower( $element->nodeName );
			if ( 'style' === $node_name ) {
				$this->process_style_element( $element );
			} elseif ( 'link' === $node_name ) {
				$this->process_link_element( $element );
			} 
		}

		$elements = array();
		foreach ( $xpath->query( '//*[ @style ]' ) as $element ) {
			$elements[] = $element;
		}
		foreach ( $elements as $element ) {
			$this->collect_inline_styles( $element );
		}
		$this->finalize_styles();
		$this->did_convert_elements = true;
	}
	public function get_validated_url_file_path( $url, $allowed_extensions = array() ) {
		$needs_base_url = (
			! is_bool( $url )
			&&
			! preg_match( '|^(https?:)?//|', $url )
			&&
			! ( $this->content_url && 0 === strpos( $url, $this->content_url ) )
		);
		if ( $needs_base_url ) {
			$url = $this->base_url . $url;
		}

		$remove_url_scheme = function( $schemed_url ) {
			return preg_replace( '#^\w+:(?=//)#', '', $schemed_url );
		};
		$url = $remove_url_scheme( preg_replace( ':[\?#].*$:', '', $url ) );

		$includes_url = $remove_url_scheme( includes_url( '/' ) );
		$content_url  = $remove_url_scheme( content_url( '/' ) );
		$admin_url    = $remove_url_scheme( get_admin_url( null, '/' ) );
		$site_url     = $remove_url_scheme( site_url( '/' ) );
		$allowed_hosts = array(
			wp_parse_url( $includes_url, PHP_URL_HOST ),
			wp_parse_url( $content_url, PHP_URL_HOST ),
			wp_parse_url( $admin_url, PHP_URL_HOST ),
		);
		$url_host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! empty( $allowed_extensions ) ) {
			$pattern = sprintf( '/\.(%s)$/i', implode( '|', $allowed_extensions ) );
			if ( ! preg_match( $pattern, $url ) ) {
				return new WP_Error( 'disallowed_file_extension', sprintf( __( 'File does not have an allowed file extension for filesystem access (%s).', 'web-vitals-page-speed-booster' ), $url ) );
			}
		}
		if ( ! in_array( $url_host, $allowed_hosts, true ) ) {
			return new WP_Error( 'external_file_url', sprintf( __( 'URL is located on an external domain: %s.', 'web-vitals-page-speed-booster' ), $url_host ) );
		}
		$base_path  = null;
		$file_path  = null;
		$wp_content = 'wp-content';
		if ( 0 === strpos( $url, $content_url ) ) {
			$base_path = WP_CONTENT_DIR;
			$file_path = substr( $url, strlen( $content_url ) - 1 );
		} elseif ( 0 === strpos( $url, $includes_url ) ) {
			$base_path = ABSPATH . WPINC;
			$file_path = substr( $url, strlen( $includes_url ) - 1 );
		} elseif ( 0 === strpos( $url, $admin_url ) ) {
			$base_path = ABSPATH . 'wp-admin';
			$file_path = substr( $url, strlen( $admin_url ) - 1 );
		} elseif ( 0 === strpos( $url, $site_url . trailingslashit( $wp_content ) ) ) {
			$base_path = ABSPATH . $wp_content;
			$file_path = substr( $url, strlen( $site_url ) + strlen( $wp_content ) );
		}

		if ( ! $file_path || false !== strpos( $file_path, '../' ) || false !== strpos( $file_path, '..\\' ) ) {
			return new WP_Error( 'file_path_not_allowed', sprintf( __( 'Disallowed URL filesystem path for %s.', 'web-vitals-page-speed-booster' ), $url ) );
		}
		if ( ! file_exists( $base_path . $file_path ) ) {
			return new WP_Error( 'file_path_not_found', sprintf( __( 'Unable to locate filesystem path for %s.', 'web-vitals-page-speed-booster' ), $url ) );
		}

		return $base_path . $file_path;
	}
	private function set_current_node( $node ) {
		if ( $this->current_node === $node ) {
			return;
		}

		$this->current_node = $node;
		if ( empty( $node ) ) {
			$this->current_sources = null;
		} /* elseif ( ! empty( $this->args['should_locate_sources'] ) ) {
			$this->current_sources = AMP_Validation_Manager::locate_sources( $node );
		} */
	}
	private function process_style_element( DOMElement $element ) {
		$this->set_current_node( $element );
		$is_keyframes = $element->hasAttribute( 'amp-keyframes' );
		$stylesheet   = trim( $element->textContent );
		$cdata_spec   = $is_keyframes ? $this->style_keyframes_cdata_spec : $this->style_custom_cdata_spec;
		$media = $element->getAttribute( 'media' );
		if ( $media && 'all' !== $media ) {
			$stylesheet = sprintf( '@media %s { %s }', $media, $stylesheet );
		}

		$processed = $this->process_stylesheet( $stylesheet, array(
			'allowed_at_rules'   => $cdata_spec['css_spec']['allowed_at_rules'],
			'property_whitelist' => $cdata_spec['css_spec']['declaration'],
			'validate_keyframes' => $cdata_spec['css_spec']['validate_keyframes'],
		) );
		$this->pending_stylesheets[] = array_merge(
			array(
				'keyframes' => $is_keyframes,
				'node'      => $element,
				'sources'   => $this->current_sources,
			),
			wp_array_slice_assoc( $processed, array( 'stylesheet', 'imported_font_urls' ) )
		);

		if ( $element->hasAttribute( 'amp-custom' ) ) {
			if ( ! $this->custom_style_element ) {
				$this->custom_style_element = $element;
			} else {
				$element->parentNode->removeChild( $element );
			}
		} else {
			$element->parentNode->removeChild( $element );
		}

		$this->set_current_node( null );
	}
	private function process_link_element( DOMElement $element ) {
		$href = $element->getAttribute( 'href' );
		$normalized_url = preg_replace( '#^(http:)?(?=//)#', 'https:', $href );
		if ( $this->allowed_font_src_regex && preg_match( $this->allowed_font_src_regex, $normalized_url ) ) {
			if ( $href !== $normalized_url ) {
				$element->setAttribute( 'href', $normalized_url );
			}
			$needs_preconnect_link = (
				'https://fonts.googleapis.com/' === substr( $normalized_url, 0, 29 )
				&&
				0 === $this->xpath->query( '//link[ @rel = "preconnect" and @crossorigin and starts-with( @href, "https://fonts.gstatic.com" ) ]', $this->head )->length
			);
			if ( $needs_preconnect_link ) {
				$link = AMP_PB_DOM_Utils::create_node( $this->dom, 'link', array(
					'rel'         => 'preconnect',
					'href'        => 'https://fonts.gstatic.com/',
					'crossorigin' => '',
				) );
				$this->head->insertBefore( $link );
			}
			return;
		}
		$css_file_path = $this->get_validated_url_file_path( $href, array( 'css', 'less', 'scss', 'sass' ) );
		if ( is_wp_error( $css_file_path ) && ( 'disallowed_file_extension' === $css_file_path->get_error_code() || 'external_file_url' === $css_file_path->get_error_code() ) ) {
			$contents = $this->fetch_external_stylesheet( $normalized_url );
			if ( is_wp_error( $contents ) ) {
				$this->remove_invalid_child( $element, array(
					'code'    => $css_file_path->get_error_code(),
					'message' => $css_file_path->get_error_message(),
					'type'    => 'amp_validation_error',
				) );
				return;
			} else {
				$stylesheet = $contents;
			}
		} elseif ( is_wp_error( $css_file_path ) ) {
			$this->remove_invalid_child( $element, array(
				'code'    => $css_file_path->get_error_code(),
				'message' => $css_file_path->get_error_message(),
				'type'    => 'amp_validation_error',
			) );
			return;
		} else {
			$stylesheet = file_get_contents( $css_file_path );
		}

		if ( false === $stylesheet ) {
			$this->remove_invalid_child( $element, array(
				'code' => 'stylesheet_file_missing',
				'type' => 'amp_validation_error',
			) );
			return;
		}
		$media = $element->getAttribute( 'media' );
		if ( $media && 'all' !== $media ) {
			$stylesheet = sprintf( '@media %s { %s }', $media, $stylesheet );
		}
		$this->set_current_node( $element );
		$processed = $this->process_stylesheet( $stylesheet, array(
			'allowed_at_rules'   => $this->style_custom_cdata_spec['css_spec']['allowed_at_rules'],
			'property_whitelist' => $this->style_custom_cdata_spec['css_spec']['declaration'],
			'stylesheet_url'     => $href,
			'stylesheet_path'    => $css_file_path,
		) );
		$this->pending_stylesheets[] = array_merge(
			array(
				'keyframes' => false,
				'node'      => $element,
				'sources'   => $this->current_sources, 
			),
			wp_array_slice_assoc( $processed, array( 'stylesheet', 'imported_font_urls' ) )
		);
		$element->parentNode->removeChild( $element );
		$this->set_current_node( null );
	}
	private function fetch_external_stylesheet( $url ) {
		$cache_key = md5( $url );
		$contents  = web_vital_style_get_file_transient( $cache_key );
		if ( false === $contents ) {
			$r = wp_remote_get( $url );
			if ( 200 !== wp_remote_retrieve_response_code( $r ) ) {
				$contents = new WP_Error(
					wp_remote_retrieve_response_code( $r ),
					wp_remote_retrieve_response_message( $r )
				);
			} else {
				$contents = wp_remote_retrieve_body( $r );
			}
			amp_pbc_set_file_transient( $cache_key, $contents, MONTH_IN_SECONDS );
		}
		return $contents;
	}
	private function process_stylesheet( $stylesheet, $options = array() ) {
		$parsed      = null;
		$cache_key   = null;
		$cache_group = 'amp-parsed-stylesheet-v13';

		$cache_impacting_options = array_merge(
			wp_array_slice_assoc(
				$options,
				array( 'property_whitelist', 'property_blacklist', 'stylesheet_url', 'allowed_at_rules' )
			),
			wp_array_slice_assoc(
				$this->args,
				array( 'should_locate_sources', 'parsed_cache_variant' )
			),
			array(
				'language' => 'en',
			)
		);
		$cache_key = md5( $stylesheet . wp_json_encode( $cache_impacting_options ) );

		if ( wp_using_ext_object_cache() ) {
			$parsed = wp_cache_get( $cache_key, $cache_group );
		} else {
			$parsed = web_vital_style_get_file_transient( $cache_key . $cache_group );
		} 
		if ( ! empty( $parsed['validation_results'] ) ) {
			foreach ( $parsed['validation_results'] as $validation_result ) {
				$sanitized = $this->should_sanitize_validation_error( $validation_result['error'] );
				if ( $sanitized !== $validation_result['sanitized'] ) {
					$parsed = null; 
					break;
				}
			}
		}
		if ( ! $parsed || ! isset( $parsed['stylesheet'] ) || ! is_array( $parsed['stylesheet'] ) ) {
			$parsed = $this->prepare_stylesheet( $stylesheet, $options );
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $parsed, $cache_group );
			} else {
				web_vital_set_file_transient( $cache_key . $cache_group, $parsed, MONTH_IN_SECONDS );
			} 
		}

		return $parsed;
	}
	private function parse_import_stylesheet( Import $item, CSSList $css_list, $options ) {
		$results      = array();
		$at_rule_args = $item->atRuleArgs();
		$location     = array_shift( $at_rule_args );
		$media_query  = array_shift( $at_rule_args );

		if ( isset( $options['stylesheet_url'] ) ) {
			$this->real_path_urls( array( $location ), $options['stylesheet_url'] );
		}

		$import_stylesheet_url = $location->getURL()->getString();
		if ( isset( $this->processed_imported_stylesheet_urls[ $import_stylesheet_url ] ) ) {
			$css_list->remove( $item );
			return array();
		}
		$this->processed_imported_stylesheet_urls[ $import_stylesheet_url ] = true;
		$https_import_stylesheet_url = preg_replace( '#^(http:)?(?=//)#', 'https:', $import_stylesheet_url );
		if ( $this->allowed_font_src_regex && preg_match( $this->allowed_font_src_regex, $https_import_stylesheet_url ) ) {
			$this->imported_font_urls[] = $https_import_stylesheet_url;
			$css_list->remove( $item );
			_doing_it_wrong(
				'wp_enqueue_style',
				esc_html( sprintf(
					__( 'It is not a best practice to use @import to load font CDN stylesheets. Please use wp_enqueue_style() to enqueue %s as its own separate script.', 'amp' ),
					$import_stylesheet_url
				) ),
				'1.0'
			);
			return array();
		}

		$css_file_path = $this->get_validated_url_file_path( $import_stylesheet_url, array( 'css', 'less', 'scss', 'sass' ) );

		if ( is_wp_error( $css_file_path ) && ( 'disallowed_file_extension' === $css_file_path->get_error_code() || 'external_file_url' === $css_file_path->get_error_code() ) ) {
			$contents = $this->fetch_external_stylesheet( $import_stylesheet_url );
			if ( is_wp_error( $contents ) ) {
				$error     = array(
					'code'    => $contents->get_error_code(),
					'message' => $contents->get_error_message(),
					'type'    => 'amp_validation_error',
				);
				$sanitized = $this->should_sanitize_validation_error( $error );
				if ( $sanitized ) {
					$css_list->remove( $item );
				}
				$results[] = compact( 'error', 'sanitized' );
				return $results;
			} else {
				$stylesheet = $contents;
			}
		} elseif ( is_wp_error( $css_file_path ) ) {
			$error     = array(
				'code'    => $css_file_path->get_error_code(),
				'message' => $css_file_path->get_error_message(),
				'type'    => 'amp_validation_error',
			);
			$sanitized = $this->should_sanitize_validation_error( $error );
			if ( $sanitized ) {
				$css_list->remove( $item );
			}
			$results[] = compact( 'error', 'sanitized' );
			return $results;
		} else {
			$stylesheet = file_get_contents( $css_file_path ); // phpcs:ignore -- It's a local filesystem path not a remote request.
		}

		if ( $media_query ) {
			$stylesheet = sprintf( '@media %s { %s }', $media_query, $stylesheet );
		}

		$options['stylesheet_url'] = $import_stylesheet_url;

		$parsed_stylesheet = $this->parse_stylesheet( $stylesheet, $options );

		$results = array_merge(
			$results,
			$parsed_stylesheet['validation_results']
		);
		$css_document = $parsed_stylesheet['css_document'];
		if ( ! empty( $parsed_stylesheet['css_document'] ) && method_exists( $css_list, 'replace' ) ) {
			$css_list->replace( $item, $css_document->getContents() );
		} else {
			$css_list->remove( $item );
		}
		return $results;
	}
	private function parse_stylesheet( $stylesheet_string, $options ) {
		$validation_results = array();
		$css_document       = null;

		$this->imported_font_urls = array();
		try {
			$stylesheet_string = $this->remove_spaces_from_data_urls( $stylesheet_string );

			$parser_settings = Sabberworm\CSS\Settings::create();
			$css_parser      = new Sabberworm\CSS\Parser( $stylesheet_string, $parser_settings );
			$css_document    = $css_parser->parse();

			if ( ! empty( $options['stylesheet_url'] ) ) {
				$this->real_path_urls(
					array_filter(
						$css_document->getAllValues(),
						function ( $value ) {
							return $value instanceof URL;
						}
					),
					$options['stylesheet_url']
				);
			}

			$validation_results = array_merge(
				$validation_results,
				$this->process_css_list( $css_document, $options )
			);
		} catch ( Exception $exception ) {
			$error = array(
				'code'    => 'css_parse_error',
				'message' => $exception->getMessage(),
				'type'    => 'amp_validation_error',
			);
			$sanitized = $this->should_sanitize_validation_error( $error );

			$validation_results[] = compact( 'error', 'sanitized' );
		}
		return array_merge(
			compact( 'validation_results', 'css_document' ),
			array(
				'imported_font_urls' => $this->imported_font_urls,
			)
		);
	}
	private function prepare_stylesheet( $stylesheet_string, $options = array() ) {
		$start_time = microtime( true );

		$options = array_merge(
			array(
				'allowed_at_rules'   => array(),
				'property_blacklist' => array(
					'behavior',
					'-moz-binding',
				),
				'property_whitelist' => array(),
				'validate_keyframes' => false,
				'stylesheet_url'     => null,
				'stylesheet_path'    => null,
			),
			$options
		);
		$stylesheet_string = preg_replace( '/^\xEF\xBB\xBF/', '', $stylesheet_string );
		$stylesheet         = array();
		$parsed_stylesheet  = $this->parse_stylesheet( $stylesheet_string, $options );
		$validation_results = $parsed_stylesheet['validation_results'];
		if ( ! empty( $parsed_stylesheet['css_document'] ) ) {
			$css_document = $parsed_stylesheet['css_document'];

			$output_format = Sabberworm\CSS\OutputFormat::createCompact();
			$output_format->setSemicolonAfterLastRule( false );

			$before_declaration_block          = '/*WP_BEFORE_DECLARATION_BLOCK*/';
			$between_selectors                 = '/*WP_BETWEEN_SELECTORS*/';
			$after_declaration_block_selectors = '/*WP_BEFORE_DECLARATION_SELECTORS*/';
			$after_declaration_block           = '/*WP_AFTER_DECLARATION*/';
			$before_at_rule                    = '/*WP_BEFORE_AT_RULE*/';
			$after_at_rule                     = '/*WP_AFTER_AT_RULE*/';

			if ( self::has_required_php_css_parser() ) {
				$output_format->set( 'BeforeDeclarationBlock', $before_declaration_block );
				$output_format->set( 'SpaceBeforeSelectorSeparator', $between_selectors );
				$output_format->set( 'AfterDeclarationBlockSelectors', $after_declaration_block_selectors );
				$output_format->set( 'AfterDeclarationBlock', $after_declaration_block );
				$output_format->set( 'BeforeAtRuleBlock', $before_at_rule );
				$output_format->set( 'AfterAtRuleBlock', $after_at_rule );
			}
			$stylesheet_string = $css_document->render( $output_format );
			$pattern  = '#';
			$pattern .= preg_quote( $before_at_rule, '#' );
			$pattern .= '|';
			$pattern .= preg_quote( $after_at_rule, '#' );
			$pattern .= '|';
			$pattern .= '(' . preg_quote( $before_declaration_block, '#' ) . ')';
			$pattern .= '(.+?)';
			$pattern .= preg_quote( $after_declaration_block_selectors, '#' );
			$pattern .= '(.+?)';
			$pattern .= preg_quote( $after_declaration_block, '#' );
			$pattern .= '#s';
			$dynamic_selector_pattern = null;
			if ( ! empty( $this->args['dynamic_element_selectors'] ) ) {
				$dynamic_selector_pattern = implode( '|', array_map(
					function( $selector ) {
						return preg_quote( $selector, '#' );
					},
					$this->args['dynamic_element_selectors']
				) );
			}
			$split_stylesheet = preg_split( $pattern, $stylesheet_string, -1, PREG_SPLIT_DELIM_CAPTURE );
			$length           = count( $split_stylesheet );
			for ( $i = 0; $i < $length; $i++ ) {
				if ( $before_declaration_block === $split_stylesheet[ $i ] ) {
					if ( preg_match( '/^((from|to)\b|-?\d+(\.\d+)?%)/i', $split_stylesheet[ $i + 1 ] ) ) {
						$stylesheet[] = str_replace( $between_selectors, '', $split_stylesheet[ ++$i ] ) . $split_stylesheet[ ++$i ];
						continue;
					}
					$selectors   = explode( $between_selectors . ',', $split_stylesheet[ ++$i ] );
					$declaration = $split_stylesheet[ ++$i ];
					$selectors_parsed = array();
					foreach ( $selectors as $selector ) {
						$selectors_parsed[ $selector ] = array();
						$reduced_selector = preg_replace( '/:[a-zA-Z0-9_-]+(\(.+?\))?/', '', $selector );
						$reduced_selector = preg_replace( '/\[\w.*?\]/', '', $reduced_selector );
						if ( $dynamic_selector_pattern ) {
							$reduced_selector = preg_replace( '#((?:' . $dynamic_selector_pattern . ')(?:\.[a-z0-9_-]+)*)[^a-z0-9_-].*#si', '$1', $reduced_selector . ' ' );
						}
						$reduced_selector = preg_replace_callback(
							'/\.([a-zA-Z0-9_-]+)/',
							function( $matches ) use ( $selector, &$selectors_parsed ) {
								$selectors_parsed[ $selector ]['classes'][] = $matches[1];
								return '';
							},
							$reduced_selector
						);
						$reduced_selector = preg_replace_callback(
							'/#([a-zA-Z0-9_-]+)/',
							function( $matches ) use ( $selector, &$selectors_parsed ) {
								$selectors_parsed[ $selector ]['ids'][] = $matches[1];
								return '';
							},
							$reduced_selector
						);
						if ( preg_match_all( '/[a-zA-Z0-9_-]+/', $reduced_selector, $matches ) ) {
							$selectors_parsed[ $selector ]['tags'] = $matches[0];
						}
					}
					$stylesheet[] = array(
						$selectors_parsed,
						$declaration,
					);
				} else {
					$stylesheet[] = $split_stylesheet[ $i ];
				}
			}
		}
		$this->parse_css_duration += ( microtime( true ) - $start_time );
		return array_merge(
			compact( 'stylesheet', 'validation_results' ),
			array(
				'imported_font_urls' => $parsed_stylesheet['imported_font_urls'],
			)
		);
	}
	protected $previous_should_sanitize_validation_error_results = array();
	public function should_sanitize_validation_error( $validation_error, $data = array() ) {
		if ( ! isset( $data['node'] ) ) {
			$data['node'] = $this->current_node;
		}
		if ( ! isset( $validation_error['sources'] ) ) {
			$validation_error['sources'] = $this->current_sources;
		}
		$args = compact( 'validation_error', 'data' );
		foreach ( $this->previous_should_sanitize_validation_error_results as $result ) {
			if ( $result['args'] === $args ) {
				return $result['sanitized'];
			}
		}
		$sanitized = parent::should_sanitize_validation_error( $validation_error, $data );
		$this->previous_should_sanitize_validation_error_results[] = compact( 'args', 'sanitized' );
		return $sanitized;
	}
	private function remove_spaces_from_data_urls( $css ) {
		return preg_replace_callback(
			'/\burl\([^}]*?\)/',
			function( $matches ) {
				return preg_replace( '/\s+/', '', $matches[0] );
			},
			$css
		);
	}
	private function process_css_list( CSSList $css_list, $options ) {
		$results = array();
		foreach ( $css_list->getContents() as $css_item ) {
			$sanitized = false;
			if ( $css_item instanceof DeclarationBlock && empty( $options['validate_keyframes'] ) ) {
				$results = array_merge(
					$results,
					$this->process_css_declaration_block( $css_item, $css_list, $options )
				);
			} elseif ( $css_item instanceof AtRuleBlockList ) {
				if ( ! in_array( $css_item->atRuleName(), $options['allowed_at_rules'], true ) ) {
					$error     = array(
						'code'    => self::ILLEGAL_AT_RULE_ERROR_CODE,
						'at_rule' => $css_item->atRuleName(),
						'type'    => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					$results[] = compact( 'error', 'sanitized' );
				}
				if ( ! $sanitized ) {
					$results = array_merge(
						$results,
						$this->process_css_list( $css_item, $options )
					);
				}
			} elseif ( $css_item instanceof Import ) {
				$results = array_merge(
					$results,
					$this->parse_import_stylesheet( $css_item, $css_list, $options )
				);
			} elseif ( $css_item instanceof AtRuleSet ) {
				if ( ! in_array( $css_item->atRuleName(), $options['allowed_at_rules'], true ) ) {
					$error     = array(
						'code'    => self::ILLEGAL_AT_RULE_ERROR_CODE,
						'at_rule' => $css_item->atRuleName(),
						'type'    => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					$results[] = compact( 'error', 'sanitized' );
				}

				if ( ! $sanitized ) {
					$results = array_merge(
						$results,
						$this->process_css_declaration_block( $css_item, $css_list, $options )
					);
				}
			} elseif ( $css_item instanceof KeyFrame ) {
				if ( ! in_array( 'keyframes', $options['allowed_at_rules'], true ) ) {
					$error     = array(
						'code'    => self::ILLEGAL_AT_RULE_ERROR_CODE,
						'at_rule' => $css_item->atRuleName(),
						'type'    => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					$results[] = compact( 'error', 'sanitized' );
				}

				if ( ! $sanitized ) {
					$results = array_merge(
						$results,
						$this->process_css_keyframes( $css_item, $options )
					);
				}
			} elseif ( $css_item instanceof AtRule ) {
				if ( 'charset' === $css_item->atRuleName() ) {
					$sanitized = true;
				} else {
					$error     = array(
						'code'    => self::ILLEGAL_AT_RULE_ERROR_CODE,
						'at_rule' => $css_item->atRuleName(),
						'type'    => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					$results[] = compact( 'error', 'sanitized' );
				}
			} else {
				$error     = array(
					'code' => 'unrecognized_css',
					'item' => get_class( $css_item ),
					'type' => 'amp_validation_error',
				);
				$sanitized = $this->should_sanitize_validation_error( $error );
				$results[] = compact( 'error', 'sanitized' );
			}

			if ( $sanitized ) {
				$css_list->remove( $css_item );
			}
		}
		return $results;
	}
	private function real_path_urls( $urls, $stylesheet_url ) {
		$base_url = preg_replace( ':[^/]+(\?.*)?(#.*)?$:', '', $stylesheet_url );
		if ( empty( $base_url ) ) {
			return;
		}

		foreach ( $urls as $url ) {
			$url_string = $url->getURL()->getString();
			if ( 'data:' === substr( $url_string, 0, 5 ) ) {
				continue;
			}
			$parsed_url = wp_parse_url( $url_string );
			if ( ! empty( $parsed_url['host'] ) || empty( $parsed_url['path'] ) || '/' === substr( $parsed_url['path'], 0, 1 ) ) {
				continue;
			}
			$relative_url = preg_replace( '#^\./#', '', $url->getURL()->getString() );
			$real_url = $base_url . $relative_url;
			do {
				$real_url = preg_replace( '#[^/]+/../#', '', $real_url, -1, $count );
			} while ( 0 !== $count );
			$url->getURL()->setString( $real_url );
		}
	}
	private function process_css_declaration_block( RuleSet $ruleset, CSSList $css_list, $options ) {
		$results = array();

		if ( $ruleset instanceof DeclarationBlock ) {
			$this->ampify_ruleset_selectors( $ruleset );
			if ( 0 === count( $ruleset->getSelectors() ) ) {
				$css_list->remove( $ruleset );
				return $results;
			}
		}
		if ( ! empty( $options['property_whitelist'] ) ) {
			$properties = $ruleset->getRules();
			foreach ( $properties as $property ) {
				$vendorless_property_name = preg_replace( '/^-\w+-/', '', $property->getRule() );
				if ( ! in_array( $vendorless_property_name, $options['property_whitelist'], true ) ) {
					$error     = array(
						'code'           => 'illegal_css_property',
						'property_name'  => $property->getRule(),
						'property_value' => $property->getValue(),
						'type'           => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					if ( $sanitized ) {
						$ruleset->removeRule( $property->getRule() );
					}
					$results[] = compact( 'error', 'sanitized' );
				}
			}
		} else {
			foreach ( $options['property_blacklist'] as $illegal_property_name ) {
				$properties = $ruleset->getRules( $illegal_property_name );
				foreach ( $properties as $property ) {
					$error     = array(
						'code'           => 'illegal_css_property',
						'property_name'  => $property->getRule(),
						'property_value' => (string) $property->getValue(),
						'type'           => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					if ( $sanitized ) {
						$ruleset->removeRule( $property->getRule() );
					}
					$results[] = compact( 'error', 'sanitized' );
				}
			}
		}

		if ( $ruleset instanceof AtRuleSet && 'font-face' === $ruleset->atRuleName() ) {
			$this->process_font_face_at_rule( $ruleset );
		}

		$results = array_merge(
			$results,
			$this->transform_important_qualifiers( $ruleset, $css_list )
		);

		if ( 0 === count( $ruleset->getRules() ) ) {
			$css_list->remove( $ruleset );
		}
		return $results;
	}
	private function process_font_face_at_rule( AtRuleSet $ruleset ) {
		$src_properties = $ruleset->getRules( 'src' );
		if ( empty( $src_properties ) ) {
			return;
		}

		foreach ( $src_properties as $src_property ) {
			$value = $src_property->getValue();
			if ( ! ( $value instanceof RuleValueList ) ) {
				continue;
			}
			$sources = array();
			foreach ( $value->getListComponents() as $component ) {
				if ( $component instanceof RuleValueList ) {
					$subcomponents = $component->getListComponents();
					$subcomponent  = array_shift( $subcomponents );
					if ( $subcomponent ) {
						if ( empty( $sources ) ) {
							$sources[] = array( $subcomponent );
						} else {
							$sources[ count( $sources ) - 1 ][] = $subcomponent;
						}
					}
					foreach ( $subcomponents as $subcomponent ) {
						$sources[] = array( $subcomponent );
					}
				} else {
					if ( empty( $sources ) ) {
						$sources[] = array( $component );
					} else {
						$sources[ count( $sources ) - 1 ][] = $component;
					}
				}
			}
			$source_file_urls = array();
			$source_data_urls = array();
			foreach ( $sources as $i => $source ) {
				if ( $source[0] instanceof URL ) {
					if ( 'data:' === substr( $source[0]->getURL()->getString(), 0, 5 ) ) {
						$source_data_urls[ $i ] = $source[0];
					} else {
						$source_file_urls[ $i ] = $source[0];
					}
				}
			}
			if ( empty( $source_file_urls ) ) {
				continue;
			}
			$source_file_url = current( $source_file_urls );
			foreach ( $source_data_urls as $i => $data_url ) {
				$mime_type = strtok( substr( $data_url->getURL()->getString(), 5 ), ';' );
				if ( ! $mime_type ) {
					continue;
				}
				$extension   = preg_replace( ':.+/(.+-)?:', '', $mime_type );
				$guessed_url = preg_replace(
					':(?<=\.)\w+(\?.*)?(#.*)?$:',
					$extension,
					$source_file_url->getURL()->getString(),
					1,
					$count
				);
				if ( 1 !== $count ) {
					continue;
				}
				$path = $this->get_validated_url_file_path( $guessed_url, array( 'woff', 'woff2', 'ttf', 'otf', 'svg' ) );
				if ( is_wp_error( $path ) ) {
					continue;
				}

				$data_url->getURL()->setString( $guessed_url );
				break;
			}
		}
	}
	private function process_css_keyframes( KeyFrame $css_list, $options ) {
		$results = array();
		if ( ! empty( $options['property_whitelist'] ) ) {
			foreach ( $css_list->getContents() as $rules ) {
				if ( ! ( $rules instanceof DeclarationBlock ) ) {
					$error     = array(
						'code' => 'unrecognized_css',
						'item' => get_class( $rules ),
						'type' => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					if ( $sanitized ) {
						$css_list->remove( $rules );
					}
					$results[] = compact( 'error', 'sanitized' );
					continue;
				}

				$results = array_merge(
					$results,
					$this->transform_important_qualifiers( $rules, $css_list )
				);

				$properties = $rules->getRules();
				foreach ( $properties as $property ) {
					$vendorless_property_name = preg_replace( '/^-\w+-/', '', $property->getRule() );
					if ( ! in_array( $vendorless_property_name, $options['property_whitelist'], true ) ) {
						$error     = array(
							'code'           => 'illegal_css_property',
							'property_name'  => $property->getRule(),
							'property_value' => (string) $property->getValue(),
							'type'           => 'amp_validation_error',
						);
						$sanitized = $this->should_sanitize_validation_error( $error );
						if ( $sanitized ) {
							$rules->removeRule( $property->getRule() );
						}
						$results[] = compact( 'error', 'sanitized' );
					}
				}
			}
		}
		return $results;
	}
	private function transform_important_qualifiers( RuleSet $ruleset, CSSList $css_list ) {
		$results = array();
		$allow_transformation = (
			$ruleset instanceof DeclarationBlock
			&&
			! ( $css_list instanceof KeyFrame )
		);

		$properties = $ruleset->getRules();
		$importants = array();
		foreach ( $properties as $property ) {
			if ( $property->getIsImportant() ) {
				if ( $allow_transformation ) {
					$importants[] = $property;
					$property->setIsImportant( false );
					$ruleset->removeRule( $property->getRule() );
				} else {
					$error     = array(
						'code' => 'illegal_css_important',
						'type' => 'amp_validation_error',
					);
					$sanitized = $this->should_sanitize_validation_error( $error );
					if ( $sanitized ) {
						$property->setIsImportant( false );
					}
					$results[] = compact( 'error', 'sanitized' );
				}
			}
		}
		if ( ! $allow_transformation || empty( $importants ) ) {
			return $results;
		}

		$important_ruleset = clone $ruleset;
		$important_ruleset->setSelectors( array_map(
			function( Selector $old_selector ) {
				$specificity_multiplier = 5 + 1 + floor( $old_selector->getSpecificity() / 100 );
				if ( $old_selector->getSpecificity() % 100 > 0 ) {
					$specificity_multiplier++;
				}
				if ( $old_selector->getSpecificity() % 10 > 0 ) {
					$specificity_multiplier++;
				}
				$selector_mod = str_repeat( ':not(#_)', $specificity_multiplier ); // Here "_" is just a short single-char ID.
				$new_selector = $old_selector->getSelector();
				if ( preg_match( '/^\s*(html|:root)\b/i', $new_selector, $matches ) ) {
					$new_selector = substr( $new_selector, 0, strlen( $matches[0] ) ) . $selector_mod . substr( $new_selector, strlen( $matches[0] ) );
				} else {
					$new_selector = sprintf( ':root%s %s', $selector_mod, $new_selector );
				}
				return new Selector( $new_selector );
			},
			$ruleset->getSelectors()
		) );
		$important_ruleset->setRules( $importants );

		$i = array_search( $ruleset, $css_list->getContents(), true );
		if ( false !== $i && method_exists( $css_list, 'splice' ) ) {
			$css_list->splice( $i + 1, 0, array( $important_ruleset ) );
		} else {
			$css_list->append( $important_ruleset );
		}

		return $results;
	}
	private function collect_inline_styles( $element ) {
		$style_attribute = $element->getAttributeNode( 'style' );
		if ( ! $style_attribute || ! trim( $style_attribute->nodeValue ) ) {
			return;
		}

		$class = 'amp-wp-' . substr( md5( $style_attribute->nodeValue ), 0, 7 );
		$root  = ':root' . str_repeat( ':not(#_)', self::INLINE_SPECIFICITY_MULTIPLIER );
		$rule  = sprintf( '%s .%s { %s }', $root, $class, $style_attribute->nodeValue );

		$this->set_current_node( $element ); // And sources when needing to be located.

		$processed = $this->process_stylesheet( $rule, array(
			'allowed_at_rules'   => array(),
			'property_whitelist' => $this->style_custom_cdata_spec['css_spec']['declaration'],
		) );

		$element->removeAttribute( 'style' );

		if ( $processed['stylesheet'] ) {
			$this->pending_stylesheets[] = array(
				'stylesheet' => $processed['stylesheet'],
				'node'       => $element,
				'sources'    => $this->current_sources,
			);

			if ( $element->hasAttribute( 'class' ) ) {
				$element->setAttribute( 'class', $element->getAttribute( 'class' ) . ' ' . $class );
			} else {
				$element->setAttribute( 'class', $class );
			}
		}

		$this->set_current_node( null );
	}
	private function finalize_styles() {

		$stylesheet_sets = array(
			'custom'    => array(
				'source_map_comment'  => "\n\n/*# sourceURL=amp-custom.css */",
				'total_size'          => 0,
				'cdata_spec'          => $this->style_custom_cdata_spec,
				'pending_stylesheets' => array(),
				'final_stylesheets'   => array(),
				'remove_unused_rules' => $this->args['remove_unused_rules'],
			),
			'keyframes' => array(
				'source_map_comment'  => "\n\n/*# sourceURL=amp-keyframes.css */",
				'total_size'          => 0,
				'cdata_spec'          => $this->style_keyframes_cdata_spec,
				'pending_stylesheets' => array(),
				'final_stylesheets'   => array(),
				'remove_unused_rules' => 'never', // Not relevant.
			),
		);

		$imported_font_urls = array();
		$imports = array();

		while ( ! empty( $this->pending_stylesheets ) ) {
			$pending_stylesheet = array_shift( $this->pending_stylesheets );

			$set_name = ! empty( $pending_stylesheet['keyframes'] ) ? 'keyframes' : 'custom';
			$size     = 0;
			foreach ( $pending_stylesheet['stylesheet'] as $i => $part ) {
				if ( is_string( $part ) ) {
					$size += strlen( $part );
					if ( '@import' === substr( $part, 0, 7 ) ) {
						$imports[] = $part;
						unset( $pending_stylesheet['stylesheet'][ $i ] );
					}
				} elseif ( is_array( $part ) ) {
					$size += strlen( implode( ',', array_keys( $part[0] ) ) ); // Selectors.
					$size += strlen( $part[1] ); // Declaration block.
				}
			}
			$stylesheet_sets[ $set_name ]['total_size']           += $size;
			$stylesheet_sets[ $set_name ]['imports']               = $imports;
			$stylesheet_sets[ $set_name ]['pending_stylesheets'][] = $pending_stylesheet;

			if ( ! empty( $pending_stylesheet['imported_font_urls'] ) ) {
				$imported_font_urls = array_merge( $imported_font_urls, $pending_stylesheet['imported_font_urls'] );
			}
		}
		foreach ( array_keys( $stylesheet_sets ) as $set_name ) {
			$stylesheet_sets[ $set_name ] = $this->finalize_stylesheet_set( $stylesheet_sets[ $set_name ] );
		}

		$this->stylesheets = $stylesheet_sets['custom']['final_stylesheets'];

		if ( empty( $this->args['use_document_element'] ) ) {
			return;
		}

		if ( ! empty( $stylesheet_sets['custom']['final_stylesheets'] ) ) {

			if ( ! $this->custom_style_element ) {
				$this->custom_style_element = $this->dom->createElement( 'style' );
				$this->custom_style_element->setAttribute( 'amp-custom', '' );
				$this->head->appendChild( $this->custom_style_element );
			}

			$css  = implode( '', $stylesheet_sets['custom']['imports'] ); // For native dirty AMP.
			$css .= implode( '', $stylesheet_sets['custom']['final_stylesheets'] );
			$css .= $stylesheet_sets['custom']['source_map_comment'];

			while ( $this->custom_style_element->firstChild ) {
				$this->custom_style_element->removeChild( $this->custom_style_element->firstChild );
			}
			$this->custom_style_element->appendChild( $this->dom->createTextNode( $css ) );

			$included_size          = 0;
			$included_original_size = 0;
			$excluded_size          = 0;
			$excluded_original_size = 0;
			$included_sources       = array();
			foreach ( $stylesheet_sets['custom']['pending_stylesheets'] as $i => $pending_stylesheet ) {
				if ( ! ( $pending_stylesheet['node'] instanceof DOMElement ) ) {
					continue;
				}
				$message = sprintf( '% 6d B', $pending_stylesheet['size'] );
				if ( $pending_stylesheet['size'] && $pending_stylesheet['size'] !== $pending_stylesheet['original_size'] ) {
					$message .= sprintf( ' (%d%%)', $pending_stylesheet['size'] / $pending_stylesheet['original_size'] * 100 );
				}
				$message .= ': ';
				$message .= $pending_stylesheet['node']->nodeName;
				if ( $pending_stylesheet['node']->getAttribute( 'id' ) ) {
					$message .= '#' . $pending_stylesheet['node']->getAttribute( 'id' );
				}
				if ( $pending_stylesheet['node']->getAttribute( 'class' ) ) {
					$message .= '.' . $pending_stylesheet['node']->getAttribute( 'class' );
				}
				foreach ( $pending_stylesheet['node']->attributes as $attribute ) {
					if ( 'id' !== $attribute->nodeName || 'class' !== $attribute->nodeName ) {
						$message .= sprintf( '[%s=%s]', $attribute->nodeName, $attribute->nodeValue );
					}
				}

				if ( ! empty( $pending_stylesheet['included'] ) ) {
					$included_sources[]      = $message;
					$included_size          += $pending_stylesheet['size'];
					$included_original_size += $pending_stylesheet['original_size'];
				} else {
					$excluded_sources[]      = $message;
					$excluded_size          += $pending_stylesheet['size'];
					$excluded_original_size += $pending_stylesheet['original_size'];
				}
			}
			$comment = '';
			if ( ! empty( $included_sources ) && $included_original_size > 0 ) {
				$comment .= esc_html__( 'The style element is populated with:', 'web-vital' ) . "\n" . implode( "\n", $included_sources ) . "\n";
				if ( self::has_required_php_css_parser() ) {
					$comment .= sprintf(
						esc_html__( 'Total included size: %1$s bytes (%2$d%% of %3$s total after tree shaking)', 'web-vital' ),
						number_format_i18n( $included_size ),
						$included_size / $included_original_size * 100,
						number_format_i18n( $included_original_size )
					) . "\n";
				} else {
					$comment .= sprintf(
						esc_html__( 'Total included size: %1$s bytes', 'amp' ),
						number_format_i18n( $included_size ),
						$included_size / $included_original_size * 100,
						number_format_i18n( $included_original_size )
					) . "\n";
				}
			}
			if ( ! empty( $excluded_sources ) && $excluded_original_size > 0 ) {
				if ( $comment ) {
					$comment .= "\n";
				}
				$comment .= esc_html__( 'The following stylesheets are too large to be included in style[amp-custom]:', 'amp' ) . "\n" . implode( "\n", $excluded_sources ) . "\n";

				if ( self::has_required_php_css_parser() ) {
					$comment .= sprintf(
						esc_html__( 'Total excluded size: %1$s bytes (%2$d%% of %3$s total after tree shaking)', 'amp' ),
						number_format_i18n( $excluded_size ),
						$excluded_size / $excluded_original_size * 100,
						number_format_i18n( $excluded_original_size )
					) . "\n";
				} else {
					$comment .= sprintf(
						esc_html__( 'Total excluded size: %1$s bytes', 'amp' ),
						number_format_i18n( $excluded_size ),
						$excluded_size / $excluded_original_size * 100,
						number_format_i18n( $excluded_original_size )
					) . "\n";
				}

				$total_size          = $included_size + $excluded_size;
				$total_original_size = $included_original_size + $excluded_original_size;
				if ( $total_size !== $total_original_size ) {
					$comment .= "\n";
					$comment .= sprintf(
						esc_html__( 'Total combined size: %1$s bytes (%2$d%% of %3$s total after tree shaking)', 'amp' ),
						number_format_i18n( $total_size ),
						( $total_size / $total_original_size ) * 100,
						number_format_i18n( $total_original_size )
					) . "\n";
				}
			}

			if ( ! self::has_required_php_css_parser() ) {
				$comment .= "\n" . esc_html__( '!!!WARNING!!! AMP CSS processing is limited because a conflicting version of PHP-CSS-Parser has been loaded by another plugin/theme. Tree shaking is not available.', 'amp' ) . "\n";
			}

			if ( $comment ) {
				$this->custom_style_element->parentNode->insertBefore(
					$this->dom->createComment( "\n$comment" ),
					$this->custom_style_element
				);
			}
		}

		foreach ( array_unique( $imported_font_urls ) as $imported_font_url ) {
			$link = $this->dom->createElement( 'link' );
			$link->setAttribute( 'rel', 'stylesheet' );
			$link->setAttribute( 'href', $imported_font_url );
			$this->head->appendChild( $link );
		}

		// Add style[amp-keyframes] to document.
		if ( ! empty( $stylesheet_sets['keyframes']['final_stylesheets'] ) ) {
			$body = $this->dom->getElementsByTagName( 'body' )->item( 0 );
			if ( ! $body ) {
				$this->should_sanitize_validation_error( array(
					'code' => 'missing_body_element',
					'type' => 'amp_validation_error',
				) );
			} else {
				$css  = implode( '', $stylesheet_sets['keyframes']['final_stylesheets'] );
				$css .= $stylesheet_sets['keyframes']['source_map_comment'];

				$style_element = $this->dom->createElement( 'style' );
				$style_element->setAttribute( 'amp-keyframes', '' );
				$style_element->appendChild( $this->dom->createTextNode( $css ) );
				$body->appendChild( $style_element );
			}
		}
	}

	private function ampify_ruleset_selectors( $ruleset ) {
		$selectors = array();
		$changes   = 0;
		$language  = 'en';
		foreach ( $ruleset->getSelectors() as $old_selector ) {
			$selector = $old_selector->getSelector();
			if ( preg_match( '/^\*\s*\+?\s*html/', $selector ) ) {
				$changes++;
				continue;
			}

			$is_other_language = (
				preg_match( '/^html\[lang(?P<starts_with>\^?)=([\'"]?)(?P<lang>.+?)\2\]/', $selector, $matches )
				&&
				(
					empty( $matches['starts_with'] )
					?
					$language !== $matches['lang']
					:
					substr( $language, 0, strlen( $matches['lang'] ) ) !== $matches['lang']
				)
			);
			if ( $is_other_language ) {
				$changes++;
				continue;
			}

			$before_type_selector_pattern = '(?<=^|\(|\s|>|\+|~|,|})';
			$after_type_selector_pattern  = '(?=$|[^a-zA-Z0-9_-])';

			$edited_selectors = array( $selector );
			foreach ( $this->selector_mappings as $html_selector => $amp_selectors ) { // Note: The $selector_mappings array contains ~6 items.
				$html_pattern = '/' . $before_type_selector_pattern . preg_quote( $html_selector, '/' ) . $after_type_selector_pattern . '/i';
				foreach ( $edited_selectors as &$edited_selector ) { // Note: The $edited_selectors array contains only item in the normal case.
					$original_selector = $edited_selector;
					$amp_selector      = array_shift( $amp_selectors );
					$amp_tag_pattern   = '/' . $before_type_selector_pattern . preg_quote( $amp_selector, '/' ) . $after_type_selector_pattern . '/i';
					preg_match( $amp_tag_pattern, $edited_selector, $matches );
					if ( ! empty( $matches ) && $amp_selector === $matches[0] ) {
						continue;
					}
					$edited_selector = preg_replace( $html_pattern, $amp_selector, $edited_selector, -1, $count );
					if ( ! $count ) {
						continue;
					}
					$changes += $count;
					while ( ! empty( $amp_selectors ) ) { // Note: This array contains only a couple items.
						$amp_selector       = array_shift( $amp_selectors );
						$edited_selectors[] = preg_replace( $html_pattern, $amp_selector, $original_selector, -1, $count );
					}
				}
			}
			$selectors = array_merge( $selectors, $edited_selectors );
		}

		if ( $changes > 0 ) {
			$ruleset->setSelectors( $selectors );
		}
	}
	private function finalize_stylesheet_set( $stylesheet_set ) {
		$max_bytes         = $stylesheet_set['cdata_spec']['max_bytes'] - strlen( $stylesheet_set['source_map_comment'] );
		$is_too_much_css   = $stylesheet_set['total_size'] > $max_bytes;
		$should_tree_shake = (
			'always' === $stylesheet_set['remove_unused_rules'] || (
				$is_too_much_css
				&&
				'sometimes' === $stylesheet_set['remove_unused_rules']
			)
		);

		if ( $is_too_much_css && $should_tree_shake && empty( $this->args['accept_tree_shaking'] ) ) {
			$should_tree_shake = $this->should_sanitize_validation_error( array(
				'code' => self::TREE_SHAKING_ERROR_CODE,
				'type' => 'amp_validation_error',
			) );
		}

		$stylesheet_set['processed_nodes'] = array();

		$final_size = 0;
		$dom        = $this->dom;
		foreach ( $stylesheet_set['pending_stylesheets'] as &$pending_stylesheet ) {
			$stylesheet_parts = array();
			$original_size    = 0;
			foreach ( $pending_stylesheet['stylesheet'] as $stylesheet_part ) {
				if ( is_string( $stylesheet_part ) ) {
					$stylesheet_parts[] = $stylesheet_part;
					$original_size     += strlen( $stylesheet_part );
					continue;
				}

				list( $selectors_parsed, $declaration_block ) = $stylesheet_part;
				if ( $should_tree_shake ) {
					$selectors = array();
					foreach ( $selectors_parsed as $selector => $parsed_selector ) {
						$should_include = (
							(
								// If all class names are used in the doc.
								(
									empty( $parsed_selector['classes'] )
									||
									0 === count( array_diff( $parsed_selector['classes'], $this->get_used_class_names() ) )
								)
								&&
								// If all IDs are used in the doc.
								(
									empty( $parsed_selector['ids'] )
									||
									0 === count( array_filter( $parsed_selector['ids'], function( $id ) use ( $dom ) {
										return ! $dom->getElementById( $id );
									} ) )
								)
								&&
								// If tag names are present in the doc.
								(
									empty( $parsed_selector['tags'] )
									||
									0 === count( array_diff( $parsed_selector['tags'], $this->get_used_tag_names() ) )
								)
							)
						);
						if ( $should_include ) {
							$selectors[] = $selector;
						}
					}
				} else {
					$selectors = array_keys( $selectors_parsed );
				}
				$stylesheet_part = implode( ',', $selectors ) . $declaration_block;
				$original_size  += strlen( $stylesheet_part );
				if ( ! empty( $selectors ) ) {
					$stylesheet_parts[] = $stylesheet_part;
				}
			}

			// Strip empty at-rules after tree shaking.
			$stylesheet_part_count = count( $stylesheet_parts );
			for ( $i = 0; $i < $stylesheet_part_count; $i++ ) {
				$stylesheet_part = $stylesheet_parts[ $i ];
				if ( '@' !== substr( $stylesheet_part, 0, 1 ) ) {
					continue;
				}

				// Delete empty at-rules.
				if ( '{}' === substr( $stylesheet_part, -2 ) ) {
					$stylesheet_part_count--;
					array_splice( $stylesheet_parts, $i, 1 );
					$i--;
					continue;
				}

				// Delete at-rules that were emptied due to tree-shaking.
				if ( '{' === substr( $stylesheet_part, -1 ) ) {
					$open_braces = 1;
					for ( $j = $i + 1; $j < $stylesheet_part_count; $j++ ) {
						$stylesheet_part = $stylesheet_parts[ $j ];
						$is_at_rule      = '@' === substr( $stylesheet_part, 0, 1 );
						if ( empty( $stylesheet_part ) ) {
							continue; // There was a shaken rule.
						} elseif ( $is_at_rule && '{}' === substr( $stylesheet_part, -2 ) ) {
							continue; // The rule opens is empty from the start.
						} elseif ( $is_at_rule && '{' === substr( $stylesheet_part, -1 ) ) {
							$open_braces++;
						} elseif ( '}' === $stylesheet_part ) {
							$open_braces--;
						} else {
							break;
						}

						// Splice out the parts that are empty.
						if ( 0 === $open_braces ) {
							array_splice( $stylesheet_parts, $i, $j - $i + 1 );
							$stylesheet_part_count = count( $stylesheet_parts );
							$i--;
							continue 2;
						}
					}
				}
			}
			$pending_stylesheet['original_size'] = $original_size;

			$stylesheet = implode( '', $stylesheet_parts );
			unset( $stylesheet_parts );
			$sheet_size                 = strlen( $stylesheet );
			$pending_stylesheet['size'] = $sheet_size;

			// Skip considering stylesheet if an identical one has already been processed.
			$hash = md5( $stylesheet );
			if ( isset( $stylesheet_set['final_stylesheets'][ $hash ] ) ) {
				$pending_stylesheet['included'] = true;
				continue;
			}
			// 
			// Report validation error if size is now too big.
			if ( false ) {
				$validation_error = array(
					'code' => 'excessive_css',
					'type' => 'amp_validation_error',
				);
				if ( isset( $pending_stylesheet['sources'] ) ) {
					$validation_error['sources'] = $pending_stylesheet['sources'];
				}

				if ( $this->should_sanitize_validation_error( $validation_error, wp_array_slice_assoc( $pending_stylesheet, array( 'node' ) ) ) ) {
					$pending_stylesheet['included'] = false;
					continue; // Skip to the next stylesheet.
				}
			}

			$final_size += $sheet_size;

			$pending_stylesheet['included']               = true;
			$stylesheet_set['final_stylesheets'][ $hash ] = $stylesheet;
		}

		return $stylesheet_set;
	}
}




/**
 * Helper function 
 */
function web_vital_get_proper_transient_name($transient){
	global $post;
	if( function_exists('ampforwp_is_home') && ampforwp_is_home()){
		$transient = "home";
	}elseif(function_exists('ampforwp_is_blog') && ampforwp_is_blog()){
		$transient = "blog";
	}elseif( function_exists('ampforwp_is_front_page') && ampforwp_is_front_page()){
		$transient = "post-".ampforwp_get_frontpage_id();
	}elseif(!empty($post) && is_object($post)){
		$transient = "post-".$post->ID;
	}
	return $transient;
}
function web_vital_set_file_transient( $transient, $value, $expiration = 0 ) {

	$transient = web_vital_get_proper_transient_name($transient);
	$expiration = (int) $expiration;

	$value = apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

	
	$expiration = apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

	if ( wp_using_ext_object_cache() ) {
		$result = wp_cache_set( $transient, $value, 'transient', $expiration );
	} else {
		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option = '_transient_' . $transient;

		/***
		Creating a file
		**/
		if($value){
			$upload_dir = wp_upload_dir(); 
			$user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			$content = $value;
			$new_file = $user_dirname."/".$transient_option.".css";
			$ifp = @fopen( $new_file, 'w+' );
			if ( ! $ifp ) {
	          return ( array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) ));
	        }
	        $result = @fwrite( $ifp, json_encode($value) );
		    fclose( $ifp );
		    //set_transient($transient_option, true, 30 * 24 * 60);
		}

	}
	return $result;
}


function web_vital_style_get_file_transient( $transient ) {

	$transient = web_vital_get_proper_transient_name($transient);
	$pre = apply_filters( "pre_transient_{$transient}", false, $transient );
	if ( false !== $pre )
		return $pre;

	if ( wp_using_ext_object_cache() ) {
		$value = wp_cache_get( $transient, 'transient' );
	} else {
		$transient_option = '_transient_' . $transient;
		if ( ! isset( $value ) ){
			$value = '';
			$upload_dir = wp_upload_dir(); 
			$user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			
			$new_file = $user_dirname."/".$transient_option.".css";

			if(file_exists($new_file) && filesize($new_file)>0){
				$ifp = @fopen( $new_file, 'r' );
				$value = fread($ifp, filesize($new_file)); 
				fclose($ifp);
			}
		}
	}

	
	return apply_filters( "transient_{$transient}", json_decode($value, true), $transient );
}