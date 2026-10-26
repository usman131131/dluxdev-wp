<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

class Google_Connect {

    private $api_old;
    private $utils;

    public function __construct(Google_Api_Old $api_old, Google_Utils $utils) {
        $this->api_old = $api_old;
        $this->utils = $utils;

        add_action('wp_ajax_grw_hide_review', array($this, 'hide_review'));
        add_action('wp_ajax_grw_connect_google', array($this, 'connect_google'));
        add_action('wp_ajax_grw_place_autocomplete', array($this, 'place_autocomplete'));
        add_action('wp_ajax_grw_get_place', array($this, 'get_place'));
    }

    public function hide_review() {
        global $wpdb;

        if (current_user_can('editor') || current_user_can('administrator')) {
            if (isset($_POST['grw_wpnonce']) === false) {
                $error = __('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.', 'widget-google-reviews');
                $response = compact('error');
            } else {
                check_admin_referer('grw_wpnonce', 'grw_wpnonce');

                $review_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

                $review = $review_id ? $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM " . $wpdb->prefix . Database::REVIEW_TABLE .
                        " WHERE id = %d", $review_id
                    )
                ) : null;

                if (!$review) {
                    header('Content-type: text/javascript');
                    echo json_encode(array('error' => __('Review not found.', 'widget-google-reviews')));
                    die();
                }

                $hide = $review->hide == '' ? 'y' : '';
                $wpdb->update($wpdb->prefix . Database::REVIEW_TABLE, array('hide' => $hide), array('id' => $review_id));

                $this->clear_feed_cache(isset($_POST['feed_id']) ? $_POST['feed_id'] : null);

                $response = array('hide' => $hide);
            }
            header('Content-type: text/javascript');
            echo json_encode($response);
            die();
        }
    }

    public function connect_google() {
        if (current_user_can('manage_options')) {

            $response = null;

            if (isset($_POST['grw_wpnonce']) === false) {
                $error = __('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.', 'widget-google-reviews');
                $response = compact('error');
            } else {
                check_admin_referer('grw_wpnonce', 'grw_wpnonce');

                if (isset($_POST['key'])) {
                    $key = sanitize_text_field(wp_unslash($_POST['key']));
                    if (strlen($key) > 0) {
                        update_option('grw_google_api_key', $key);
                    }
                }

                $lang = empty($_POST['lang']) ? null : sanitize_text_field(wp_unslash($_POST['lang']));
                $local_img = isset($_POST['local_img']) ? filter_var(wp_unslash($_POST['local_img']), FILTER_VALIDATE_BOOLEAN) : true;
                $key = get_option('grw_google_api_key');

                if ($key && strlen($key) > 0) {

                    $pid = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
                    $response = $this->utils->call('connect', $pid, $lang, $key, $local_img);

                } else {

                    $pid = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
                    $token = empty($_POST['token']) ? null : sanitize_text_field(wp_unslash($_POST['token']));

                    if (!empty($token)) {
                        $siteurl = get_option('siteurl');
                        $authcode = get_option('grw_auth_code');
                        $app_url = 'https://app.richplugins.com/public/connect/reviews';
                        $args = [
                            'input'    => $pid,
                            'token'    => $token,
                            'siteurl'  => $siteurl,
                            'authcode' => $authcode
                        ];
                        if ($lang && strlen($lang) > 0) {
                            $args['lang'] = $lang;
                        }
                        if ($this->is_playground()) {
                            $app_url = add_query_arg($args, $app_url);
                            $response = $this->api_old->call($app_url, null, $local_img);
                        } else {
                            $response = $this->api_old->post($app_url, $args, null, $local_img);
                        }
                    }
                }

                $this->clear_feed_cache(isset($_POST['feed_id']) ? $_POST['feed_id'] : null);
            }

            header('Content-type: text/javascript');
            echo json_encode($response);
            die();
        }
    }

    private function clear_feed_cache($feed_id) {
        // The place can stand in other feeds too, the site-wide badge among them
        $ids = explode(',', (string) get_option('grw_feed_ids'));
        array_push($ids, $feed_id);
        foreach (array_filter($ids) as $id) {
            delete_transient('grw_feed_' . GRW_VERSION . '_' . $id . '_reviews', false);
        }
    }

     public function place_autocomplete() {
        if (current_user_can('manage_options')) {
            $result = null;
            if (isset($_POST['grw_nonce']) === false) {
                $error = __('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.', 'widget-google-reviews');
                $result = compact('error');
            } else {
                check_admin_referer('grw_wpnonce', 'grw_nonce');

                $key = get_option('grw_google_api_key');
                if (!empty($key)) {
                    $input = isset($_POST['input']) ? sanitize_text_field(wp_unslash($_POST['input'])) : '';
                    $url = add_query_arg(
                        array(
                            'input' => $input,
                            'types' => 'establishment',
                            'key'   => $key,
                        ),
                        GRW_GOOGLE_PLACE_API . 'autocomplete/json'
                    );
                    $res = wp_remote_get($url);
                    $body = wp_remote_retrieve_body($res);
                    $result = json_decode($body);
                }
            }
            header('Content-type: text/json');
            echo json_encode($result);
            wp_die();
        }
    }

    public function get_place() {
        if (current_user_can('manage_options')) {

            $response = null;

            if (isset($_POST['grw_nonce']) === false) {
                $error = __('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.', 'widget-google-reviews');
                $response = compact('error');
            } else {
                check_admin_referer('grw_wpnonce', 'grw_nonce');

                $lang = empty($_POST['lang']) ? null : sanitize_text_field(wp_unslash($_POST['lang']));
                $key = get_option('grw_google_api_key');

                if ($key && strlen($key) > 0) {

                    $pid = isset($_POST['pid']) ? sanitize_text_field(wp_unslash($_POST['pid'])) : '';
                    $response = $this->utils->call('place', $pid, $lang, $key);

                } else {

                    $pid = isset($_POST['pid']) ? sanitize_text_field(wp_unslash($_POST['pid'])) : '';
                    $token = empty($_POST['token']) ? null : sanitize_text_field(wp_unslash($_POST['token']));

                    if (!empty($token)) {
                        $siteurl = get_option('siteurl');
                        $authcode = get_option('grw_auth_code');
                        $app_url = 'https://app.richplugins.com/connector/place/json';
                        $args = [
                            'pid'      => $pid,
                            'token'    => $token,
                            'siteurl'  => $siteurl,
                            'authcode' => $authcode
                        ];
                        if ($lang && strlen($lang) > 0) {
                            $args['lang'] = $lang;
                        }
                        if ($this->is_playground()) {
                            $app_url = add_query_arg($args, $app_url);
                            $response = $this->api_old->call($app_url, null, false, false);
                        } else {
                            $response = $this->api_old->post($app_url, $args, null, false, false);
                        }
                    }
                }
            }

            header('Content-type: text/json');
            echo json_encode($response);
            wp_die();
        }
    }

    private function is_playground() {
        if (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'PHP.wasm') !== false) {
            return true;
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (stripos($host, 'playground.wordpress.net') !== false
                || stripos($host, '.wasm.wordpress.net') !== false
                || stripos($host, 'playground.test') !== false) {
                return true;
            }
        }
        if (defined('WP_PLAYGROUND') && WP_PLAYGROUND) {
            return true;
        }
        return false;
    }
}