<h2>Core Web Vitals & PageSpeed Booster Settings</h2>

<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : "images";

if (isset($_POST['submit'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings have been saved!</p></div>';
    }
?>
<h2 class="nav-tab-wrapper">
    <a href="?page=cwvpsb-images&tab=images" class="nav-tab <?php echo $active_tab == 'images' ? 'nav-tab-active' : ''; ?>">images</a>
    <a href="?page=cwvpsb-images&tab=minify" class="nav-tab <?php echo $active_tab == 'minify' ? 'nav-tab-active' : ''; ?>">Minify</a>
    <a href="?page=cwvpsb-images&tab=js" class="nav-tab <?php echo $active_tab == 'js' ? 'nav-tab-active' : ''; ?>">Javascript</a>
</h2>

<?php 
    
    switch ($active_tab) {
        case 'images':
            cwvpsb_image_optimization();
            break;
        case 'minify':
            cwvpsb_minify_optimization();
            break;
        case 'js':
            cwvpsb_js_optimization();
            break;     
        default:
            cwvpsb_image_optimization();
    }
    
function cwvpsb_image_optimization() {
    if (isset($_POST['submit'])) {
        update_option('cwvpsb_check_webp', sanitize_text_field($_POST['check_webp']));
    }
    $check_webp = get_option('cwvpsb_check_webp');
    ?>
    <form method="POST">
        <?php wp_nonce_field('cwvpsb-nonce', 'cwvpsb-nonce-settings'); ?>
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Webp images</label></th>
                <td>
                    <input name="check_webp" type="checkbox" value="1" <?php if ($check_webp) {echo "checked";} ?>>
                    <p class="description">Images are converted to WebP on the fly if the browser supports it. You don't have to do anything</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>
    </form>
<?php
}
function cwvpsb_minify_optimization()
{
    if (isset($_POST['submit'])) {
        update_option('cwvpsb_check_minification', sanitize_text_field($_POST['check_minify']));
    }
    $check_minify = get_option('cwvpsb_check_minification'); 
    ?>
    <form method="POST">
        <?php wp_nonce_field('cwvpsb-nonce', 'cwvpsb-nonce-settings'); ?>
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Minification</label></th>
                <td>
                    <input name="check_minify" type="checkbox" value="1" <?php if ($check_minify) {echo "checked";} ?>>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
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
                <td>
                    <input name="check_js" type="checkbox" value="1" <?php if ($check_js) {echo "checked";} ?>>
                    <p class="description">Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>
    </form>
<?php
}