<?php
require_once CWVPSB_PLUGIN_DIR."/includes/vendor/autoload.php";
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
class cwvpsb_treeshaking {

	const STYLE_AMP_CUSTOM_SPEC_NAME    = 'style';
	const STYLE_AMP_KEYFRAMES_SPEC_NAME = 'style[keyframes]';
	const ORIGINAL_STYLE_ATTRIBUTE_NAME = 'data-webvital-original-style';

	const STYLE_AMP_CUSTOM_GROUP_INDEX    = 0;
	const STYLE_AMP_KEYFRAMES_GROUP_INDEX = 1;

	const CSS_SYNTAX_INVALID_AT_RULE         = 'CSS_SYNTAX_INVALID_AT_RULE';
	const CSS_SYNTAX_INVALID_DECLARATION     = 'CSS_SYNTAX_INVALID_DECLARATION';
	const CSS_SYNTAX_INVALID_PROPERTY        = 'CSS_SYNTAX_INVALID_PROPERTY';
	const CSS_SYNTAX_INVALID_PROPERTY_NOLIST = 'CSS_SYNTAX_INVALID_PROPERTY_NOLIST';
	const CSS_SYNTAX_INVALID_IMPORTANT       = 'CSS_SYNTAX_INVALID_IMPORTANT';
	const CSS_SYNTAX_PARSE_ERROR             = 'CSS_SYNTAX_PARSE_ERROR';
	const STYLESHEET_TOO_LONG                = 'STYLESHEET_TOO_LONG';
	const STYLESHEET_FETCH_ERROR             = 'STYLESHEET_FETCH_ERROR';
	const STYLESHEET_DISALLOWED_FILE_EXT   = 'STYLESHEET_DISALLOWED_FILE_EXT';
	const STYLESHEET_EXTERNAL_FILE_URL     = 'STYLESHEET_EXTERNAL_FILE_URL';
	const STYLESHEET_FILE_PATH_NOT_FOUND   = 'STYLESHEET_FILE_PATH_NOT_FOUND';
	const STYLESHEET_FILE_PATH_NOT_ALLOWED = 'STYLESHEET_FILE_PATH_NOT_ALLOWED';
	const STYLESHEET_URL_SYNTAX_ERROR      = 'STYLESHEET_URL_SYNTAX_ERROR';
	const STYLESHEET_INVALID_RELATIVE_PATH = 'STYLESHEET_INVALID_RELATIVE_PATH';
	const CSS_BUDGET_WARNING_PERCENTAGE = 80;
	const INLINE_SPECIFICITY_MULTIPLIER = 5; // @todo The correctness of using "5" should be validated.
	const SELECTOR_EXTRACTED_TAGS = 0;
	const SELECTOR_EXTRACTED_CLASSES = 1;
	const SELECTOR_EXTRACTED_IDS = 2;
	const SELECTOR_EXTRACTED_ATTRIBUTES = 3;
	protected $args;
	protected $DEFAULT_ARGS = [
		'dynamic_element_selectors' => [],
		'should_locate_sources'     => false,
		'parsed_cache_variant'      => null,
		'focus_within_classes'      => [ 'focus' ],
		'low_priority_plugins'      => [ 'query-monitor' ],
		'allow_transient_caching'   => true,
	];
	private $pending_stylesheets = [];
	private $style_custom_cdata_spec;
	private $amp_custom_style_element;
	private $style_keyframes_cdata_spec;
	private $allowed_font_src_regex;
	private $base_url;
	private $content_url;
	private $used_class_names;
	private $focus_class_name_selector_pattern;
	private $used_attributes = [
		'autofocus' => true,
		'checked'   => true,
		'controls'  => true,
		'disabled'  => true,
		'hidden'    => true,
		'loop'      => true,
		'multiple'  => true,
		'readonly'  => true,
		'required'  => true,
		'selected'  => true,
	];
	private $used_tag_names;
	private $current_node;
	private $current_sources;
	private $processed_imported_stylesheet_urls = [];
	private $selector_mappings = [];
	private $video_autoplay_elements = [];
	private $remote_request;
	private $is_customize_preview;
	public static function get_css_parser_validation_error_codes() {
		return [
			self::CSS_SYNTAX_INVALID_AT_RULE,
			self::CSS_SYNTAX_INVALID_DECLARATION,
			self::CSS_SYNTAX_INVALID_IMPORTANT,
			self::CSS_SYNTAX_INVALID_PROPERTY,
			self::CSS_SYNTAX_INVALID_PROPERTY_NOLIST,
			self::CSS_SYNTAX_PARSE_ERROR,
			self::STYLESHEET_FETCH_ERROR,
			self::STYLESHEET_TOO_LONG,
		];
	}
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
	public function __construct( $dom, array $args = [] ) {
		$this->dom = $dom;
		$this->dom->xpath       = new DOMXPath( $dom );
		$this->dom->head = $this->dom->getElementsByTagName( 'head' )->item( 0 );
		$this->dom->body = $this->dom->getElementsByTagName( 'body' )->item( 0 );
		/* if ( ! $this->dom->head ) {
			$this->dom->head = $this->dom->createElement( 'head' );
			$this->dom->documentElement->insertBefore( $this->dom->head, $this->dom->documentElement->firstChild );
		} */
		$this->args = $args;
		$this->style_keyframes_cdata_spec = array(
					'css_spec' => array(
						'allowed_at_rules' => array(
							'keyframes',
							'media',
							'supports',
						),
						'declaration' => array(
							'animation-timing-function',
							'offset-distance',
							'opacity',
							'transform',
							'visibility',
						),
						'font_url_spec' => array(),
						'image_url_spec' => array(),
						'validate_keyframes' => true,
					),
					'max_bytes' => 5000000,
				);
		$this->style_custom_cdata_spec = array(
					'blacklisted_cdata_regex' => array(
						'error_message' => 'CSS !important',
						'regex' => '!important',
					),
					'css_spec' => array(
						'allowed_at_rules' => array(
							'font-face',
							'keyframes',
							'media',
							'page',
							'supports',
						),
						'declaration' => array(),
						'font_url_spec' => array(
							'allow_empty' => true,
							'protocol' => array(
								'https',
								'http',
								'data',
							),
						),
						'image_url_spec' => array(
							'allow_empty' => true,
							'protocol' => array(
								'https',
								'http',
								'data',
								'absolute',
							),
						),
						'validate_keyframes' => false,
					),
					'max_bytes' => 500000,
				);

		$this->allowed_font_src_regex = '@^(https://cdn\\.materialdesignicons\\.com/([0-9]+\\.?)+/css/materialdesignicons\\.min\\.css|https://cloud\\.typography\\.com/[0-9]*/[0-9]*/css/fonts\\.css|https://fast\\.fonts\\.net/.*|https://fonts\\.googleapis\\.com/css2?\\?.*|https://fonts\\.googleapis\\.com/icon\\?.*|https://fonts\\.googleapis\\.com/earlyaccess/.*\\.css|https://maxcdn\\.bootstrapcdn\\.com/font-awesome/([0-9]+\\.?)+/css/font-awesome\\.min\\.css(\\?.*)?|https://(use|pro)\\.fontawesome\\.com/releases/v([0-9]+\\.?)+/css/[0-9a-zA-Z-]+\\.css|https://(use|pro)\\.fontawesome\\.com/[0-9a-zA-Z-]+\\.css|https://use\\.typekit\\.net/[\\w\\p{L}\\p{N}_]+\\.css)$@';

		$guessurl = site_url();
		if ( ! $guessurl ) {
			$guessurl = wp_guess_url();
		}
		$this->base_url    = untrailingslashit( $guessurl );
		$this->content_url = WP_CONTENT_URL;
	}
	public function get_styles() {
		return [];
	}
	public function get_stylesheets() {
		return wp_list_pluck(
			array_filter(
				$this->pending_stylesheets,
				static function( $pending_stylesheet ) {
					return $pending_stylesheet['included'] && self::STYLE_AMP_CUSTOM_GROUP_INDEX === $pending_stylesheet['group'];
				}
			),
			'serialized'
		);
	}
	private function get_used_class_names() {
		if ( isset( $this->used_class_names ) ) {
			return $this->used_class_names;
		}

		$dynamic_class_names = [];

		$classes = ' ';
		foreach ( $this->dom->xpath->query( '//*/@class' ) as $class_attribute ) {
			$classes .= ' ' . $class_attribute->nodeValue;
		}
		foreach ( $this->dom->xpath->query( '//*/@web-vital-bind-class' ) as $bound_class_attribute ) {
			if ( preg_match_all( '/([\'"])([^\1]*?)\1/', $bound_class_attribute->nodeValue, $matches ) ) {
				$classes .= ' ' . implode( ' ', $matches[2] );
			}
		}

		$class_names = array_merge(
			$dynamic_class_names,
			array_unique( array_filter( preg_split( '/\s+/', trim( $classes ) ) ) )
		);
		foreach ( $this->dom->xpath->query( '//*/@on[ contains( ., "toggleClass" ) ]' ) as $on_attribute ) {
			if ( preg_match_all( '/\.\s*toggleClass\s*\(\s*class\s*=\s*(([\'"])([^\1]*?)\2|[a-zA-Z0-9_\-]+)/', $on_attribute->nodeValue, $matches ) ) {
				$class_names = array_merge(
					$class_names,
					array_map(
						static function ( $match ) {
							return trim( $match, '"\'' );
						},
						$matches[1]
					)
				);
			}
		}

		$this->used_class_names = array_fill_keys( $class_names, true );
		return $this->used_class_names;
	}
	private function has_used_class_name( $class_names ) {
		if ( empty( $this->used_class_names ) ) {
			$this->get_used_class_names();
		}

		foreach ( $class_names as $class_name ) {
			if ( isset( $this->used_class_names[ $class_name ] ) ) {
				continue;
			}
			if ( ! isset( $this->used_class_names[ $class_name ] ) ) {
				return false;
			}
		}

		return true;
	}
	private function get_used_tag_names() {
		if ( ! isset( $this->used_tag_names ) ) {
			$this->used_tag_names = [];
			foreach ( $this->dom->getElementsByTagName( '*' ) as $el ) {
				$this->used_tag_names[ $el->tagName ] = true;
			}
		}
		return $this->used_tag_names;
	}
	private function has_used_tag_names( $tag_names ) {
		if ( empty( $this->used_tag_names ) ) {
			$this->get_used_tag_names();
		}
		foreach ( $tag_names as $tag_name ) {
			if ( ! isset( $this->used_tag_names[ $tag_name ] ) ) {
				return false;
			}
		}
		return true;
	}
	private function has_used_attributes( $attribute_names ) {
		foreach ( $attribute_names as $attribute_name ) {
			if ( ! isset( $this->used_attributes[ $attribute_name ] ) ) {
				$expression = sprintf( '(//@%s)[1]', $attribute_name );

				$this->used_attributes[ $attribute_name ] = ( 0 !== $this->dom->xpath->query( $expression )->length );
			}
			if ( ! $this->used_attributes[ $attribute_name ] ) {
				return false;
			}
		}
		return true;
	}
	private function is_class_allowed_in_amp_date_picker( $class ) {
		static $class_prefixes = [];

		return in_array( strtok( $class, '_' ), $class_prefixes, true );
	}
	public function init( $sanitizers ) {
		
	}
	public function sanitize() {
		$this->is_customize_preview = is_customize_preview();

		$elements = [];

		$this->focus_class_name_selector_pattern = (
			! empty( $this->args['focus_within_classes'] ) ?
				self::get_class_name_selector_pattern( $this->args['focus_within_classes'] ) :
				null
		);

		$lower_case = 'translate( %s, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz" )'; // In XPath 2.0 this is lower-case().
		$predicates = [
			sprintf( '( self::style and not( @amp-boilerplate ) and ( not( @type ) or %s = "text/css" ) %s )', sprintf( $lower_case, '@type' ), '' ),
			sprintf( '( self::link and @href and %s = "stylesheet" %s )', sprintf( $lower_case, '@rel' ), '' ),
		];

		foreach ( $this->dom->xpath->query( '//*[ ' . implode( ' or ', $predicates ) . ' ]' ) as $element ) {
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
				if ( $element->parentNode && 'head' !== $element->parentNode->nodeName ) {
					$this->dom->head->appendChild( $element->parentNode->removeChild( $element ) );
				}
			}
		}
		$elements = [];
		foreach ( $this->dom->xpath->query( "//*[ @style ]" ) as $element ) {
			$elements[] = $element;
		}
		foreach ( $elements as $element ) {
			$this->collect_inline_styles( $element );
		}

		$this->finalize_styles();

		$this->did_convert_elements = true;

		$parse_css_duration = 0.0;
		$shake_css_duration = 0.0;
		foreach ( $this->pending_stylesheets as $pending_stylesheet ) {
			if ( ! $pending_stylesheet['cached'] ) {
				$parse_css_duration += $pending_stylesheet['parse_time'];
			}
			$shake_css_duration += $pending_stylesheet['shake_time'];
		}
		/*do_action( 'amp_server_timing_log', 'amp_parse_css', '', [ 'dur' => $parse_css_duration * 1000 ], true );
		do_action( 'amp_server_timing_log', 'amp_shake_css', '', [ 'dur' => $shake_css_duration * 1000 ], true );*/
	}
	private function get_stylesheet_priority( DOMNode $node ) {
		$print_priority_base = 100;
		$admin_bar_priority  = 200;

		$remove_url_scheme = static function( $url ) {
			return preg_replace( '/^https?:/', '', $url );
		};

		if ( $node instanceof DOMElement && 'link' === $node->nodeName ) {
			$element_id      = (string) $node->getAttribute( 'id' );
			$schemeless_href = $remove_url_scheme( $node->getAttribute( 'href' ) );

			$plugin = null;
			if ( preg_match(
				sprintf(
					'#^(?:%s|%s)(?<plugin>[^/]+)#i',
					preg_quote( $remove_url_scheme( trailingslashit( WP_PLUGIN_URL ) ), '#' ),
					preg_quote( $remove_url_scheme( trailingslashit( WPMU_PLUGIN_URL ) ), '#' )
				),
				$schemeless_href,
				$matches
			) ) {
				$plugin = $matches['plugin'];
			}

			$style_handle = null;
			if ( preg_match( '/^(.+)-css$/', $element_id, $matches ) ) {
				$style_handle = $matches[1];
			}

			$core_frontend_handles = [
				'wp-block-library',
				'wp-block-library-theme',
			];
			$non_amp_handles       = [
				'mediaelement',
				'wp-mediaelement',
				'thickbox',
			];

			if ( in_array( $style_handle, $non_amp_handles, true ) ) {
				$priority = 1000;
			} elseif ( 'admin-bar' === $style_handle ) {
				$priority = $admin_bar_priority;
			} elseif ( 'dashicons' === $style_handle ) {
				$priority = 90;
			} elseif ( false !== strpos( $schemeless_href, $remove_url_scheme( trailingslashit( get_template_directory_uri() ) ) ) ) {
				$priority = 1;
			} elseif ( false !== strpos( $schemeless_href, $remove_url_scheme( trailingslashit( get_stylesheet_directory_uri() ) ) ) ) {
				$priority = 10;
			} elseif ( in_array( $style_handle, $core_frontend_handles, true ) ) {
				$priority = 20;
			} elseif ( $plugin ) {
				$priority = in_array( $plugin, $this->args['low_priority_plugins'], true ) ? 150 : 30;
			} elseif ( 0 === strpos( $schemeless_href, $remove_url_scheme( includes_url() ) ) ) {
				$priority = 40;
			} else {
				$priority = 50;
			}

			if ( 'print' === $node->getAttribute( 'media' ) ) {
				$priority += $print_priority_base;
			}
		} elseif ( $node instanceof DOMElement && 'style' === $node->nodeName && $node->hasAttribute( 'id' ) ) {
			$id                  = $node->getAttribute( 'id' );
			$is_theme_inline_css = preg_match( '/^(?<handle>.+)-inline-css$/', $id, $matches ) && wp_style_is( $matches['handle'], 'registered' );
			if ( $is_theme_inline_css && 0 === strpos( wp_styles()->registered[ $matches['handle'] ]->src, get_template_directory_uri() ) ) {
				$priority = 2;
			} elseif ( $is_theme_inline_css && get_stylesheet() !== get_template() && 0 === strpos( wp_styles()->registered[ $matches['handle'] ]->src, get_stylesheet_directory_uri() ) ) {
				$priority = 12;
			} elseif ( 'admin-bar-inline-css' === $id ) {
				$priority = $admin_bar_priority;
			} elseif ( 'wp-custom-css' === $id ) {
				$priority = 60;
			} else {
				$priority = 70;
			}

			if ( 'print' === $node->getAttribute( 'media' ) ) {
				$priority += $print_priority_base;
			}
		} else {
			$priority = 70;
		}

		return $priority;
	}
	private function unrelativize_path( $path ) {
		do {
			$path = preg_replace(
				'#/\./#',
				'/',
				$path,
				-1,
				$count
			);
		} while ( 0 !== $count );
		do {
			$path = preg_replace(
				'#(?<=/)(?!\.\./)[^/]+/\.\./#',
				'',
				$path,
				1,
				$count
			);
		} while ( 0 !== $count );

		if ( preg_match( '#(^|/)\.+/#', $path ) ) {
			return new WP_Error( self::STYLESHEET_INVALID_RELATIVE_PATH );
		}

		return $path;
	}
	private function reconstruct_url( $parsed_url ) {
		$url = '';
		if ( ! empty( $parsed_url['host'] ) ) {
			if ( ! empty( $parsed_url['scheme'] ) ) {
				$url .= $parsed_url['scheme'] . ':';
			}
			$url .= '//';
			$url .= $parsed_url['host'];

			if ( ! empty( $parsed_url['port'] ) ) {
				$url .= ':' . $parsed_url['port'];
			}
		}
		if ( ! empty( $parsed_url['path'] ) ) {
			$url .= $parsed_url['path'];
		}
		if ( ! empty( $parsed_url['query'] ) ) {
			$url .= '?' . $parsed_url['query'];
		}
		if ( ! empty( $parsed_url['fragment'] ) ) {
			$url .= '#' . $parsed_url['fragment'];
		}
		return $url;
	}
	public function get_validated_url_file_path( $url, $allowed_extensions = [] ) {
		if ( ! is_string( $url ) ) {
			return new WP_Error( self::STYLESHEET_URL_SYNTAX_ERROR );
		}

		$needs_base_url = (
			! preg_match( '|^(https?:)?//|', $url )
			&&
			! ( $this->content_url && 0 === strpos( $url, $this->content_url ) )
		);
		if ( $needs_base_url ) {
			$url = $this->base_url . '/' . ltrim( $url, '/' );
		}

		$parsed_url = wp_parse_url( $url );
		if ( empty( $parsed_url['host'] ) ) {
			return new WP_Error( self::STYLESHEET_URL_SYNTAX_ERROR );
		}
		if ( empty( $parsed_url['path'] ) ) {
			return new WP_Error( self::STYLESHEET_URL_SYNTAX_ERROR );
		}

		$path = $this->unrelativize_path( $parsed_url['path'] );
		if ( is_wp_error( $path ) ) {
			return $path;
		}
		$parsed_url['path'] = $path;

		$remove_url_scheme = static function( $schemed_url ) {
			return preg_replace( '#^\w+:(?=//)#', '', $schemed_url );
		};

		unset( $parsed_url['scheme'], $parsed_url['query'], $parsed_url['fragment'] );
		$url = $this->reconstruct_url( $parsed_url );

		$includes_url = $remove_url_scheme( includes_url( '/' ) );
		$content_url  = $remove_url_scheme( content_url( '/' ) );
		$admin_url    = $remove_url_scheme( get_admin_url( null, '/' ) );
		$site_url     = $remove_url_scheme( site_url( '/' ) );

		$allowed_hosts = [
			wp_parse_url( $includes_url, PHP_URL_HOST ),
			wp_parse_url( $content_url, PHP_URL_HOST ),
			wp_parse_url( $admin_url, PHP_URL_HOST ),
		];
		if ( ! empty( $allowed_extensions ) ) {
			$pattern = sprintf( '/\.(%s)$/i', implode( '|', $allowed_extensions ) );
			if ( ! preg_match( $pattern, $url ) ) {
				return new WP_Error( self::STYLESHEET_DISALLOWED_FILE_EXT );
			}
		}

		if ( ! in_array( $parsed_url['host'], $allowed_hosts, true ) ) {
			return new WP_Error( self::STYLESHEET_EXTERNAL_FILE_URL );
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
			return new WP_Error( self::STYLESHEET_FILE_PATH_NOT_ALLOWED );
		}
		if ( ! file_exists( $base_path . $file_path ) ) {
			return new WP_Error( self::STYLESHEET_FILE_PATH_NOT_FOUND );
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
		} /*elseif ( ! empty( $this->args['should_locate_sources'] ) ) {
			$this->current_sources = AMP_Validation_Manager::locate_sources( $node );
		}*/
		$this->current_sources = null;
	}
	private function process_style_element( DOMElement $element ) {
		$this->set_current_node( $element ); // And sources when needing to be located.
		$is_keyframes = $element->hasAttribute( 'keyframes' );
		$stylesheet   = trim( $element->textContent );
		$cdata_spec   = $is_keyframes ? $this->style_keyframes_cdata_spec : $this->style_custom_cdata_spec;
		$media = $element->getAttribute( 'media' );
		if ( $media && 'all' !== $media ) {
			$stylesheet = sprintf( '@media %s { %s }', $media, $stylesheet );
		}

		$parsed = $this->get_parsed_stylesheet(
			$stylesheet,
			[
				'allowed_at_rules'   => $cdata_spec['css_spec']['allowed_at_rules'],
				'property_allowlist' => $cdata_spec['css_spec']['declaration'],
				'validate_keyframes' => $cdata_spec['css_spec']['validate_keyframes'],
				'spec_name'          => $is_keyframes ? self::STYLE_AMP_KEYFRAMES_SPEC_NAME : self::STYLE_AMP_CUSTOM_SPEC_NAME,
			]
		);

		if ( $parsed['viewport_rules'] ) {
			$this->create_meta_viewport( $element, $parsed['viewport_rules'] );
		}

		$this->pending_stylesheets[] = [
			'group'              => $is_keyframes ? self::STYLE_AMP_KEYFRAMES_GROUP_INDEX : self::STYLE_AMP_CUSTOM_GROUP_INDEX,
			'original_size'      => (int) strlen( $stylesheet ),
			'final_size'         => null,
			'element'            => $element,
			'origin'             => 'style_element',
			'sources'            => $this->current_sources,
			'priority'           => $this->get_stylesheet_priority( $element ),
			'tokens'             => $parsed['tokens'],
			'hash'               => $parsed['hash'],
			'parse_time'         => $parsed['parse_time'],
			'shake_time'         => null,
			'cached'             => $parsed['cached'],
			'imported_font_urls' => $parsed['imported_font_urls'],
		];
		$element->parentNode->removeChild( $element );

		$this->set_current_node( null );
	}
	private function process_link_element( DOMElement $element ) {
		$href = $element->getAttribute( 'href' );
		$normalized_url = preg_replace( '#^(http:)?(?=//)#', 'https:', $href );
		if(preg_match('/wp-content\/themes\/betheme\/css\/be\.css/i', $normalized_url)){
			return;
		}
		if(preg_match('/fontawesome-all.min.css/i', $normalized_url)){
			return;
		}
		if ( $this->allowed_font_src_regex && preg_match( $this->allowed_font_src_regex, $normalized_url ) ) {
			if ( $href !== $normalized_url ) {
				$element->setAttribute( 'href', $normalized_url );
			}
			$needs_preconnect_link = (
				'https://fonts.googleapis.com/' === substr( $normalized_url, 0, 29 )
				&&
				0 === $this->dom->xpath->query( '//link[ @rel = "preconnect" and @crossorigin and starts-with( @href, "https://fonts.gstatic.com" ) ]', $this->dom->head )->length
			);
			if ( $needs_preconnect_link ) {
				$link = $this->dom->createElement( "link" );
				$link->setAttribute( 'rel', 'preconnect' );
				$link->setAttribute( 'href', 'https://fonts.gstatic.com/' );
				$link->setAttribute( 'crossorigin', '' );
				$this->dom->head->insertBefore( $link );
			}
			return;
		}

		$stylesheet = $this->get_stylesheet_from_url( $href );
		if ( $stylesheet instanceof WP_Error ) {
			$this->remove_invalid_child(
				$element,
				[
					'code'    => self::STYLESHEET_FETCH_ERROR,
					'type'    => 'css_error',
					'url'     => $normalized_url,
					'message' => $stylesheet->get_error_message(),
				]
			);
			return;
		}
		$media = $element->getAttribute( 'media' );
		if ( $media && 'all' !== $media ) {
			$stylesheet = sprintf( '@media %s { %s }', $media, $stylesheet );
		}

		$this->set_current_node( $element ); // And sources when needing to be located.

		$parsed = $this->get_parsed_stylesheet(
			$stylesheet,
			[
				'allowed_at_rules'   => $this->style_custom_cdata_spec['css_spec']['allowed_at_rules'],
				'property_allowlist' => $this->style_custom_cdata_spec['css_spec']['declaration'],
				'stylesheet_url'     => $href,
				'spec_name'          => self::STYLE_AMP_CUSTOM_SPEC_NAME,
			]
		);

		if ( $parsed['viewport_rules'] ) {
			$this->create_meta_viewport( $element, $parsed['viewport_rules'] );
		}

		$this->pending_stylesheets[] = [
			'group'              => self::STYLE_AMP_CUSTOM_GROUP_INDEX,
			'original_size'      => strlen( $stylesheet ),
			'final_size'         => null,
			'element'            => $element,
			'origin'             => 'link_element',
			'sources'            => $this->current_sources, // Needed because node is removed below.
			'priority'           => $this->get_stylesheet_priority( $element ),
			'tokens'             => $parsed['tokens'],
			'hash'               => $parsed['hash'],
			'parse_time'         => $parsed['parse_time'],
			'shake_time'         => null,
			'cached'             => $parsed['cached'],
			'imported_font_urls' => $parsed['imported_font_urls'],
		];
		$element->parentNode->removeChild( $element );

		$this->set_current_node( null );
	}
	private function get_stylesheet_from_url( $stylesheet_url ) {
		$stylesheet    = false;
		$css_file_path = $this->get_validated_url_file_path( $stylesheet_url, [ 'css', 'less', 'scss', 'sass' ] );
		if ( ! is_wp_error( $css_file_path ) ) {
			$stylesheet = file_get_contents( $css_file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- It's a local filesystem path not a remote request.
		}
		if ( is_string( $stylesheet ) ) {
			return $stylesheet;
		}
		return $this->fetch_external_stylesheet( $stylesheet_url );
	}
	private function fetch_external_stylesheet( $url ) {
		if ( '//' === substr( $url, 0, 2 ) ) {
			$url = wp_parse_url( home_url(), PHP_URL_SCHEME ) . ':' . $url;
		}

		try {
			$response    = wp_remote_get( $url );
		} catch ( Exception $exception ) {
			if ( $exception instanceof FailedToGetFromRemoteUrl && $exception->hasStatusCode() ) {
				return new WP_Error( "http_{$exception->getStatusCode()}", $exception->getMessage() );
			}
			return new WP_Error( 'http_error', sprintf( esc_html__( 'Failed to fetch: %1$s (%2$s)', 'cwvpsb' ), $url, $exception->getMessage() ) );
		}

		$status  = wp_remote_retrieve_response_code( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error( "http_{$status}", sprintf( esc_html__( 'Failed to fetch: %s', 'cwvpsb' ), $url ) );
		}

		$content_type = (array) wp_remote_retrieve_header( $response, 'content-type' );

		if ( ! empty( $content_type ) && ! preg_match( '#^text/css#', $content_type[0] ) ) {
			return new WP_Error(
				'no_css_content_type',
				esc_html__( 'Response did not contain the expected text/css content type.', 'cwvpsb' )
			);
		}
		return wp_remote_retrieve_body( $response );
	}
	private function get_parsed_stylesheet( $stylesheet, $options = [] ) {
		$parsed         = null;
		$cache_key      = null;
		$cached         = true;
		$cache_group    = 'web-vital-parsed-stylesheet-v30'; // This should be bumped whenever the PHP-CSS-Parser is updated or parsed format is updated.
		/*$use_transients = $this->should_use_transient_caching();*/

		$cache_impacting_options = array_merge(
			wp_array_slice_assoc(
				$options,
				[ 'property_allowlist', 'property_denylist', 'stylesheet_url', 'allowed_at_rules' ]
			),
			wp_array_slice_assoc(
				$this->args,
				[ 'should_locate_sources', 'parsed_cache_variant', 'dynamic_element_selectors' ]
			),
			[
				'language' => get_bloginfo( 'language' ), // Used to tree-shake html[lang] selectors.
			]
		);

		$cache_key = md5( $stylesheet . wp_json_encode( $cache_impacting_options ) );

		$parsed = cwvpsb_style_get_file_transient( $cache_group . '-' . $cache_key );

		/*if ( $use_transients ) {
			$parsed = get_transient( $cache_group . '-' . $cache_key );
		} else {
			$parsed = wp_cache_get( $cache_key, $cache_group );
		}*/
		if ( ! empty( $parsed['validation_results'] ) ) {
			foreach ( $parsed['validation_results'] as $validation_result ) {
				$sanitized = '';
				if(@$validation_result['error']){
					$sanitized = $this->should_sanitize_validation_error( @$validation_result['error'] );
				}
				if ( $sanitized !== @$validation_result['sanitized'] ) {
					$parsed = null; // Change to sanitization of validation error detected, so cache cannot be used.
					break;
				}
			}
		}

		if ( ! $parsed || ! isset( $parsed['tokens'] ) || ! is_array( $parsed['tokens'] ) ) {
			$parsed = $this->parse_stylesheet( $stylesheet, $options );
			
			cwvpsb_set_file_transient( $cache_group . '-' . $cache_key, $parsed, MONTH_IN_SECONDS );
			/*$cached = false;
			if ( $use_transients ) {
				set_transient( $cache_group . '-' . $cache_key, $parsed, MONTH_IN_SECONDS );
			} else {
				wp_cache_set( $cache_key, $parsed, $cache_group );
			}*/
		}

		$parsed['cached'] = $cached;
		return $parsed;
	}
	private function splice_imported_stylesheet( Import $item, CSSList $css_list, $options ) {
		$validation_results = [];
		$imported_font_urls = [];
		$viewport_rules     = [];
		$at_rule_args       = $item->atRuleArgs();
		$location           = array_shift( $at_rule_args );
		$media_query        = array_shift( $at_rule_args );

		if ( isset( $options['stylesheet_url'] ) ) {
			$this->real_path_urls( [ $location ], $options['stylesheet_url'] );
		}

		$import_stylesheet_url = $location->getURL()->getString();
		if ( isset( $this->processed_imported_stylesheet_urls[ $import_stylesheet_url ] ) ) {
			$css_list->remove( $item );
			return compact( 'validation_results', 'imported_font_urls', 'viewport_rules' );
		}
		$this->processed_imported_stylesheet_urls[ $import_stylesheet_url ] = true;
		$https_import_stylesheet_url = preg_replace( '#^(http:)?(?=//)#', 'https:', $import_stylesheet_url );
		if ( $this->allowed_font_src_regex && preg_match( $this->allowed_font_src_regex, $https_import_stylesheet_url ) ) {
			$imported_font_urls[] = $https_import_stylesheet_url;
			$css_list->remove( $item );
			_doing_it_wrong(
				'wp_enqueue_style',
				esc_html(
					sprintf(
						esc_html__( 'It is not a best practice to use %1$s to load font CDN stylesheets. Please use %2$s to enqueue %3$s as its own separate script.', 'cwvpsb' ),
						'@import',
						'wp_enqueue_style()',
						$import_stylesheet_url
					)
				),
				'1.0'
			);

			return compact( 'validation_results', 'imported_font_urls', 'viewport_rules' );
		}

		$stylesheet = $this->get_stylesheet_from_url( $import_stylesheet_url );
		if ( $stylesheet instanceof WP_Error ) {
			$error     = [
				'code'    => self::STYLESHEET_FETCH_ERROR,
				'type'    => 'css_error',
				'url'     => $import_stylesheet_url,
				'message' => $stylesheet->get_error_message(),
			];
			$sanitized = $this->should_sanitize_validation_error( $error );
			if ( $sanitized ) {
				$css_list->remove( $item );
			}
			$validation_results[] = compact( 'error', 'sanitized' );

			return compact( 'validation_results', 'imported_font_urls', 'viewport_rules' );
		}

		if ( $media_query ) {
			$stylesheet = sprintf( '@media %s { %s }', $media_query, $stylesheet );
		}

		$options['stylesheet_url'] = $import_stylesheet_url;

		$parsed_stylesheet = $this->create_validated_css_document( $stylesheet, $options );

		$validation_results = array_merge(
			$validation_results,
			$parsed_stylesheet['validation_results']
		);
		$viewport_rules     = $parsed_stylesheet['viewport_rules'];

		if ( ! empty( $parsed_stylesheet['css_document'] ) && method_exists( $css_list, 'replace' ) ) {
			$css_document = $parsed_stylesheet['css_document'];
			$css_list->setContents( array_values( $css_list->getContents() ) );

			$css_list->replace( $item, $css_document->getContents() );
		} else {
			$css_list->remove( $item );
		}

		return compact( 'validation_results', 'imported_font_urls', 'viewport_rules' );
	}
	private function create_validated_css_document( $stylesheet_string, $options ) {
		$validation_results = [];
		$imported_font_urls = [];
		$viewport_rules     = [];
		$css_document       = null;

		try {
			$stylesheet_string = $this->remove_spaces_from_url_values( $stylesheet_string );

			$parser_settings = Sabberworm\CSS\Settings::create();
			$css_parser      = new Sabberworm\CSS\Parser( $stylesheet_string, $parser_settings );
			$css_document    = $css_parser->parse(); // @todo If 'utf-8' is not $css_parser->getCharset() then issue warning?

			if ( ! empty( $options['stylesheet_url'] ) ) {
				$this->real_path_urls(
					array_filter(
						$css_document->getAllValues(),
						static function ( $value ) {
							return $value instanceof URL;
						}
					),
					$options['stylesheet_url']
				);
			}

			$processed_css_list = $this->process_css_list( $css_document, $options );

			$validation_results = array_merge(
				$validation_results,
				$processed_css_list['validation_results']
			);
			$viewport_rules     = array_merge(
				$viewport_rules,
				$processed_css_list['viewport_rules']
			);
			$imported_font_urls = $processed_css_list['imported_font_urls'];
		} catch ( Exception $exception ) {
			$error = [
				'code'      => self::CSS_SYNTAX_PARSE_ERROR,
				'message'   => $exception->getMessage(),
				'type'      => 'css_error',
				'spec_name' => $options['spec_name'],
			];
			$sanitized = $this->should_sanitize_validation_error( $error );

			$validation_results[] = compact( 'error', 'sanitized' );
		}
		return compact( 'validation_results', 'css_document', 'imported_font_urls', 'viewport_rules' );
	}
	private function parse_stylesheet( $stylesheet_string, $options = [] ) {
		$start_time = microtime( true );

		$options = array_merge(
			[
				'allowed_at_rules'   => [],
				'property_blacklist' => [
					'behavior',
					'-moz-binding',
				],
				'property_whitelist' => [],
				'validate_keyframes' => false,
				'stylesheet_url'     => null,
				'spec_name'          => null,
			],
			$options
		);
		$stylesheet_string = preg_replace( '/^\xEF\xBB\xBF/', '', $stylesheet_string );
		$stylesheet_string = preg_replace( '#^\s*<!--#', '', $stylesheet_string );
		$stylesheet_string = preg_replace( '#^\s*<!\[CDATA\[#', '', $stylesheet_string );
		$stylesheet_string = preg_replace( '#\]\]>\s*$#', '', $stylesheet_string );
		$stylesheet_string = preg_replace( '#-->\s*$#', '', $stylesheet_string );

		$tokens             = [];
		$parsed_stylesheet  = $this->create_validated_css_document( $stylesheet_string, $options );
		$validation_results = $parsed_stylesheet['validation_results'];
		if ( ! empty( $parsed_stylesheet['css_document'] ) ) {
			$css_document = $parsed_stylesheet['css_document'];

			$output_format = Sabberworm\CSS\OutputFormat::createCompact();
			$output_format->setSemicolonAfterLastRule( false );

			$before_declaration_block          = sprintf( '/*%s*/', chr( 1 ) );
			$between_selectors                 = sprintf( '/*%s*/', chr( 2 ) );
			$after_declaration_block_selectors = sprintf( '/*%s*/', chr( 3 ) );
			$between_properties                = sprintf( '/*%s*/', chr( 4 ) );
			$after_declaration_block           = sprintf( '/*%s*/', chr( 5 ) );
			$before_at_rule                    = sprintf( '/*%s*/', chr( 6 ) );
			$after_at_rule                     = sprintf( '/*%s*/', chr( 7 ) );
			if ( self::has_required_php_css_parser() ) {
				$output_format->set( 'BeforeDeclarationBlock', $before_declaration_block );
				$output_format->set( 'SpaceBeforeSelectorSeparator', $between_selectors );
				$output_format->set( 'AfterDeclarationBlockSelectors', $after_declaration_block_selectors );
				$output_format->set( 'AfterDeclarationBlock', $after_declaration_block );
				$output_format->set( 'BeforeAtRuleBlock', $before_at_rule );
				$output_format->set( 'AfterAtRuleBlock', $after_at_rule );
			}
			$output_format->set( 'SpaceBetweenRules', $between_properties );

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
				$dynamic_selector_pattern = implode(
					'|',
					array_map(
						static function( $selector ) {
							return preg_quote( $selector, '#' );
						},
						$this->args['dynamic_element_selectors']
					)
				);
			}
			do_action('cwvpsb_css_whitelist_data', $stylesheet_string);
			$split_stylesheet = preg_split( $pattern, $stylesheet_string, -1, PREG_SPLIT_DELIM_CAPTURE );
			$length           = count( $split_stylesheet );
			for ( $i = 0; $i < $length; $i++ ) {
				if ( '' === $split_stylesheet[ $i ] ) {
					unset( $split_stylesheet[ $i ] );
					continue;
				}

				if ( $before_declaration_block === $split_stylesheet[ $i ] ) {
					if ( preg_match( '/^((from|to)\b|-?\d+(\.\d+)?%)/i', $split_stylesheet[ $i + 1 ] ) ) {
						$tokens[] = (
							str_replace( $between_selectors, '', $split_stylesheet[ ++$i ] )
							.
							str_replace( $between_properties, '', $split_stylesheet[ ++$i ] )
						);
						continue;
					}

					$selectors   = explode( $between_selectors . ',', $split_stylesheet[ ++$i ] );
					$declaration = explode( ';' . $between_properties, trim( $split_stylesheet[ ++$i ], '{}' ) );
					// @todo The following logic could be made much more robust if PHP-CSS-Parser did parsing of selectors. See <https://github.com/sabberworm/PHP-CSS-Parser/pull/138#issuecomment-418193262> and <https://github.com/ampproject/amp-wp/issues/2102>.
					$selectors_parsed = [];
					foreach ( $selectors as $selector ) {
						$selectors_parsed[ $selector ] = [];

						// Remove :not() and pseudo selectors to eliminate false negatives, such as with `body:not(.title-tagline-hidden) .site-branding-text` (but not after escape character).
						$reduced_selector = preg_replace( '/(?<!\\\\)::?[a-zA-Z0-9_-]+(\(.+?\))?/', '', $selector );

						// Ignore any selector terms that occur under a dynamic selector.
						if ( $dynamic_selector_pattern ) {
							$reduced_selector = preg_replace( '#((?:' . $dynamic_selector_pattern . ')(?:\.[a-z0-9_-]+)*)[^a-z0-9_-].*#si', '$1', $reduced_selector . ' ' );
						}

						/*
						 * Gather attribute names while removing attribute selectors to eliminate false negative,
						 * such as with `.social-navigation a[href*="example.com"]:before`.
						 */
						$reduced_selector = preg_replace_callback(
							'/\[([A-Za-z0-9_:-]+)(\W?=[^\]]+)?\]/',
							static function( $matches ) use ( $selector, &$selectors_parsed ) {
								$selectors_parsed[ $selector ][ self::SELECTOR_EXTRACTED_ATTRIBUTES ][] = $matches[1];
								return '';
							},
							$reduced_selector
						);

						// Extract class names.
						$reduced_selector = preg_replace_callback(
							'/\.((?:[a-zA-Z0-9_-]+|\\\\.)+)/', // The `\\\\.` will allow any char via escaping, like the colon in `.lg\:w-full`.
							static function( $matches ) use ( $selector, &$selectors_parsed ) {
								$selectors_parsed[ $selector ][ self::SELECTOR_EXTRACTED_CLASSES ][] = stripslashes( $matches[1] );
								return '';
							},
							$reduced_selector
						);

						// Extract IDs.
						$reduced_selector = preg_replace_callback(
							'/#([a-zA-Z0-9_-]+)/',
							static function( $matches ) use ( $selector, &$selectors_parsed ) {
								$selectors_parsed[ $selector ][ self::SELECTOR_EXTRACTED_IDS ][] = $matches[1];
								return '';
							},
							$reduced_selector
						);

						// Extract tag names.
						$reduced_selector = preg_replace_callback(
							'/[a-zA-Z0-9_-]+/',
							static function( $matches ) use ( $selector, &$selectors_parsed ) {
								$selectors_parsed[ $selector ][ self::SELECTOR_EXTRACTED_TAGS ][] = $matches[0];
								return '';
							},
							$reduced_selector
						);

						// At this point, $reduced_selector should contain just the remnants of the selector, primarily combinators.
						unset( $reduced_selector );
					}

					$tokens[] = [
						$selectors_parsed,
						$declaration,
					];
				} else {
					$tokens[] = str_replace( $between_properties, '', $split_stylesheet[ $i ] );
				}
			}
		}

		return array_merge(
			compact( 'tokens', 'validation_results' ),
			[
				'imported_font_urls' => $parsed_stylesheet['imported_font_urls'],
				'hash'               => md5( wp_json_encode( $tokens ) ),
				'parse_time'         => ( microtime( true ) - $start_time ),
				'viewport_rules'     => $parsed_stylesheet['viewport_rules'],
			]
		);
	}
	public function remove_invalid_child( $node, $validation_error = [] ) {
		// Prevent double-reporting nodes that are rejected for sanitization.
		if ( isset( $this->nodes_to_keep[ $node->nodeName ] ) && in_array( $node, $this->nodes_to_keep[ $node->nodeName ], true ) ) {
			return false;
		}

		$should_remove = $this->should_sanitize_validation_error( $validation_error, compact( 'node' ) );
		if ( $should_remove ) {
			if ( null === $node->parentNode ) {
				// Node no longer exists.
				return $should_remove;
			}

			$node->parentNode->removeChild( $node );
		} else {
			$this->nodes_to_keep[ $node->nodeName ][] = $node;
		}
		return $should_remove;
	}
	protected $previous_should_sanitize_validation_error_results = [];
	public function should_sanitize_validation_error( $validation_error, $data = [] ) {
		if ( ! isset( $data['node'] ) ) {
			$data['node'] = $this->current_node;
		}
		if ( ! isset( $validation_error['sources'] ) ) {
			@$validation_error['sources'] = $this->current_sources;
		}
		$args = compact( 'validation_error', 'data' );
		foreach ( $this->previous_should_sanitize_validation_error_results as $result ) {
			if ( $result['args'] === $args ) {
				return $result['sanitized'];
			}
		}

		$sanitized = array();//parent_should_sanitize_validation_error( $validation_error, $data );

		$this->previous_should_sanitize_validation_error_results[] = compact( 'args', 'sanitized' );
		return $sanitized;
	}
	private function remove_spaces_from_url_values( $css ) {
		return preg_replace_callback(
			'/\burl\(\s*(?=\w)(?P<url>[^}]*?\s*)\)/',
			static function( $matches ) {
				return preg_replace( '/\s+/', '', $matches[0] );
			},
			$css
		);
	}
	private function process_css_list( CSSList $css_list, $options ) {
		$validation_results = [];
		$viewport_rules     = [];
		$imported_font_urls = [];

		foreach ( $css_list->getContents() as $css_item ) {
			$sanitized = false;
			if ( $css_item instanceof DeclarationBlock && empty( $options['validate_keyframes'] ) ) {
				$validation_results = array_merge(
					$validation_results,
					$this->process_css_declaration_block( $css_item, $css_list, $options )
				);
			} elseif ( $css_item instanceof AtRuleBlockList ) {
				if ( ! in_array( $css_item->atRuleName(), $options['allowed_at_rules'], true ) ) {
					$error                = [
						'code'      => self::CSS_SYNTAX_INVALID_AT_RULE,
						'at_rule'   => $css_item->atRuleName(),
						'type'      => 'css_error',
						'spec_name' => $options['spec_name'],
					];
					$sanitized            = $this->should_sanitize_validation_error( $error );
					$validation_results[] = compact( 'error', 'sanitized' );
				}
				if ( ! $sanitized ) {
					$at_rule_processed_list = $this->process_css_list( $css_item, $options );
					$viewport_rules         = array_merge( $viewport_rules, $at_rule_processed_list['viewport_rules'] );
					$validation_results     = array_merge(
						$validation_results,
						$at_rule_processed_list['validation_results']
					);
				}
			} elseif ( $css_item instanceof Import ) {
				$imported_stylesheet = $this->splice_imported_stylesheet( $css_item, $css_list, $options );
				$imported_font_urls  = array_merge( $imported_font_urls, $imported_stylesheet['imported_font_urls'] );
				$validation_results  = array_merge( $validation_results, $imported_stylesheet['validation_results'] );
				$viewport_rules      = array_merge( $viewport_rules, $imported_stylesheet['viewport_rules'] );
			} elseif ( $css_item instanceof AtRuleSet ) {
				if ( preg_match( '/^(-.+-)?viewport$/', $css_item->atRuleName() ) ) {
					$output_format = new OutputFormat();
					foreach ( $css_item->getRules() as $rule ) {
						$rule_value = $rule->getValue();
						if ( $rule_value instanceof Value ) {
							$rule_value = $rule_value->render( $output_format );
						}

						$viewport_rules[ $rule->getRule() ] = $rule_value;
					}
					$css_list->remove( $css_item );
				} elseif ( ! in_array( $css_item->atRuleName(), $options['allowed_at_rules'], true ) ) {
					$error                = [
						'code'      => self::CSS_SYNTAX_INVALID_AT_RULE,
						'at_rule'   => $css_item->atRuleName(),
						'type'      => 'css_error',
						'spec_name' => $options['spec_name'],
					];
					$sanitized            = $this->should_sanitize_validation_error( $error );
					$validation_results[] = compact( 'error', 'sanitized' );
				}

				if ( ! $sanitized ) {
					$validation_results = array_merge(
						$validation_results,
						$this->process_css_declaration_block( $css_item, $css_list, $options )
					);
				}
			} elseif ( $css_item instanceof KeyFrame ) {
				if ( ! in_array( 'keyframes', $options['allowed_at_rules'], true ) ) {
					$error                = [
						'code'      => self::CSS_SYNTAX_INVALID_AT_RULE,
						'at_rule'   => $css_item->atRuleName(),
						'type'      => 'css_error',
						'spec_name' => $options['spec_name'],
					];
					$sanitized            = $this->should_sanitize_validation_error( $error );
					$validation_results[] = compact( 'error', 'sanitized' );
				}

				if ( ! $sanitized ) {
					$validation_results = array_merge(
						$validation_results,
						$this->process_css_keyframes( $css_item, $options )
					);
				}
			} elseif ( $css_item instanceof AtRule ) {
				if ( 'charset' === $css_item->atRuleName() ) {
					$sanitized = true;
				} else {
					$error                = [
						'code'      => self::CSS_SYNTAX_INVALID_AT_RULE,
						'at_rule'   => $css_item->atRuleName(),
						'type'      => 'css_error',
						'spec_name' => $options['spec_name'],
					];
					$sanitized            = $this->should_sanitize_validation_error( $error );
					$validation_results[] = compact( 'error', 'sanitized' );
				}
			} else {
				$error                = [
					'code'      => self::CSS_SYNTAX_INVALID_DECLARATION,
					'item'      => get_class( $css_item ),
					'type'      => 'css_error',
					'spec_name' => $options['spec_name'],
				];
				$sanitized            = $this->should_sanitize_validation_error( $error );
				$validation_results[] = compact( 'error', 'sanitized' );
			}

			if ( $sanitized ) {
				$css_list->remove( $css_item );
			}
		}

		return compact( 'validation_results', 'imported_font_urls', 'viewport_rules' );
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

			$parsed_url = wp_parse_url( $base_url . $url->getURL()->getString() );
			$path = $this->unrelativize_path( $parsed_url['path'] );
			if ( is_wp_error( $path ) ) {
				continue;
			}
			$parsed_url['path'] = $path;

			$real_url = $this->reconstruct_url( $parsed_url );

			$url->getURL()->setString( $real_url );
		}
	}
	private function process_css_declaration_block( RuleSet $ruleset, CSSList $css_list, $options ) {
		$results = [];

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
					$error     = [
						'code'               => self::CSS_SYNTAX_INVALID_PROPERTY,
						'css_property_name'  => $property->getRule(),
						'css_property_value' => $property->getValue(),
						'type'               => 'css_error',
						'spec_name'          => $options['spec_name'],
					];
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
					$error     = [
						'code'               => self::CSS_SYNTAX_INVALID_PROPERTY_NOLIST,
						'css_property_name'  => $property->getRule(),
						'css_property_value' => (string) $property->getValue(),
						'type'               => 'css_error',
						'spec_name'          => $options['spec_name'],
					];
					$sanitized = $this->should_sanitize_validation_error( $error );
					if ( $sanitized ) {
						$ruleset->removeRule( $property->getRule() );
					}
					$results[] = compact( 'error', 'sanitized' );
				}
			}
		}
		if ( $ruleset instanceof AtRuleSet && 'font-face' === $ruleset->atRuleName() ) {
			$this->process_font_face_at_rule( $ruleset, $options );
		}
		$results = array_merge(
			$results,
			$this->transform_important_qualifiers( $ruleset, $css_list, $options )
		);
		if ( 0 === count( $ruleset->getRules() ) ) {
			$css_list->remove( $ruleset );
		}
		return $results;
	}
	private function process_font_face_at_rule( AtRuleSet $ruleset, $options ) {
		$src_properties = $ruleset->getRules( 'src' );
		if ( empty( $src_properties ) ) {
			return;
		}
		$font_family   = null;
		$font_basename = null;
		$properties    = $ruleset->getRules( 'font-family' );
		if ( isset( $properties[0] ) ) {
			$font_family = trim( $properties[0]->getValue(), '"\'' );
			$font_basename = preg_replace( '/[^A-Za-z0-9_\-]/', '', $font_family ); // Same as sanitize_key() minus case changes.
		}
		$stylesheet_base_url = null;
		if ( ! empty( $options['stylesheet_url'] ) ) {
			$stylesheet_base_url = preg_replace(
				':[^/]+(\?.*)?(#.*)?$:',
				'',
				$options['stylesheet_url']
			);
			$stylesheet_base_url = trailingslashit( $stylesheet_base_url );
		}
		$converted_count = 0;
		foreach ( $src_properties as $src_property ) {
			$value = $src_property->getValue();
			if ( ! ( $value instanceof RuleValueList ) ) {
				continue;
			}
			$sources = [];
			foreach ( $value->getListComponents() as $component ) {
				if ( $component instanceof RuleValueList ) {
					$subcomponents = $component->getListComponents();
					$subcomponent  = array_shift( $subcomponents );
					if ( $subcomponent ) {
						if ( empty( $sources ) ) {
							$sources[] = [ $subcomponent ];
						} else {
							$sources[ count( $sources ) - 1 ][] = $subcomponent;
						}
					}
					foreach ( $subcomponents as $subcomponent ) {
						$sources[] = [ $subcomponent ];
					}
				} elseif ( empty( $sources ) ) {
					$sources[] = [ $component ];
				} else {
					$sources[ count( $sources ) - 1 ][] = $component;
				}
			}
			$source_file_urls = [];
			$source_data_url_objects = [];
			foreach ( $sources as $i => $source ) {
				if ( $source[0] instanceof URL ) {
					$value = $source[0]->getURL()->getString();
					if ( 'data:' === substr( $value, 0, 5 ) ) {
						$source_data_url_objects[ $i ] = $source[0];
					} else {
						$source_file_urls[ $i ] = $value;
					}
				}
			}
			foreach ( $source_data_url_objects as $i => $data_url ) {
				$mime_type = strtok( substr( $data_url->getURL()->getString(), 5 ), ';' );
				if ( ! $mime_type ) {
					continue;
				}
				$extension = preg_replace( ':.+/(.+-)?:', '', $mime_type );

				$guessed_urls = [];
				foreach ( $source_file_urls as $source_file_url ) {
					$guessed_url = preg_replace(
						':(?<=\.)\w+(\?.*)?(#.*)?$:', // Match the file extension in the URL.
						$extension,
						$source_file_url,
						1,
						$count
					);
					if ( 1 === $count ) {
						$guessed_urls[] = $guessed_url;
					}
				}
				if ( $stylesheet_base_url && $font_basename ) {
					$guessed_urls[] = $stylesheet_base_url . sprintf( 'fonts/%s.%s', $font_basename, $extension );
					$guessed_urls[] = $stylesheet_base_url . sprintf( 'fonts/%s.%s', strtolower( $font_basename ), $extension );
				}
				foreach ( $guessed_urls as $guessed_url ) {
					$path = $this->get_validated_url_file_path( $guessed_url, [ 'woff', 'woff2', 'ttf', 'otf', 'svg' ] );
					if ( ! is_wp_error( $path ) ) {
						$data_url->getURL()->setString( $guessed_url );
						$converted_count++;
						continue 2;
					}
				}
			} // End foreach $source_data_url_objects.
		} // End foreach $src_properties.
		if ( $converted_count && 0 === count( $ruleset->getRules( 'font-display' ) ) ) {
			$font_display_rule = new Rule( 'font-display' );
			$font_display_rule->setValue( 'swap' );
			$ruleset->addRule( $font_display_rule );
		}
	}
	private function process_css_keyframes( KeyFrame $css_list, $options ) {
		$results = [];
		if ( ! empty( $options['property_allowlist'] ) ) {
			foreach ( $css_list->getContents() as $rules ) {
				if ( ! ( $rules instanceof DeclarationBlock ) ) {
					$error     = [
						'code'      => self::CSS_SYNTAX_INVALID_DECLARATION,
						'item'      => get_class( $rules ),
						'type'      => 'css_error',
						'spec_name' => $options['spec_name'],
					];
					$sanitized = $this->should_sanitize_validation_error( $error );
					if ( $sanitized ) {
						$css_list->remove( $rules );
					}
					$results[] = compact( 'error', 'sanitized' );
					continue;
				}

				$results = array_merge(
					$results,
					$this->transform_important_qualifiers( $rules, $css_list, $options )
				);

				$properties = $rules->getRules();
				foreach ( $properties as $property ) {
					$vendorless_property_name = preg_replace( '/^-\w+-/', '', $property->getRule() );
					if ( ! in_array( $vendorless_property_name, $options['property_allowlist'], true ) ) {
						$error     = [
							'code'               => self::CSS_SYNTAX_INVALID_PROPERTY,
							'css_property_name'  => $property->getRule(),
							'css_property_value' => (string) $property->getValue(),
							'type'               => 'css_error',
							'spec_name'          => $options['spec_name'],
						];
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
	private function transform_important_qualifiers( RuleSet $ruleset, CSSList $css_list, $options ) {
		return (array) $ruleset;
	}
	private function collect_inline_styles( DOMElement $element ) {
		$attr_node = $element->getAttributeNode( 'style' );
		if ( ! $attr_node ) {
			return;
		}

		$value = trim( $attr_node->nodeValue );
		if ( empty( $value ) ) {
			return;
		}
		if (
			preg_match( '/{{[^}]+?}}/', $value ) &&
			0 !== $this->dom->xpath->query( '//template[ @type="amp-mustache" ]//.|//script[ @template="amp-mustache" and @type="text/plain" ]//.', $element )->length
		) {
			return;
		}

		$class = 'amp-wp-' . substr( md5( $value ), 0, 7 );
		$root  = ':root' . str_repeat( ':not(#_)', self::INLINE_SPECIFICITY_MULTIPLIER );
		$rule  = sprintf( '%s .%s { %s }', $root, $class, $value );

		$this->set_current_node( $element ); // And sources when needing to be located.

		$parsed = $this->get_parsed_stylesheet(
			$rule,
			[
				'allowed_at_rules'   => [],
				'property_allowlist' => $this->style_custom_cdata_spec['css_spec']['declaration'],
				'spec_name'          => self::STYLE_AMP_CUSTOM_SPEC_NAME,
			]
		);

		$element->removeAttribute( 'style' );
		$element->setAttribute( self::ORIGINAL_STYLE_ATTRIBUTE_NAME, $value );

		if ( $parsed['tokens'] ) {
			$this->pending_stylesheets[] = [
				'group'         => self::STYLE_AMP_CUSTOM_GROUP_INDEX,
				'original_size' => strlen( $rule ),
				'final_size'    => null,
				'element'       => $element,
				'origin'        => 'style_attribute',
				'sources'       => $this->current_sources,
				'priority'      => $this->get_stylesheet_priority( $attr_node ),
				'tokens'        => $parsed['tokens'],
				'hash'          => $parsed['hash'],
				'parse_time'    => $parsed['parse_time'],
				'shake_time'    => null,
				'cached'        => $parsed['cached'],
			];

			if ( $element->hasAttribute( 'class' ) ) {
				$element->setAttribute( 'class', $element->getAttribute( 'class' ) . ' ' . $class );
			} else {
				$element->setAttribute( 'class', $class );
			}
		}

		$this->set_current_node( null );
	}
	private function finalize_styles() {
		$white_list_css = '';
		$white_list_css = apply_filters('cwvpsb_whitelist_css_code', $white_list_css );
		$stylesheet_groups = [
			self::STYLE_AMP_CUSTOM_GROUP_INDEX    => [
				'source_map_comment'  => $white_list_css . "\n\n/*# sourceURL=web-vital-custom.css */",
				'cdata_spec'          => $this->style_custom_cdata_spec,
				'included_count'      => 0,
				'import_front_matter' => '', // Extra @import statements that are prepended when fetch fails and validation error is rejected.
			],
			self::STYLE_AMP_KEYFRAMES_GROUP_INDEX => [
				'source_map_comment'  => "\n\n/*# sourceURL=web-vital-keyframes.css */",
				'cdata_spec'          => $this->style_keyframes_cdata_spec,
				'included_count'      => 0,
				'import_front_matter' => '',
			],
		];

		$imported_font_urls = [];
		foreach ( $this->pending_stylesheets as $i => $pending_stylesheet ) {
			foreach ( $pending_stylesheet['tokens'] as $j => $part ) {
				if ( is_string( $part ) && 0 === strpos( $part, '@import' ) ) {
					$stylesheet_groups[ $pending_stylesheet['group'] ]['import_front_matter'] .= $part; // @todo Not currently relayed in stylesheet data.
					unset( $this->pending_stylesheets[ $i ]['tokens'][ $j ] );
				}
			}

			if ( ! empty( $pending_stylesheet['imported_font_urls'] ) ) {
				$imported_font_urls = array_merge( $imported_font_urls, $pending_stylesheet['imported_font_urls'] );
			}
		}
		foreach ( array_keys( $stylesheet_groups ) as $group ) {
			$stylesheet_groups[ $group ]['included_count'] = $this->finalize_stylesheet_group( $group, $stylesheet_groups[ $group ] );
		}
		if ( empty( $this->args['use_document_element'] ) ) {
			return;
		}
		if ( $stylesheet_groups[ self::STYLE_AMP_CUSTOM_GROUP_INDEX ]['included_count'] > 0 ) {
			$css = $stylesheet_groups[ self::STYLE_AMP_CUSTOM_GROUP_INDEX ]['import_front_matter'];
			$css .= implode( '', $this->get_stylesheets() );
			$css .= $stylesheet_groups[ self::STYLE_AMP_CUSTOM_GROUP_INDEX ]['source_map_comment'];
			$this->amp_custom_style_element = $this->dom->createElement( 'style' );
			$this->amp_custom_style_element->appendChild( $this->dom->createTextNode( $css ) );
			if(is_user_logged_in()){
				$imported_font_urls[] = site_url().'/wp-includes/css/admin-bar.min.css';
			}
			$this->dom->head->appendChild( $this->amp_custom_style_element );
		}
		foreach ( array_unique( $imported_font_urls ) as $imported_font_url ) {
			$link = $this->dom->createElement( 'link' );
			$link->setAttribute( 'rel', 'stylesheet' );
			$link->setAttribute( 'href', $imported_font_url );
			$this->dom->head->appendChild( $link );
		}
		if ( $stylesheet_groups[ self::STYLE_AMP_KEYFRAMES_GROUP_INDEX ]['included_count'] > 0 ) {
			$css = $stylesheet_groups[ self::STYLE_AMP_KEYFRAMES_GROUP_INDEX ]['import_front_matter'];

			$css .= implode(
				'',
				wp_list_pluck(
					array_filter(
						$this->pending_stylesheets,
						static function( $pending_stylesheet ) {
							return $pending_stylesheet['included'] && self::STYLE_AMP_KEYFRAMES_GROUP_INDEX === $pending_stylesheet['group'];
						}
					),
					'serialized'
				)
			);
			$css .= $stylesheet_groups[ self::STYLE_AMP_KEYFRAMES_GROUP_INDEX ]['source_map_comment'];

			$style_element = $this->dom->createElement( 'style' );
			/*$style_element->setAttribute( 'amp-keyframes', '' );*/
			$style_element->appendChild( $this->dom->createTextNode( $css ) );
			$this->dom->body->appendChild( $style_element );
		}

		$this->remove_admin_bar_if_css_excluded();
		$this->add_css_budget_to_admin_bar();
	}
	private function remove_admin_bar_if_css_excluded() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$admin_bar_id = 'wpadminbar';
		$admin_bar    = $this->dom->getElementById( $admin_bar_id );
		if ( ! $admin_bar || ! $admin_bar->parentNode ) {
			return;
		}

		$included = true;
		foreach ( $this->pending_stylesheets as &$pending_stylesheet ) {
			$is_admin_bar_css = (
				self::STYLE_AMP_CUSTOM_GROUP_INDEX === $pending_stylesheet['group']
				&&
				'admin-bar-css' === $pending_stylesheet['element']->getAttribute( 'id' )
			);
			if ( $is_admin_bar_css ) {
				$included = $pending_stylesheet['included'];
				break;
			}
		}

		unset( $pending_stylesheet );

		if ( ! $included ) {
			// @todo It would be nice if any style rules which refer to .admin-bar could also be removed, but this would mean retroactively going back over the CSS again and re-shaking it.
			if ( $this->dom->body->hasAttribute( 'class' ) ) {
				$this->dom->body->setAttribute(
					'class',
					preg_replace( '/(^|\s)admin-bar(\s|$)/', ' ', $this->dom->body->getAttribute( 'class' ) )
				);
			}
			$comment_text = sprintf(
				esc_html__( 'Admin bar (%s) was removed to preserve validity due to excessive CSS.', 'cwvpsb' ),
				'#' . $admin_bar_id
			);
			$admin_bar->parentNode->replaceChild(
				$this->dom->createComment( ' ' . $comment_text . ' ' ),
				$admin_bar
			);
		}
	}
	public function get_validate_response_data() {
		$stylesheets = [];
		foreach ( $this->pending_stylesheets as $pending_stylesheet ) {
			$attributes = [];
			foreach ( $pending_stylesheet['element']->attributes as $attribute ) {
				$attributes[ $attribute->nodeName ] = $attribute->nodeValue;
			}
			$pending_stylesheet['element'] = [
				'name'       => $pending_stylesheet['element']->nodeName,
				'attributes' => $attributes,
			];

			switch ( $pending_stylesheet['group'] ) {
				case self::STYLE_AMP_CUSTOM_GROUP_INDEX:
					$pending_stylesheet['group'] = 'custom';
					break;
				case self::STYLE_AMP_KEYFRAMES_SPEC_NAME:
					$pending_stylesheet['group'] = 'keyframes';
					break;
			}

			unset( $pending_stylesheet['serialized'] );
			$stylesheets[] = $pending_stylesheet;
		}

		return compact( 'stylesheets' );
	}
	public function add_css_budget_to_admin_bar() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		$validity_li_element = $this->dom->getElementById( 'wp-admin-bar-amp-validity' );
		if ( ! $validity_li_element instanceof DOMElement ) {
			return;
		}
		$stylesheets_li_element = $validity_li_element->cloneNode( true );
		$stylesheets_li_element->setAttribute( 'id', 'wp-admin-bar-amp-stylesheets' );

		$stylesheets_a_element = $stylesheets_li_element->getElementsByTagName( 'a' )->item( 0 );
		if ( ! ( $stylesheets_a_element instanceof DOMElement ) ) {
			return;
		}
		$stylesheets_a_element->setAttribute(
			'href',
			$stylesheets_a_element->getAttribute( 'href' ) . '#amp_stylesheets'
		);

		while ( $stylesheets_a_element->firstChild ) {
			$stylesheets_a_element->removeChild( $stylesheets_a_element->firstChild );
		}

		$total_size = 0;
		foreach ( $this->pending_stylesheets as $pending_stylesheet ) {
			if ( empty( $pending_stylesheet['duplicate'] ) ) {
				$total_size += $pending_stylesheet['final_size'];
			}
		}

		$css_usage_percentage = ceil( ( $total_size / $this->style_custom_cdata_spec['max_bytes'] ) * 100 );
		$menu_item_text       = esc_html__( 'CSS Usage', 'cwvpsb' ) . ': ';
		$menu_item_text      .= $css_usage_percentage . '%';
		$stylesheets_a_element->appendChild( $this->dom->createTextNode( $menu_item_text ) );

		if ( $css_usage_percentage > 100 ) {
			$icon = Icon::INVALID;
		} elseif ( $css_usage_percentage >= self::CSS_BUDGET_WARNING_PERCENTAGE ) {
			$icon = Icon::WARNING;
		}
		if ( isset( $icon ) ) {
			$span = $this->dom->createElement( 'span' );
			$span->setAttribute( 'class', 'ab-icon amp-icon ' . $icon );
			$stylesheets_a_element->appendChild( $span );
		}

		$validity_li_element->parentNode->insertBefore( $stylesheets_li_element, $validity_li_element->nextSibling );
	}
	private function ampify_ruleset_selectors( $ruleset ) {
		$selectors             = [];
		$has_changed_selectors = false;
		$language              = strtolower( get_bloginfo( 'language' ) );
		foreach ( $ruleset->getSelectors() as $old_selector ) {
			$selector = $old_selector->getSelector();
			if ( preg_match( '/^\*\s*\+?\s*html/', $selector ) ) {
				$has_changed_selectors = true;
				continue;
			}
			$is_other_language_root = (
				preg_match( '/^html\[lang(?P<starts_with>\^)?=([\'"]?)(?P<lang>.+?)\2\]/', strtolower( $selector ), $matches )
				&&
				(
					empty( $matches['starts_with'] )
					?
					$language !== $matches['lang']
					:
					substr( $language, 0, strlen( $matches['lang'] ) ) !== $matches['lang']
				)
			);
			if ( $is_other_language_root ) {
				$has_changed_selectors = true;
				continue;
			}
			if ( preg_match( '/:lang\((?P<languages>.+?)\)/', $selector, $matches ) ) {
				$has_matching_language = 0;
				$selector_languages    = array_map(
					static function ( $selector_language ) {
						return trim( $selector_language, '"\'' );
					},
					preg_split( '/\s*,\s*/', strtolower( trim( $matches['languages'] ) ) )
				);
				foreach ( $selector_languages as $selector_language ) {
					if (
						substr( $language, 0, strlen( $selector_language ) ) === $selector_language
						||
						substr( $selector_language, 0, strlen( $language ) ) === $language
					) {
						$has_matching_language = true;
						break;
					}
				}
				if ( ! $has_matching_language ) {
					$has_changed_selectors = true;
					continue;
				}
			}
			$before_type_selector_pattern = '(?<=^|\(|\s|>|\+|~|,|})';
			$after_type_selector_pattern  = '(?=$|[^a-zA-Z0-9_-])';
			if ( $this->focus_class_name_selector_pattern ) {
				$count    = 0;
				$selector = preg_replace(
					$this->focus_class_name_selector_pattern,
					':focus-within',
					$selector,
					-1,
					$count
				);
				if ( $count > 0 ) {
					$has_changed_selectors = true;
				}
			}
			$selector = preg_replace(
				'/(?<=\[)style(?=([*$~]?=.*?)?])/is',
				self::ORIGINAL_STYLE_ATTRIBUTE_NAME,
				$selector,
				-1,
				$count
			);
			if ( $count > 0 ) {
				$has_changed_selectors = true;
			}
			$edited_selectors = [ $selector ];
			foreach ( $this->selector_mappings as $html_tag => $amp_tags ) {
				$html_pattern = '/' . $before_type_selector_pattern . preg_quote( $html_tag, '/' ) . $after_type_selector_pattern . '/i';
				for ( $i = 0; $i < count( $edited_selectors ); $i++ ) { // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed, Squiz.PHP.DisallowSizeFunctionsInLoops.Found
					$amp_tag_pattern = '/' . $before_type_selector_pattern . implode( '|', $amp_tags ) . $after_type_selector_pattern . '/i';
					if ( preg_match( $amp_tag_pattern, $edited_selectors[ $i ], $matches ) && in_array( $matches[0], $amp_tags, true ) ) {
						continue;
					}
					$edited_selector = preg_replace( $html_pattern, $amp_tags[0], $edited_selectors[ $i ], -1, $count );
					if ( 0 === $count ) {
						continue;
					}

					$edited_selectors_from_selector = [ $edited_selector ];
					foreach ( array_slice( $amp_tags, 1 ) as $amp_tag ) { // Note: This array contains only a couple items.
						$edited_selectors_from_selector[] = preg_replace( $html_pattern, $amp_tag, $edited_selectors[ $i ] );
					}
					array_splice( $edited_selectors, $i, 1, $edited_selectors_from_selector );
					$has_changed_selectors = true;
				}
			}

			$selectors = array_merge( $selectors, $edited_selectors );
		}

		if ( $has_changed_selectors ) {
			$ruleset->setSelectors( $selectors );
		}
	}
	private static function get_class_name_selector_pattern( $class_names ) {
		$class_pattern = implode(
			'|',
			array_map(
				static function ( $class_name ) {
					return preg_quote( $class_name, '/' );
				},
				(array) $class_names
			)
		);
		return "/\.({$class_pattern})(?=$|[^a-zA-Z0-9_-])/";
	}
	private function finalize_stylesheet_group( $group, $group_config ) {
		$included_count = 0;
		$max_bytes      = $group_config['cdata_spec']['max_bytes'] - strlen( $group_config['source_map_comment'] );

		$previously_seen_stylesheet_index = [];
		foreach ( $this->pending_stylesheets as $pending_stylesheet_index => &$pending_stylesheet ) {
			if ( $group !== $pending_stylesheet['group'] ) {
				continue;
			}

			$start_time    = microtime( true );
			$shaken_tokens = [];
			foreach ( $pending_stylesheet['tokens'] as $token ) {
				if ( is_string( $token ) ) {
					$shaken_tokens[] = [ true, $token ];
					continue;
				}

				list( $selectors_parsed, $declaration_block ) = $token;

				$used_selector_count = 0;
				$selectors           = [];
				foreach ( $selectors_parsed as $selector => $parsed_selector ) {
					$should_include = $this->is_customize_preview || (
						(
							empty( $parsed_selector[ self::SELECTOR_EXTRACTED_CLASSES ] )
							||
							$this->has_used_class_name( $parsed_selector[ self::SELECTOR_EXTRACTED_CLASSES ] )
						)
						&&
						(
							empty( $parsed_selector[ self::SELECTOR_EXTRACTED_IDS ] )
							||
							0 === count(
								array_filter(
									$parsed_selector[ self::SELECTOR_EXTRACTED_IDS ],
									function( $id ) {
										return ! $this->dom->getElementById( $id );
									}
								)
							)
						)
						&&
						(
							empty( $parsed_selector[ self::SELECTOR_EXTRACTED_TAGS ] )
							||
							$this->has_used_tag_names( $parsed_selector[ self::SELECTOR_EXTRACTED_TAGS ] )
						)
						&&
						(
							empty( $parsed_selector[ self::SELECTOR_EXTRACTED_ATTRIBUTES ] )
							||
							$this->has_used_attributes( $parsed_selector[ self::SELECTOR_EXTRACTED_ATTRIBUTES ] )
						)
					);
					$selectors[ $selector ] = $should_include;
					if ( $should_include ) {
						$used_selector_count++;
					}
				}
				$shaken_tokens[] = [
					0 !== $used_selector_count,
					$selectors,
					$declaration_block,
				];
			}
			$stylesheet_part_count = count( $shaken_tokens );
			for ( $i = 0; $i < $stylesheet_part_count; $i++ ) {
				if ( ! is_string( $shaken_tokens[ $i ][1] ) || '@' !== substr( $shaken_tokens[ $i ][1], 0, 1 ) ) {
					continue;
				}
				if ( '{}' === substr( $shaken_tokens[ $i ][1], -2 ) ) {
					$shaken_tokens[ $i ][0] = false;
					continue;
				}
				if ( '{' === substr( $shaken_tokens[ $i ][1], -1 ) ) {
					$open_braces = 1;
					for ( $j = $i + 1; $j < $stylesheet_part_count; $j++ ) {
						if ( is_array( $shaken_tokens[ $j ][1] ) ) { // Is declaration block.
							if ( true === $shaken_tokens[ $j ][0] ) {
								// rule cannot be removed and so we must abort.
								break;
							} else {
								// list of parts that may be part of an at-rule that is now empty and should be removed.
								continue;
							}
						}

						$is_at_rule = '@' === substr( $shaken_tokens[ $j ][1], 0, 1 );
						if ( $is_at_rule && '{}' === substr( $shaken_tokens[ $j ][1], -2 ) ) {
							continue; // The rule opened is empty from the start.
						}

						if ( $is_at_rule && '{' === substr( $shaken_tokens[ $j ][1], -1 ) ) {
							$open_braces++;
						} elseif ( '}' === $shaken_tokens[ $j ][1] ) {
							$open_braces--;
						} else {
							break;
						}
						if ( 0 === $open_braces ) {
							for ( $k = $i; $k <= $j; $k++ ) {
								$shaken_tokens[ $k ][0] = false;
							}
							$i = $j; // Jump the outer loop ahead to skip over what has been already marked as removed.
							continue 2;
						}
					}
				}
			}
			$pending_stylesheet['shaken_tokens'] = $shaken_tokens;
			unset( $pending_stylesheet['tokens'], $shaken_tokens );
			$pending_stylesheet['serialized'] = implode(
				'',
				array_map(
					static function ( $shaken_token ) {
						if ( is_array( $shaken_token[1] ) ) {
							$selectors = array_keys( array_filter( $shaken_token[1] ) );
							if ( empty( $selectors ) ) {
								return '';
							} else {
								return implode( ',', $selectors ) . '{' . implode( ';', $shaken_token[2] ) . '}';
							}
						} else {
							return $shaken_token[1];
						}
					},
					array_filter(
						$pending_stylesheet['shaken_tokens'],
						static function( $shaken_token ) {
							return false !== $shaken_token[0];
						}
					)
				)
			);

			$pending_stylesheet['included']   = null; // To be determined below.
			$pending_stylesheet['final_size'] = strlen( $pending_stylesheet['serialized'] );
			if ( isset( $previously_seen_stylesheet_index[ $pending_stylesheet['hash'] ] ) ) {
				$this->pending_stylesheets[ $previously_seen_stylesheet_index[ $pending_stylesheet['hash'] ] ]['included']  = false;
				$this->pending_stylesheets[ $previously_seen_stylesheet_index[ $pending_stylesheet['hash'] ] ]['duplicate'] = true;
			}
			$previously_seen_stylesheet_index[ $pending_stylesheet['hash'] ] = $pending_stylesheet_index;

			$pending_stylesheet['shake_time'] = microtime( true ) - $start_time;
		} // End foreach pending_stylesheets.

		unset( $pending_stylesheet );
		$pending_stylesheet_indices = array_keys( $this->pending_stylesheets );
		usort(
			$pending_stylesheet_indices,
			function ( $a, $b ) {
				return $this->pending_stylesheets[ $a ]['priority'] - $this->pending_stylesheets[ $b ]['priority'];
			}
		);

		$current_concatenated_size = 0;
		foreach ( $pending_stylesheet_indices as $i ) {
			if ( $group !== $this->pending_stylesheets[ $i ]['group'] ) {
				continue;
			}
			if ( false === $this->pending_stylesheets[ $i ]['included'] ) {
				continue;
			}
			if ( ! $this->is_customize_preview && $current_concatenated_size + $this->pending_stylesheets[ $i ]['final_size'] > $max_bytes ) {
				$validation_error = [
					'code'      => self::STYLESHEET_TOO_LONG,
					'type'      => 'css_error',
					'spec_name' => self::STYLE_AMP_KEYFRAMES_GROUP_INDEX === $group ? self::STYLE_AMP_KEYFRAMES_SPEC_NAME : self::STYLE_AMP_CUSTOM_SPEC_NAME,
				];
				if ( isset( $this->pending_stylesheets[ $i ]['sources'] ) ) {
					$validation_error['sources'] = $this->pending_stylesheets[ $i ]['sources'];
				}

				$data = [
					'node' => $this->pending_stylesheets[ $i ]['element'],
				];
				if ( $this->should_sanitize_validation_error( $validation_error, $data ) ) {
					$this->pending_stylesheets[ $i ]['included'] = false;
					continue; // Skip to the next stylesheet.
				}
			}

			if ( ! isset( $this->pending_stylesheets[ $i ]['included'] ) ) {
				$this->pending_stylesheets[ $i ]['included'] = true;
				$included_count++;
				$current_concatenated_size += $this->pending_stylesheets[ $i ]['final_size'];
			}
		}

		return $included_count;
	}
	private function create_meta_viewport( DOMElement $element, $viewport_rules ) {
		if ( empty( $viewport_rules ) ) {
			return;
		}
		$viewport_meta = $this->dom->createElement( 'meta' );
		$viewport_meta->setAttribute( 'name', 'viewport' );
		$viewport_meta->setAttribute(
			'content',
			implode(
				',',
				array_map(
					static function ( $property_name ) use ( $viewport_rules ) {
						return $property_name . '=' . $viewport_rules[ $property_name ];
					},
					array_keys( $viewport_rules )
				)
			)
		);
		$element->parentNode->insertBefore( $viewport_meta, $element );
	}
}

/**
 * Helper function 
 */
function cwvpsb_is_blog () {
	if ( (is_archive()) || (is_author()) || (is_category()) || (is_home()) || (is_single()) || (is_tag()) ) {
		return true;
	}
	else {
		return false; 
	}
}
function cwvpsb_get_proper_transient_name($transient){
	global $post;
	if( function_exists('is_home') && is_home()){
		$transient = "home";
	}elseif(function_exists('cwvpsb_is_blog') && cwvpsb_is_blog()){
		$transient = "blog";
	}elseif( function_exists('is_front_page') && is_front_page()){
		$transient = "post-".get_option( 'page_on_front' );
	}elseif(!empty($post) && is_object($post)){
		$transient = "post-".$post->ID;
	}
	return $transient;
}
function cwvpsb_set_file_transient( $transient, $value, $expiration = 0 ) {
	return $value;
	$transient = cwvpsb_get_proper_transient_name($transient);
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
	          return ( array( 'error' => sprintf( esc_html__( 'Could not write file %s', 'cwvpsb' ), $new_file ) ));
	        }
	        $result = @fwrite( $ifp, json_encode($value) );
		    fclose( $ifp );
		    //set_transient($transient_option, true, 30 * 24 * 60);
		}

	}
	return $result;
}


function cwvpsb_style_get_file_transient( $transient ) {

	$transient = cwvpsb_get_proper_transient_name($transient);
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