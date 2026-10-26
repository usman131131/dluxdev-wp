<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Menu;
use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Tophead;
use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Notice;
use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Feed_Columns;
use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Rev;
use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Rateus_Ajax;
use WP_Rplg_Google_Reviews\Includes\Admin\Admin_Deactivate;

use WP_Rplg_Google_Reviews\Includes\Core\Core;
use WP_Rplg_Google_Reviews\Includes\Core\Database;

use WP_Rplg_Google_Reviews\Includes\Core\Google_Dao;
use WP_Rplg_Google_Reviews\Includes\Core\Connect_Helper;
use WP_Rplg_Google_Reviews\Includes\Core\Google_Api_Old;
use WP_Rplg_Google_Reviews\Includes\Core\Google_Api_New;
use WP_Rplg_Google_Reviews\Includes\Core\Google_Utils;
use WP_Rplg_Google_Reviews\Includes\Core\Google_Connect;

final class Plugin {

    protected $name;
    protected $version;
    protected $activator;
    protected $deactivator;

    public function __construct() {
        $this->name = 'widget-google-reviews';
        $this->version = GRW_VERSION;
    }

    public function register() {
        register_activation_hook(GRW_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(GRW_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('plugins_loaded', array($this, 'register_services'));
    }

    public function init() {
        $this->init_language();
    }

    public function admin_init() {
        if (wp_doing_ajax() || !get_option('grw_do_activation', false)) return;
        delete_option('grw_do_activation');
        // A bulk activation lands back on its plugin list.
        if (current_user_can('manage_options') && !isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('admin.php?page=grw'));
            exit;
        }
    }

    public function register_services() {
        $database = new Database();

        $activator = new Activator($database);
        $activator->register();

        $assets = new Assets(GRW_ASSETS_URL, $this->version, get_option('grw_debug_mode') == '1');
        $assets->register();

        $post_types = new Post_Types();
        $post_types->register();

        $feed_deserializer = new Feed_Deserializer(new \WP_Query());

        $core = new Core();

        $view = new View();

        $connect_helper = new Connect_Helper();
        $google_dao = new Google_Dao($connect_helper);

        $plugin_places = new Plugin_Places($google_dao);

        $builder_page = new Builder_Page($feed_deserializer, $core, $view, $google_dao, $plugin_places);
        $builder_page->register();

        $feed_page = new Feed_Page();
        $feed_page->register();

        $feed_old = new Feed_Old();

        $feed_shortcode = new Feed_Shortcode($feed_deserializer, $assets, $core, $view, $feed_old);
        $feed_shortcode->register();

        Feed_Widget::$static_feed_deserializer = $feed_deserializer;
        Feed_Widget::$static_core              = $core;
        Feed_Widget::$static_view              = $view;
        Feed_Widget::$static_assets            = $assets;
        Feed_Widget::$static_feed_old          = $feed_old;
        add_action('widgets_init', function() {
            register_widget('WP_Rplg_Google_Reviews\Includes\Feed_Widget');
        });

        $feed_block = new Feed_Block($feed_deserializer, $core, $view, $assets);
        $feed_block->register();

        $badge_global = new Badge_Global($feed_deserializer, $core, $view, $assets);
        $badge_global->register();

        $reviews_ajax = new Reviews_Ajax($feed_deserializer, $core, $view);
        $reviews_ajax->register();

        $google_api_old = new Google_Api_Old($google_dao, $connect_helper);
        $google_api_new = new Google_Api_New($google_dao, $connect_helper);
        $google_utils = new Google_Utils($google_api_old, $google_api_new, $google_dao);

        $reviews_cron = new Reviews_Cron($google_utils, $feed_deserializer);
        $reviews_cron->register();

        $this->deactivator = new Deactivator($reviews_cron);

        if (is_admin()) {
            $google_connect = new Google_Connect($google_api_old, $google_utils);

            $feed_serializer = new Feed_Serializer();
            $feed_ajax = new Feed_Ajax($feed_serializer, $feed_deserializer, $core, $view);

            $admin_notice = new Admin_Notice();
            $admin_notice->register();

            $admin_menu = new Admin_Menu();
            $admin_menu->register();

            $admin_tophead = new Admin_Tophead();
            $admin_tophead->register();

            $admin_feed_columns = new Admin_Feed_Columns($feed_deserializer);
            $admin_feed_columns->register();

            $plugin_overview_ajax = new Plugin_Overview_Ajax($core);
            $plugin_overview = new Plugin_Overview($google_dao);
            $plugin_overview->register();

            $plugin_places->register();

            $plugin_badge = new Plugin_Badge($google_dao, $core, $plugin_places, $feed_serializer, $feed_deserializer);
            $plugin_badge->register();

            $plugin_theme = new Plugin_Theme($plugin_badge, $google_dao, $core, $plugin_places, $feed_serializer, $feed_deserializer);
            $plugin_theme->register();

            $settings_save = new Settings_Save($activator, $reviews_cron);
            $settings_save->register();

            $debug_info = new Debug_Info($activator, $feed_deserializer);

            $plugin_settings = new Plugin_Settings($debug_info);
            $plugin_settings->register();

            $plugin_support = new Plugin_Support($debug_info);
            $plugin_support->register();

            $admin_rev = new Admin_Rev();
            $admin_rev->register();

            $rateus_ajax = new Admin_Rateus_Ajax();

            $admin_deactivate = new Admin_Deactivate();
            $admin_deactivate->register();
        }
    }

    public function init_language() {
        load_plugin_textdomain('widget-google-reviews', false, basename(dirname(GRW_PLUGIN_FILE)) . '/languages');
    }

    public function activate($network_wide = false) {
        $now = time();
        update_option('grw_activation_time', $now);

        update_option('grw_is_multisite', $network_wide ? '1' : '0');
        if (!is_multisite()) {
            update_option('grw_is_multisite', '0');
        }

        if (!$network_wide && !(defined('WP_CLI') && WP_CLI)) add_option('grw_do_activation', true);

        $activator = new Activator(new Database());
        $activator->activate();
    }

    public function deactivate() {
        $this->deactivator->deactivate();
    }
}