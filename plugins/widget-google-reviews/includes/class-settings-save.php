<?php

namespace WP_Rplg_Google_Reviews\Includes;

class Settings_Save {

    private $activator;
    private $reviews_cron;

    public function __construct(Activator $activator, Reviews_Cron $reviews_cron) {
        $this->activator = $activator;
        $this->reviews_cron = $reviews_cron;
    }

    public function register() {
        add_action('admin_post_grw_settings_save', array($this, 'save_from_post_array'));
    }

    public function save_from_post_array() {
        global $wpdb;

        if (!function_exists('wp_nonce_field')) {
            function wp_nonce_field() {}
        }

        if (!current_user_can('manage_options')) {
            die('The account you\'re logged in to doesn\'t have permission to access this page.');
        }

        if (!empty($_POST)) {
            $nonce_result_check = $this->check_nonce();
            if ($nonce_result_check === false) {
                die('Unable to save changes. Make sure you are accessing this page from the Wordpress dashboard.');
            }
        }

        $notice_code = null;
        update_option('grw_notice_type', 'success');

        if (isset($_POST['active']) && isset($_GET['active'])) {
            $active = $_GET['active'] == '1' ? '1' : '0';
            update_option('grw_active', $active);
            $notice_code = 'settings_active_' . $active;
        }

        if (isset($_POST['save'])) {
            $notice_code = 'settings_save';
            $fields = array(
                'grw_async_css',
                'grw_demand_assets',
                'grw_rucss_safelist',
                'grw_inlinecss',
                'grw_freq_revs_upd',
                'grw_google_api_key'
            );
            foreach ($fields as $field) {

                if (isset($_POST[$field])) {
                    $value = trim(sanitize_text_field(wp_unslash($_POST[$field])));
                    update_option($field, $value);

                    // If save Google API key automatically enable a reviews update cron
                    if ($field == 'grw_google_api_key' && strlen($value) > 0) {
                        update_option('grw_revupd_cron', '1');

                        delete_option('grw_google_api_error');

                        // Test Google API key
                        $res = wp_remote_get(GRW_GOOGLE_PLACE_API . 'details/json?placeid=ChIJ3TH9CwFZwokRIvNO1SP0WLg&key=' . $value);
                        $body_json = json_decode(wp_remote_retrieve_body($res));
                        $legacy_error = '';
                        if (isset($body_json->status) && $body_json->status == 'REQUEST_DENIED') {
                            $legacy_error = isset($body_json->error_message) ? $body_json->error_message : $body_json->status;
                            $res = wp_remote_get(GRW_GOOGLE_PLACE_API_NEW . 'ChIJ3TH9CwFZwokRIvNO1SP0WLg?fields=rating&key=' . $value);
                            $body_json = json_decode(wp_remote_retrieve_body($res));
                        }
                        if ($body_json) {
                            $error = isset($body_json->error) ? $body_json->error->message : (isset($body_json->error_message) ? $body_json->error_message : '');
                            if (!empty($error)) {
                                update_option('grw_google_api_error', $legacy_error ? 'Places API: ' . $legacy_error . "\nPlaces API (New): " . $error : $error);
                                update_option('grw_notice_msg', $error);
                                update_option('grw_notice_type', 'error');
                                $notice_code = 'custom_msg';
                            }
                        }
                    }

                    // Change reviews update cron frequency
                    if ($field == 'grw_freq_revs_upd' && strlen($value) > 0) {
                        $this->reviews_cron->deactivate();
                        $this->reviews_cron->activate();
                    }
                }
            }
        }

        if (isset($_POST['create_db'])) {
            $this->activator->create_db();
            $notice_code = 'settings_create_db';
        }

        if (isset($_POST['install'])) {
            $install_multisite = isset($_POST['install_multisite']) ? sanitize_text_field(wp_unslash($_POST['install_multisite'])) : null;
            $this->activator->drop_db($install_multisite);
            $this->activator->delete_all_options($install_multisite);
            $this->activator->delete_all_feeds($install_multisite);
            $this->activator->activate();
            $this->reviews_cron->deactivate();
            $notice_code = 'settings_install';
        }

        if (isset($_POST['reset_all'])) {
            $reset_all_multisite = isset($_POST['reset_all_multisite']) ? sanitize_text_field(wp_unslash($_POST['reset_all_multisite'])) : false;
            $this->activator->drop_db($reset_all_multisite);
            $this->activator->delete_all_options($reset_all_multisite);
            $this->activator->delete_all_feeds($reset_all_multisite);
            $this->reviews_cron->deactivate();
            $notice_code = 'settings_reset_all';
        }

        if (isset($_POST['debug_mode'])) {
            $debug_mode = $_POST['debug_mode'] == 'Enable' ? '1' : '0';
            update_option('grw_debug_mode', $debug_mode);
            $notice_code = 'settings_debug_mode_' . $debug_mode;
        }

        if (isset($_POST['update_db_ver']) && isset($_POST['update_db'])) {
            $update_db_ver = sanitize_text_field(wp_unslash($_POST['update_db_ver']));
            if (strlen($update_db_ver) > 0) {
                $this->activator->update_db($update_db_ver);
                $notice_code = 'settings_update_db';
            }
        }

        if (isset($_POST['del_dup_revs'])) {
            $this->activator->delete_duplicates();
            $notice_code = 'settings_del_dup_revs';
        }

        if (isset($_POST['revupd_cron'])) {
            $revupd_cron = $_POST['revupd_cron'] == 'Enable' ? '1' : '0';
            if ($revupd_cron == '0') {
                $this->reviews_cron->deactivate();
            }
            update_option('grw_revupd_cron', $revupd_cron);
            $notice_code = 'settings_revupd_cron_' . $revupd_cron;
        }

        $this->redirect_to_tab($notice_code);
    }

    public function redirect_to_tab($notice_code = '') {
        if (empty($_GET['grw_tab'])) {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        $tab = sanitize_text_field(wp_unslash($_GET['grw_tab']));

        $query_args = array(
            'grw_tab' => $tab,
        );

        if (!empty($notice_code)) {
            $query_args['grw_notice'] = $notice_code;
        }

        wp_safe_redirect(add_query_arg($query_args, wp_get_referer()));
        exit;
    }

    private function check_nonce() {
        $nonce_actions = array('active', 'save', 'create_db', 'reset_all', 'debug_mode', 'update_db', 'del_dup_revs');
        $nonce_form_prefix = 'grw-form_nonce_';
        $nonce_action_prefix = 'grw-wpnonce_';
        foreach ($nonce_actions as $key => $value) {
            if (isset($_POST[$nonce_form_prefix.$value])) {
                check_admin_referer($nonce_action_prefix.$value, $nonce_form_prefix.$value);
                return true;
            }
        }
        return false;
    }

}
