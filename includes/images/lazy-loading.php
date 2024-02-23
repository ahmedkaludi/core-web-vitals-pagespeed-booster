<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function cwvpsb_lazy_load() {
  $plugin = new CWV_Lazy_Load();
  $plugin->run();
}
cwvpsb_lazy_load();

class CWV_Lazy_Load {

  protected $loader;

  protected $plugin_name;

  
  protected $version;

  
  public function __construct() {
     
    $this->version = CWVPSB_VERSION;
     
    $this->plugin_name = 'Core Web Vitals & PageSpeed Booster';
    
    $this->load_dependencies();
    
    $this->define_public_hooks();

  }

  
  private function load_dependencies() {

    if (!class_exists('phpQuery')) {
        require_once plugin_dir_path( __FILE__ ) . 'phpQuery-onefile.php';
    }

    $this->loader = new CWV_Lazy_Load_Loader();

  }
  
  private function define_public_hooks() { 

    if ( !is_admin() || !function_exists('is_checkout') || (function_exists('is_checkout') && !is_checkout()) ) {

        $plugin_public = new CWV_Lazy_Load_Public( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $settings = cwvpsb_defaults();
        if(isset($settings['image_optimization_alt']) && $settings['image_optimization_alt'] == 1 ){
          add_filter( 'cwvpsb_complete_html_after_dom_loaded', array($plugin_public, 'buffer_start_cwv_regex'), 45 );
        }else{
          add_filter( 'cwvpsb_complete_html_after_dom_loaded', array($plugin_public, 'buffer_start_cwv'), 45 );
        }
    }
  }

  
  public function run() {
    $this->loader->run();
  }

  
  public function get_plugin_name() {
    return $this->plugin_name;
  }

  public function get_loader() {
    return $this->loader;
  }

  
  public function get_version() {
    return $this->version;
  }

}

class CWV_Lazy_Load_Loader {

  
  protected $actions;

  
  protected $filters;

  public function __construct() {

    $this->actions = array();
    $this->filters = array();

  }

  
  public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
    $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
  }

  
  public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
    $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
  }

  
  private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

    $hooks[] = array(
      'hook'          => $hook,
      'component'     => $component,
      'callback'      => $callback,
      'priority'      => $priority,
      'accepted_args' => $accepted_args
    );

    return $hooks;

  }

  
  public function run() {

    foreach ( $this->filters as $hook ) {
      add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
    }

    foreach ( $this->actions as $hook ) {
      add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
    }

  }

}

class CWV_Lazy_Load_Public {

  
  private $plugin_name;

  private $version;

  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;

  }
 

  
  public function enqueue_scripts() {
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
      return ;
    }
    $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';	
    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . "lazy-load-public{$min}.js", array( 'jquery' ), $this->version, false );

  }
  public function buffer_start_cwv($wphtml) { 
    if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
      return $wphtml;
    }
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
      return $wphtml;
    }
      $lazy_jq_selector = 'img';   
      
      $pq = phpQuery::newDocument($wphtml); 
      if(!empty(pq($lazy_jq_selector))){
        foreach(pq($lazy_jq_selector) as $stuff)
        {
         if ($stuff->tagName == 'img'){
          $sized_src = pq($stuff)->attr('src');
          // Fix for woocommerce zoom image to work properly
          if(!empty(pq('.woocommerce')) && !empty(pq('.single-product')) &&  pq($stuff)->attr('data-large_image')){
            pq($stuff)->attr('data-src',pq($stuff)->attr('data-large_image')); 
          }else{
             pq($stuff)->attr('data-src',$sized_src);
          }
           pq($stuff)->removeAttr('src');
           pq($stuff)->attr('src','data:image/gif;base64,R0lGODlhAQABAIAAAP//////zCH5BAEHAAAALAAAAAABAAEAAAICRAEAOw==');
           pq($stuff)->attr('data-srcset',pq($stuff)->attr('srcset'));
           pq($stuff)->removeAttr('srcset');
           pq($stuff)->attr('data-sizes',pq($stuff)->attr('sizes'));
           pq($stuff)->removeAttr('sizes');
           pq($stuff)->addClass('cwvlazyload');
         }
      }       
      }
      foreach($pq->xpath->query('//*[@style]') as $node)
      {
        $style =  $node->getAttribute('style');
        $gotmatches = preg_match_all("/url\(.*?(gif|png|jpg|jpeg)\'\)/", $style, $matches);
        if($gotmatches){
          foreach ($matches[0] as $key => $value) {
            $newstyle = str_replace($value, "url('data:image/gif;base64,R0lGODlhAQABAIAAAP//////zCH5BAEHAAAALAAAAAABAAEAAAICRAEAOw==')", $style); 
            pq($node)->attr("data-style", $style);
            pq($node)->attr("style", $newstyle);
            pq($node)->addClass('cwvlazyload');
          }
          
        }
      }
        return $pq->html();
  }

  public function buffer_start_cwv_regex($wphtml) { 
    if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
      return $wphtml;
    }
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
      return $wphtml;
    }

// Convert data-src attributes
$wphtml = preg_replace_callback(
  '/<img\s(.*?)>/i',
  function ($matches) {
      $attributes = [];
      $optimum_src="";
      if (isset($matches[1])) {
        $attributesString = $matches[1];

        // Regular expression to extract attributes and their values
        preg_match_all('/(\w+)\s*=\s*([\'"])(.*?)\2/', $attributesString, $attributeMatches, PREG_SET_ORDER);
        $src_default='';
        foreach ($attributeMatches as $match) {
         if($match[1] == 'src'){
            $attributes['src'] = "data:image/gif;base64,R0lGODlhAQABAIAAAP//////zCH5BAEHAAAALAAAAAABAAEAAAICRAEAOw==";
            $attributes['data-src'] = esc_url($match[3]);
          }elseif($match[1] == 'srcset'){
            $attributes['data-srcset']= esc_attr($match[3]);
          }elseif($match[1] == 'sizes'){
            $attributes['data-sizes']= esc_attr($match[3]);
          }else{
            $attributes[$match[1]]= esc_attr($match[3]);
          }
        }

        if(empty($attributes['class'])){
          $attributes['class'] = "cwvlazyload";
        }else{
          $attributes['class'] .= " cwvlazyload";
        }
        if(isset($attributes['srcset'])){
          unset($attributes['srcset']);
        }
        if(isset($attributes['sizes'])){
          unset($attributes['sizes']);
        }
      
        if(isset($attributes['large_image'])){
          $attributes['data-src'] = $attributes['large_image'];
        }
    
    }
      // Construct the updated img tag with lazy-loading attributes
      $lazyImgTag = '<img '.$this->attributes_to_string($attributes).'>'; // no need to escape attribtes are  already escaped
      return $lazyImgTag;
  },
  $wphtml
);

return $wphtml;
}

public function attributes_to_string($attributes) {
  $result = '';
  foreach ($attributes as $key => $value) {
      $result .= $key . '="' . htmlspecialchars($value) . '" ';
  }
  return trim($result);
}

  public function buffer_end_cwv() { ob_end_flush(); }
}