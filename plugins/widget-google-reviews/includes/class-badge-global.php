<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Core;

class Badge_Global {

    private $feed_deserializer;
    private $core;
    private $view;
    private $assets;

    public function __construct(Feed_Deserializer $feed_deserializer, Core $core, View $view, Assets $assets) {
        $this->feed_deserializer = $feed_deserializer;
        $this->core              = $core;
        $this->view              = $view;
        $this->assets            = $assets;
    }

    public function register() {
        // Before wp_print_footer_scripts (also wp_footer, 20): assets enqueued from here must still get printed.
        add_action('wp_footer', array($this, 'render'), 10);
    }

    public function render() {
        if (get_option('grw_active') === '0' || is_admin() || is_feed() || is_embed()) {
            return;
        }
        $id = (int) get_option(Plugin_Badge::GLOBAL_OPTION);
        if (!$id || in_array((string) $id, View::$rendered, true)) {
            return;
        }
        $feed = $this->feed_deserializer->get_feed($id);
        if (!$feed) {
            // A trashed badge keeps its slot for a restore; only one that is gone frees it.
            if (!get_post($id)) {
                delete_option(Plugin_Badge::GLOBAL_OPTION);
            }
            return;
        }

        $data = $this->core->get_reviews($feed);
        if (empty($data['options']->view_mode) || $data['options']->view_mode !== 'badge') {
            return;
        }
        // An embed badge belongs in the page content; one made the site's badge before saves stopped that is skipped.
        if (isset($data['options']->badge_pos) && $data['options']->badge_pos === 'embed') {
            return;
        }

        $grw_demand_assets = get_option('grw_demand_assets');
        if ($grw_demand_assets || $grw_demand_assets == 'true') {
            $this->assets->enqueue_public_styles();
            $this->assets->enqueue_public_scripts();
        }

        echo $this->view->render($feed->ID, $data['businesses'], $data['reviews'], $data['options']);
    }
}
