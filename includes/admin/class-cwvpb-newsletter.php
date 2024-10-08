<?php
/**
 * Newsletter class
 *
 * @author   Magazine3
 * @category Admin
 * @path     controllers/admin/newsletter
 * @Version 1.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH'))
        exit;

class CWVPB_newsletter
{

        function __construct()
        {

                add_filter('cwvpsb_localize_filter', array($this, 'cwvpb_add_localize_footer_data'), 10, 2);
                add_action('wp_ajax_cwvpsb_subscribe_to_news_letter', array($this, 'cwvpsb_subscribe_to_news_letter'));

        }

        function cwvpsb_subscribe_to_news_letter()
        {

                if (!isset($_POST['cwvpb_security_nonce'])) {
                        return;
                }
                if (!wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['cwvpb_security_nonce'] ) ), 'cwvpb_ajax_check_nonce')) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        return;
                }
                if (!(current_user_can('manage_options'))) {
                        return;
                }
                $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
                $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
                $website = isset($_POST['website']) ? sanitize_url(wp_unslash($_POST['website'])) : '';

                if ($email) {

                        $api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';

                        $api_params = array(
                                'name' => $name,
                                'email' => $email,
                                'website' => $website,
                                'type' => 'cwvpb'
                        );

                        $response = wp_remote_post($api_url, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));
                        if (is_wp_error($response)) {
                                $error_message = $response->get_error_message();
                                echo "Something went wrong:" . esc_html($error_message);
                        } else {
                                $response = wp_remote_retrieve_body($response);
                                wp_send_json($response);
                        }


                } else {
                        echo esc_html__('Email id required', 'cwvpsb');
                }

                wp_die();
        }

        function cwvpb_add_localize_footer_data($object, $object_name)
        {

                $dismissed = explode(',', get_user_meta(wp_get_current_user()->ID, 'dismissed_wp_pointers', true));
                $do_tour = !in_array('cwvpsb_subscribe_pointer', $dismissed);

                if ($do_tour) {
                        wp_enqueue_style('wp-pointer');
                        wp_enqueue_script('wp-pointer');
                }

                if ($object_name == 'cwvpsb_localize_data') {

                        global $current_user;
                        $tour = array();
                        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        if (!array_key_exists($tab, $tour)) {

                                $object['do_tour'] = $do_tour;
                                $object['get_home_url'] = get_home_url();
                                $object['current_user_email'] = $current_user->user_email;
                                $object['current_user_name'] = $current_user->display_name;
                                $object['displayID'] = '#menu-settings';
                                $object['button1'] = esc_html__('No Thanks', 'cwvpsb');
                                $object['button2'] = false;
                                $object['function_name'] = '';
                                $object['ajax_url'] = admin_url('admin-ajax.php');
                        }

                }
                return $object;

        }

}
$cwvps_newsletter = new CWVPB_newsletter();
?>