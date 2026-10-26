<?php

namespace WP_Rplg_Google_Reviews\Includes;

class Assets {

    private $url;
    private $version;
    private $debug;

    private $css_cache = array();

    private static $css_assets = array(
        'grw-admin-main-css'      => 'css/admin-main',
        'grw-public-main-css'     => 'css/public-main',

        'rpi-flex-css'            => 'https://cdn.reviewsplugin.com/assets/css/flex.css',
        'rpi-stars-css'           => 'https://cdn.reviewsplugin.com/assets/css/stars.css',
        'rpi-slider-css'          => 'https://cdn.reviewsplugin.com/assets/css/slider.css',
        'rpi-common-css'          => 'https://cdn.reviewsplugin.com/assets/css/common.css',
        'rpi-lightbox-css'        => 'https://cdn.reviewsplugin.com/assets/css/lightbox.css',
        'rpi-icons-css'           => 'https://cdn.reviewsplugin.com/assets/css/icons.css',
        'rpi-badge-css'           => 'https://cdn.reviewsplugin.com/assets/css/badge.css',
        'rpi-card-css'            => 'https://cdn.reviewsplugin.com/assets/css/card.css',
        'rpi-card-editor-css'     => 'https://cdn.reviewsplugin.com/assets/css/card-editor.css'
    );

    private static $js_assets = array(
        'grw-admin-main-js'       => 'js/admin-main',
        'grw-admin-builder-js'    => 'js/admin-builder',
        'grw-admin-steps-js'      => 'js/admin-steps',
        'grw-admin-badge-js'      => 'js/admin-badge',
        'grw-admin-themes-js'     => 'js/admin-themes',
        'grw-admin-apexcharts-js' => 'js/admin-apexcharts',
        'grw-public-main-js'      => 'js/public-main',

        'rpi-time-js'             => 'https://cdn.reviewsplugin.com/assets/js/time.js',
        'rpi-utils-js'            => 'https://cdn.reviewsplugin.com/assets/js/utils.js',
        'rpi-column-js'           => 'https://cdn.reviewsplugin.com/assets/js/column.js',
        'rpi-common-js'           => 'https://cdn.reviewsplugin.com/assets/js/common.js',
        'rpi-lightbox-js'         => 'https://cdn.reviewsplugin.com/assets/js/lightbox.js',
        'rpi-toast-js'            => 'https://cdn.reviewsplugin.com/assets/js/toast.js',
        'rpi-langs-js'            => 'https://cdn.reviewsplugin.com/assets/js/langs.js',
        'rpi-order-js'            => 'https://cdn.reviewsplugin.com/assets/js/order.js',
        'rpi-media-js'            => 'https://cdn.reviewsplugin.com/assets/js/media.js',
        'rpi-slider-js'           => 'https://cdn.reviewsplugin.com/assets/js/slider.js',
        'rpi-badge-js'            => 'https://cdn.reviewsplugin.com/assets/js/badge.js',
        'rpi-card-js'             => 'https://cdn.reviewsplugin.com/assets/js/card.js',
        'rpi-card-editor-js'      => 'https://cdn.reviewsplugin.com/assets/js/card-editor.js'
    );

    public function __construct($url, $version, $debug) {
        $this->url     = $url;
        $this->version = $version;
        $this->debug   = $debug;
    }

    public function register() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'register_styles'));
            add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        } else {
            add_action('wp_enqueue_scripts', array($this, 'register_styles'));
            add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
            $grw_demand_assets = get_option('grw_demand_assets');
            if (!$grw_demand_assets || $grw_demand_assets != 'true') {
                add_action('wp_enqueue_scripts', array($this, 'enqueue_public_styles'));
                add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
            }
            add_filter('script_loader_tag', array($this, 'script_async'), 10, 2);

            $async_css = get_option('grw_async_css');
            if ($async_css === 'true') {
                add_filter('style_loader_tag', array($this, 'style_async'), 10, 2);
            }
        }

        $grw_rucss_safelist = get_option('grw_rucss_safelist');
        if (!$grw_rucss_safelist || $grw_rucss_safelist != 'true') {
            add_filter('get_rocket_option_remove_unused_css_safelist', array($this, 'rucss_safelist'));
        }
    }

    function script_async($tag, $handle) {
        $js_assets = array(
            'grw-admin-main-js'    => 'js/admin-main',
            'grw-admin-builder-js' => 'js/admin-builder',
            'grw-public-main-js'   => 'js/public-main',
        );
        if (isset($handle) && array_key_exists($handle, $js_assets)) {
            return str_replace(' src', ' defer="defer" src', $tag);
        }
        return $tag;
    }

    function style_async($tag, $handle) {
        $targets = array('grw-admin-main-css', 'grw-public-main-css');
        if (!in_array($handle, $targets, true)) return $tag;

        if (strpos($tag, 'rel=\'preload\'') !== false || strpos($tag, 'rel="preload"') !== false) return $tag;

        if (strpos($tag, "rel='stylesheet'") !== false) {
            return str_replace(
                "rel='stylesheet'",
                "rel='preload' as='style' onload='this.onload=null;this.rel=\"stylesheet\";window.dispatchEvent(new Event(\"resize\"))'",
                $tag
            );
        }
        if (strpos($tag, 'rel="stylesheet"') !== false) {
            return str_replace(
                'rel="stylesheet"',
                'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\';window.dispatchEvent(new Event(\'resize\'))"',
                $tag
            );
        }
        return $tag;
    }

    function rucss_safelist($safelist) {
        $css_main = $this->get_public_css_url();
        if (array_search($css_main, $safelist) === false) {
            $safelist[] = $css_main;
        }
        return $safelist;
    }

    private function get_public_css_url() {
        $href = $this->get_css_asset('grw-public-main-css');
        if (is_rtl()) {
            $href = preg_replace('/\.css$/', '-rtl.css', $href);
        }
        return $href;
    }

    public function register_styles() {
        $styles = array(
            'grw-admin-main-css',
            'grw-public-main-css',
            'rpi-flex-css',
            'rpi-stars-css',
            'rpi-slider-css',
            'rpi-common-css',
            'rpi-lightbox-css',
            'rpi-icons-css',
            'rpi-badge-css',
            'rpi-card-css',
            'rpi-card-editor-css'
        );
        $this->register_styles_loop($styles);
    }

    public function register_scripts() {
        $scripts = array('grw-admin-main-js', 'grw-public-main-js', 'grw-admin-apexcharts-js');
        if ($this->debug) {
            array_push($scripts, 'grw-admin-builder-js');
            array_push($scripts, 'grw-admin-steps-js');
            array_push($scripts, 'grw-admin-badge-js');
            array_push($scripts, 'grw-admin-themes-js');
            array_push($scripts, 'rpi-time-js');
            array_push($scripts, 'rpi-utils-js');
            array_push($scripts, 'rpi-column-js');
            array_push($scripts, 'rpi-common-js');
            array_push($scripts, 'rpi-lightbox-js');
            array_push($scripts, 'rpi-toast-js');
            array_push($scripts, 'rpi-langs-js');
            array_push($scripts, 'rpi-order-js');
            array_push($scripts, 'rpi-media-js');
            array_push($scripts, 'rpi-slider-js');
            array_push($scripts, 'rpi-badge-js');
            array_push($scripts, 'rpi-card-js');
            array_push($scripts, 'rpi-card-editor-js');
        }
        $this->register_scripts_loop($scripts);
    }

    public function enqueue_admin_styles() {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style('grw-admin-main-css');
        wp_style_add_data('grw-admin-main-css', 'rtl', 'replace');
        if ($this->debug) {
            wp_enqueue_style('rpi-card-editor-css');
        }
        $this->enqueue_public_styles();
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-dialog');

        $vars = array(
            'lang'       => get_locale(),
            'builderUrl' => admin_url('admin.php?page=grw-builder')
        );

        if ($this->debug) {
            wp_enqueue_script('rpi-toast-js');
            wp_enqueue_script('rpi-lightbox-js');
            wp_enqueue_script('rpi-langs-js');
            wp_enqueue_script('rpi-order-js');
            wp_enqueue_script('rpi-card-js');
            wp_enqueue_script('rpi-card-editor-js');
            wp_localize_script('grw-admin-builder-js', 'GRW_VARS', $vars);
            wp_enqueue_script('grw-admin-builder-js');
            wp_enqueue_script('grw-admin-steps-js');
            wp_enqueue_script('grw-admin-badge-js');
            wp_enqueue_script('grw-admin-themes-js');
        } else {
            wp_localize_script('grw-admin-main-js', 'GRW_VARS', $vars);
        }
        wp_enqueue_script('grw-admin-main-js');

        $this->enqueue_public_scripts();
    }

    public function enqueue_public_styles() {
        if ($this->debug) {
            wp_enqueue_style('rpi-flex-css');
            wp_enqueue_style('rpi-stars-css');
            wp_enqueue_style('rpi-slider-css');
            wp_enqueue_style('rpi-common-css');
            wp_enqueue_style('rpi-lightbox-css');
            wp_enqueue_style('rpi-icons-css');
            wp_enqueue_style('rpi-badge-css');
            wp_enqueue_style('rpi-card-css');
        }

        $handle = 'grw-public-main-css';
        $inlinecss = get_option('grw_inlinecss');
        if ($inlinecss === 'true') {
            $css = $this->get_css_content('public-main');
            if (!empty($css)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
                wp_register_style($handle, false);
                wp_enqueue_style($handle);
                wp_add_inline_style($handle, $css);
                return;
            }
        }

        wp_enqueue_style($handle);
        wp_style_add_data($handle, 'rtl', 'replace');
    }

    public function enqueue_public_scripts() {
        if ($this->debug) {
            wp_enqueue_script('rpi-time-js');
            wp_enqueue_script('rpi-utils-js');
            wp_enqueue_script('rpi-column-js');
            wp_enqueue_script('rpi-common-js');
            wp_enqueue_script('rpi-lightbox-js');
            wp_enqueue_script('rpi-media-js');
            wp_enqueue_script('rpi-slider-js');
            wp_enqueue_script('rpi-badge-js');
        }
        wp_enqueue_script('grw-public-main-js');
    }

    private function register_styles_loop($styles) {
        foreach ($styles as $style) {
            wp_register_style($style, $this->get_css_asset($style), array(), null);
        }
    }

    private function register_scripts_loop($scripts) {
        foreach ($scripts as $script) {
            wp_register_script($script, $this->get_js_asset($script), array(), null);
        }
    }

    public function get_css_asset($asset) {
        $css = self::$css_assets[$asset];
        return strpos($css, 'https:') === 0 ? $this->cdn($css) : $this->url . ($this->debug ? 'src/' : $this->version . '/') . $css . '.css';
    }

    public function get_js_asset($asset) {
        $js = self::$js_assets[$asset];
        return strpos($js, 'https:') === 0 ? $this->cdn($js) : $this->url . ($this->debug ? 'src/' : $this->version . '/') . $js . '.js';
    }

    private function cdn($url) {
        $base = apply_filters('grw_cdn_url', 'https://cdn.reviewsplugin.com');
        return $base === 'https://cdn.reviewsplugin.com' ? $url : str_replace('https://cdn.reviewsplugin.com', rtrim($base, '/'), $url);
    }

    public function version() {
        return $this->version;
    }

    private function get_css_content($name) {
        $key = $name . (is_rtl() ? '-rtl' : '');

        if (isset($this->css_cache[$key])) {
            return $this->css_cache[$key];
        }

        $file = GRW_PLUGIN_PATH . '/assets/' . ($this->debug ? 'src/' : $this->version . '/') . 'css/' . $key . '.css';
        if (!file_exists($file) || !is_readable($file)) {
            return $this->css_cache[$key] = '';
        }
        $css = (string) file_get_contents($file);
        return $this->css_cache[$key] = $css;
    }
}