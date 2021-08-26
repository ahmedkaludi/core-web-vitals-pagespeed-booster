<h2>Core Web Vitals & PageSpeed Booster Settings</h2>

<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : "images";

if (isset($_POST['submit'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings have been saved!</p></div>';
    }
?>
<h2 class="nav-tab-wrapper">
    <a href="?page=cwvpsb-images&tab=images" class="nav-tab <?php echo $active_tab == 'images' ? 'nav-tab-active' : ''; ?>">images</a>
    <a href="?page=cwvpsb-images&tab=css" class="nav-tab <?php echo $active_tab == 'css' ? 'nav-tab-active' : ''; ?>">CSS</a>
    <a href="?page=cwvpsb-images&tab=js" class="nav-tab <?php echo $active_tab == 'js' ? 'nav-tab-active' : ''; ?>">Javascript</a>
    <a href="?page=cwvpsb-images&tab=cache" class="nav-tab <?php echo $active_tab == 'cache' ? 'nav-tab-active' : ''; ?>">Cache</a>
</h2>

<?php 
    
    switch ($active_tab) {
        case 'images':
            cwvpsb_image_optimization();
            break;
        case 'css':
            cwvpsb_css_optimization();
            break;
        case 'js':
            cwvpsb_js_optimization();
            break;
        case 'cache':
            cwvpsb_cache_optimization();
            break;       
        default:
            cwvpsb_image_optimization();
    }
    
function cwvpsb_image_optimization() {
    if (isset($_POST['submit'])) {
        update_option('cwvpsb_check_webp', sanitize_text_field($_POST['check_webp']));
        update_option('cwvpsb_check_lazyload', sanitize_text_field($_POST['check_ll']));
    }
    $check_webp = get_option('cwvpsb_check_webp');
    $check_ll = get_option('cwvpsb_check_lazyload');
    ?>
    <form method="POST">
        <?php wp_nonce_field('cwvpsb-nonce', 'cwvpsb-nonce-settings'); ?>
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Webp images</label></th>
                <td><label class="switch">
                    <input name="check_webp" type="checkbox" value="1" <?php if ($check_webp) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">Images are converted to WebP on the fly if the browser supports it. You don't have to do anything</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Lazy Load</label></th>
                <td><label class="switch">
                    <input name="check_ll" type="checkbox" value="1" <?php if ($check_ll) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">Lazy Load delays loading of images and iframes in long web pages. which are outside of viewport and will not be loaded before user scrolls to them</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></br></br>
            <input name="clear_site_cache_on_saved" type="submit" class="button-primary" value="Save Changes and Clear Site Cache" />
        </p>
    </form>
<?php
}
function cwvpsb_css_optimization()
{
    if (isset($_POST['submit'])) {
        update_option('cwvpsb_check_minification', sanitize_text_field($_POST['check_minify']));
        update_option('cwvpsb_check_unused_css', sanitize_text_field($_POST['check_unused_css']));
        update_option('cwvpsb_check_fonts', sanitize_text_field($_POST['cwvpsb_check_fonts']));
    }
    $check_minify = get_option('cwvpsb_check_minification'); 
    $check_unused_css = get_option('cwvpsb_check_unused_css'); 
    $check_fonts = get_option('cwvpsb_check_fonts'); 
    ?>
    <form method="POST">
        <?php wp_nonce_field('cwvpsb-nonce', 'cwvpsb-nonce-settings'); ?>
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Minification</label></th>
                <td><label class="switch">
                    <input name="check_minify" type="checkbox" value="1" <?php if ($check_minify) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">You will see the source of your HTML, CSS and JavaScript are now compressed and the size will be smaller which will be helpful to improve your page load speed.</p>
                </td>
            </tr>
             <tr>
                <th scope="row"><label>Remove Unused CSS</label></th>
                <td><label class="switch">
                    <input name="check_unused_css" type="checkbox" value="1" <?php if ($check_unused_css) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">Makes your site even faster and lighter by automatically removing unused CSS from your website.</p>
                </td>
            </tr>
             <tr>
                <th scope="row"><label>Google Fonts Optimizations</label></th>
                <td><label class="switch">
                    <input name="cwvpsb_check_fonts" type="checkbox" value="1" <?php if ($check_fonts) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">Locally hosting Google fonts for Pagespeed Insights or GT Metrix improvements.</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></br></br>
            <input name="clear_site_cache_on_saved" type="submit" class="button-primary" value="Save Changes and Clear Site Cache" />
        </p>
    </form>
<?php
}

function cwvpsb_js_optimization()
{
    if (isset($_POST['submit'])) {
        update_option('cwvpsb_check_javascript_delay', sanitize_text_field($_POST['check_js']));
    }
    $check_js = get_option('cwvpsb_check_javascript_delay'); 
    ?>
    <form method="POST">
        <?php wp_nonce_field('cwvpsb-nonce', 'cwvpsb-nonce-settings'); ?>
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Delay JavaScript Execution</label></th>
                <td><label class="switch">
                    <input name="check_js" type="checkbox" value="1" <?php if ($check_js) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></br></br>
            <input name="clear_site_cache_on_saved" type="submit" class="button-primary" value="Save Changes and Clear Site Cache" />
        </p>
    </form>
<?php
}

function cwvpsb_cache_optimization()
{
    if (isset($_POST['submit'])) {
        update_option('cwvpsb_check_cache', sanitize_text_field($_POST['check_cache']));
    }
    $check_cache = get_option('cwvpsb_check_cache'); 
    ?>
    <form method="POST">
        <?php wp_nonce_field('cwvpsb-nonce', 'cwvpsb-nonce-settings'); ?>
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Cache</label></th>
                <td><label class="switch">
                    <input name="check_cache" type="checkbox" value="1" <?php if ($check_cache) {echo "checked";} ?>>
                    <span class="slider round"></span></label>
                    <p class="description">Caching pages will reduce the response time of your site and your web pages load much faster, directly from cache.</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></br></br>
            <input name="clear_site_cache_on_saved" type="submit" class="button-primary" value="Save Changes and Clear Site Cache" />
        </p>
    </form>
<?php
}